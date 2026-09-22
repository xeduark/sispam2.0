<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Servicio de Sincronización de Pacientes con API Externa (Contrato JSON / Basic Auth / SISPAM INSERTAR)
 */
class ExternalPatientSyncService {
    private $db;
    private static $instance = null;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la configuración activa del API
     */
    public function getConfig() {
        $stmt = $this->db->query("SELECT * FROM config_api_externo ORDER BY id ASC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$config) {
            return [
                'id' => 1,
                'api_url' => '',
                'api_user' => '',
                'api_password' => '',
                'api_body_user' => '',
                'is_active' => 0,
                'timeout_seconds' => 4,
                'default_ciudad' => '05001',
                'default_zona' => 'U',
                'default_barrio' => '05001001',
                'default_administradora' => '0100000011',
                'default_plan' => 'CSRS26',
                'default_sede' => '01',
                'default_grupo_pob' => '5',
                'default_grupo_etnico' => 'N',
                'default_discapacidad' => 'N',
                'default_escolaridad' => 'NA'
            ];
        }
        return $config;
    }

    /**
     * Guarda la configuración del API
     */
    public function saveConfig($data) {
        $sql = "UPDATE config_api_externo SET 
                    api_url = :api_url,
                    api_user = :api_user,
                    api_password = :api_password,
                    api_body_user = :api_body_user,
                    is_active = :is_active,
                    timeout_seconds = :timeout_seconds,
                    default_ciudad = :default_ciudad,
                    default_zona = :default_zona,
                    default_barrio = :default_barrio,
                    default_administradora = :default_administradora,
                    default_plan = :default_plan,
                    default_sede = :default_sede,
                    default_grupo_pob = :default_grupo_pob,
                    default_grupo_etnico = :default_grupo_etnico,
                    default_discapacidad = :default_discapacidad,
                    default_escolaridad = :default_escolaridad
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':api_url'               => trim($data['api_url'] ?? ''),
            ':api_user'              => trim($data['api_user'] ?? ''),
            ':api_password'          => trim($data['api_password'] ?? ''),
            ':api_body_user'         => trim($data['api_body_user'] ?? ''),
            ':is_active'             => !empty($data['is_active']) ? 1 : 0,
            ':timeout_seconds'       => max(1, intval($data['timeout_seconds'] ?? 4)),
            ':default_ciudad'        => trim($data['default_ciudad'] ?? '05001'),
            ':default_zona'          => trim($data['default_zona'] ?? 'U'),
            ':default_barrio'        => trim($data['default_barrio'] ?? '05001001'),
            ':default_administradora'=> trim($data['default_administradora'] ?? '0100000011'),
            ':default_plan'          => trim($data['default_plan'] ?? 'CSRS26'),
            ':default_sede'          => trim($data['default_sede'] ?? '01'),
            ':default_grupo_pob'     => trim($data['default_grupo_pob'] ?? '5'),
            ':default_grupo_etnico'  => trim($data['default_grupo_etnico'] ?? 'N'),
            ':default_discapacidad'  => trim($data['default_discapacidad'] ?? 'N'),
            ':default_escolaridad'   => trim($data['default_escolaridad'] ?? 'NA'),
            ':id'                    => 1
        ]);
    }

    /**
     * Mapea un array de datos de paciente de SISPAM al payload del API externo
     */
    public function mapPacienteToPayload($p, $config) {
        // 1. Tipo de documento
        $tipoDoc = strtoupper(trim(explode('-', $p['tipo_documento'] ?? 'CC')[0]));
        if (empty($tipoDoc)) $tipoDoc = 'CC';

        // 2. Número de documento
        $numDoc = preg_replace('/[^a-zA-Z0-9]/', '', trim($p['numero_documento'] ?? ''));

        // 3. Nombres y apellidos
        $pNombre = strtoupper(trim($p['primer_nombre'] ?? ''));
        $sNombre = strtoupper(trim($p['segundo_nombre'] ?? ''));
        $pApellido = strtoupper(trim($p['primer_apellido'] ?? ''));
        $sApellido = strtoupper(trim($p['segundo_apellido'] ?? ''));

        if (empty($pNombre) && !empty($p['nombres'])) {
            $partsN = explode(' ', trim($p['nombres']));
            $pNombre = strtoupper(array_shift($partsN));
            $sNombre = strtoupper(implode(' ', $partsN));
        }
        if (empty($pApellido) && !empty($p['apellidos'])) {
            $partsA = explode(' ', trim($p['apellidos']));
            $pApellido = strtoupper(array_shift($partsA));
            $sApellido = strtoupper(implode(' ', $partsA));
        }

        if (strlen($pNombre) < 2) $pNombre = str_pad($pNombre, 2, 'A');
        if (strlen($pApellido) < 2) $pApellido = str_pad($pApellido, 2, 'A');

        // 4. Fecha de nacimiento
        $fNac = !empty($p['fecha_nacimiento']) ? date('Y-m-d', strtotime($p['fecha_nacimiento'])) : '1990-01-01';

        // 5. Sexo
        $sexoRaw = trim($p['sexo'] ?? '');
        if (stripos($sexoRaw, 'Fem') !== false || $sexoRaw === 'F') {
            $sexo = 'Femenino';
        } elseif (stripos($sexoRaw, 'Ind') !== false || $sexoRaw === 'I') {
            $sexo = 'Indeterminado';
        } else {
            $sexo = 'Masculino';
        }

        // 6. Estado civil
        $estCivil = trim($p['estado_civil'] ?? 'Soltero');
        if (empty($estCivil)) $estCivil = 'Soltero';

        // 7. Contacto
        $celular = preg_replace('/[^0-9]/', '', trim($p['numero_celular'] ?? ($p['telefono'] ?? '')));
        if (strlen($celular) < 7) $celular = '3000000000';

        $email = trim($p['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'paciente.' . $numDoc . '@sispam.salud.com';
        }

        $direccion = trim($p['direccion_residencia'] ?? ($p['direccion'] ?? 'CALLE 1 # 1-1'));
        if (strlen($direccion) < 3) $direccion = 'CALLE 1 # 1-1';

        // 8. Ciudad / Zona / Barrio
        $ciudad = trim($p['ciudad_residencia'] ?? ($config['default_ciudad'] ?? '05001'));
        $ciudad = preg_replace('/[^0-9]/', '', explode('-', $ciudad)[0]);
        if (strlen($ciudad) < 5) $ciudad = str_pad($ciudad, 5, '0', STR_PAD_LEFT);
        if (empty($ciudad)) $ciudad = '05001';

        $zonaRaw = strtoupper(trim($p['zona'] ?? ($config['default_zona'] ?? 'U')));
        $zona = (substr($zonaRaw, 0, 1) === 'R') ? 'R' : 'U';

        $barrio = trim($p['barrio_residencia'] ?? ($config['default_barrio'] ?? '05001001'));
        if (empty($barrio)) $barrio = $ciudad . '001';

        // 9. Aseguradora, Plan y Afiliación Dinámica (Contributivo vs Subsidiado)
        $administradora = trim($p['eps_id'] ?? '');
        if (empty($administradora) || !preg_match('/^[0-9]{5,20}$/', $administradora)) {
            $administradora = trim($config['default_administradora'] ?? '0100000011');
        }

        // TIPOUSUARIO y PLAN según el Régimen / Tipo de Afiliado
        $tipoAfRaw = trim($p['tipo_afiliado'] ?? ($p['regimen'] ?? '04'));
        $esCotizante = ($tipoAfRaw === '01' || stripos($tipoAfRaw, 'Cotiz') !== false);
        $esBeneficiario = ($tipoAfRaw === '02' || stripos($tipoAfRaw, 'Benef') !== false);
        $esAdicional = ($tipoAfRaw === '03' || stripos($tipoAfRaw, 'Adic') !== false);
        $esSubsidiado = ($tipoAfRaw === '04' || stripos($tipoAfRaw, 'Subsid') !== false);

        if ($esCotizante) {
            $tipoUsuario = '01'; // 01 - Cotizante Contributivo
            $planDefault = 'CSRC26';
        } elseif ($esBeneficiario) {
            $tipoUsuario = '02'; // 02 - Beneficiario Contributivo
            $planDefault = 'CSRC26';
        } elseif ($esAdicional) {
            $tipoUsuario = '03'; // 03 - Adicional Contributivo
            $planDefault = 'CSRC26';
        } else {
            $tipoUsuario = '04'; // 04 - Subsidiado
            $planDefault = 'CSRS26';
        }

        // Si el paciente trae un código de plan válido explícito (alfanumérico <= 6 caracteres y no descriptivo), usarlo
        $plan = trim($p['plan_salud'] ?? '');
        if (empty($plan) || strlen($plan) > 6 || stripos($plan, 'Plan') !== false || stripos($plan, 'Básico') !== false) {
            $plan = $planDefault;
        }

        $nivel = trim($p['categoria'] ?? ($p['estrato'] ?? ($p['nivel_socioeconomico'] ?? ($config['default_nivel'] ?? '1'))));
        if (strlen($nivel) > 2) $nivel = substr($nivel, 0, 2);
        if (empty($nivel)) $nivel = '1';

        $sede = trim($p['sede_id'] ?? ($p['sede_atencion'] ?? ($config['default_sede'] ?? '01')));
        if (empty($sede) || !preg_match('/^[0-9]{1,10}$/', $sede)) $sede = strval($config['default_sede'] ?? '01');

        $estado = 'Activo';

        // 10. Contacto de emergencia (Valores obligatorios para SPQ_SISPAM en Qrystalos)
        $contactoEmergencia = trim($p['contacto_emergencia_nombre'] ?? 'RESPONSABLE FAMILIAR');
        if (empty($contactoEmergencia)) $contactoEmergencia = 'RESPONSABLE FAMILIAR';
        $contactoEmergencia = mb_substr($contactoEmergencia, 0, 100, 'UTF-8');

        $telefonoEmergencia = preg_replace('/[^0-9]/', '', trim($p['contacto_emergencia_telefono'] ?? ($p['numero_celular'] ?? '3000000000')));
        if (strlen($telefonoEmergencia) < 7) $telefonoEmergencia = $celular;
        $telefonoEmergencia = mb_substr($telefonoEmergencia, 0, 50, 'UTF-8');

        $parentescoEmergencia = trim($p['contacto_emergencia_parentesco'] ?? 'Madre');
        if (empty($parentescoEmergencia)) $parentescoEmergencia = 'Madre';
        // Qrystalos AFI.URG_VINCULO tiene límite de 20 caracteres en base de datos
        if (stripos($parentescoEmergencia, 'Papá') !== false || stripos($parentescoEmergencia, 'Mamá') !== false) {
            $parentescoEmergencia = 'Madre';
        } else {
            $parentescoEmergencia = mb_substr($parentescoEmergencia, 0, 20, 'UTF-8');
        }

        $params = [
            'TIPO_DOC'                       => $tipoDoc,
            'DOCIDAFILIADO'                  => $numDoc,
            'FNACIMIENTO'                    => $fNac,
            'PAPELLIDO'                      => $pApellido,
            'PNOMBRE'                        => $pNombre,
            'SEXO'                           => $sexo,
            'ESTADO_CIVIL'                   => $estCivil,
            'GRUPOPOB'                       => strval($p['grupo_poblacional'] ?? ($config['default_grupo_pob'] ?? '5')),
            'GRUPOETNICO'                    => strval($p['grupo_etnico'] ?? ($config['default_grupo_etnico'] ?? 'N')),
            'TIPODISCAPACIDAD'               => strval($p['tipo_discapacidad'] ?? ($config['default_discapacidad'] ?? 'N')),
            'IDESCOLARIDAD'                  => strval($p['tipo_escolaridad'] ?? ($config['default_escolaridad'] ?? 'NA')),
            'DIRECCION'                      => mb_substr($direccion, 0, 150, 'UTF-8'),
            'CELULAR'                        => $celular,
            'EMAIL'                          => mb_substr($email, 0, 99, 'UTF-8'),
            'CIUDAD'                         => $ciudad,
            'ZONA'                           => $zona,
            'IDBARRIO'                       => $barrio,
            'IDADMINISTRADORA'                => $administradora,
            'IDPLAN'                         => $plan,
            'NIVELSOCIOEC'                   => $nivel,
            'TIPOUSUARIO'                    => $tipoUsuario,
            'IDSEDE'                         => $sede,
            'ESTADO'                         => $estado,
            'SAPELLIDO'                      => $sApellido,
            'SNOMBRE'                        => $sNombre,
            'PREFIJO_CELULAR'                => '+57',
            'TELEFONORES'                    => '',
            'PREFIJO_TELEFONORES'            => '+57',
            'PROCEDENCIA'                    => 'SISPAM',
            'URG_NOMBRE'                     => $contactoEmergencia,
            'URG_TELE'                       => $telefonoEmergencia,
            'URG_VINCULO'                    => $parentescoEmergencia
        ];

        // Solo agregar FECHAAFILIACION si viene definida
        if (!empty($p['fecha_afiliacion'])) {
            $params['FECHAAFILIACION'] = date('Y-m-d', strtotime($p['fecha_afiliacion']));
        }

        return [
            'MODELO'     => 'SISPAM',
            'METODO'     => 'INSERTAR',
            'USUARIO'    => !empty($config['api_body_user']) ? $config['api_body_user'] : 'SISPAM_API',
            'PARAMETROS' => $params
        ];
    }

    /**
     * Sincroniza un paciente específico por su ID o array de datos
     */
    public function syncPaciente($pacienteIdOrData, $forceManual = false) {
        $config = $this->getConfig();

        // Si la integración no está activa y no es forzada manualmente, salir
        if (!$config['is_active'] && !$forceManual) {
            return [
                'status'  => 'skipped',
                'mensaje' => 'La integración con API externa está inactiva en la configuración.'
            ];
        }

        if (empty($config['api_url'])) {
            return [
                'status'  => 'error',
                'mensaje' => 'La URL del API externa no está configurada.'
            ];
        }

        // Normalizar URL del endpoint para que siempre apunte a /api/json/
        $apiUrl = trim($config['api_url']);
        if (substr(rtrim($apiUrl, '/'), -4) !== 'json' && substr(rtrim($apiUrl, '/'), -8) !== 'api/json') {
            $apiUrl = rtrim($apiUrl, '/') . '/json/';
        }

        // Obtener datos del paciente
        $paciente = null;
        if (is_numeric($pacienteIdOrData)) {
            $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE id = :id");
            $stmt->execute([':id' => $pacienteIdOrData]);
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (is_array($pacienteIdOrData)) {
            $paciente = $pacienteIdOrData;
        }

        if (!$paciente) {
            return [
                'status'  => 'error',
                'mensaje' => 'No se encontraron datos del paciente para sincronizar.'
            ];
        }

        $pacienteId = $paciente['id'] ?? null;
        $numDoc = $paciente['numero_documento'] ?? '';
        $tipoDoc = $paciente['tipo_documento'] ?? '';

        // Construir Payload
        $payload = $this->mapPacienteToPayload($paciente, $config);
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Armar cabecera Basic Auth
        $authString = trim($config['api_user']) . ':' . trim($config['api_password']);
        $authBase64 = base64_encode($authString);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $authBase64
        ];

        // Medir tiempo de ejecución
        $startMs = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, intval($config['timeout_seconds'] ?? 4));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, intval($config['timeout_seconds'] ?? 4));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $duracionMs = round((microtime(true) - $startMs) * 1000);

        // Manejo de Error cURL
        if ($response === false || !empty($curlErr)) {
            $msgError = "Error cURL (" . ($curlErr ?: 'Timeout/No response') . ")";
            $this->logSync($pacienteId, $numDoc, $tipoDoc, 'INSERTAR', $jsonPayload, $httpCode ?: 0, $response ?: '', 'ERROR_CONEXION', $msgError, $duracionMs);
            $this->updatePacienteSyncStatus($pacienteId, 'ERROR', null, $msgError);
            return [
                'status'    => 'error',
                'http_code' => $httpCode,
                'mensaje'   => $msgError
            ];
        }

        // Parsear respuesta JSON
        $dataResp = json_decode($response, true);
        
        // Verificar estructura de negocio
        $isOk = false;
        $consecutivo = null;
        $accion = '';
        $mensajeFinal = '';

        if ($httpCode === 200 && is_array($dataResp)) {
            $recordsets = $dataResp['result']['recordsets'] ?? [];
            if (!empty($recordsets) && isset($recordsets[0][0])) {
                $primeraFila = $recordsets[0][0];
                if (isset($primeraFila['OK']) && $primeraFila['OK'] === 'OK') {
                    $isOk = true;
                    $consecutivo = $primeraFila['CONSECUTIVO'] ?? null;
                    $accion = $primeraFila['ACCION'] ?? 'OK';
                    $mensajeFinal = $primeraFila['MENSAJE'] ?? "Paciente sincronizado con éxito ({$accion})";
                } elseif (isset($primeraFila['OK']) && $primeraFila['OK'] === 'KO') {
                    // Extraer mensajes de error
                    $errores = [];
                    if (isset($recordsets[1]) && is_array($recordsets[1])) {
                        foreach ($recordsets[1] as $errRow) {
                            if (!empty($errRow['ERROR'])) $errores[] = $errRow['ERROR'];
                        }
                    }
                    $mensajeFinal = !empty($errores) ? implode('; ', $errores) : 'Error de validación en servidor externo.';
                }
            }
        }

        if ($isOk) {
            $this->logSync($pacienteId, $numDoc, $tipoDoc, 'INSERTAR', $jsonPayload, $httpCode, $response, 'OK', $mensajeFinal, $duracionMs);
            $this->updatePacienteSyncStatus($pacienteId, 'SINCRONIZADO', $consecutivo, "{$accion}: {$mensajeFinal}");
            return [
                'status'      => 'success',
                'consecutivo' => $consecutivo,
                'accion'      => $accion,
                'mensaje'     => $mensajeFinal,
                'http_code'   => $httpCode,
                'duracion_ms' => $duracionMs
            ];
        } else {
            if (empty($mensajeFinal)) {
                $mensajeFinal = "Error HTTP {$httpCode}: " . mb_substr($response, 0, 200);
            }
            $this->logSync($pacienteId, $numDoc, $tipoDoc, 'INSERTAR', $jsonPayload, $httpCode, $response, 'KO', $mensajeFinal, $duracionMs);
            $this->updatePacienteSyncStatus($pacienteId, 'ERROR', null, $mensajeFinal);
            return [
                'status'      => 'error',
                'mensaje'     => $mensajeFinal,
                'http_code'   => $httpCode,
                'response'    => $dataResp ?: $response,
                'duracion_ms' => $duracionMs
            ];
        }
    }

    /**
     * Prueba la conectividad con el servidor externo
     */
    public function testConnection() {
        $config = $this->getConfig();
        if (empty($config['api_url'])) {
            return ['status' => 'error', 'mensaje' => 'La URL del API no está configurada.'];
        }
        if (empty($config['api_user']) || empty($config['api_password'])) {
            return ['status' => 'error', 'mensaje' => 'Las credenciales Basic Auth están incompletas.'];
        }

        // Payload de prueba controlado
        $payload = [
            'MODELO'  => 'SISPAM',
            'METODO'  => 'INSERTAR',
            'USUARIO' => !empty($config['api_body_user']) ? $config['api_body_user'] : 'TEST_CONEXION',
            'PARAMETROS' => [
                'TIPO_DOC'            => 'CC',
                'DOCIDAFILIADO'       => '9999999999',
                'FNACIMIENTO'         => '1990-01-01',
                'PAPELLIDO'           => 'PRUEBA_SISPAM',
                'PNOMBRE'             => 'TEST_CONEXION',
                'SEXO'                => 'Masculino',
                'ESTADO_CIVIL'        => 'Soltero',
                'GRUPOPOB'            => strval($config['default_grupo_pob'] ?? '5'),
                'GRUPOETNICO'         => strval($config['default_grupo_etnico'] ?? 'N'),
                'TIPODISCAPACIDAD'    => strval($config['default_discapacidad'] ?? 'N'),
                'IDESCOLARIDAD'       => strval($config['default_escolaridad'] ?? 'NA'),
                'DIRECCION'           => 'CALLE 1 # 1-1',
                'CELULAR'             => '3000000000',
                'EMAIL'               => 'test.sispam@test.com',
                'CIUDAD'              => strval($config['default_ciudad'] ?? '05001'),
                'ZONA'                => strval($config['default_zona'] ?? 'U'),
                'IDBARRIO'            => strval($config['default_barrio'] ?? '05001001'),
                'IDADMINISTRADORA'     => strval($config['default_administradora'] ?? '0100000011'),
                'IDPLAN'              => strval($config['default_plan'] ?? 'CSRS26'),
                'NIVELSOCIOEC'        => '1',
                'TIPOUSUARIO'         => '01',
                'IDSEDE'              => strval($config['default_sede'] ?? '01'),
                'ESTADO'              => 'Activo',
                'PROCEDENCIA'         => 'SISPAM',
                'URG_NOMBRE'          => 'TEST RESPONSABLE',
                'URG_TELE'            => '3000000000',
                'URG_VINCULO'         => 'Madre'
            ]
        ];

        // Normalizar URL del endpoint para que siempre apunte a /api/json/
        $apiUrl = trim($config['api_url']);
        if (substr(rtrim($apiUrl, '/'), -4) !== 'json' && substr(rtrim($apiUrl, '/'), -8) !== 'api/json') {
            $apiUrl = rtrim($apiUrl, '/') . '/json/';
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $authUser = trim($config['api_user']);
        $authPass = trim($config['api_password']);
        $authBase64 = base64_encode($authUser . ':' . $authPass);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $authBase64
        ];

        $startMs = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $duracionMs = round((microtime(true) - $startMs) * 1000);

        if ($response === false || !empty($curlErr)) {
            $msg = "Fallo de conexión cURL: " . ($curlErr ?: 'Timeout sin respuesta');
            $this->logSync(null, '9999999999', 'CC', 'TEST_CONEXION', $jsonPayload, $httpCode, '', 'ERROR_CONEXION', $msg, $duracionMs);
            return ['status' => 'error', 'http_code' => $httpCode, 'mensaje' => $msg];
        }

        $dataResp = json_decode($response, true);
        if ($httpCode === 200) {
            $this->logSync(null, '9999999999', 'CC', 'TEST_CONEXION', $jsonPayload, $httpCode, $response, 'OK', 'Prueba de conexión HTTP 200 OK', $duracionMs);
            return [
                'status'      => 'success',
                'http_code'   => $httpCode,
                'duracion_ms' => $duracionMs,
                'mensaje'     => '¡Conexión y autenticación con el servidor externo exitosas!',
                'response'    => $dataResp ?: $response
            ];
        } elseif ($httpCode === 401) {
            return ['status' => 'error', 'http_code' => 401, 'mensaje' => 'Error 401: Usuario o contraseña Basic Auth incorrectos.'];
        } elseif ($httpCode === 403) {
            return ['status' => 'error', 'http_code' => 403, 'mensaje' => 'Error 403: El usuario autenticado no tiene permisos para el modelo SISPAM.'];
        } else {
            return ['status' => 'error', 'http_code' => $httpCode, 'mensaje' => "El servidor respondió con código HTTP {$httpCode}.", 'response' => $dataResp ?: $response];
        }
    }

    /**
     * Actualiza el estado de sincronización en la tabla pacientes
     */
    private function updatePacienteSyncStatus($pacienteId, $estado, $idAfiliadoExterno = null, $mensaje = null) {
        if (!$pacienteId) return;
        try {
            $sql = "UPDATE pacientes SET 
                        estado_sync_externo = :estado,
                        fecha_sync_externo = NOW(),
                        ultimo_mensaje_sync = :mensaje";
            $params = [
                ':estado'  => $estado,
                ':mensaje' => $mensaje,
                ':id'      => $pacienteId
            ];

            if ($idAfiliadoExterno !== null) {
                $sql .= ", id_afiliado_externo = :afiliado";
                $params[':afiliado'] = $idAfiliadoExterno;
            }

            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (Exception $e) {
            // Silenciar error en actualización de estado
        }
    }

    /**
     * Registra un log en la bitácora api_sync_logs
     */
    private function logSync($pacienteId, $doc, $tipoDoc, $metodo, $reqPayload, $httpCode, $respPayload, $estado, $mensaje, $duracionMs) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO api_sync_logs 
                    (paciente_id, numero_documento, tipo_documento, metodo, request_payload, http_code, response_payload, estado, mensaje, duracion_ms)
                VALUES 
                    (:p_id, :doc, :tdoc, :metodo, :req, :code, :resp, :estado, :msg, :dur)
            ");
            $stmt->execute([
                ':p_id'   => $pacienteId,
                ':doc'    => $doc,
                ':tdoc'   => $tipoDoc,
                ':metodo' => $metodo,
                ':req'    => $reqPayload,
                ':code'   => $httpCode,
                ':resp'   => is_string($respPayload) ? $respPayload : json_encode($respPayload),
                ':estado' => $estado,
                ':msg'    => $mensaje,
                ':dur'    => $duracionMs
            ]);
        } catch (Exception $e) {
            // Silenciar para no interrumpir el flujo
        }
    }

    /**
     * Cuenta cuántos pacientes aplican para la sincronización según el modo
     */
    public function countPacientesParaSync($modo = 'pendientes', $filtros = []) {
        $sql = "SELECT COUNT(id) FROM pacientes WHERE 1=1";
        $params = [];

        if ($modo === 'pendientes') {
            $sql .= " AND (estado_sync_externo != 'SINCRONIZADO' OR estado_sync_externo IS NULL)";
        }

        if (!empty($filtros['eps'])) {
            $sql .= " AND eps_nombre = :eps";
            $params[':eps'] = $filtros['eps'];
        }

        if (!empty($filtros['sede_id'])) {
            $sql .= " AND sede_atencion = :sede";
            $params[':sede'] = $filtros['sede_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?: 0);
    }

    /**
     * Sincroniza un lote (batch) de pacientes de forma progresiva
     */
    public function syncBatchPacientes($modo = 'pendientes', $ultimoId = 0, $limit = 20, $filtros = []) {
        $sql = "SELECT * FROM pacientes WHERE id > :ultimo_id";
        $params = [':ultimo_id' => intval($ultimoId)];

        if ($modo === 'pendientes') {
            $sql .= " AND (estado_sync_externo != 'SINCRONIZADO' OR estado_sync_externo IS NULL)";
        }

        if (!empty($filtros['eps'])) {
            $sql .= " AND eps_nombre = :eps";
            $params[':eps'] = $filtros['eps'];
        }

        if (!empty($filtros['sede_id'])) {
            $sql .= " AND sede_atencion = :sede";
            $params[':sede'] = $filtros['sede_id'];
        }

        $limit = max(1, min(100, intval($limit)));
        $sql .= " ORDER BY id ASC LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $procesados = count($pacientes);
        $exitosos = 0;
        $errores = 0;
        $nuevoUltimoId = intval($ultimoId);
        $detalles = [];

        foreach ($pacientes as $p) {
            $nuevoUltimoId = max($nuevoUltimoId, intval($p['id']));
            $res = $this->syncPaciente($p, true);

            $isSuccess = ($res['status'] ?? '') === 'success';
            if ($isSuccess) {
                $exitosos++;
            } else {
                $errores++;
            }

            $detalles[] = [
                'id'         => $p['id'],
                'documento'  => ($p['tipo_documento'] ?? 'CC') . ' ' . ($p['numero_documento'] ?? ''),
                'nombre'     => trim(($p['primer_nombre'] ?? '') . ' ' . ($p['primer_apellido'] ?? '')),
                'status'     => $res['status'] ?? 'error',
                'mensaje'    => $res['mensaje'] ?? 'Sin respuesta',
                'tiempo_ms'  => $res['duracion_ms'] ?? 0
            ];
        }

        return [
            'status'        => 'success',
            'modo'          => $modo,
            'procesados'    => $procesados,
            'exitosos'      => $exitosos,
            'errores'       => $errores,
            'ultimo_id'     => $nuevoUltimoId,
            'completado'    => ($procesados < $limit),
            'detalles'      => $detalles
        ];
    }

    /**
     * Obtiene los últimos logs de la bitácora
     */
    public function getRecentLogs($limit = 30) {
        $stmt = $this->db->prepare("SELECT * FROM api_sync_logs ORDER BY id DESC LIMIT :lim");
        $stmt->bindValue(':lim', intval($limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

