<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Services\EntregaService;
use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PDO;

class EntregaController extends Controller
{
    public function __construct(private FlujoIngresoService $flujo, private EntregaService $entrega) {}

    private function sedeActiva(): ?int
    {
        $sede = session('active_sede_id') ?? auth()->user()?->sede_id;

        return $sede ? (int) $sede : null;
    }

    public function index(Request $request): View|JsonResponse
    {
        $sede = $this->sedeActiva();

        // Búsqueda de paciente por cédula, tiquete o nombre
        if ($request->has('ajax_buscar')) {
            return $this->json(fn () => ['status' => 'ok', 'data' => $this->flujo->buscarParaEntrega(trim((string) $request->query('query', '')), $sede) ?: []]);
        }

        // Detalle de medicamentos alistados y faltantes para entregar en ventanilla
        if ($request->has('ajax_get_detalle_entrega')) {
            return $this->json(fn () => $this->entrega->detalle((int) $request->query('ingreso_id', 0)));
        }

        // Llamados de hoy, pacientes en la TV y KPIs en tiempo real
        if ($request->has('ajax_get_llamados_hoy')) {
            return response()->json([
                'status' => 'ok',
                'historial' => $this->flujo->getHistorialLlamadosEntregaHoy($sede, 50) ?: [],
                'pacientes_tv' => $this->flujo->getPacientesEnPantallaTV($sede) ?: [],
                'stats' => $this->flujo->getEstadisticasEntregaHoy($sede),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $busqueda = trim((string) ($request->query('query') ?? $request->query('busqueda') ?? ''));

        return view('entrega.index', [
            'mensaje' => session('mensaje', ''),
            'error' => session('error', ''),
            'active_sede' => $sede,
            'busquedaTermino' => $busqueda,
            'pacientesBuscados' => $busqueda !== '' ? $this->flujo->buscarParaEntrega($busqueda, $sede) : [],
            'modulos_activos' => $this->modulosActivos($sede),
            'statsIniciales' => $this->flujo->getEstadisticasEntregaHoy($sede),
            'historialHoy' => $this->flujo->getHistorialLlamadosEntregaHoy($sede, 50),
            'pacientesEnTV' => $this->flujo->getPacientesEnPantallaTV($sede),
        ]);
    }

    /** POST único con `action` (mismo contrato de la vista nativa); todas las respuestas son JSON. */
    public function accion(Request $request): JsonResponse
    {
        $accion = $request->input('action');
        $ingresoId = (int) $request->input('ingreso_id', 0);
        $userId = (int) auth()->id();

        if ($accion === 'buscar_paciente') {
            return $this->json(fn () => ['status' => 'ok', 'data' => $this->flujo->buscarParaEntrega(trim((string) $request->input('query', '')), $this->sedeActiva()) ?: []]);
        }

        if ($ingresoId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ID de orden inválido.']);
        }

        return match ($accion) {
            'llamar_turno_entrega' => $this->llamar($request, $ingresoId, $userId),

            'rellamar_paciente_tv' => response()->json($this->flujo->rellamarTurnoEntrega($ingresoId, $userId)
                ? ['status' => 'ok', 'message' => 'Alerta sonora y modal de rellamado emitidos en la pantalla TV.']
                : ['status' => 'error', 'message' => 'No se pudo emitir el rellamado.']),

            'cerrar_entrega_ajax' => $this->cerrar($request, $ingresoId, $userId),

            'confirmar_entrega_con_firma' => $this->json(fn () => $this->entrega->confirmarConFirma(
                $ingresoId,
                json_decode($request->input('items_dispensar', '[]'), true) ?: [],
                json_decode($request->input('items_faltantes', '[]'), true) ?: [],
                trim((string) $request->input('observaciones', 'Entrega de medicamentos conforme con fórmula original.')),
                trim((string) $request->input('firma_base64', '')),
                trim((string) $request->input('foto_base64', '')),
                $userId,
                [
                    'direccion' => trim((string) $request->input('direccion_domicilio', '')),
                    'telefono' => trim((string) $request->input('telefono_domicilio', '')),
                    'barrio' => trim((string) $request->input('barrio_domicilio', '')),
                ]
            )),

            default => response()->json(['status' => 'error', 'message' => 'Acción no soportada.'], 422),
        };
    }

    private function llamar(Request $request, int $ingresoId, int $userId): JsonResponse
    {
        $modulo = trim((string) $request->input('modulo_nombre', 'VENTANILLA 1'));
        $ok = $this->flujo->iniciarLlamadoEntrega($ingresoId, $modulo, $userId);

        return response()->json([
            'status' => $ok ? 'ok' : 'error',
            'message' => $ok ? "¡Paciente llamado exitosamente al {$modulo} y proyectado en el Turnero 2!" : 'No se pudo actualizar el turno.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function cerrar(Request $request, int $ingresoId, int $userId): JsonResponse
    {
        $resultado = $this->flujo->cerrarTicketSalida($ingresoId, $userId, trim((string) $request->input('observacion', 'Entrega física de medicamentos y cierre en ventanilla.')));

        if (! $resultado) {
            return response()->json(['status' => 'error', 'message' => 'No fue posible registrar el cierre de la entrega.']);
        }

        return response()->json([
            'status' => 'ok',
            'message' => "¡Tiquete {$resultado['ticket_numero']} entregado y cerrado exitosamente!",
            'ticket' => $resultado['ticket_numero'],
            'paciente' => trim(($resultado['nombres'] ?? '').' '.($resultado['apellidos'] ?? '')),
            'sla_min' => isset($resultado['fecha_salida'], $resultado['fecha_ingreso'])
                ? round(max(0, strtotime($resultado['fecha_salida']) - strtotime($resultado['fecha_ingreso'])) / 60, 1)
                : 0,
            'data' => $resultado,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Ejecuta un endpoint que puede lanzar; devuelve el JSON con status=error en vez de un 500. */
    private function json(callable $accion): JsonResponse
    {
        try {
            return response()->json($accion(), 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    private function modulosActivos(?int $sede): array
    {
        return DB::table('modulos_entrega')->where('estado', 'ACTIVO')
            ->when($sede, fn ($q, $s) => $q->where('sede_id', $s))
            ->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    }

    public function acta(int $ingreso): View
    {
        $datos = $this->flujo->getById($ingreso);
        abort_unless($datos, 404, 'Registro de ingreso no encontrado.');

        return view('entrega.acta', [
            'id' => $ingreso,
            'ingreso' => $datos,
            'config' => EmpresaConfig::actual()->toArray(),
        ] + $this->datosActa($ingreso, $datos));
    }

    /** Datos del acta: dispensados con lote/FEFO, pendientes, cronograma multimes y datos de IA. */
    private function datosActa(int $id, array $ingreso): array
    {
        $db = DB::connection()->getPdo();

        $faltantesDetalle = ! empty($ingreso['faltantes_alistamiento'])
            ? trim($ingreso['faltantes_alistamiento'])
            : trim($ingreso['observaciones_pendientes'] ?? '');

        $stmt = $db->prepare("
            SELECT md.*, p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.codigo_sku, p.codigo_cums,
                   il.numero_lote, il.fecha_vencimiento, il.fabricante_laboratorio
            FROM ingreso_medicamentos_dispensados md
            JOIN productos_medicamentos p ON md.producto_id = p.id
            JOIN inventario_lotes il ON md.lote_id = il.id
            WHERE md.ingreso_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $medsDispensadosActa = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pendientes / faltantes, sin repetir el mismo medicamento
        $stmt = $db->prepare('SELECT * FROM ingreso_medicamentos_pendientes WHERE ingreso_id = :id ORDER BY id ASC');
        $stmt->execute([':id' => $id]);
        $medsPendientesActa = [];
        $vistos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $clave = mb_strtoupper(trim($item['nombre_medicamento']), 'UTF-8');
            if (! isset($vistos[$clave])) {
                $vistos[$clave] = true;
                $medsPendientesActa[] = $item;
            }
        }

        $esFaltanteReal = ! empty($medsPendientesActa);
        if (! $esFaltanteReal && $faltantesDetalle !== '') {
            $txt = mb_strtoupper($faltantesDetalle, 'UTF-8');
            $exitoso = str_contains($txt, 'VERIFICACIÓN EXITOSA') || str_contains($txt, 'ENTREGA COMPLETA')
                || str_contains($txt, 'SIN NOVEDADES') || str_contains($txt, 'ENTREGA 100%');
            $hayNovedad = str_contains($txt, 'FALTANTE') || str_contains($txt, 'DIFERENCIA') || str_contains($txt, 'SIN REGISTRO')
                || str_contains($txt, 'PENDIENTE') || str_contains($txt, 'NOVEDAD');
            $esFaltanteReal = ! $exitoso && $hayNovedad;
        }

        // Entregas futuras programadas (multimes / crónicos) de este ingreso
        $stmt = $db->prepare('SELECT * FROM tratamientos_cronicos_entregas WHERE ingreso_origen_id = :id ORDER BY fecha_programada ASC, periodo_numero ASC');
        $stmt->execute([':id' => $id]);
        $entregasMultimesActa = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Datos de IA para complementar el multimes si aún no está en la tabla
        $stmt = $db->prepare('SELECT datos_extraidos_json FROM ingreso_formulas_ia WHERE ingreso_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $iaRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $iaDataActa = (! empty($iaRow['datos_extraidos_json']) && is_string($iaRow['datos_extraidos_json']))
            ? (json_decode($iaRow['datos_extraidos_json'], true) ?: [])
            : [];

        $medsMultimesFormula = array_values(array_filter($iaDataActa['medicamentos'] ?? [],
            fn ($m) => ! empty($m['es_multimes']) || (int) ($m['total_periodos'] ?? 1) > 1 || (int) ($m['cantidad_proxima_entrega'] ?? 0) > 0));

        // Cronograma agrupado por medicamento
        $gruposMultimes = [];
        foreach ($entregasMultimesActa as $tc) {
            $k = mb_strtoupper(trim($tc['medicamento_nombre']), 'UTF-8');
            $gruposMultimes[$k] ??= [
                'nombre' => $tc['medicamento_nombre'],
                'posologia' => $tc['posologia'],
                'total_periodos' => (int) $tc['total_periodos'],
                'cantidad_periodo' => (int) $tc['cantidad_periodo'],
                'modalidad' => $tc['modalidad'] ?: 'DOMICILIO',
                'direccion_entrega' => $tc['direccion_entrega'] ?? '',
                'telefono_contacto' => $tc['telefono_contacto'] ?? '',
                'periodos_futuros' => [],
            ];
            $gruposMultimes[$k]['periodos_futuros'][] = [
                'periodo_numero' => (int) $tc['periodo_numero'],
                'fecha_programada' => $tc['fecha_programada'],
                'cantidad' => (int) $tc['cantidad_periodo'],
                'estado' => $tc['estado'],
                'modalidad' => $tc['modalidad'] ?: 'DOMICILIO',
            ];
        }

        return [
            'faltantesDetalle' => $faltantesDetalle,
            'medsDispensadosActa' => $medsDispensadosActa,
            'medsPendientesActa' => $medsPendientesActa,
            'esFaltanteReal' => $esFaltanteReal,
            'entregasMultimesActa' => $entregasMultimesActa,
            'iaDataActa' => $iaDataActa,
            'medsMultimesFormula' => $medsMultimesFormula,
            'gruposMultimes' => $gruposMultimes,
            'tieneMultimes' => ! empty($gruposMultimes) || ! empty($medsMultimesFormula),
        ];
    }
}
