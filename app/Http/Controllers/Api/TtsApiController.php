<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Síntesis de voz femenina en español para el llamado de pacientes en el Turnero TV.
 * Público (lo consumen las Smart TV sin sesión). El audio se cachea en disco para que
 * el rellamado sea instantáneo.
 */
class TtsApiController extends Controller
{
    private const PRONUNCIACION = [
        'Jesus' => 'Jesús', 'Maria' => 'María', 'Jose' => 'José', 'Angel' => 'Ángel', 'Ramon' => 'Ramón',
        'Andres' => 'Andrés', 'Sebastian' => 'Sebastián', 'Raul' => 'Raúl', 'Ivan' => 'Iván', 'Julian' => 'Julián',
        'Hernan' => 'Hernán', 'Ruben' => 'Rubén', 'Hector' => 'Héctor', 'Oscar' => 'Óscar', 'Cesar' => 'César',
        'Victor' => 'Víctor', 'Sofia' => 'Sofía', 'Lucia' => 'Lucía', 'Matias' => 'Matías', 'Martin' => 'Martín',
        'Damian' => 'Damián', 'Agustin' => 'Agustín', 'Joaquin' => 'Joaquín', 'Monica' => 'Mónica',
        'Veronica' => 'Verónica', 'Alvaro' => 'Álvaro',
    ];

    public function voz(Request $request): Response
    {
        $texto = trim((string) ($request->query('text') ?? $request->query('q', '')));

        if ($texto === '') {
            return response()->json(['status' => 'error', 'message' => 'Texto requerido'], 400);
        }

        $limpio = str_replace('*', '', preg_replace('/\s+/', ' ', mb_substr($texto, 0, 220)));
        $limpio = mb_convert_case(mb_strtolower($limpio), MB_CASE_TITLE);
        foreach (self::PRONUNCIACION as $sin => $con) {
            $limpio = preg_replace('/\b'.$sin.'\b/i', $con, $limpio);
        }

        $disco = Storage::disk('local');
        $ruta = 'tts_cache/'.md5('tts_v3_'.$limpio).'.mp3';

        if ($disco->exists($ruta) && $disco->size($ruta) > 500) {
            return $this->audio($disco->get($ruta));
        }

        foreach (['es', 'es-CO', 'es-ES'] as $idioma) {
            $respuesta = Http::timeout(6)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->get('https://translate.google.com/translate_tts', ['ie' => 'UTF-8', 'tl' => $idioma, 'client' => 'tw-ob', 'q' => $limpio]);

            if ($respuesta->successful() && strlen($respuesta->body()) > 500) {
                $disco->put($ruta, $respuesta->body());

                return $this->audio($respuesta->body());
            }
        }

        return response()->json(['status' => 'error', 'message' => 'No fue posible generar el audio TTS'], 502);
    }

    private function audio(string $binario): Response
    {
        return response($binario, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => strlen($binario),
            'Cache-Control' => 'public, max-age=604800',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
