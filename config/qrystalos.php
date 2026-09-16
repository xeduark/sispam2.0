<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente y credenciales
    |--------------------------------------------------------------------------
    | La URL de producción queda lista pero no se usa hasta que QRYSTALOS_ENV
    | se cambie explícitamente a "production".
    */

    'env' => env('QRYSTALOS_ENV', 'test'),

    'test_url' => env('QRYSTALOS_TEST_URL', 'https://api-test.qrystalos.com/api/'),
    'production_url' => env('QRYSTALOS_PROD_URL', 'https://cem.qrystalos.com/api/'),

    'usuario' => env('QRYSTALOS_USUARIO', 'SISPAM'),
    'auth_user' => env('QRYSTALOS_AUTH_USER'),
    'auth_pass' => env('QRYSTALOS_AUTH_PASS'),

    /*
    |--------------------------------------------------------------------------
    | Catálogos verificados contra database/example/LISTADO.xlsx (hoja "otros")
    | y confirmados con un POST real a api-test.qrystalos.com. Los valores
    | guardados en los campos correspondientes de `pacientes` pasan a ser
    | directamente estos códigos (no el texto libre que se usaba antes).
    |--------------------------------------------------------------------------
    */

    'estado_civil' => [
        'Soltero' => 'Soltero',
        'Casado' => 'Casado',
        'Divorciado' => 'Divorciado',
        'Separado' => 'Separado',
        'Viudo' => 'Viudo',
        'Union libre' => 'Union libre',
        'S/N' => 'S/N',
    ],

    'zona' => [
        'U' => 'Urbana',
        'R' => 'Rural',
    ],

    'grupo_poblacional' => [
        '1' => 'Indigente',
        '2' => 'Población Infantil a cargo del ICDF',
        '3' => 'Madres Comunitarias',
        '4' => 'Artistas, autores, compositores',
        '5' => 'Otro Grupo Poblacional',
        '6' => 'Recién nacido',
        '7' => 'Discapacitado',
        '8' => 'Desmovilizado',
        '9' => 'Desplazado',
        '10' => 'Población ROM',
        '11' => 'Población Raizal',
        '12' => 'Población en centro psiquiatro',
        '13' => 'Migratorios',
        '14' => 'Poblacion en centro carcelarios',
        '15' => 'Poblacion Rural no migratorias',
        '16' => 'Afrocolombiano',
        '17' => 'Adulto Mayor',
        '18' => 'Cabeza de familia',
        '20' => 'Mujer embarazada',
        '21' => 'Mujer lactante',
        '22' => 'Trabajador Urbano',
        '23' => 'Trabajador Rural',
        '24' => 'Victima de violencia armada',
        '25' => 'Jovenes vulnerable rurales',
        '26' => 'Jovenes vulnerable Urbano',
        '27' => 'Discapacitado - El sistema Nervioso',
        '28' => 'Discapacitado - los ojos',
        '29' => 'Discapacitado - otros',
        '30' => 'Víctimas del conflicto armado',
        '31' => 'Población LGBT',
        '32' => 'Población pediátrica (0-13)',
        '33' => 'Población pediátrica (14-17)',
    ],

    'grupo_etnico' => [
        'A' => 'Afrocolombiano',
        'I' => 'Indigena',
        'G' => 'Gitano',
        'R' => 'Raizal',
        'P' => 'Palenquero',
        'N' => 'No Aplica',
    ],

    'tipo_discapacidad' => [
        'A' => 'Auditiva',
        'C' => 'SordoCeguera',
        'F' => 'Física',
        'M' => 'Mental',
        'N' => 'No Aplica',
        'S' => 'Psicológica',
        'V' => 'Visual',
    ],

    'escolaridad' => [
        '01' => 'PREESCOLAR',
        '02' => 'BASICA PRIMARIA',
        '03' => 'BASICA SECUNDARIA',
        '04' => 'MEDICA ACAMEDIA CLASICA',
        '05' => 'MEDIA TECNICA (BACHILLERATO TECNICO)',
        '06' => 'NORMALISTA',
        '07' => 'TECNICA PROFESIONAL',
        '08' => 'TECNOLOGICA',
        '09' => 'PROFESIONAL',
        '10' => 'ESPECIALIZACION',
        '11' => 'MAESTRIA',
        '12' => 'DOCTORADO',
        '13' => 'NINGUNO',
    ],

    'sexo' => [
        'Masculino' => 'Masculino',
        'Femenino' => 'Femenino',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogos NO confirmados por Qrystalos todavía (ver plan / TODOs en
    | QrystalosService). Mapeo best-effort mientras llega el catálogo real.
    |--------------------------------------------------------------------------
    */

    // Solo confirmado para régimen contributivo.
    'tipo_usuario_por_afiliado' => [
        'Contributivo Cotizante' => '01',
        'Contributivo Beneficiario' => '02',
        'Contributivo Adicional' => '03',
        // TODO Qrystalos: código real para Subsidiado / Vinculado / Particular / Especial.
        'Subsidiado' => '01',
        'Vinculado' => '01',
        'Particular' => '01',
        'Especial / Excepción' => '01',
    ],

];
