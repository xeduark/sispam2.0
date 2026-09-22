<?php

namespace App\Http\Controllers;

use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoreoController extends Controller
{
    public function index(Request $request, FlujoIngresoService $flujo): View|JsonResponse
    {
        if ($request->filled('ajax_get_detail') && $request->filled('id')) {
            return response()->json($flujo->getById((int) $request->input('id')) ?: []);
        }

        return view('monitoreo.index', [
            'ingresosMonitoreo' => $flujo->getIngresosParaMonitoreo(),
        ]);
    }

    public function guardarVerificacion(Request $request, FlujoIngresoService $flujo): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'ingreso_id' => ['required', 'integer'],
            'estado_verificacion' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string'],
            'transcripcion_texto_nuevo' => ['nullable', 'string'],
            'monitoreo_contiene_mipres' => ['nullable', 'in:SI,NO'],
            'nuevo_pdf_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:25600'],
            'pdf_mipres_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:25600'],
        ]);

        $id = (int) $datos['ingreso_id'];
        $estado = $datos['estado_verificacion'] ?? 'VERIFICADA';
        $observaciones = trim($datos['observaciones'] ?? '');
        $contieneMipres = $datos['monitoreo_contiene_mipres'] ?? 'NO';
        $mipres = $request->file('pdf_mipres_file');

        $exito = $flujo->marcarVerificadoMonitor(
            $id,
            (int) auth()->id(),
            $estado,
            $observaciones,
            $request->file('nuevo_pdf_file'),
            $request->has('transcripcion_texto_nuevo') ? trim($datos['transcripcion_texto_nuevo'] ?? '') : null,
            $contieneMipres,
            $mipres
        );

        if ($exito) {
            $marca = ($contieneMipres === 'SI' || $mipres) ? ' [Marcada con MIPRES]' : ' [Sin MIPRES]';
            registrar_log_auditoria('MONITOREO', 'VERIFICACION_TECNICA', $id, "Verificación técnica completada. Estado: {$estado}.{$marca} Observaciones: {$observaciones}");
        }

        $mensaje = $estado === 'VERIFICADA'
            ? "¡Orden #{$id} marcada como VERIFICADA OK y enviada al Supervisor de Alistamiento!"
            : "¡Orden #{$id} corregida y enviada al Supervisor de Alistamiento!";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(
                $exito ? ['status' => 'ok', 'message' => $mensaje] : ['status' => 'error', 'message' => "Error al actualizar la verificación de la orden #{$id}."],
                $exito ? 200 : 422
            );
        }

        return redirect()->route('monitoreo.index')->with($exito ? 'success' : 'error', $exito ? $mensaje : 'Error al actualizar la verificación de la orden.');
    }
}
