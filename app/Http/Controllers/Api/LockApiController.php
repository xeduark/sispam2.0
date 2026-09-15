<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Services\IngresoService;
use Illuminate\Http\JsonResponse;

class LockApiController extends Controller
{
    public function __construct(private IngresoService $ingresos) {}

    public function bloquear(Ingreso $ingreso): JsonResponse
    {
        if ($this->ingresos->bloquear($ingreso->id, auth()->id())) {
            return response()->json(['status' => 'ok', 'message' => 'Registro bloqueado para este usuario']);
        }

        $quien = $ingreso->fresh()->bloqueadoPor?->nombre_completo ?? 'Otro usuario';

        return response()->json([
            'status' => 'locked',
            'message' => 'El expediente está siendo gestionado en este momento por: '.$quien,
        ]);
    }

    public function liberar(Ingreso $ingreso): JsonResponse
    {
        $this->ingresos->liberar($ingreso->id, auth()->id());

        return response()->json(['status' => 'ok', 'message' => 'Registro liberado']);
    }
}
