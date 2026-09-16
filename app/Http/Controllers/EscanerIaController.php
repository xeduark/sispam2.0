<?php

namespace App\Http\Controllers;

use App\Services\AiExtractorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscanerIaController extends Controller
{
    public function index(): View
    {
        return view('ia_scanner.index', [
            'modoDemo' => empty(config('ia_scanner.gemini_api_key')),
            'resultado' => null,
        ]);
    }

    public function procesar(Request $request, AiExtractorService $extractor): View
    {
        $request->validate([
            'formula' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $resultado = $extractor->extraerDatosFormula($request->file('formula'));

        return view('ia_scanner.index', [
            'modoDemo' => empty(config('ia_scanner.gemini_api_key')),
            'resultado' => $resultado,
        ]);
    }
}
