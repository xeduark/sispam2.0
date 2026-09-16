<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;

class NotificacionApiController extends Controller
{
    public function index(): JsonResponse
    {
        $notificaciones = Notificacion::with('ingreso.paciente')
            ->sinLeerPara(auth()->id())
            ->get()
            ->map(fn (Notificacion $n) => [
                'id' => $n->id,
                'ingreso_id' => $n->ingreso_id,
                'mensaje' => $n->mensaje,
                'leido' => $n->leido,
                'created_at' => $n->created_at,
                'ticket_numero' => $n->ingreso?->ticket_numero,
                'nombres' => $n->ingreso?->paciente?->nombres,
                'apellidos' => $n->ingreso?->paciente?->apellidos,
            ]);

        return response()->json($notificaciones);
    }

    public function marcarLeido(Notificacion $notificacion): JsonResponse
    {
        abort_unless($notificacion->usuario_destino_id === auth()->id(), 403);

        $notificacion->update(['leido' => 1]);

        return response()->json(['status' => 'ok']);
    }
}
