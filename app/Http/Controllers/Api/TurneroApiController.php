<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TurneroApiController extends Controller
{
    /** Pantallas públicas de sala de espera: sin autenticación, como el legacy. */
    public function data(Request $request): JsonResponse
    {
        $tipo = (string) $request->query('type', '1');

        return response()->json([
            'config' => EmpresaConfig::actual(),
            'turnos' => $tipo === '1' ? $this->enProceso() : $this->listosParaEntrega(),
        ]);
    }

    private function enProceso(): array
    {
        return Ingreso::with('paciente')
            ->whereIn('estado_tramite', ['INGRESADO', 'EN_TRANSCRIPCION', 'TRANSCRITO_COMPLETO', 'TRANSCRITO_PENDIENTE'])
            ->ordenAtencion()
            ->limit(12)
            ->get()
            ->map(fn (Ingreso $i) => [
                'ticket_numero' => $i->ticket_numero,
                'estado_tramite' => $i->estado_tramite,
                'fecha_ingreso' => $i->fecha_ingreso,
                'prioridad' => $i->prioridad,
                // Habeas Data: en pantalla pública solo primer nombre e inicial del apellido.
                'nombre_habeas' => $this->anonimizar($i->paciente?->nombres, $i->paciente?->apellidos),
            ])
            ->all();
    }

    private function listosParaEntrega(): array
    {
        return Ingreso::with('paciente')
            ->where('estado_tramite', 'ALISTADO')
            ->orderByRaw("IF(prioridad = 'NORMAL', 1, 0) ASC")
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Ingreso $i) => [
                'id' => $i->id,
                'ticket_numero' => $i->ticket_numero,
                'modulo_entrega_asignado' => $i->modulo_entrega_asignado,
                'updated_at' => $i->updated_at,
                'prioridad' => $i->prioridad,
                'nombre_completo' => mb_strtoupper(trim($i->paciente?->nombres.' '.$i->paciente?->apellidos)),
            ])
            ->all();
    }

    private function anonimizar(?string $nombres, ?string $apellidos): string
    {
        $n = explode(' ', trim((string) $nombres))[0] ?? '';
        $a = explode(' ', trim((string) $apellidos))[0] ?? '';

        return mb_strtoupper($n).' '.mb_substr($a, 0, 1).'***';
    }
}
