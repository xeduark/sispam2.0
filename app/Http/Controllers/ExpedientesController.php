<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpedientesController extends Controller
{
    public function index(Request $request): View
    {
        $numDoc = trim((string) $request->query('num_doc', ''));

        $resultados = $numDoc === ''
            ? collect()
            : Ingreso::query()
                ->with(['paciente', 'orientador', 'documentos'])
                ->whereHas('paciente', fn ($q) => $q->where('numero_documento', $numDoc))
                ->orderByDesc('fecha_ingreso')
                ->get();

        return view('expedientes.index', compact('numDoc', 'resultados'));
    }
}
