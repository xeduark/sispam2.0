<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalidaController extends Controller
{
    public function __construct(private FlujoIngresoService $flujo) {}

    public function index(Request $request): View|JsonResponse
    {
        $sedeActiva = session('active_sede_id') ?? auth()->user()?->sede_id;
        $sedeFiltro = $request->has('sede_id') ? $request->query('sede_id') : $sedeActiva;

        // Búsqueda rápida por código de barras o documento (pistola lectora)
        if ($request->has('ajax_buscar_salida')) {
            $resultado = $this->flujo->buscarIngresoParaSalida(trim((string) $request->query('criterio', '')), $sedeFiltro);

            return response()->json(['status' => $resultado ? 'ok' : 'not_found', 'data' => $resultado]);
        }

        $mensaje = session('success', '');
        $error = session('error', '');
        $infoMsg = '';
        $ingresoEncontrado = null;
        $criterioBusqueda = trim((string) $request->query('buscar', ''));

        if ($criterioBusqueda !== '') {
            $ingresoEncontrado = $this->flujo->buscarIngresoParaSalida($criterioBusqueda, $sedeFiltro);

            if (! $ingresoEncontrado) {
                $error = 'No se encontró ningún tiquete <strong>activo para el día de hoy</strong> con el criterio: <strong>'.e($criterioBusqueda).'</strong>.';
            } elseif (! empty($ingresoEncontrado['ya_cerrado'])) {
                $hora = date('h:i A', strtotime($ingresoEncontrado['fecha_salida']));
                $tiquete = e($ingresoEncontrado['ticket_numero']);
                $paciente = e($ingresoEncontrado['nombres'].' '.$ingresoEncontrado['apellidos']);
                $minutos = $ingresoEncontrado['minutos_transcurridos'];
                $infoMsg = "El paciente <strong>{$paciente}</strong> ya cuenta con su tiquete de hoy <strong>{$tiquete}</strong> cerrado a las <strong>{$hora}</strong> (Tiempo SLA: <strong>{$minutos} min</strong>).";
                $ingresoEncontrado = null; // no reabrir el modal de cierre
            }
        }

        return view('salida.index', [
            'sedes_disponibles' => Sede::todasConEmpresa(),
            'sede_filtro' => $sedeFiltro,
            'mensaje' => $mensaje,
            'error' => $error,
            'infoMsg' => $infoMsg,
            'ingresoEncontrado' => $ingresoEncontrado,
            'criterioBusqueda' => $criterioBusqueda,
            'pendientesSalida' => $this->flujo->getListaPendientesSalida($sedeFiltro, null),
            'ultimasSalidas' => $this->flujo->getListaUltimasSalidas($sedeFiltro, null),
        ]);
    }

    /** Cierre express por AJAX (pistola lectora): responde JSON. */
    public function cerrarExpressAjax(Request $request): JsonResponse
    {
        $ingresoId = (int) $request->input('ingreso_id', 0);
        $resultado = $ingresoId > 0
            ? $this->flujo->cerrarTicketSalida($ingresoId, (int) auth()->id(), trim((string) $request->input('observacion_salida', '')))
            : false;

        if (! $resultado) {
            return response()->json(['status' => 'error', 'message' => 'No fue posible registrar la salida del tiquete.'], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => "¡Tiquete {$resultado['ticket_numero']} cerrado exitosamente!",
            'ticket' => $resultado['ticket_numero'],
            'paciente' => trim(($resultado['nombres'] ?? '').' '.($resultado['apellidos'] ?? '')),
            'sla_min' => $this->minutosSla($resultado),
            'data' => $resultado,
        ]);
    }

    /** Cierre desde el modal de confirmación (formulario estándar). */
    public function cerrarTicket(Request $request): RedirectResponse
    {
        $ingresoId = (int) $request->input('ingreso_id', 0);
        $sedeFiltro = $request->input('sede_id');
        $destino = route('salida.index', $sedeFiltro ? ['sede_id' => $sedeFiltro] : []);

        if ($ingresoId <= 0) {
            return redirect($destino)->with('error', 'Identificador de ingreso no válido para cierre.');
        }

        $resultado = $this->flujo->cerrarTicketSalida($ingresoId, (int) auth()->id(), trim((string) $request->input('observacion_salida', '')));

        if (! $resultado) {
            return redirect($destino)->with('error', 'No fue posible registrar la salida del tiquete. Verifique los datos o intente nuevamente.');
        }

        $tiquete = e($resultado['ticket_numero'] ?? '');
        $minutos = $this->minutosSla($resultado);

        return redirect($destino)->with('success', "¡Tiquete <strong>{$tiquete}</strong> cerrado exitosamente! Tiempo total de atención registrado: <strong>{$minutos} minutos</strong> (SLA actualizado).");
    }

    private function minutosSla(array $resultado): float
    {
        if (! isset($resultado['fecha_salida'], $resultado['fecha_ingreso'])) {
            return 0;
        }

        return round(max(0, strtotime($resultado['fecha_salida']) - strtotime($resultado['fecha_ingreso'])) / 60, 1);
    }
}
