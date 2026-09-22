<?php

namespace App\Http\Controllers;

use App\Models\IPSPlantilla;
use App\Services\AiExtractorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IaColaController extends Controller
{
    private function sedeActiva(): ?int
    {
        $id = session('active_sede_id') ?? auth()->user()?->sede_id;

        return $id ? (int) $id : null;
    }

    public function index(Request $request, AiExtractorService $extractor): View|JsonResponse
    {
        if ($request->isMethod('post') && $request->has('action')) {
            return $this->accion($request, $extractor);
        }

        $filtroEstado = trim((string) $request->query('estado', ''));
        $sede = $this->sedeActiva();

        $base = DB::table('ingreso_formulas_ia as f')
            ->join('ingresos as i', 'f.ingreso_id', '=', 'i.id')
            ->join('pacientes as p', 'i.paciente_id', '=', 'p.id')
            ->leftJoin('ingreso_documentos as d', 'f.documento_id', '=', 'd.id')
            ->when($sede, fn ($q) => $q->where('i.sede_id', $sede));

        $colaItems = (clone $base)
            ->when($filtroEstado !== '', fn ($q) => $q->where('f.estado_ia', $filtroEstado))
            ->orderByDesc('f.id')
            ->limit(100)
            ->get([
                'f.id', 'f.ingreso_id', 'f.estado_ia', 'f.tiempo_segundos', 'f.total_medicamentos', 'f.observaciones', 'f.created_at',
                'd.ruta_archivo', 'd.nombre_original',
                'i.ticket_numero', 'i.ips_remite',
                'p.nombres', 'p.apellidos', 'p.tipo_documento', 'p.numero_documento', 'p.eps_nombre',
            ])
            ->map(fn ($r) => (array) $r)
            ->all();

        $kpiIA = (clone $base)->selectRaw("
                COUNT(CASE WHEN f.estado_ia NOT IN ('DISPENSADO', 'CANCELADO') AND i.estado_tramite NOT IN ('ENTREGADO', 'CANCELADO') THEN 1 END) AS total_activos,
                COUNT(CASE WHEN f.estado_ia = 'PENDIENTE' THEN 1 END) AS pendientes,
                COUNT(CASE WHEN f.estado_ia = 'PROCESADO' AND i.estado_tramite != 'ENTREGADO' THEN 1 END) AS procesados,
                COUNT(CASE WHEN f.estado_ia = 'DISPENSADO' OR i.estado_tramite = 'ENTREGADO' THEN 1 END) AS dispensados,
                COUNT(CASE WHEN f.estado_ia = 'CANCELADO' THEN 1 END) AS cancelados
            ")->first();
        $kpiIA = (array) $kpiIA;

        $plantillasActivas = (new IPSPlantilla())->getPlantillas(['es_activa' => 1]);

        return view('ia_scanner.cola', compact('colaItems', 'filtroEstado', 'kpiIA', 'plantillasActivas'));
    }

    public function accion(Request $request, AiExtractorService $extractor): JsonResponse
    {
        $idIa = (int) $request->input('id_ia', 0);

        return match ($request->input('action')) {
            'procesar_ia_servidor' => $this->procesarIaServidor($extractor, $idIa),
            'guardar_extraccion_ia' => $this->guardarExtraccion($request, $idIa),
            'cancelar_item_cola' => $this->cambiarEstado($idIa, 'CANCELADO', trim((string) $request->input('motivo', 'Cancelado desde la cola por el usuario')), 'Orden médica cancelada y retirada de la cola activa.'),
            'reactivar_item_cola' => $this->cambiarEstado($idIa, 'PENDIENTE', null, '¡Orden médica reactivada en la cola de procesamiento!'),
            'eliminar_item_cola' => $this->eliminar($idIa),
            default => response()->json(['status' => 'error', 'message' => 'Acción no reconocida.'], 422),
        };
    }

    /** Reprocesa con IA un archivo ya digitalizado (subido previamente en Ingreso), pendiente en la cola. */
    private function procesarIaServidor(AiExtractorService $extractor, int $idIa): JsonResponse
    {
        $item = DB::table('ingreso_formulas_ia as f')
            ->leftJoin('ingreso_documentos as d', 'f.documento_id', '=', 'd.id')
            ->where('f.id', $idIa)->first(['f.ingreso_id', 'd.ruta_archivo', 'd.nombre_original']);

        $rutaAbsoluta = $item && $item->ruta_archivo ? public_path(ltrim($item->ruta_archivo, '/')) : null;

        if (! $item || ! $rutaAbsoluta || ! is_file($rutaAbsoluta)) {
            return response()->json(['status' => 'error', 'message' => 'No se pudo extraer los datos de la fórmula médica. Verifique el archivo.']);
        }

        $resultado = $extractor->extraerDatosDesdeRuta($rutaAbsoluta, $item->nombre_original ?: 'formula.pdf');

        if (empty($resultado['status']) || $resultado['status'] !== 'ok' || empty($resultado['data']['medicamentos'])) {
            return response()->json(['status' => 'error', 'message' => 'No se pudo extraer los datos de la fórmula médica. Verifique el archivo.']);
        }

        $datos = $this->enriquecerConPaciente($resultado['data'], (int) $item->ingreso_id);
        $this->guardarResultado($idIa, $datos, $resultado['tiempo_segundos'] ?? 0.45, $resultado['motor'] ?? 'SISPAM Vision Engine');

        return response()->json([
            'status' => 'ok', 'message' => '¡Fórmula extraída con máxima precisión!',
            'tiempo_segundos' => $resultado['tiempo_segundos'] ?? 0.45, 'motor' => $resultado['motor'] ?? 'SISPAM Vision Engine',
            'total_medicamentos' => count($datos['medicamentos']), 'data' => $datos,
        ]);
    }

    private function guardarExtraccion(Request $request, int $idIa): JsonResponse
    {
        $datos = json_decode((string) $request->input('datos_json', ''), true);
        if (empty($datos) || ! isset($datos['medicamentos'])) {
            return response()->json(['status' => 'error', 'message' => 'Estructura de datos inválida.']);
        }

        $tiempo = (float) $request->input('tiempo_segundos', 0.3);
        $motor = trim((string) $request->input('motor', 'SISPAM Vision Engine'));
        $this->guardarResultado($idIa, $datos, $tiempo, $motor);

        return response()->json([
            'status' => 'ok', 'message' => '¡Fórmula procesada y guardada!', 'tiempo_segundos' => $tiempo, 'motor' => $motor,
            'total_medicamentos' => count($datos['medicamentos'] ?? []), 'data' => $datos,
        ]);
    }

    private function enriquecerConPaciente(array $datos, int $ingresoId): array
    {
        $pac = DB::table('ingresos as i')->join('pacientes as p', 'i.paciente_id', '=', 'p.id')
            ->where('i.id', $ingresoId)
            ->first(['p.nombres', 'p.apellidos', 'p.tipo_documento', 'p.numero_documento', 'p.eps_nombre', 'i.ips_remite']);

        if ($pac) {
            $datos['paciente']['nombre_completo'] = trim($pac->nombres.' '.$pac->apellidos);
            $datos['paciente']['tipo_documento'] = $pac->tipo_documento;
            $datos['paciente']['numero_documento'] = $pac->numero_documento;
            $datos['paciente']['eps'] = $pac->eps_nombre ?: ($datos['paciente']['eps'] ?? '');
            $datos['paciente']['ips'] = $pac->ips_remite ?: ($datos['paciente']['ips'] ?? '');
        }

        $inv = new \App\Models\Inventario();
        foreach ($datos['medicamentos'] as &$med) {
            $nombre = trim($med['descripcion'] ?? $med['medicamento'] ?? '');
            $match = $nombre !== '' ? $inv->buscarMedicamentoInteligente($nombre) : null;
            $med['producto_id'] = $match['id'] ?? null;
            if ($match) {
                $med['codigo_sku'] = $match['codigo_sku'];
                $med['codigo_cums'] = $match['codigo_cums'];
                $med['nombre_comercial_match'] = $match['nombre_comercial'];
                $med['stock_disponible'] = $match['stock_bodega'] ?? null;
            }
        }
        unset($med);

        return $datos;
    }

    private function guardarResultado(int $idIa, array $datos, float $tiempo, string $motor): void
    {
        DB::table('ingreso_formulas_ia')->where('id', $idIa)->update([
            'estado_ia' => 'PROCESADO',
            'datos_extraidos_json' => json_encode($datos, JSON_UNESCAPED_UNICODE),
            'tiempo_segundos' => $tiempo,
            'motor_utilizado' => $motor,
            'total_medicamentos' => count($datos['medicamentos'] ?? []),
            'procesado_at' => now(),
        ]);

        $item = DB::table('ingreso_formulas_ia')->where('id', $idIa)->first(['ingreso_id']);
        $pacienteId = $item ? DB::table('ingresos')->where('id', $item->ingreso_id)->value('paciente_id') : null;
        if ($pacienteId) {
            try {
                (new \App\Models\TratamientoCronico())->programarEntregasFuturas($pacienteId, $item->ingreso_id, $datos, 'DOMICILIO');
            } catch (\Throwable $e) {
                logger()->error('Error programando multimes desde cola IA: '.$e->getMessage());
            }
        }
    }

    private function cambiarEstado(int $idIa, string $estado, ?string $observaciones, string $mensajeOk): JsonResponse
    {
        if ($idIa <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Identificador de cola no válido.']);
        }

        $datos = ['estado_ia' => $estado];
        if ($observaciones !== null) {
            $datos['observaciones'] = $observaciones;
        }
        $ok = DB::table('ingreso_formulas_ia')->where('id', $idIa)->update($datos);

        return response()->json($ok ? ['status' => 'ok', 'message' => $mensajeOk] : ['status' => 'error', 'message' => 'No se pudo actualizar el registro en la base de datos.']);
    }

    private function eliminar(int $idIa): JsonResponse
    {
        if ($idIa <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Identificador de cola no válido.']);
        }
        $ok = DB::table('ingreso_formulas_ia')->where('id', $idIa)->delete();

        return response()->json($ok ? ['status' => 'ok', 'message' => 'Registro eliminado permanentemente de la cola.'] : ['status' => 'error', 'message' => 'No se pudo eliminar el registro.']);
    }
}
