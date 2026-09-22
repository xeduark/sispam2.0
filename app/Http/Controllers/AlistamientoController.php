<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlistamientoController extends Controller
{
    public function __construct(private FlujoIngresoService $flujo) {}

    private function sedeActiva(): ?int
    {
        $sede = session('active_sede_id') ?? auth()->user()?->sede_id;

        return $sede ? (int) $sede : null;
    }

    public function index(Request $request): View|JsonResponse
    {
        $esAdmin = auth()->user()->esAdministrador();

        if ($request->has('ajax_get_list')) {
            return response()->json([
                'status' => 'ok',
                'user_id' => auth()->id(),
                'es_admin' => $esAdmin,
                'data' => $this->flujo->getListaAlistamiento('TODOS'),
                'alistados_hoy' => $this->flujo->getUltimosAlistadosHoy($this->sedeActiva(), 50),
            ]);
        }

        if ($request->filled('ajax_get_detail') && $request->filled('id')) {
            return response()->json($this->flujo->getById((int) $request->query('id')) ?: []);
        }

        return view('alistamiento.index', [
            'esAdmin' => $esAdmin,
            'mensaje' => session('mensaje', ''),
            'error' => session('error', ''),
            'ingreso_alistado_data' => null,
            'listaAlistamiento' => $this->flujo->getListaAlistamiento('TODOS'),
            'modulos_activos' => $this->modulosActivos(),
            'listaAlistadosHoy' => $this->flujo->getUltimosAlistadosHoy($this->sedeActiva(), 50),
        ]);
    }

    /** Ventanillas activas de la sede de trabajo (mismo formato de filas que usa la vista). */
    private function modulosActivos(): array
    {
        return \Illuminate\Support\Facades\DB::table('modulos_entrega')
            ->where('estado', 'ACTIVO')
            ->when($this->sedeActiva(), fn ($q, $sede) => $q->where('sede_id', $sede))
            ->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * POST único con `action`: guardar_alistamiento | marcar_gestionado | devolver_transcripcion.
     * Responde JSON cuando el cliente manda ajax=1 (mismo contrato que la vista nativa).
     */
    public function accion(Request $request): RedirectResponse|JsonResponse
    {
        $accion = $request->input('action');
        $ingresoId = (int) $request->input('ingreso_id', 0);
        $userId = (int) auth()->id();
        $ajax = $request->boolean('ajax');

        if ($ingresoId <= 0) {
            return back()->with('error', 'Identificador de ingreso no válido.');
        }

        if ($accion === 'devolver_transcripcion') {
            $motivo = trim((string) $request->input('motivo_devolucion', '')) ?: 'Devuelto desde alistamiento por falta o inconsistencia de soportes';
            $exito = (bool) $this->flujo->devolverATranscripcion($ingresoId, $userId, $motivo);

            if ($ajax) {
                return response()->json([
                    'status' => $exito ? 'ok' : 'error',
                    'message' => $exito ? "Orden #{$ingresoId} devuelta a Transcripción exitosamente." : 'Error al devolver la orden a Transcripción.',
                ]);
            }

            return back()->with($exito ? 'mensaje' : 'error', $exito ? "¡Orden #{$ingresoId} devuelta a Transcripción para corrección de soportes!" : 'No se pudo devolver la orden a Transcripción.');
        }

        if ($accion === 'marcar_gestionado') {
            $exito = (bool) $this->flujo->marcarGestionadoSupervisor($ingresoId, $userId);
            if ($exito) {
                registrar_log_auditoria('ALISTAMIENTO', 'MARCAR_GESTIONADO_SUPERVISOR', $ingresoId, "Orden #{$ingresoId} aprobada por el supervisor de alistamiento y enviada al Módulo de Entrega.");
            }

            if ($ajax) {
                return response()->json([
                    'status' => $exito ? 'ok' : 'error',
                    'message' => $exito ? "Orden #{$ingresoId} impresa y lista en espera física para Entrega & Facturación." : 'Error al actualizar la orden.',
                ]);
            }

            return back()->with($exito ? 'mensaje' : 'error', $exito ? "¡Orden #{$ingresoId} impresa y colocada en espera para Entrega & Facturación!" : 'No se pudo actualizar el estado de la orden.');
        }

        // guardar_alistamiento (picking)
        $request->validate(['pdf_alistamiento' => ['nullable', 'file', 'mimes:pdf', 'max:25600']]);

        $modulo = $this->flujo->guardarAlistamiento(
            $ingresoId,
            $request->file('pdf_alistamiento'),
            trim((string) $request->input('faltantes_alistamiento', '')),
            $userId,
            trim((string) $request->input('modulo_entrega_asignado', 'AUTO')) ?: 'AUTO'
        );

        if (! $modulo) {
            return back()->with('error', 'No se pudo guardar la gestión de alistamiento.');
        }

        $urlTicket = route('alistamiento.ticket', ['ingreso' => $ingresoId, 'auto_print' => 1]);

        return back()->with('mensaje',
            "¡Alistamiento completado con éxito! La orden pasó al Módulo de Entrega y Facturación (Asignado: <strong>".e($modulo)."</strong>). ".
            "<a href='{$urlTicket}' target='_blank' class='btn btn-sm btn-dark ms-2 fw-bold shadow-sm'><i class='fa-solid fa-print me-1'></i> 🖨️ Imprimir Tiquete de Alistamiento</a>"
        );
    }

    public function ordenUnificada(int $ingreso): View
    {
        $datos = $this->flujo->getById($ingreso);
        abort_unless($datos, 404, 'Registro de ingreso u orden no encontrado.');

        return view('alistamiento.orden_unificada', [
            'id' => $ingreso,
            'ingreso' => $datos,
            'config' => EmpresaConfig::actual()->toArray(),
            'nombre_sede' => $datos['nombre_sede'] ?? ($datos['sede_nombre'] ?? 'Sede Principal'),
        ]);
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
