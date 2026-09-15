<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function index(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Entrega & Factura']);
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
