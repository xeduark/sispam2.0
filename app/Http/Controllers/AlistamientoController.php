<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AlistamientoController extends Controller
{
    public function index(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Alistamiento']);
    }
}
