<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PacientesController extends Controller
{
    public function importar(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Carga Masiva de Pacientes (CSV)']);
    }
}
