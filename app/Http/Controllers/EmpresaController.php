<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function edit(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Empresa & Sede']);
    }
}
