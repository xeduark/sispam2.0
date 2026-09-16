<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Services\IngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranscripcionController extends Controller
{
    public function index(IngresoService $ingresos): View
    {
        return view('transcripcion.index', [
            'listaTrabajo' => $ingresos->listaTranscripcion(),
            'esAdmin' => auth()->user()->esAdministrador(),
        ]);
    }

    /** Polling de autorefresco de la tabla (antes ?ajax_get_list=1 en la misma página). */
    public function lista(IngresoService $ingresos): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'user_id' => auth()->id(),
            'es_admin' => auth()->user()->esAdministrador(),
            'data' => $ingresos->listaTranscripcion()->map(fn (Ingreso $i) => [
                'id' => $i->id,
                'ticket_numero' => $i->ticket_numero,
                'fecha_ingreso' => $i->fecha_ingreso?->format('Y-m-d H:i:s'),
                'prioridad' => $i->prioridad,
                'estado_tramite' => $i->estado_tramite,
                'locked_by_user_id' => $i->locked_by_user_id,
                'locked_by_nombre' => $i->bloqueadoPor?->nombre_completo,
                'tipo_documento' => $i->paciente->tipo_documento,
                'numero_documento' => $i->paciente->numero_documento,
                'nombres' => $i->paciente->nombres,
                'apellidos' => $i->paciente->apellidos,
                'eps_nombre' => $i->paciente->eps_nombre,
            ]),
        ]);
    }

    /** Detalle para el modal de gestión (antes ?ajax_get_detail=1 en la misma página). */
    public function detalle(Ingreso $ingreso): JsonResponse
    {
        $ingreso->load('paciente', 'orientador', 'documentos');

        return response()->json([
            ...$ingreso->paciente->only(['tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'eps_nombre']),
            ...$ingreso->only(['id', 'ticket_numero', 'estado_tramite', 'prioridad']),
            'orientador_nombre' => $ingreso->orientador?->nombre_completo,
            'documentos' => $ingreso->documentos,
        ]);
    }

    public function guardarTranscripcion(Request $request, IngresoService $ingresos): RedirectResponse
    {
        $datos = $request->validate([
            'ingreso_id' => ['required', 'exists:ingresos,id'],
            'pdf_transcripcion' => ['nullable', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $ingreso = Ingreso::with('paciente')->findOrFail($datos['ingreso_id']);
        $ingresos->guardarTranscripcion($ingreso, $request->file('pdf_transcripcion'));

        return back()->with('mensaje', 'Transcripción completada con éxito. La orden pasó a Alistamiento.');
    }
}
