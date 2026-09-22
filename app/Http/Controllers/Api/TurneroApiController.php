<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfig;
use App\Models\Sede;
use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TurneroApiController extends Controller
{
    /**
     * Datos de las pantallas de sala de espera (públicas, sin autenticación, kioscos de TV).
     * type=1: pacientes en proceso; type=2: listos para entrega + último rellamado.
     * La sede se resuelve de forma estricta para que no se filtren turnos ni audios entre sedes.
     */
    public function data(Request $request, FlujoIngresoService $flujo): JsonResponse
    {
        $sedeId = (int) $request->query('sede_id', 0);
        $sedeId = $sedeId > 0 ? $sedeId : (int) (session('active_sede_id') ?: 1);

        $turnos = $request->query('type', '1') === '1' ? $flujo->getTurnero1($sedeId) : $flujo->getTurnero2($sedeId);
        $respuesta = [
            'config' => EmpresaConfig::actual()->toArray(),
            'sede_id' => $sedeId,
            'sede_nombre' => Sede::find($sedeId)?->nombre_sede ?? 'Sede Principal',
            'turnos' => array_map(fn ($t) => $this->conNombres($t), $turnos),
        ];

        if ($request->query('type', '1') !== '1') {
            $ultimo = $flujo->getUltimoRellamado($sedeId, 20);
            $respuesta['ultimo_rellamado'] = $ultimo ? $this->conNombres($ultimo) : $ultimo;
        }

        return response()->json($respuesta);
    }

    private function conNombres(array $fila): array
    {
        $fila['nombre_habeas'] = $this->anonimizar($fila['nombres'] ?? '', $fila['apellidos'] ?? '');
        $fila['nombre_completo'] = trim(($fila['nombres'] ?? '').' '.($fila['apellidos'] ?? ''));

        return $fila;
    }

    /** Habeas Data en pantallas públicas: primer nombre completo y solo 2 letras de cada apellido. */
    private function anonimizar(string $nombres, string $apellidos): string
    {
        $primerNombre = mb_convert_case(preg_split('/\s+/', trim($nombres))[0] ?? '', MB_CASE_TITLE, 'UTF-8');

        $apellidosAnonimos = collect(preg_split('/\s+/', trim($apellidos)))
            ->filter()
            ->map(function ($ape) {
                $prefijo = mb_convert_case(mb_substr($ape, 0, 2, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

                return $prefijo.(mb_strlen($ape, 'UTF-8') <= 2 ? '****' : '******');
            })->implode(' ');

        return trim($primerNombre.' '.$apellidosAnonimos);
    }
}
