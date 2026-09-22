<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\TratamientoCronico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacienteApiController extends Controller
{
    /**
     * Busca un paciente por documento (autocompleta el formulario de Ingreso) e incluye
     * sus entregas de tratamiento crónico (multimes) pendientes.
     */
    public function buscar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tipo_documento' => ['nullable', 'string'],
            'numero_documento' => ['required', 'string'],
        ]);

        $paciente = Paciente::buscarPorDocumento($datos['tipo_documento'] ?? 'CC', trim($datos['numero_documento']));

        if (! $paciente) {
            return response()->json(['status' => 'not_found', 'message' => 'Paciente no encontrado']);
        }

        $entregas = (new TratamientoCronico())->getEntregasPendientesPaciente($paciente->id);

        return response()->json([
            'status' => 'found',
            'data' => $paciente,
            'entregas_programadas' => $entregas,
            'total_entregas_programadas' => count($entregas),
        ]);
    }

    /** Últimas 10 atenciones del documento, para el aviso de historial del formulario de Ingreso. */
    public function historial(Request $request): JsonResponse
    {
        $tipo = trim((string) ($request->query('tipo_doc') ?? $request->query('tipo_documento', 'CC')));
        $numero = trim((string) ($request->query('num_doc') ?? $request->query('numero_documento', '')));

        if ($numero === '') {
            return response()->json(['status' => 'error', 'message' => 'Número de documento requerido']);
        }

        $paciente = Paciente::buscarPorDocumento($tipo, $numero);
        $nombre = $paciente ? trim(($paciente->primer_nombre ?: $paciente->nombres).' '.($paciente->primer_apellido ?: $paciente->apellidos)) : "Paciente {$numero}";
        $limpio = preg_replace('/[^\w\-]/', '', $numero);

        $filas = DB::table('ingresos as i')
            ->leftJoin('pacientes as p', 'i.paciente_id', '=', 'p.id')
            ->where(fn ($q) => $q->where('p.numero_documento', $numero)
                ->orWhereRaw("REPLACE(REPLACE(p.numero_documento, '.', ''), ' ', '') = ?", [$limpio])
                ->orWhere('i.ticket_numero', 'like', "%{$limpio}%"))
            ->orderByDesc('i.id')->limit(10)
            ->get(['i.id', 'i.ticket_numero', 'i.estado_tramite', 'i.created_at', 'i.updated_at']);

        $incompletas = 0;
        $atenciones = $filas->map(function ($h) use (&$incompletas) {
            $estado = $h->estado_tramite ?? 'PENDIENTE';
            $clase = 'success';

            if (in_array($estado, ['PENDIENTE', 'TRANSCRITO_PENDIENTE', 'RECHAZADO'], true)) {
                $clase = 'danger';
                $incompletas++;
            } elseif (in_array($estado, ['EN_PROCESO', 'ALISTADO', 'ESPERA_ENTREGA'], true)) {
                $clase = 'warning';
            }

            return [
                'id' => $h->id,
                'fecha' => date('d/m/Y h:i A', strtotime($h->created_at)),
                'turno' => $h->ticket_numero ?? 'ID-'.$h->id,
                'estado' => $estado,
                'etiqueta' => $estado,
                'clase' => $clase,
                'faltantes' => [],
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'paciente' => ['nombre' => $nombre, 'documento' => $numero],
            'atenciones' => $atenciones,
            'total' => $atenciones->count(),
            'incompletas' => $incompletas,
            'alerta_duplicado' => null,
        ]);
    }
}
