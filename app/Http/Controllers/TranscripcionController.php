<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TranscripcionController extends Controller
{
    public function index(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Transcripción & Stock']);
    }
}
