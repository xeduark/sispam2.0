<?php
/**
 * Servicio de Auto-Procesador Desatendido de Escáner RICOH (Zero-Click Watcher)
 * SISPAM - Digitalización Autónoma & Extracción IA Multimodal
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/TratamientoCronico.php';
require_once __DIR__ . '/../services/AIExtractorService.php';

class RicohAutoWatcherService {

    private $db;
    private $hotFolderDir;
    private $procesadosBaseDir;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->hotFolderDir = BASE_DIR . '/uploads/repositorio_escaneo/hot_folder';
        $this->procesadosBaseDir = BASE_DIR . '/uploads/repositorio_escaneo/procesados';

        if (!is_dir($this->hotFolderDir)) {
            @mkdir($this->hotFolderDir, 0777, true);
        }
        if (!is_dir($this->procesadosBaseDir)) {
            @mkdir($this->procesadosBaseDir, 0777, true);
        }
    }

    /**
     * Escanea la bandeja caliente, procesa cada archivo con IA y asocia al paciente/tiquete
     */
    public function procesarBandeja() {
        @set_time_limit(180);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $lockFile = sys_get_temp_dir() . '/sispam_ricoh_watcher.lock';
        $fpLock = @fopen($lockFile, 'c+');
        if ($fpLock) {
            if (!flock($fpLock, LOCK_EX | LOCK_NB)) {
                fclose($fpLock);
                $fpLock = null;
                return ['status' => 'busy', 'message' => 'Procesamiento IA en curso en segundo plano.'];
            }
        }

        $procesados = [];
        $errores = [];

        try {
            if (!is_dir($this->hotFolderDir)) {
                return ['status' => 'ok', 'total_procesados' => 0, 'items' => []];
            }

            $items = scandir($this->hotFolderDir);
            $archivosValidos = [];

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullPath = $this->hotFolderDir . '/' . $item;
                if (is_file($fullPath)) {
                    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                    if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
                        $size1 = filesize($fullPath);
                        usleep(30000); // 30ms
                        clearstatcache(true, $fullPath);
                        $size2 = filesize($fullPath);
                        if ($size1 === $size2 && $size1 > 1024) {
                            $archivosValidos[] = [
                                'filename' => $item,
                                'path'     => $fullPath,
                                'size'     => $size1,
                                'ext'      => $ext
                            ];
                        }
                    }
                }
            }

            if (empty($archivosValidos)) {
                return ['status' => 'ok', 'total_procesados' => 0, 'items' => []];
            }

            // Procesar 1 archivo por ciclo para máxima fluidez y evitar esperas prolongadas
            $loteAProcesar = array_slice($archivosValidos, 0, 1);

            $extractor = new AIExtractorService();
            $pacienteModel = new Paciente();
            $ingresoModel = new Ingreso();
            $cronicoModel = new TratamientoCronico();

            foreach ($loteAProcesar as $archivo) {
            $filename = $archivo['filename'];
            $fullPath = $archivo['path'];
            $ext = $archivo['ext'];

            try {
                // 1. Extraer con IA en el Servidor
                $extRes = $extractor->extraerDatosFormula($fullPath, null, $filename, false, 0);

                if (!$extRes || !isset($extRes['status']) || $extRes['status'] !== 'ok' || empty($extRes['data']['medicamentos'])) {
                    $errores[] = [
                        'archivo' => $filename,
                        'error'   => 'La IA no pudo extraer medicamentos legibles del archivo.'
                    ];
                    continue;
                }

                $datos = $extRes['data'];
                $tiempoIA = $extRes['tiempo_segundos'] ?? 1.2;
                $motorIA = $extRes['motor'] ?? 'Google Gemini Vision';

                // 2. Identificar Documento y Datos del Paciente
                // Prioridad A: Desde el nombre de archivo si contiene cédula (ej: 1037584920.pdf o 1037584920_formula.pdf)
                $docFromFilename = '';
                if (preg_match('/(?:^|[^0-9])([0-9]{6,12})(?:[^0-9]|$)/', $filename, $mDoc)) {
                    $docFromFilename = $mDoc[1];
                }

                $numDoc = $docFromFilename ?: trim($datos['paciente']['numero_documento'] ?? '');
                $tipoDoc = trim($datos['paciente']['tipo_documento'] ?? 'CC');
                $nombreCompleto = trim($datos['paciente']['nombre_completo'] ?? 'PACIENTE SIN IDENTIFICAR');
                $epsNombre = trim($datos['paciente']['eps'] ?? 'SAVIA SALUD EPS');
                $ipsRemite = trim($datos['paciente']['ips'] ?? 'HOSPITAL / IPS NO ESPECIFICADA');

                if (empty($numDoc)) {
                    $numDoc = 'DOC_' . date('ymdHis');
                }

                // Separar nombres y apellidos aproximados
                $partesNombre = explode(' ', preg_replace('/\s+/', ' ', $nombreCompleto));
                $primerNombre = $partesNombre[0] ?? 'PACIENTE';
                $segundoNombre = (count($partesNombre) >= 3) ? $partesNombre[1] : '';
                $primerApellido = (count($partesNombre) >= 2) ? $partesNombre[count($partesNombre) - 2] : 'NO_REGISTRA';
                $segundoApellido = (count($partesNombre) >= 4) ? $partesNombre[count($partesNombre) - 1] : '';

                // 3. Crear o Actualizar Paciente en Base de Datos
                $datosPac = [
                    'tipo_documento'  => $tipoDoc,
                    'numero_documento'=> $numDoc,
                    'primer_nombre'   => $primerNombre,
                    'segundo_nombre'  => $segundoNombre,
                    'primer_apellido' => $primerApellido,
                    'segundo_apellido'=> $segundoApellido,
                    'nombres'         => trim("{$primerNombre} {$segundoNombre}"),
                    'apellidos'       => trim("{$primerApellido} {$segundoApellido}"),
                    'eps_nombre'      => $epsNombre,
                    'estado'          => '1'
                ];
                $paciente_id = $pacienteModel->createOrUpdate($datosPac);

                // 4. Buscar si el paciente ya tiene un ingreso abierto hoy sin fórmula adjunta
                $stmtBuscaIng = $this->db->prepare("
                    SELECT id, ticket_numero 
                    FROM ingresos 
                    WHERE paciente_id = :pid 
                      AND DATE(fecha_ingreso) = CURDATE()
                      AND estado_tramite IN ('INGRESADO', 'TRANSCRIPCION', 'TRIAGE')
                    ORDER BY id DESC LIMIT 1
                ");
                $stmtBuscaIng->execute([':pid' => $paciente_id]);
                $ingresoExistente = $stmtBuscaIng->fetch(PDO::FETCH_ASSOC);

                $ingreso_id = 0;
                $ticket = '';

                if ($ingresoExistente) {
                    $ingreso_id = intval($ingresoExistente['id']);
                    $ticket     = $ingresoExistente['ticket_numero'];
                } else {
                    // Si no tiene ingreso previo, crear ingreso automáticamente
                    $resIng = $ingresoModel->crearIngreso(
                        $paciente_id,
                        $_SESSION['user_id'] ?? 1,
                        [],
                        'NORMAL',
                        'Ingreso Automático Escáner RICOH',
                        'PACIENTE_DIRECTO',
                        $ipsRemite,
                        0
                    );
                    if ($resIng) {
                        $ingreso_id = intval($resIng['id']);
                        $ticket     = $resIng['ticket'];
                    }
                }

                if ($ingreso_id <= 0) {
                    $errores[] = ['archivo' => $filename, 'error' => 'No se pudo crear ni asociar ingreso para este paciente.'];
                    continue;
                }

                // 5. Mover el archivo al repositorio estructurado por fecha
                $year = date('Y');
                $month = date('m');
                $day = date('d');
                $repoRelDir = "uploads/repositorio_escaneo/procesados/{$year}/{$month}/{$day}/";
                $repoFullDir = BASE_DIR . '/' . $repoRelDir;
                if (!is_dir($repoFullDir)) {
                    @mkdir($repoFullDir, 0777, true);
                }

                $cleanFilename = "{$ticket}_{$numDoc}_formula_" . time() . "_{$archivo['size']}.{$ext}";
                $targetFile = $repoFullDir . $cleanFilename;
                $rutaRelativa = $repoRelDir . $cleanFilename;

                if (!copy($fullPath, $targetFile)) {
                    $errores[] = ['archivo' => $filename, 'error' => 'Error al mover archivo al repositorio procesado.'];
                    continue;
                }

                // Eliminar archivo de la bandeja caliente temporal
                @unlink($fullPath);

                // 6. Registrar documento en ingreso_documentos
                $stmtDoc = $this->db->prepare("
                    INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo, nombre_original)
                    VALUES (:ingreso_id, 'ORDEN_MEDICA', :ruta, :orig)
                ");
                $stmtDoc->execute([
                    ':ingreso_id' => $ingreso_id,
                    ':ruta'       => $rutaRelativa,
                    ':orig'       => $filename
                ]);
                $docIdCreated = $this->db->lastInsertId();

                // 7. Mapear medicamentos contra productos_medicamentos (Búsqueda por Principio Activo, Comercial y Stock)
                require_once __DIR__ . '/../models/Inventario.php';
                $invModelWatcher = new Inventario();

                foreach ($datos['medicamentos'] as &$med) {
                    $nom = trim($med['descripcion'] ?? $med['medicamento'] ?? '');
                    if (!empty($nom)) {
                        $prodMatch = $invModelWatcher->buscarMedicamentoInteligente($nom);
                        if ($prodMatch) {
                            $med['producto_id']          = $prodMatch['id'];
                            $med['codigo_sku']            = $prodMatch['codigo_sku'];
                            $med['codigo_cums']           = $prodMatch['codigo_cums'];
                            $med['nombre_comercial_match'] = $prodMatch['nombre_comercial'];
                            $med['stock_disponible']      = $prodMatch['stock_bodega'];
                        } else {
                            $med['producto_id'] = null;
                        }
                    }
                }

                // 8. Guardar en ingreso_formulas_ia
                $totalMeds = count($datos['medicamentos'] ?? []);
                $stmtIA = $this->db->prepare("
                    INSERT INTO ingreso_formulas_ia (ingreso_id, documento_id, estado_ia, motor_utilizado, tiempo_segundos, total_medicamentos, datos_extraidos_json, created_at, procesado_at)
                    VALUES (:ingreso_id, :doc_id, 'PROCESADO', :motor, :tiempo, :total_meds, :json, NOW(), NOW())
                ");
                $stmtIA->execute([
                    ':ingreso_id'  => $ingreso_id,
                    ':doc_id'      => $docIdCreated,
                    ':motor'       => $motorIA,
                    ':tiempo'      => $tiempoIA,
                    ':total_meds'  => $totalMeds,
                    ':json'        => json_encode($datos, JSON_UNESCAPED_UNICODE)
                ]);
                $ia_id = $this->db->lastInsertId();

                // 9. Actualizar estado, auditoría y datos del médico en ingresos para Verificación Farmacéutica
                $prescObj = $datos['prescripcion'] ?? [];
                $ipsEmisora = $prescObj['ips_emisora'] ?? ($datos['paciente']['ips'] ?? 'NO ESPECIFICADA');
                $diagCie10 = $prescObj['diagnostico_cie10'] ?? ($datos['paciente']['diagnostico_cie10'] ?? 'NO REGISTRA');
                $numMipres = $prescObj['numero_mipres'] ?? ($datos['paciente']['numero_mipres'] ?? 'NO REGISTRA');
                $numAut = $prescObj['numero_autorizacion'] ?? ($datos['paciente']['numero_autorizacion'] ?? 'NO REGISTRA');
                $fVence = $prescObj['fecha_vencimiento_formula'] ?? ($datos['paciente']['fecha_vencimiento_formula'] ?? 'NO REGISTRA');

                $medicoObj = $datos['medico'] ?? [];
                $nomMed = $medicoObj['nombre_completo'] ?? ($datos['paciente']['medico'] ?? 'NO REGISTRA');
                $identMed = $medicoObj['identificacion'] ?? ($datos['paciente']['registro_medico'] ?? 'NO REGISTRA');
                $especMed = $medicoObj['especialidad'] ?? ($datos['paciente']['especialidad'] ?? 'MEDICINA GENERAL / NO ESPECIFICADA');
                $completosMed = !empty($medicoObj['datos_completos']) ? 1 : (($nomMed !== 'NO REGISTRA' && $identMed !== 'NO REGISTRA') ? 1 : 0);

                $this->db->prepare("
                    UPDATE ingresos 
                    SET estado_verificacion = 'PENDIENTE_VERIFICACION',
                        estado_tramite = 'TRANSCRIPCION',
                        ips_remite = :ips_rem,
                        diagnostico_cie10 = :diag_cie,
                        numero_mipres = :num_mipres,
                        numero_autorizacion = :num_aut,
                        fecha_vencimiento_formula = :f_vence,
                        medico_nombre = :med_nom,
                        medico_identificacion = :med_ident,
                        medico_especialidad = :med_esp,
                        medico_datos_completos = :med_comp,
                        updated_at = NOW()
                    WHERE id = :id
                ")->execute([
                    ':ips_rem'   => $ipsEmisora,
                    ':diag_cie'  => $diagCie10,
                    ':num_mipres'=> $numMipres,
                    ':num_aut'   => $numAut,
                    ':f_vence'   => $fVence,
                    ':med_nom'   => $nomMed,
                    ':med_ident' => $identMed,
                    ':med_esp'   => $especMed,
                    ':med_comp'  => $completosMed,
                    ':id'        => $ingreso_id
                ]);

                // 10. Programar cronograma si es multimes
                $cronicoModel->programarEntregasFuturas($paciente_id, $ingreso_id, $datos, 'DOMICILIO');

                $procesados[] = [
                    'archivo_original'   => $filename,
                    'ruta_repositorio'   => $rutaRelativa,
                    'ingreso_id'         => $ingreso_id,
                    'ticket'             => $ticket,
                    'paciente_id'        => $paciente_id,
                    'paciente_nombre'    => $nombreCompleto,
                    'documento'          => $numDoc,
                    'total_medicamentos' => count($datos['medicamentos']),
                    'motor_ia'           => $motorIA,
                    'tiempo_ia'          => $tiempoIA
                ];

                registrar_log_auditoria('RICOH_WATCHER', 'AUTO_EXTRACCION', $ingreso_id, "Fórmula {$filename} auto-procesada con IA. Ticket {$ticket}, Doc: {$numDoc}, Meds: " . count($datos['medicamentos']));

            } catch (Exception $e) {
                $errores[] = [
                    'archivo' => $filename,
                    'error'   => $e->getMessage()
                ];
            }
        }

        return [
            'status'           => 'ok',
            'total_procesados' => count($procesados),
            'items'            => $procesados,
            'errores'          => $errores
        ];
        } finally {
            if ($fpLock) {
                @flock($fpLock, LOCK_UN);
                @fclose($fpLock);
            }
        }
    }
}
