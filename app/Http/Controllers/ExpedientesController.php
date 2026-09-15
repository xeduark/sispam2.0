<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ExpedientesController extends Controller
{
    public function index(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Consulta de Órdenes']);
    }
}
