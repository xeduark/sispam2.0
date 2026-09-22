<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Services\FlujoIngresoService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(FlujoIngresoService $flujo): View
    {
        $usuario = auth()->user();
        $error = session('error_acceso');

        // El middleware de roles redirige aquí con ?error=acceso_denegado
        if (request('error') === 'acceso_denegado') {
            $error = 'No tiene permisos para acceder a ese módulo.';
        }

        return view('dashboard', [
            'config' => EmpresaConfig::actual()->toArray(),
            'transcripcionList' => $flujo->getListaTranscripcion(),
            'alistamientoList' => $flujo->getListaAlistamiento(),
            'entregaList' => $flujo->getListaEntrega(),
            'user_role' => $usuario->rol?->nombre ?? '',
            'active_sede_id' => session('active_sede_id') ?? $usuario->sede_id,
            'error' => $error,
        ]);
    }
}
