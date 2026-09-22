<?php

namespace App\Http\Controllers;

use App\Services\FlujoIngresoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpedientesController extends Controller
{
    public function index(Request $request, FlujoIngresoService $flujo): View
    {
        $num_doc = trim((string) ($request->query('num_doc') ?? $request->query('buscar', '')));

        return view('expedientes.index', [
            'num_doc' => $num_doc,
            'resultados' => $num_doc !== '' ? $flujo->buscarPorDocumento($num_doc) : [],
        ]);
    }
}
