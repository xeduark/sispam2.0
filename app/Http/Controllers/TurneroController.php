<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use Illuminate\View\View;

/**
 * Pantallas de sala de espera. Documentos standalone, sin sidebar ni sesión:
 * corren en un televisor, igual que en el sistema legacy.
 */
class TurneroController extends Controller
{
    public function uno(): View
    {
        return view('turnero.turnero1', ['config' => EmpresaConfig::actual()]);
    }

    public function dos(): View
    {
        return view('turnero.turnero2', ['config' => EmpresaConfig::actual()]);
    }
}
