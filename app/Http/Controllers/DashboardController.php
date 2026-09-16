<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Services\IngresoService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(IngresoService $ingresos): View
    {
        return view('dashboard', [
            'config' => EmpresaConfig::actual(),
            'totalTranscripcion' => $ingresos->listaTranscripcion()->count(),
            'totalAlistamiento' => $ingresos->listaAlistamiento()->count(),
            'totalEntrega' => $ingresos->listaEntrega()->count(),
        ]);
    }
}
