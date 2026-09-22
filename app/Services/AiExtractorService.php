<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Extrae paciente + medicamentos de una fórmula médica escaneada (imagen o PDF).
 *
 * Sin GEMINI_API_KEY configurada, cae al modo demo (extractorSimuladoInteligente):
 * heurística por nombre de archivo, igual que en el sistema de producción, útil
 * para probar el flujo completo sin depender de una cuenta de Google AI Studio.
 */
class AiExtractorService
{
    public function diagnosticarApiKey(string $key): array
    {
        $key = trim($key);

        if ($key === '') {
            return ['status' => 'error', 'message' => 'No se ha ingresado ninguna API Key.'];
        }

        $respuesta = Http::timeout(6)->get('https://generativelanguage.googleapis.com/v1beta/models', ['key' => $key]);

        if ($respuesta->successful() && ! empty($respuesta->json('models'))) {
            $modelos = collect($respuesta->json('models'))
                ->filter(fn ($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true))
                ->map(fn ($m) => str_replace('models/', '', $m['name']))
                ->values();

            return [
                'status' => 'ok',
                'total_modelos' => $modelos->count(),
                'modelos' => $modelos,
                'mensaje' => 'API Key válida y conectada exitosamente con Google AI Studio.',
            ];
        }

        return [
            'status' => 'error',
            'message' => $respuesta->json('error.message') ?? "HTTP {$respuesta->status()} - Respuesta no esperada",
        ];
    }

    public function extraerDatosFormula(UploadedFile $archivo, ?string $apiKeyOverride = null): array
    {
        return $this->extraerDatosDesdeRuta($archivo->getRealPath(), $archivo->getClientOriginalName(), $archivo->getMimeType(), $apiKeyOverride);
    }

    /** Igual que extraerDatosFormula() pero para un archivo ya guardado en disco (reprocesamiento desde la cola IA). */
    public function extraerDatosDesdeRuta(string $rutaAbsoluta, string $nombreOriginal, ?string $mime = null, ?string $apiKeyOverride = null): array
    {
        $inicio = microtime(true);
        $key = trim($apiKeyOverride ?: (config('ia_scanner.gemini_api_key') ?: ''));

        if ($key === '') {
            $resultado = $this->extractorSimuladoInteligente($nombreOriginal);
            $resultado['tiempo_segundos'] = round(microtime(true) - $inicio, 2);
            $resultado['motor'] = 'Extractor Clínico Demostrativo SISPAM (Local)';
            $resultado['modo'] = 'demo_local';

            return $resultado;
        }

        try {
            $resultado = $this->ejecutarLlamadaGenerativa($rutaAbsoluta, $mime, $key);
            $resultado['tiempo_segundos'] = round(microtime(true) - $inicio, 2);
            $resultado['motor'] = 'Google Gemini ('.config('ia_scanner.modelo').') - Live Vision OCR';
            $resultado['modo'] = 'en_vivo';

            return $resultado;
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Google Gemini Cloud: '.$e->getMessage().'. Por favor intenta de nuevo en unos segundos.',
            ];
        }
    }

    private function ejecutarLlamadaGenerativa(string $rutaAbsoluta, ?string $mimeHint, string $key): array
    {
        $modelo = config('ia_scanner.modelo');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent";

        $mime = $mimeHint ?: (function_exists('mime_content_type') ? mime_content_type($rutaAbsoluta) : null) ?: 'application/octet-stream';
        $base64 = base64_encode(file_get_contents($rutaAbsoluta));

        $respuesta = Http::timeout(30)->post("{$url}?key={$key}", [
            'contents' => [[
                'parts' => [
                    ['text' => $this->prompt()],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                ],
            ]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.1,
            ],
        ]);

        if ($respuesta->failed()) {
            throw new \RuntimeException("HTTP {$respuesta->status()} ({$modelo}) - ".($respuesta->json('error.message') ?? 'sin detalle'));
        }

        $textoCrudo = trim($respuesta->json('candidates.0.content.parts.0.text') ?? '{}');
        $textoCrudo = preg_replace('/^```json|^```|```$/m', '', $textoCrudo);
        $datos = json_decode(trim($textoCrudo), true);

        if (! $datos || ! isset($datos['paciente'])) {
            throw new \RuntimeException('La respuesta de la IA no contiene la estructura JSON esperada.');
        }

        return ['status' => 'ok', 'data' => $datos];
    }

    private function prompt(): string
    {
        return 'Eres un farmacéutico clínico y auditor experto en extracción exhaustiva de fórmulas médicas en Colombia. '
            .'Tu tarea es analizar minuciosamente este archivo (PDF o Imagen) e IDENTIFICAR ABSOLUTAMENTE TODOS LOS MEDICAMENTOS PRESCRITOS, '
            .'EMPEZANDO OBLIGATORIAMENTE POR EL PRIMER MEDICAMENTO (#1) EN LA GRILLA O TABLA Y CONTINUANDO CON TODOS LOS DEMÁS (#2, #3, etc.) SIN OMITIR NINGUNO. '
            .'Revisa cada fila de la grilla de prescripción, encabezados de ítems y observaciones. '
            .'Responde ÚNICAMENTE con esta estructura JSON estricta y válida:'."\n\n".json_encode([
                'paciente' => [
                    'nombre_completo' => 'Nombre y apellidos completos del paciente tal como figuran en el documento',
                    'tipo_documento' => 'CC, TI, RC, etc.',
                    'numero_documento' => 'Número de identificación',
                    'eps' => 'Nombre de la EPS',
                    'ips' => 'Nombre de la IPS emisora',
                    'fecha_formula' => 'Fecha de la fórmula',
                    'edad_sexo' => 'Edad y sexo',
                    'episodio' => 'Número de episodio o historia',
                    'diagnostico_cie10' => 'Código y descripción del diagnóstico CIE-10',
                    'medico' => 'Nombre del médico tratante',
                    'registro_medico' => 'Registro médico o RM',
                ],
                'medicamentos' => [[
                    'item' => 1,
                    'codigo' => 'Código CUM o institucional',
                    'descripcion' => 'Nombre genérico/comercial y concentración exacta',
                    'forma_farmaceutica' => 'Tableta, Jeringa Prellena, Jarabe, Inyectable, etc.',
                    'dosis' => 'Dosis prescrita',
                    'frecuencia' => 'Frecuencia de toma/aplicación',
                    'duracion' => 'Duración del tratamiento',
                    'cantidad_solicitada' => 30,
                    'cantidad_dispensar' => 30,
                    'unidad_medida' => 'TAB, JPL, CAP, FCO, etc.',
                    'via' => 'Oral, Subcutánea, Intravenosa, etc.',
                    'observaciones' => 'Indicaciones específicas',
                ]],
                'observaciones_generales' => 'Observaciones legibles en la fórmula',
                'calidad_lectura' => 'ALTA',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Heurística de demo por nombre de archivo, igual que en producción: permite
     * probar el flujo completo sin API Key. Sube un archivo cuyo nombre contenga
     * "nueva_eps" o "savia" para ver ejemplos distintos.
     */
    private function extractorSimuladoInteligente(string $nombreArchivo): array
    {
        $nombre = strtolower($nombreArchivo);

        if (str_contains($nombre, 'nueva_eps')) {
            return ['status' => 'ok', 'data' => [
                'paciente' => [
                    'nombre_completo' => 'JUAN PABLO GÓMEZ RODRÍGUEZ',
                    'tipo_documento' => 'CC',
                    'numero_documento' => '88197902',
                    'eps' => 'NUEVA EMPRESA PROMOTORA DE SALUD S.A. (NUEVA EPS)',
                    'ips' => 'SEDE AYACUCHO / CENTRO DE ATENCIÓN INTEGRAL',
                    'fecha_formula' => now()->format('Y-m-d'),
                    'edad_sexo' => '45 años / M',
                    'episodio' => '290000006943',
                    'diagnostico_cie10' => 'M05 - ARTRITIS REUMATOIDE SEROPOSITIVA',
                    'medico' => 'DR. CARLOS ANDRÉS VALLEJO',
                    'registro_medico' => 'RM-44921',
                ],
                'medicamentos' => [[
                    'item' => 1,
                    'codigo' => 'MX997-2',
                    'descripcion' => 'METOTREXATO 2.5 MG TABLETAS',
                    'forma_farmaceutica' => 'TABLETA',
                    'dosis' => '2.5 mg',
                    'frecuencia' => 'Cada 1 semana (Dosis Semanal)',
                    'duracion' => '30 Días',
                    'cantidad_solicitada' => 10,
                    'cantidad_dispensar' => 10,
                    'unidad_medida' => 'TAB',
                    'via' => 'Oral',
                    'observaciones' => 'Tomar según pauta reumatológica con ácido fólico.',
                ]],
                'observaciones_generales' => 'Tratamiento especializado de reumatología.',
                'calidad_lectura' => 'ALTA (99.8% de precisión)',
            ]];
        }

        if (str_contains($nombre, 'savia')) {
            return ['status' => 'ok', 'data' => [
                'paciente' => [
                    'nombre_completo' => 'CARLOS MARIO RESTREPO JARAMILLO',
                    'tipo_documento' => 'CC',
                    'numero_documento' => '71625344',
                    'eps' => 'ALIANZA MEDELLIN ANTIOQUIA EPS SAS (SAVIA SALUD)',
                    'ips' => 'METROSALUD SAN JAVIER',
                    'fecha_formula' => now()->format('Y-m-d'),
                    'edad_sexo' => '58 años / M',
                    'episodio' => '00984712',
                    'diagnostico_cie10' => 'I10 - HIPERTENSIÓN ARTERIAL ESENCIAL (PRIMARIA)',
                    'medico' => 'DRA. ANA MARÍA MONTOYA',
                    'registro_medico' => 'RM-62341',
                ],
                'medicamentos' => [
                    [
                        'item' => 1,
                        'codigo' => 'MED-LOS50',
                        'descripcion' => 'LOSARTÁN POTÁSICO 50MG TABLETAS RECUBIERTAS',
                        'forma_farmaceutica' => 'TABLETA',
                        'dosis' => '50 mg',
                        'frecuencia' => 'Cada 12 Horas',
                        'duracion' => '30 Días',
                        'cantidad_solicitada' => 60,
                        'cantidad_dispensar' => 60,
                        'unidad_medida' => 'TAB',
                        'via' => 'Oral',
                        'observaciones' => 'Control de presión arterial cada 12 horas.',
                    ],
                    [
                        'item' => 2,
                        'codigo' => 'MED-AML5',
                        'descripcion' => 'AMLODIPINO 5MG TABLETAS',
                        'forma_farmaceutica' => 'TABLETA',
                        'dosis' => '5 mg',
                        'frecuencia' => 'Cada 24 Horas',
                        'duracion' => '30 Días',
                        'cantidad_solicitada' => 30,
                        'cantidad_dispensar' => 30,
                        'unidad_medida' => 'TAB',
                        'via' => 'Oral',
                        'observaciones' => 'Tomar por la mañana.',
                    ],
                ],
                'observaciones_generales' => 'Tratamiento antihipertensivo continuo.',
                'calidad_lectura' => 'ALTA (99.8% de precisión)',
            ]];
        }

        return ['status' => 'ok', 'data' => [
            'paciente' => [
                'nombre_completo' => 'MARTHA NELLY HERRERA HERRERA',
                'tipo_documento' => 'CC',
                'numero_documento' => '32433115',
                'eps' => 'ALIANZA MEDELLIN ANTIOQUIA EPS SAS (SAVIA SALUD)',
                'ips' => 'HOSPITAL GENERAL DE MEDELLÍN LUZ CASTRO DE GUTIÉRREZ E.S.E.',
                'fecha_formula' => now()->format('Y-m-d').' (14:21:17)',
                'edad_sexo' => '81 años / F',
                'episodio' => '0002475165',
                'diagnostico_cie10' => 'PROFILAXIS Y TRATAMIENTO TROMBOEMBÓLICO / ANTICOAGULACIÓN',
                'medico' => 'LOBO VILLADIEGO, JOSE FERNANDO',
                'registro_medico' => '50819',
            ],
            'medicamentos' => [[
                'item' => 1,
                'codigo' => 'MED-ENOX40',
                'descripcion' => 'ENOXAPARINA 40MG/0.4ML JERINGA PRELLENA - (SOLUCIÓN INYECTABLE)',
                'forma_farmaceutica' => 'JERINGA PRELLENA / INYECTABLE',
                'dosis' => '0,4 ML',
                'frecuencia' => 'Cada 24 Horas',
                'duracion' => '30 Días',
                'cantidad_solicitada' => 30,
                'cantidad_dispensar' => 30,
                'unidad_medida' => 'JPL (Jeringas Prellenadas)',
                'via' => 'SUBCUTANEA',
                'observaciones' => 'Cantidad en Letras: TREINTA (30 JPL). Aplicación subcutánea cada 24 horas.',
            ]],
            'observaciones_generales' => 'Fórmula Médica Oficial - Hospital General de Medellín E.S.E.',
            'calidad_lectura' => 'ALTA (99.8% de precisión)',
        ]];
    }
}
