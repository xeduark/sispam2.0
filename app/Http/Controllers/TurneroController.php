<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TurneroController extends Controller
{
    public function uno(): View
    {
        // TODO: pendiente de portar desde el sistema legacy (vista standalone, sin sidebar).
        return view('_pendiente', ['modulo' => 'Turnero 1 (En Proceso)']);
    }

    public function dos(): View
    {
        // TODO: pendiente de portar desde el sistema legacy (vista standalone, sin sidebar).
        return view('_pendiente', ['modulo' => 'Turnero 2 (Listo Entrega)']);
    }
}
