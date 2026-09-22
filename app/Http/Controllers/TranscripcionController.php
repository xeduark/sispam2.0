<?php

namespace App\Http\Controllers;

use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranscripcionController extends Controller
{
    public function index(Request $request, FlujoIngresoService $flujo): View|JsonResponse
    {
        $esAdmin = auth()->user()->esAdministrador();

        // Lista de trabajo en JSON para el autorefresco silencioso de la tabla
        if ($request->has('ajax_get_list')) {
            return response()->json([
                'status' => 'ok',
                'user_id' => auth()->id(),
                'es_admin' => $esAdmin,
                'data' => $flujo->getListaTranscripcion(),
            ]);
        }

        if ($request->filled('ajax_get_detail') && $request->filled('id')) {
            return response()->json($flujo->getById((int) $request->query('id')) ?: []);
        }

        if ($request->has('ajax_get_historial')) {
            $pacienteId = (int) $request->query('paciente_id', 0);
            $numeroDocumento = trim((string) $request->query('numero_documento', ''));

            if ((int) $request->query('ingreso_id', 0) > 0 && ($ingreso = $flujo->getById((int) $request->query('ingreso_id')))) {
                $pacienteId = (int) ($ingreso['paciente_id'] ?? 0);
                $numeroDocumento = $numeroDocumento ?: trim($ingreso['numero_documento'] ?? '');
            }

            return response()->json([
                'status' => 'ok',
                'paciente_id' => $pacienteId,
                'numero_documento' => $numeroDocumento,
                'data' => $flujo->getHistorialPacienteByDocumentoOrPacienteId($pacienteId, $numeroDocumento),
            ]);
        }

        $listaTrabajo = $flujo->getListaTranscripcion();

        return view('transcripcion.index', [
            'esAdmin' => $esAdmin,
            'mensaje' => session('success', ''),
            'error' => session('error', ''),
            'listaTrabajo' => $listaTrabajo,
            'totalCola' => count($listaTrabajo),
            'totalPrioritarios' => count(array_filter($listaTrabajo, fn ($r) => ($r['prioridad'] ?? 'NORMAL') !== 'NORMAL')),
            'totalEnGestion' => count(array_filter($listaTrabajo, fn ($r) => ! empty($r['locked_by_user_id']))),
        ]);
    }

    public function guardarTranscripcion(Request $request, FlujoIngresoService $flujo): RedirectResponse
    {
        $datos = $request->validate([
            'ingreso_id' => ['required', 'integer'],
            'contiene_mipres' => ['nullable', 'in:SI,NO'],
            'pdf_transcripcion' => ['required', 'array', 'min:1'],
            'pdf_transcripcion.*' => ['file', 'mimes:pdf', 'max:25600'],
        ], [
            'pdf_transcripcion.required' => 'Por favor adjunte obligatoriamente al menos un archivo PDF de la orden médica transcrita.',
        ]);

        $exito = $flujo->guardarTranscripcion(
            (int) $datos['ingreso_id'],
            $request->file('pdf_transcripcion', []),
            (int) auth()->id(),
            $datos['contiene_mipres'] ?? 'NO'
        );

        return redirect()->route('transcripcion.index')->with(
            $exito ? 'success' : 'error',
            $exito ? 'Transcripción completada con éxito. La orden pasó a Monitoreo.' : 'Error al guardar la transcripción.'
        );
    }
}
