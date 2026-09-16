<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use App\Models\ModuloEntrega;
use App\Services\IngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlistamientoController extends Controller
{
    public function index(IngresoService $ingresos): View
    {
        return view('alistamiento.index', [
            'listaAlistamiento' => $ingresos->listaAlistamiento(),
            'modulosActivos' => ModuloEntrega::activos()->get(),
            'esAdmin' => auth()->user()->esAdministrador(),
        ]);
    }

    public function lista(IngresoService $ingresos): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'user_id' => auth()->id(),
            'es_admin' => auth()->user()->esAdministrador(),
            'data' => $ingresos->listaAlistamiento()->map(fn (Ingreso $i) => [
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

    public function detalle(Ingreso $ingreso): JsonResponse
    {
        $ingreso->load('paciente', 'orientador', 'documentos');

        return response()->json([
            ...$ingreso->paciente->only(['tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'eps_nombre']),
            ...$ingreso->only(['id', 'ticket_numero', 'estado_tramite', 'prioridad', 'pdf_transcripcion_url']),
            'orientador_nombre' => $ingreso->orientador?->nombre_completo,
            'documentos' => $ingreso->documentos,
        ]);
    }

    public function guardar(Request $request, IngresoService $ingresos): \Illuminate\Http\RedirectResponse
    {
        $datos = $request->validate([
            'ingreso_id' => ['required', 'exists:ingresos,id'],
            'modulo_entrega_asignado' => ['nullable', 'string'],
            'faltantes_alistamiento' => ['nullable', 'string'],
            'pdf_alistamiento' => ['nullable', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $ingreso = Ingreso::with('paciente')->findOrFail($datos['ingreso_id']);

        $moduloAsignado = $ingresos->guardarAlistamiento(
            $ingreso,
            $request->file('pdf_alistamiento'),
            trim((string) ($datos['faltantes_alistamiento'] ?? '')),
            auth()->id(),
            $datos['modulo_entrega_asignado'] ?? 'AUTO'
        );

        $urlTicket = route('alistamiento.ticket', ['ingreso' => $ingreso->id, 'auto_print' => 1]);

        return back()->with('mensaje',
            "¡Alistamiento completado con éxito! La orden pasó al Módulo de Entrega y Facturación (Asignado: <strong>{$moduloAsignado}</strong>). ".
            "<a href='{$urlTicket}' target='_blank' class='btn btn-sm btn-dark ms-2 fw-bold shadow-sm'><i class='fa-solid fa-print me-1'></i> 🖨️ Imprimir Tiquete de Alistamiento</a>"
        );
    }

    public function ticket(Request $request, int $id): View
    {
        $ingreso = Ingreso::with(['paciente', 'sede'])->findOrFail($id);
        $config = EmpresaConfig::actual();

        if ($request->query('rawbt') === '1') {
            return view('alistamiento.ticket_rawbt', compact('ingreso', 'config'));
        }

        abort_unless(auth()->check(), 403);

        return view('alistamiento.ticket', [
            'ingreso' => $ingreso,
            'config' => $config,
            'rawbtUrl' => route('alistamiento.ticket', ['ingreso' => $id, 'rawbt' => 1]),
        ]);
    }
}
