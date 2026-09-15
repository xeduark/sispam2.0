<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use App\Services\IngresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function index(IngresoService $ingresos): View
    {
        return view('entrega.index', [
            'listaEntrega' => $ingresos->listaEntrega('TODOS'),
        ]);
    }

    public function finalizar(Request $request, IngresoService $ingresos): RedirectResponse
    {
        $datos = $request->validate([
            'ingreso_id' => ['required', 'exists:ingresos,id'],
            'firma_base64' => ['required', 'string'],
            'foto_paciente_base64' => ['nullable', 'string'],
            'foto_paciente_file' => ['nullable', 'file', 'image', 'max:10240'],
        ], [
            'firma_base64.required' => 'Es obligatorio capturar la firma digital del paciente.',
        ]);

        $ingreso = Ingreso::findOrFail($datos['ingreso_id']);

        $ingresos->finalizarEntrega(
            $ingreso,
            $datos['firma_base64'],
            $datos['foto_paciente_base64'] ?? null,
            $request->file('foto_paciente_file')
        );

        $urlActa = route('entrega.acta', $ingreso->id);

        return back()->with('mensaje',
            'Entrega finalizada con éxito. El acta de entrega ha sido firmada digitalmente. '.
            "<a href='{$urlActa}' target='_blank' class='btn btn-sm btn-success ms-2 fw-bold'><i class='fa-solid fa-print me-1'></i> Imprimir Acta Firmada + PDFs</a>"
        );
    }

    public function acta(int $id): View
    {
        $ingreso = Ingreso::with(['paciente', 'orientador'])->findOrFail($id);

        return view('entrega.acta', [
            'ingreso' => $ingreso,
            'config' => EmpresaConfig::actual(),
            'faltantesDetalle' => $ingreso->faltantes_alistamiento ?: ($ingreso->observaciones_pendientes ?? ''),
        ]);
    }
}
