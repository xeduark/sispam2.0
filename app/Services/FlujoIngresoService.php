<?php

namespace App\Services;

use DateTime;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Throwable;

/**
 * Flujo de atención posterior al ingreso: transcripción, monitoreo, supervisión de
 * alistamiento, llamado/entrega, salida, cola de IA, escáner y reportes.
 *
 * Port del modelo Ingreso del sistema nativo (consultas PDO ya probadas en producción)
 * sobre la conexión de Laravel. El esquema lo gestionan las migraciones, no el código.
 */
class FlujoIngresoService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    /** Mueve un archivo subido a la ruta destino (equivale al antiguo move_uploaded_file). */
    private function moverArchivo(UploadedFile $archivo, string $destino): bool
    {
        $carpeta = dirname($destino);
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        return $archivo->move($carpeta, basename($destino)) !== null;
    }

    /** Ventanillas activas de la sede; si la sede aún no tiene, se crean las de por defecto. */
    private function modulosActivos($sede_id = null): array
    {
        $sql = "SELECT m.*, COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
                FROM modulos_entrega m LEFT JOIN sedes s ON m.sede_id = s.id
                WHERE m.estado = 'ACTIVO'";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND m.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY m.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll();

        if (empty($res) && !empty($sede_id)) {
            $ins = $this->db->prepare("INSERT IGNORE INTO modulos_entrega (sede_id, nombre, descripcion, estado) VALUES (:sede_id, :nombre, :descripcion, 'ACTIVO')");
            foreach ([
                ['MÓDULO 1', 'Ventanilla General 1'], ['MÓDULO 2', 'Ventanilla General 2'], ['MÓDULO 3', 'Ventanilla General 3'],
                ['VENTANILLA PREFERENCIAL', 'Atención Prioritaria (Adulto Mayor, Embarazadas, Discapacidad)'],
            ] as [$nombre, $descripcion]) {
                $ins->execute([':sede_id' => $sede_id, ':nombre' => $nombre, ':descripcion' => $descripcion]);
            }
            $stmt->execute($params);
            $res = $stmt->fetchAll();
        }

        return $res;
    }

    /**
     * Guarda uno o varios PDF de la transcripción y pasa la orden a Monitoreo (estado TRANSCRITO).
     *
     * @param  array<int, UploadedFile>  $pdfs
     */
    public function guardarTranscripcion($ingreso_id, array $pdfs = [], $user_id = null, $contiene_mipres = null) {
        $ingreso = $this->getById($ingreso_id);
        if (!$ingreso) return false;

        $rutas_pdfs = [];
        $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/transcripciones/';
        $full_dir = public_path() . '/' . $rel_dir;

        foreach (array_values($pdfs) as $idx => $f) {
            if (!($f instanceof UploadedFile) || !$f->isValid()) continue;

            $origName = $f->getClientOriginalName();
            $ext = strtolower($f->getClientOriginalExtension()) ?: 'pdf';
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $filename = 'transcripcion_' . $ingreso['ticket_numero'] . '_' . time() . '_' . ($idx + 1) . '_' . $cleanName . '.' . $ext;

            if ($this->moverArchivo($f, $full_dir . $filename)) {
                $ruta = $rel_dir . $filename;
                $rutas_pdfs[] = ['url' => $ruta, 'nombre' => $origName, 'indice' => $idx + 1];

                $this->db->prepare("INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo, nombre_original, created_at) VALUES (:iid, 'TRANSCRIPCION', :ruta, :nombre, NOW())")
                    ->execute([':iid' => $ingreso_id, ':ruta' => $ruta, ':nombre' => $origName]);
            }
        }

        $primer_pdf = $rutas_pdfs[0]['url'] ?? null;
        $json_pdfs = $rutas_pdfs ? json_encode($rutas_pdfs, JSON_UNESCAPED_UNICODE) : null;

        // Sin ningún soporte guardado (ni previo) no se avanza: evita órdenes huérfanas sin PDF.
        if (empty($primer_pdf) && empty($ingreso['pdf_transcripcion_url'])) {
            return false;
        }

        $res = $this->db->prepare("
            UPDATE ingresos SET
                estado_tramite = 'TRANSCRITO',
                pdf_transcripcion_url = COALESCE(:pdf, pdf_transcripcion_url),
                pdf_transcripciones_json = COALESCE(:json_pdfs, pdf_transcripciones_json),
                contiene_mipres = COALESCE(:mipres, contiene_mipres),
                locked_by_user_id = NULL,
                locked_at = NULL
            WHERE id = :id
        ")->execute([':pdf' => $primer_pdf, ':json_pdfs' => $json_pdfs, ':mipres' => $contiene_mipres, ':id' => $ingreso_id]);

        $this->db->exec("DELETE FROM `locks` WHERE `record_id` = " . intval($ingreso_id));

        if ($res) {
            registrar_log_auditoria('TRANSCRIPCION', 'GUARDAR_TRANSCRIPCION', $ingreso_id, "Orden transcrita con " . count($rutas_pdfs) . " archivo(s) PDF y enviada a Monitoreo. Tiquete: " . ($ingreso['ticket_numero'] ?? 'N/A'));
        }

        return $res;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, i.*, i.id AS id,
                   u.nombre_completo AS orientador_nombre,
                   l.nombre_completo AS lock_user_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS sede_nombre,
                   s.direccion AS sede_direccion,
                   s.telefono AS sede_telefono,
                   COALESCE(e.razon_social, 'Empresa Principal') AS empresa_nombre
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios l ON i.locked_by_user_id = l.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresas e ON s.empresa_id = e.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $ingreso = $stmt->fetch();

        if ($ingreso) {
            $stmtDocs = $this->db->prepare("SELECT * FROM ingreso_documentos WHERE ingreso_id = :id");
            $stmtDocs->execute([':id' => $id]);
            $docs = $stmtDocs->fetchAll();
            foreach ($docs as &$doc) {
                if (empty($doc['tipo_documento']) || $doc['tipo_documento'] === 'DOCUMENTO' || $doc['tipo_documento'] === 'OTRO') {
                    $s = strtolower(($doc['ruta_archivo'] ?? '') . ' ' . ($doc['nombre_original'] ?? ''));
                    if (strpos($s, 'mipres') !== false) $doc['tipo_documento'] = 'MIPRES';
                    else if (strpos($s, 'cedula') !== false) $doc['tipo_documento'] = 'CEDULA';
                    else if (strpos($s, 'orden') !== false || strpos($s, 'formula') !== false) $doc['tipo_documento'] = 'ORDEN_MEDICA';
                    else if (strpos($s, 'autorizacion') !== false) $doc['tipo_documento'] = 'AUTORIZACION';
                    else if (strpos($s, 'historia') !== false) $doc['tipo_documento'] = 'HISTORIA_CLINICA';
                }
            }
            $ingreso['documentos'] = $docs;

            // Extraer y normalizar lista de PDFs de transcripción
            $transcripciones = [];
            if (!empty($ingreso['pdf_transcripciones_json'])) {
                $decoded = json_decode($ingreso['pdf_transcripciones_json'], true);
                if (is_array($decoded)) {
                    $transcripciones = $decoded;
                }
            }
            if (empty($transcripciones) && !empty($ingreso['pdf_transcripcion_url'])) {
                $transcripciones[] = [
                    'url' => $ingreso['pdf_transcripcion_url'],
                    'nombre' => 'Orden Transcrita Principal',
                    'indice' => 1
                ];
            }
            foreach ($docs as $d) {
                if (($d['tipo_documento'] ?? '') === 'TRANSCRIPCION') {
                    $existe = false;
                    foreach ($transcripciones as $t) {
                        if ($t['url'] === $d['ruta_archivo']) {
                            $existe = true;
                            break;
                        }
                    }
                    if (!$existe) {
                        $transcripciones[] = [
                            'url' => $d['ruta_archivo'],
                            'nombre' => $d['nombre_original'] ?: ('Transcripción #' . (count($transcripciones) + 1)),
                            'indice' => count($transcripciones) + 1
                        ];
                    }
                }
            }
            $ingreso['transcripciones_archivos'] = $transcripciones;
        }

        return $ingreso;
    }

    public function getHistorialPacienteByDocumentoOrPacienteId($paciente_id, $numero_documento = '') {
        $num_doc_clean = preg_replace('/\D/', '', $numero_documento);
        
        // Si el número de documento viene vacío, obtenerlo desde la tabla pacientes
        if (empty($num_doc_clean) && $paciente_id > 0) {
            $stmtP = $this->db->prepare("SELECT numero_documento FROM pacientes WHERE id = :pid LIMIT 1");
            $stmtP->execute([':pid' => $paciente_id]);
            $rowP = $stmtP->fetch();
            if ($rowP && !empty($rowP['numero_documento'])) {
                $num_doc_clean = preg_replace('/\D/', '', $rowP['numero_documento']);
            }
        }

        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS orientador_nombre,
                   u_alist.nombre_completo AS alistador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios u_alist ON i.alistado_por_user_id = u_alist.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($num_doc_clean)) {
            $sql .= " AND (REPLACE(REPLACE(REPLACE(p.numero_documento, '.', ''), ' ', ''), '-', '') LIKE :num_doc OR i.paciente_id = :pid)";
            $params[':num_doc'] = '%' . $num_doc_clean . '%';
            $params[':pid']     = $paciente_id;
        } else {
            $sql .= " AND i.paciente_id = :pid";
            $params[':pid'] = $paciente_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorDocumento($num_doc) {
        $criterio = trim($num_doc);
        $stmt = $this->db->prepare("
            SELECT p.*, i.*, i.id AS id,
                   u.nombre_completo AS orientador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS sede_nombre,
                   s.direccion AS sede_direccion,
                   s.telefono AS sede_telefono,
                   COALESCE(e.razon_social, 'Empresa Principal') AS empresa_nombre
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresas e ON s.empresa_id = e.id
            WHERE TRIM(p.numero_documento) = :doc
               OR TRIM(i.ticket_numero) = :tkt
               OR i.ticket_numero LIKE :tktLike
               OR p.numero_documento LIKE :docLike
               OR p.nombres LIKE :nomLike
               OR p.apellidos LIKE :apeLike
               OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :nomCompLike
            ORDER BY i.fecha_ingreso DESC
        ");
        $stmt->execute([
            ':doc'         => $criterio,
            ':tkt'         => $criterio,
            ':tktLike'     => '%' . $criterio . '%',
            ':docLike'     => '%' . $criterio . '%',
            ':nomLike'     => '%' . $criterio . '%',
            ':apeLike'     => '%' . $criterio . '%',
            ':nomCompLike' => '%' . $criterio . '%'
        ]);
        return $stmt->fetchAll();
    }

    public function getListaTranscripcion($filtro_sede = null) {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS orientador_nombre,
                   l.nombre_completo AS locked_by_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   (SELECT COUNT(*) FROM ingreso_documentos d WHERE d.ingreso_id = i.id) AS num_docs
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios l ON i.locked_by_user_id = l.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('INGRESADO', 'EN_TRANSCRIPCION')";

        $params = [];
        // Transcripción es un proceso centralizado que procesa todas las sedes para máxima agilidad
        if (!empty($filtro_sede) && $filtro_sede !== 'TODAS') {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $filtro_sede;
        }

        $sql .= " ORDER BY IF(i.prioridad = 'NORMAL', 1, 0) ASC, i.fecha_ingreso ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function devolverATranscripcion($ingreso_id, $user_id, $motivo = '') {
        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'INGRESADO',
                estado_verificacion = 'CON_ERRORES',
                observacion_verificacion = :motivo,
                locked_by_user_id = NULL,
                locked_at = NULL
            WHERE id = :id
        ");
        $res = $stmt->execute([
            ':motivo' => $motivo ?: 'Devuelto a transcripción para corrección de soportes.',
            ':id'     => $ingreso_id
        ]);
        if ($res) {
            try { $this->db->exec("DELETE FROM `locks` WHERE `record_id` = " . intval($ingreso_id)); } catch (Exception $e) {}
            registrar_log_auditoria('FLUJO', 'DEVOLVER_A_TRANSCRIPCION', $ingreso_id, "Orden devuelta a Transcripción. Motivo: {$motivo}");
        }
        return $res;
    }

    public function getListaAlistamiento($filtro = 'TODOS') {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   l.nombre_completo AS locked_by_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            LEFT JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios l ON i.locked_by_user_id = l.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('VERIFICADO', 'VERIFICADA')";

        $params = [];
        $active_sede = sesion('active_sede_id') ?? sesion('sede_id') ?? null;
        if (!empty($active_sede)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $active_sede;
        }

        if (!empty($filtro) && $filtro !== 'TODOS') {
            $sql .= " AND i.estado_tramite = :filtro";
            $params[':filtro'] = $filtro;
        }

        $sql .= " ORDER BY IF(i.prioridad = 'NORMAL', 1, 0) ASC, i.fecha_ingreso ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUltimosAlistadosHoy($sede_id = null, $limit = 50) {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS alistador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.alistado_por_user_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('ESPERA_ENTREGA', 'ALISTADO', 'GESTIONADO', 'EN_ENTREGA', 'ENTREGADO', 'TRANSCRITO_COMPLETO', 'TRANSCRITO_PENDIENTE', 'VERIFICADO')
              AND (DATE(i.fecha_ingreso) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY) OR DATE(i.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY))
        ";
        $params = [];
        $active_sede = $sede_id ?: (sesion('active_sede_id') ?? sesion('sede_id') ?? null);
        if (!empty($active_sede) && $active_sede !== 'TODAS') {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $active_sede;
        }
        $sql .= " ORDER BY i.updated_at DESC, i.id DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getIngresosParaMonitoreo($filtro_sede = null) {
        $sql = "
            SELECT p.*, i.*, i.id AS id,
                   u.nombre_completo AS orientador_nombre,
                   l.user_id AS locked_by_user,
                   u_lock.nombre_completo AS locked_by_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN locks l ON l.record_id = i.id AND l.expires_at > NOW()
            LEFT JOIN usuarios u_lock ON l.user_id = u_lock.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('TRANSCRITO', 'TRANSCRITO_COMPLETO')";

        $params = [];
        // Monitoreo es un proceso centralizado que procesa todas las sedes para máxima agilidad
        if (!empty($filtro_sede) && $filtro_sede !== 'TODAS') {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $filtro_sede;
        }

        $sql .= " ORDER BY 
                CASE WHEN i.prioridad = 'EMBARAZADA' THEN 1
                     WHEN i.prioridad = 'TERCERA_EDAD' THEN 2
                     WHEN i.prioridad = 'DISCAPACIDAD' THEN 3
                     WHEN i.prioridad = 'NIÑO_LACTANTE' THEN 4
                     WHEN i.prioridad = 'OTRO_PREFERENCIAL' THEN 5
                     ELSE 6 END,
                i.fecha_ingreso ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function marcarVerificadoMonitor($id, $user_id, $estado_verificacion, $observaciones = '', $file_remplazo = null, $transcripcion_texto_nuevo = null, $contiene_mipres = null, $pdf_mipres_file = null) {
        $id = intval($id);
        $ingreso = $this->getById($id);
        if (!$ingreso) return false;


        $pdf_url = $ingreso['pdf_transcripcion_url'];
        $folder_name = $ingreso['numero_documento'] . '_' . date('dmy');
        $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/' . $folder_name . '/';
        $full_dir = public_path() . '/' . $rel_dir;

        if ($file_remplazo && $file_remplazo instanceof UploadedFile && $file_remplazo->isValid()) {
            $ext = strtolower($file_remplazo->getClientOriginalExtension());

            if (!file_exists($full_dir)) {
                mkdir($full_dir, 0755, true);
            }

            $filename = 'formula_transcrita_verificada_' . time() . '.' . $ext;
            $target_file = $full_dir . $filename;

            if ($this->moverArchivo($file_remplazo, $target_file)) {
                $pdf_url = $rel_dir . $filename;
            }
        }

        // Subida y Registro de soporte MIPRES por el Monitor
        $pdf_mipres_url = $ingreso['pdf_mipres_url'] ?? null;
        if ($pdf_mipres_file && $pdf_mipres_file instanceof UploadedFile && $pdf_mipres_file->isValid()) {
            $ext_mip = strtolower($pdf_mipres_file->getClientOriginalExtension());
            $rel_savia_dir = $rel_dir . 'soportes Savia/';
            $full_savia_dir = public_path() . '/' . $rel_savia_dir;
            if (!file_exists($full_savia_dir)) {
                mkdir($full_savia_dir, 0755, true);
            }
            $filename_mip = 'mipres_monitor_' . time() . '.' . $ext_mip;
            $target_file_mip = $full_savia_dir . $filename_mip;

            if ($this->moverArchivo($pdf_mipres_file, $target_file_mip)) {
                $pdf_mipres_url = $rel_savia_dir . $filename_mip;
                $contiene_mipres = 'SI';

                // Registrar en ingreso_documentos para que sea visible e identificado en todos los históricos
                try {
                    $stmtDoc = $this->db->prepare("
                        INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo, nombre_original) 
                        VALUES (:iid, 'MIPRES', :ruta, :nombre_orig)
                    ");
                    $stmtDoc->execute([
                        ':iid' => $id,
                        ':ruta' => $pdf_mipres_url,
                        ':nombre_orig' => $pdf_mipres_file->getClientOriginalName()
                    ]);
                } catch (Exception $e) {}
            }
        }

        $stmtMain = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'VERIFICADO',
                pdf_transcripcion_url = :pdf_url,
                pdf_mipres_url = COALESCE(:pdf_mipres, pdf_mipres_url),
                contiene_mipres = COALESCE(:contiene_mipres, contiene_mipres)
            WHERE id = :id
        ");
        $exitoMain = $stmtMain->execute([
            ':pdf_url'         => $pdf_url,
            ':pdf_mipres'      => $pdf_mipres_url,
            ':contiene_mipres' => $contiene_mipres,
            ':id'              => $id
        ]);

        try {
            $stmtSec = $this->db->prepare("
                UPDATE ingresos SET 
                    estado_verificacion = :estado_verif,
                    verificado_por_user_id = :user_id,
                    fecha_verificacion = NOW(),
                    observacion_verificacion = :obs,
                    locked_by_user_id = NULL,
                    locked_at = NULL
                WHERE id = :id
            ");
            $stmtSec->execute([
                ':estado_verif' => $estado_verificacion,
                ':user_id'      => $user_id,
                ':obs'          => $observaciones ?: null,
                ':id'           => $id
            ]);
        } catch (Exception $e) {}

        if ($transcripcion_texto_nuevo !== null) {
            try {
                $stmtTxt = $this->db->prepare("UPDATE ingresos SET transcripcion_texto = :txt WHERE id = :id");
                $stmtTxt->execute([':txt' => $transcripcion_texto_nuevo, ':id' => $id]);
            } catch (Exception $e) {}
        }

        try { $this->db->exec("DELETE FROM `locks` WHERE `record_id` = " . $id); } catch (Exception $e) {}

        return $exitoMain;
    }

    public function getIngresosParaSupervisor() {
        $sql = "
            SELECT p.*, i.*, i.id AS id,
                   u.nombre_completo AS orientador_nombre,
                   l.user_id AS locked_by_user,
                   u_lock.nombre_completo AS locked_by_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            LEFT JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN locks l ON l.record_id = i.id AND l.expires_at > NOW()
            LEFT JOIN usuarios u_lock ON l.user_id = u_lock.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('VERIFICADO', 'VERIFICADA')";

        $params = [];
        $active_sede = sesion('active_sede_id') ?? sesion('sede_id') ?? null;
        if (!empty($active_sede)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $active_sede;
        }

        $sql .= " ORDER BY 
                CASE WHEN i.prioridad = 'EMBARAZADA' THEN 1
                     WHEN i.prioridad = 'TERCERA_EDAD' THEN 2
                     WHEN i.prioridad = 'DISCAPACIDAD' THEN 3
                     WHEN i.prioridad = 'NIÑO_LACTANTE' THEN 4
                     WHEN i.prioridad = 'OTRO_PREFERENCIAL' THEN 5
                     ELSE 6 END,
                i.fecha_ingreso ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function marcarGestionadoSupervisor($id, $user_id, $pdf_alistamiento_file = null, $faltantes = null) {
        $ingreso = $this->getById($id);
        if (!$ingreso) return false;

        $pdf_alist_url = $ingreso['pdf_alistamiento'];

        if ($pdf_alistamiento_file && $pdf_alistamiento_file instanceof UploadedFile && $pdf_alistamiento_file->isValid()) {
            $ext = strtolower($pdf_alistamiento_file->getClientOriginalExtension());
            $folder_name = $ingreso['numero_documento'] . '_' . date('dmy');
            $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/' . $folder_name . '/';
            $full_dir = public_path() . '/' . $rel_dir;

            if (!file_exists($full_dir)) {
                mkdir($full_dir, 0755, true);
            }

            $filename = 'alistamiento_' . time() . '.' . $ext;
            $target_file = $full_dir . $filename;

            if ($this->moverArchivo($pdf_alistamiento_file, $target_file)) {
                $pdf_alist_url = $rel_dir . $filename;
            }
        }

        // Al imprimir y aprobar en supervisión de alistamiento, el registro pasa a ESPERA_ENTREGA
        // El módulo se asignará únicamente cuando el entregador tome el paquete físico y lo llame en su ventanilla.
        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'ESPERA_ENTREGA',
                modulo_entrega_asignado = NULL,
                alistado_por_user_id = :user_id,
                fecha_alistado = NOW(),
                pdf_alistamiento = :pdf_alist,
                faltantes_alistamiento = :faltantes,
                locked_by_user_id = NULL,
                locked_at = NULL
            WHERE id = :id
        ");
        $res = $stmt->execute([
            ':user_id'   => $user_id,
            ':pdf_alist' => $pdf_alist_url,
            ':faltantes' => $faltantes ?: $ingreso['faltantes_alistamiento'],
            ':id'        => $id
        ]);
        try { $this->db->exec("DELETE FROM `locks` WHERE `record_id` = " . intval($id)); } catch (Exception $e) {}
        return $res;
    }

    public function getListaEntrega($modulo_filtro = null) {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   (SELECT d.ruta_archivo FROM ingreso_documentos d WHERE d.ingreso_id = i.id AND (d.tipo_documento LIKE '%FORMULA%' OR d.tipo_documento LIKE '%TRANSCRIPCION%') ORDER BY d.id DESC LIMIT 1) AS pdf_documento_ingreso
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('ESPERA_ENTREGA', 'EN_ENTREGA', 'ALISTADO')";
        
        $params = [];
        $active_sede = sesion('active_sede_id') ?? sesion('sede_id') ?? null;
        if (!empty($active_sede)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $active_sede;
        }

        if ($modulo_filtro && $modulo_filtro !== 'TODOS') {
            $sql .= " AND i.modulo_entrega_asignado = " . $this->db->quote($modulo_filtro);
        }

        $sql .= " ORDER BY IF(i.prioridad = 'NORMAL', 1, 0) ASC, i.updated_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarParaEntrega($termino, $sede_id = null) {
        $termino = trim($termino);
        if (empty($termino)) return [];

        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   p.ciudad_residencia, p.direccion_residencia,
                   u.nombre_completo AS orientador_nombre,
                   ua.nombre_completo AS alistador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios ua ON i.alistado_por_user_id = ua.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE (TRIM(i.ticket_numero) = :termExacto 
                   OR i.ticket_numero LIKE :termLike 
                   OR TRIM(p.numero_documento) = :termDoc 
                   OR p.numero_documento LIKE :termDocLike
                   OR p.nombres LIKE :termNombreLike
                   OR p.apellidos LIKE :termApellidoLike
                   OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :termNombreCompLike)
              AND i.estado_tramite NOT IN ('CANCELADO')
              AND (DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                   OR DATE(i.fecha_ingreso) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                   OR DATE(i.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   OR i.fecha_salida IS NULL 
                   OR TRIM(i.ticket_numero) = :termExacto2 
                   OR TRIM(p.numero_documento) = :termDoc2)
        ";
        $params = [
            ':termExacto'         => $termino,
            ':termExacto2'        => $termino,
            ':termLike'           => '%' . $termino . '%',
            ':termDoc'            => $termino,
            ':termDoc2'           => $termino,
            ':termDocLike'        => '%' . $termino . '%',
            ':termNombreLike'     => '%' . $termino . '%',
            ':termApellidoLike'   => '%' . $termino . '%',
            ':termNombreCompLike' => '%' . $termino . '%'
        ];

        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id IS NULL OR i.sede_id = 0 OR i.sede_id = :sede_id OR TRIM(i.ticket_numero) = :termExacto3 OR TRIM(p.numero_documento) = :termDoc3)";
            $params[':sede_id'] = $sede_id;
            $params[':termExacto3'] = $termino;
            $params[':termDoc3'] = $termino;
        }

        // Priorizar órdenes en entrega activa o alistadas pendientes
        $sql .= " ORDER BY 
                    CASE 
                        WHEN i.estado_tramite = 'EN_ENTREGA' AND i.fecha_salida IS NULL THEN 1
                        WHEN i.estado_tramite IN ('ALISTADO', 'EN_PROCESO', 'INGRESADO') AND i.fecha_salida IS NULL THEN 2
                        ELSE 3
                    END ASC, 
                    i.id DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll();

        foreach ($resultados as &$ingreso) {
            $esPendiente = ($ingreso['estado_tramite'] !== 'ENTREGADO' && empty($ingreso['fecha_salida']));
            $ingreso['tipo_atencion_entrega'] = $esPendiente ? 'PENDIENTE' : 'ENTREGADO';

            $stmtDocs = $this->db->prepare("SELECT * FROM ingreso_documentos WHERE ingreso_id = :id");
            $stmtDocs->execute([':id' => $ingreso['id']]);
            $ingreso['documentos'] = $stmtDocs->fetchAll();
        }
        unset($ingreso);

        return $resultados;
    }

    public function getIngresosParaEntrega() {
        $sql = "
            SELECT p.*, i.*, i.id AS id,
                   u.nombre_completo AS orientador_nombre,
                   l.user_id AS locked_by_user,
                   u_lock.nombre_completo AS locked_by_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN locks l ON l.record_id = i.id AND l.expires_at > NOW()
            LEFT JOIN usuarios u_lock ON l.user_id = u_lock.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('ALISTADO', 'EN_ENTREGA')
            ORDER BY 
                CASE WHEN i.prioridad = 'EMBARAZADA' THEN 1
                     WHEN i.prioridad = 'TERCERA_EDAD' THEN 2
                     WHEN i.prioridad = 'DISCAPACIDAD' THEN 3
                     WHEN i.prioridad = 'NIÑO_LACTANTE' THEN 4
                     WHEN i.prioridad = 'OTRO_PREFERENCIAL' THEN 5
                     ELSE 6 END,
                i.fecha_ingreso ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function iniciarLlamadoEntrega($ingreso_id, $modulo_nombre, $user_id) {
        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'EN_ENTREGA',
                modulo_entrega_asignado = :modulo,
                locked_by_user_id = :user_id,
                locked_at = NOW(),
                fecha_llamado_entrega = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ");
        $res = $stmt->execute([
            ':modulo'  => $modulo_nombre ?: 'MÓDULO DE ENTREGA',
            ':user_id' => $user_id,
            ':id'      => $ingreso_id
        ]);
        if ($res && function_exists('registrar_log_auditoria')) {
            registrar_log_auditoria('ENTREGA', 'LLAMAR_A_MODULO', $ingreso_id, "Paciente llamado al {$modulo_nombre} para entrega de medicamentos.");
        }
        return $res;
    }

    public function rellamarTurnoEntrega($ingreso_id, $user_id = null) {
        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                fecha_rellamado = NOW(),
                contador_rellamados = COALESCE(contador_rellamados, 0) + 1,
                updated_at = NOW()
            WHERE id = :id AND estado_tramite = 'EN_ENTREGA'
        ");
        $res = $stmt->execute([':id' => $ingreso_id]);
        if ($res && function_exists('registrar_log_auditoria')) {
            registrar_log_auditoria('ENTREGA', 'RELLAMAR_A_TV', $ingreso_id, "Paciente rellamado a pantalla TV.");
        }
        return $res;
    }

    public function getUltimoRellamado($sede_id = null, $segundos_recientes = 20) {
        $sql = "
            SELECT i.id, i.ticket_numero, i.modulo_entrega_asignado, i.fecha_rellamado, i.contador_rellamados,
                   p.nombres, p.apellidos,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite = 'EN_ENTREGA'
              AND i.fecha_rellamado IS NOT NULL
              AND i.fecha_rellamado >= (NOW() - INTERVAL " . intval($segundos_recientes) . " SECOND)
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY i.fecha_rellamado DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPacientesEnPantallaTV($sede_id = null) {
        $sql = "
            SELECT i.id, i.ticket_numero, i.modulo_entrega_asignado, i.fecha_llamado_entrega, i.fecha_rellamado, i.contador_rellamados, i.updated_at, i.prioridad, i.es_alto_costo,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite = 'EN_ENTREGA'
              AND i.fecha_salida IS NULL
              AND DATE(i.fecha_ingreso) = CURDATE()
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY i.updated_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAlertaReingresoMayor27Dias($paciente_id, $ingreso_actual_id) {
        if (empty($paciente_id) && empty($ingreso_actual_id)) return null;
        try {
            // 1. Obtener fecha y documento del ingreso actual
            $stmtCurr = $this->db->prepare("
                SELECT i.fecha_ingreso, p.numero_documento, i.paciente_id
                FROM ingresos i 
                JOIN pacientes p ON i.paciente_id = p.id 
                WHERE i.id = :id
            ");
            $stmtCurr->execute([':id' => $ingreso_actual_id]);
            $curr = $stmtCurr->fetch(PDO::FETCH_ASSOC);
            if (!$curr) return null;

            $fechaCurr = $curr['fecha_ingreso'] ?: date('Y-m-d H:i:s');
            $pid       = $paciente_id ?: $curr['paciente_id'];
            $doc       = trim($curr['numero_documento'] ?? '');

            // 2. Buscar si tiene algún ingreso histórico cuya diferencia con la fecha actual sea >= 27 días
            $stmt = $this->db->prepare("
                SELECT i.id, i.ticket_numero, i.fecha_ingreso, i.sede_id,
                       COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede_anterior,
                       DATEDIFF(:f1, i.fecha_ingreso) AS dias_transcurridos
                FROM ingresos i
                JOIN pacientes p ON i.paciente_id = p.id
                LEFT JOIN sedes s ON i.sede_id = s.id
                WHERE (i.paciente_id = :pid OR TRIM(p.numero_documento) = :doc)
                  AND i.id != :current_id
                  AND DATEDIFF(:f2, i.fecha_ingreso) >= 27
                ORDER BY i.fecha_ingreso DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':f1'         => $fechaCurr,
                ':f2'         => $fechaCurr,
                ':pid'        => $pid,
                ':doc'        => $doc,
                ':current_id' => $ingreso_actual_id
            ]);
            $prev = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($prev) {
                return [
                    'es_reingreso_mayor_27' => true,
                    'dias_transcurridos'    => intval($prev['dias_transcurridos']),
                    'fecha_ultimo_ingreso'  => $prev['fecha_ingreso'],
                    'ticket_anterior'       => $prev['ticket_numero'],
                    'sede_anterior'         => $prev['nombre_sede_anterior'] ?: 'Sede Principal'
                ];
            }
        } catch (Exception $e) {}
        return null;
    }

    public function getHistorialLlamadosEntregaHoy($sede_id = null, $limit = 50) {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   u.nombre_completo AS llamado_por_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.locked_by_user_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE (i.estado_tramite IN ('EN_ENTREGA', 'ENTREGADO') OR i.modulo_entrega_asignado IS NOT NULL)
              AND (DATE(i.created_at) = CURDATE() OR DATE(i.fecha_ingreso) = CURDATE() OR DATE(i.updated_at) = CURDATE())
        ";
        $params = [];
        if ($sede_id) {
            $sql .= " AND (i.sede_id IS NULL OR i.sede_id = 0 OR i.sede_id = :sede_id)";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY IF(i.estado_tramite = 'EN_ENTREGA', 0, 1) ASC, i.updated_at DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getEstadisticasEntregaHoy($sede_id = null) {
        $stats = [
            'total_llamados' => 0,
            'en_ventanilla'  => 0,
            'completados'    => 0
        ];
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT CASE WHEN (i.modulo_entrega_asignado IS NOT NULL OR i.estado_tramite IN ('EN_ENTREGA', 'ENTREGADO')) THEN i.id END) AS total_llamados,
                    COUNT(DISTINCT CASE WHEN i.estado_tramite = 'EN_ENTREGA' AND i.fecha_salida IS NULL THEN i.id END) AS en_ventanilla,
                    COUNT(DISTINCT CASE WHEN i.fecha_salida IS NOT NULL THEN i.id END) AS completados
                FROM ingresos i
                WHERE DATE(i.fecha_ingreso) = CURDATE()
                  AND i.estado_tramite != 'CANCELADO'
            ";
            $params = [];
            if ($sede_id) {
                $sql .= " AND (i.sede_id IS NULL OR i.sede_id = 0 OR i.sede_id = :sede_id)";
                $params[':sede_id'] = $sede_id;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch();
            if ($res) {
                $stats['total_llamados'] = intval($res['total_llamados']);
                $stats['en_ventanilla']  = intval($res['en_ventanilla']);
                $stats['completados']    = intval($res['completados']);
            }
        } catch (Exception $e) {}
        return $stats;
    }

    public function getUltimasEntregasHoy($sede_id = null, $modulo_filtro = null, $limit = 10) {
        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS entregador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.locked_by_user_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite = 'ENTREGADO' AND DATE(i.updated_at) = CURDATE()
        ";
        $params = [];
        if ($sede_id) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        if (!empty($modulo_filtro) && $modulo_filtro !== 'TODOS') {
            $sql .= " AND i.modulo_entrega_asignado = :mod";
            $params[':mod'] = $modulo_filtro;
        }
        $sql .= " ORDER BY i.updated_at DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function procesarEntregaFinalConFormula($id, $user_id, $file_formula_final = null, $firma_paciente_base64 = null, $faltantes_manuales = null, $foto_paciente_base64 = null, $foto_paciente_file = null, $pdf_savia_derechos = null, $pdf_savia_mipres = null) {
        $ingreso = $this->getById($id);
        if (!$ingreso) return false;


        $pdf_final_url    = $ingreso['pdf_formula_final_url'];
        $firma_url        = $ingreso['firma_paciente_url'];
        $foto_url          = $ingreso['foto_paciente_url'] ?? null;
        $pdf_derechos_url = $ingreso['pdf_validacion_derechos_url'] ?? null;
        $pdf_mipres_url   = $ingreso['pdf_mipres_url'] ?? null;

        $folder_name = $ingreso['numero_documento'] . '_' . date('dmy');
        $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/' . $folder_name . '/';
        $full_dir = public_path() . '/' . $rel_dir;

        if (!file_exists($full_dir)) {
            mkdir($full_dir, 0755, true);
        }

        // Subcarpeta 'soportes Savia' para Validación de Derechos y Mipres
        $rel_savia_dir = $rel_dir . 'soportes Savia/';
        $full_savia_dir = public_path() . '/' . $rel_savia_dir;
        if (!file_exists($full_savia_dir)) {
            mkdir($full_savia_dir, 0755, true);
        }

        // Subida de la fórmula final descargada del software de terceros
        if ($file_formula_final && $file_formula_final instanceof UploadedFile && $file_formula_final->isValid()) {
            $ext = strtolower($file_formula_final->getClientOriginalExtension());
            $filename = 'formula_final_terceros_' . time() . '.' . $ext;
            $target_file = $full_dir . $filename;

            if ($this->moverArchivo($file_formula_final, $target_file)) {
                $pdf_final_url = $rel_dir . $filename;
            }
        }

        // Subida de Validación de Derechos de Savia (en subcarpeta 'soportes Savia')
        if ($pdf_savia_derechos && $pdf_savia_derechos instanceof UploadedFile && $pdf_savia_derechos->isValid()) {
            $ext_der = strtolower($pdf_savia_derechos->getClientOriginalExtension());
            $filename_der = 'validacion_derechos_savia_' . time() . '.' . $ext_der;
            $target_file_der = $full_savia_dir . $filename_der;

            if ($this->moverArchivo($pdf_savia_derechos, $target_file_der)) {
                $pdf_derechos_url = $rel_savia_dir . $filename_der;
                try {
                    $stmtDoc = $this->db->prepare("INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo) VALUES (:iid, 'Validación Derechos Savia', :ruta)");
                    $stmtDoc->execute([':iid' => $id, ':ruta' => $pdf_derechos_url]);
                } catch (Exception $e) {}
            }
        }

        // Subida de Mipres (en subcarpeta 'soportes Savia')
        if ($pdf_savia_mipres && $pdf_savia_mipres instanceof UploadedFile && $pdf_savia_mipres->isValid()) {
            $ext_mip = strtolower($pdf_savia_mipres->getClientOriginalExtension());
            $filename_mip = 'mipres_' . time() . '.' . $ext_mip;
            $target_file_mip = $full_savia_dir . $filename_mip;

            if ($this->moverArchivo($pdf_savia_mipres, $target_file_mip)) {
                $pdf_mipres_url = $rel_savia_dir . $filename_mip;
                try {
                    $stmtDoc = $this->db->prepare("INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo) VALUES (:iid, 'Mipres', :ruta)");
                    $stmtDoc->execute([':iid' => $id, ':ruta' => $pdf_mipres_url]);
                } catch (Exception $e) {}
            }
        }

        // Guardar Firma digital del Paciente
        if (!empty($firma_paciente_base64)) {
            $img_data = str_replace('data:image/png;base64,', '', $firma_paciente_base64);
            $img_data = str_replace(' ', '+', $img_data);
            $data = base64_decode($img_data);
            $filename_firma = 'firma_' . time() . '.png';
            $file_path_firma = $full_dir . $filename_firma;
            file_put_contents($file_path_firma, $data);
            $firma_url = $rel_dir . $filename_firma;
        }

        // Guardar Foto del Paciente desde Cámara Web (Base64)
        if (!empty($foto_paciente_base64)) {
            $img_data = str_replace('data:image/jpeg;base64,', '', $foto_paciente_base64);
            $img_data = str_replace('data:image/png;base64,', '', $img_data);
            $img_data = str_replace(' ', '+', $img_data);
            $data = base64_decode($img_data);
            $filename_foto = 'foto_paciente_' . time() . '.jpg';
            $file_path_foto = $full_dir . $filename_foto;
            file_put_contents($file_path_foto, $data);
            $foto_url = $rel_dir . $filename_foto;
        }

        // Guardar Foto del Paciente desde Subida de Archivo
        if ($foto_paciente_file && $foto_paciente_file instanceof UploadedFile && $foto_paciente_file->isValid()) {
            $ext_foto = strtolower($foto_paciente_file->getClientOriginalExtension());
            $filename_foto = 'foto_paciente_' . time() . '.' . $ext_foto;
            $target_file_foto = $full_dir . $filename_foto;
            if ($this->moverArchivo($foto_paciente_file, $target_file_foto)) {
                $foto_url = $rel_dir . $filename_foto;
            }
        }

        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'ENTREGADO',
                pdf_formula_final_url = :pdf_final,
                pdf_validacion_derechos_url = :pdf_derechos,
                pdf_mipres_url = :pdf_mipres,
                firma_paciente_url = :firma,
                foto_paciente_url = :foto,
                faltantes_alistamiento = :faltantes
            WHERE id = :id
        ");

        return $stmt->execute([
            ':pdf_final'    => $pdf_final_url,
            ':pdf_derechos' => $pdf_derechos_url,
            ':pdf_mipres'   => $pdf_mipres_url,
            ':firma'        => $firma_url,
            ':foto'         => $foto_url,
            ':faltantes'    => $faltantes_manuales ?: $ingreso['faltantes_alistamiento'],
            ':id'           => $id
        ]);
    }

    public function getTurnero1($sede_id = null) {
        $sql = "
            SELECT i.ticket_numero, p.nombres, p.apellidos, i.estado_tramite, i.fecha_ingreso, i.prioridad,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite IN ('INGRESADO', 'EN_TRANSCRIPCION', 'TRANSCRITO', 'TRANSCRITO_COMPLETO', 'TRANSCRITO_PENDIENTE', 'VERIFICADO', 'ESPERA_ENTREGA')
              AND i.fecha_salida IS NULL
              AND DATE(i.fecha_ingreso) = CURDATE()
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY IF(i.prioridad = 'NORMAL', 1, 0) ASC, i.fecha_ingreso ASC LIMIT 12";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTurnero2($sede_id = null) {
        $sql = "
            SELECT i.id, i.ticket_numero, i.modulo_entrega_asignado, p.nombres, p.apellidos, i.updated_at, i.prioridad,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite = 'EN_ENTREGA'
              AND i.fecha_salida IS NULL
              AND DATE(i.fecha_ingreso) = CURDATE()
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY i.updated_at DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarIngresoParaSalida($criterio, $sede_id = null) {
        $criterioLimpio = trim($criterio);
        if (empty($criterioLimpio)) return null;

        $docDigits = preg_replace('/\D/', '', $criterioLimpio);

        // 1. PRIMERA PRIORIDAD: Buscar tiquetes ACTIVOS (Sin fecha_salida registrada y no cancelados)
        $sqlActivo = "
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, 
                   p.eps_nombre, p.telefono, p.email, p.direccion_residencia, p.sexo,
                   u_ori.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u_ori ON i.orientador_id = u_ori.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE (
                i.ticket_numero = :crit1 
                OR i.ticket_numero LIKE :crit_like
                OR REPLACE(REPLACE(REPLACE(p.numero_documento, '.', ''), ' ', ''), '-', '') = :doc_digits
                OR p.numero_documento = :crit2
            )
            AND i.fecha_salida IS NULL
            AND i.estado_tramite != 'CANCELADO'
        ";

        $params = [
            ':crit1'      => $criterioLimpio,
            ':crit_like'  => '%' . $criterioLimpio . '%',
            ':doc_digits' => $docDigits ?: $criterioLimpio,
            ':crit2'      => $criterioLimpio
        ];

        $sqlActivoConSede = $sqlActivo;
        $paramsConSede = $params;
        if (!empty($sede_id)) {
            $sqlActivoConSede .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
            $paramsConSede[':sede_id'] = $sede_id;
        }

        $sqlActivoConSede .= " ORDER BY i.fecha_ingreso DESC LIMIT 1";

        $stmt = $this->db->prepare($sqlActivoConSede);
        $stmt->execute($paramsConSede);
        $ingreso = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback: Si no se encontró en la sede seleccionada pero el usuario digitó el código directo, buscar global
        if (!$ingreso && !empty($sede_id)) {
            $sqlActivoGlobal = $sqlActivo . " ORDER BY i.fecha_ingreso DESC LIMIT 1";
            $stmtGlobal = $this->db->prepare($sqlActivoGlobal);
            $stmtGlobal->execute($params);
            $ingreso = $stmtGlobal->fetch(PDO::FETCH_ASSOC);
        }

        if ($ingreso) {
            $horarioInfo = get_horario_apertura_dia(
                $ingreso['fecha_ingreso'], 
                $ingreso['hora_apertura_semana'] ?? '07:00:00', 
                $ingreso['hora_apertura_festivos'] ?? '08:00:00'
            );
            $ingreso['hora_apertura_oficial']  = $horarioInfo['hora'];
            $ingreso['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
            $ingreso['label_horario_apertura'] = $horarioInfo['label'];
            $ingreso['es_festivo_o_finde']     = $horarioInfo['es_festivo_o_finde'];

            $fIngreso  = new DateTime($ingreso['fecha_ingreso']);
            $fFinal    = new DateTime();
            $fApertura = new DateTime($fIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            $diffTotalSeg = max(0, $fFinal->getTimestamp() - $fIngreso->getTimestamp());
            $ingreso['minutos_transcurridos_totales'] = round($diffTotalSeg / 60, 1);
            $ingreso['horas_transcurridas']           = round($diffTotalSeg / 3600, 2);

            if ($fIngreso < $fApertura) {
                $diffFilaSeg = max(0, min($fFinal->getTimestamp(), $fApertura->getTimestamp()) - $fIngreso->getTimestamp());
                $ingreso['tiempo_fila_externa_min'] = round($diffFilaSeg / 60, 1);
                
                $diffFarmaciaSeg = max(0, $fFinal->getTimestamp() - $fApertura->getTimestamp());
                $ingreso['minutos_transcurridos'] = round($diffFarmaciaSeg / 60, 1);
                $ingreso['ingresado_antes_apertura'] = true;
            } else {
                $ingreso['tiempo_fila_externa_min'] = 0;
                $ingreso['minutos_transcurridos'] = $ingreso['minutos_transcurridos_totales'];
                $ingreso['ingresado_antes_apertura'] = false;
            }

            $ingreso['ya_cerrado'] = false;
            return $ingreso;
        }

        // 2. SEGUNDA PRIORIDAD: Si no hay activo, verificar si ya fue CERRADO
        $sqlCerrado = "
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, 
                   p.eps_nombre, p.telefono, p.email, p.direccion_residencia, p.sexo,
                   u_ori.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u_ori ON i.orientador_id = u_ori.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE (
                i.ticket_numero = :crit1 
                OR i.ticket_numero LIKE :crit_like
                OR REPLACE(REPLACE(REPLACE(p.numero_documento, '.', ''), ' ', ''), '-', '') = :doc_digits
                OR p.numero_documento = :crit2
            )
            AND i.fecha_salida IS NOT NULL
        ";

        $sqlCerradoConSede = $sqlCerrado;
        if (!empty($sede_id)) {
            $sqlCerradoConSede .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
        }
        $sqlCerradoConSede .= " ORDER BY i.fecha_salida DESC LIMIT 1";

        $stmtCerrado = $this->db->prepare($sqlCerradoConSede);
        $stmtCerrado->execute($paramsConSede);
        $cerrado = $stmtCerrado->fetch(PDO::FETCH_ASSOC);

        if (!$cerrado && !empty($sede_id)) {
            $sqlCerradoGlobal = $sqlCerrado . " ORDER BY i.fecha_salida DESC LIMIT 1";
            $stmtCG = $this->db->prepare($sqlCerradoGlobal);
            $stmtCG->execute($params);
            $cerrado = $stmtCG->fetch(PDO::FETCH_ASSOC);
        }

        if ($cerrado) {
            $horarioInfo = get_horario_apertura_dia(
                $cerrado['fecha_ingreso'], 
                $cerrado['hora_apertura_semana'] ?? '07:00:00', 
                $cerrado['hora_apertura_festivos'] ?? '08:00:00'
            );
            $cerrado['hora_apertura_oficial']  = $horarioInfo['hora'];
            $cerrado['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
            $cerrado['label_horario_apertura'] = $horarioInfo['label'];
            $cerrado['es_festivo_o_finde']     = $horarioInfo['es_festivo_o_finde'];

            $fIngreso  = new DateTime($cerrado['fecha_ingreso']);
            $fSalida   = new DateTime($cerrado['fecha_salida']);
            $fApertura = new DateTime($fIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            $diffTotalSeg = max(0, $fSalida->getTimestamp() - $fIngreso->getTimestamp());
            $cerrado['minutos_transcurridos_totales'] = round($diffTotalSeg / 60, 1);
            $cerrado['horas_transcurridas']           = round($diffTotalSeg / 3600, 2);

            if ($fIngreso < $fApertura) {
                $diffFilaSeg = max(0, min($fSalida->getTimestamp(), $fApertura->getTimestamp()) - $fIngreso->getTimestamp());
                $cerrado['tiempo_fila_externa_min'] = round($diffFilaSeg / 60, 1);
                
                $diffFarmaciaSeg = max(0, $fSalida->getTimestamp() - $fApertura->getTimestamp());
                $cerrado['minutos_transcurridos'] = round($diffFarmaciaSeg / 60, 1);
                $cerrado['ingresado_antes_apertura'] = true;
            } else {
                $cerrado['tiempo_fila_externa_min'] = 0;
                $cerrado['minutos_transcurridos'] = $cerrado['minutos_transcurridos_totales'];
                $cerrado['ingresado_antes_apertura'] = false;
            }

            $cerrado['ya_cerrado'] = true;
            return $cerrado;
        }

        return null;
    }

    public function cerrarTicketSalida($ingreso_id, $user_id, $observacion = '') {
        $ingreso = $this->getById($ingreso_id);
        if (!$ingreso) return false;

        $stmt = $this->db->prepare("
            UPDATE ingresos SET
                estado_tramite = 'ENTREGADO',
                fecha_salida = NOW(),
                salida_por_user_id = :uid,
                observacion_salida = :obs,
                locked_by_user_id = NULL,
                locked_at = NULL
            WHERE id = :id
        ");

        $res = $stmt->execute([
            ':uid' => $user_id,
            ':obs' => trim($observacion) ?: 'Cierre de tiquete registrado en módulo de Salida.',
            ':id'  => $ingreso_id
        ]);

        if ($res) {
            $ticketNum = $ingreso['ticket_numero'] ?? 'N/A';
            registrar_log_auditoria('SALIDA', 'CIERRE_TICKET', $ingreso_id, "Tiquete {$ticketNum} cerrado exitosamente. Fecha/Hora de Salida registrada para SLA. Obs: " . ($observacion ?: 'Sin observaciones adicionales.'));
            
            // Re-obtener para retornar datos actualizados y tiempo final
            return $this->getById($ingreso_id);
        }

        return false;
    }

    public function getListaPendientesSalida($sede_id = null, $limit = null) {
        $sql = "
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS orientador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE i.fecha_salida IS NULL 
              AND i.estado_tramite != 'CANCELADO'
              AND DATE(i.fecha_ingreso) = CURDATE()
        ";

        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY IF(i.prioridad = 'NORMAL', 1, 0) ASC, i.fecha_ingreso DESC";
        if (!empty($limit)) {
            $sql .= " LIMIT " . intval($limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ahora = new DateTime();
        foreach ($rows as &$r) {
            $horarioInfo = get_horario_apertura_dia(
                $r['fecha_ingreso'], 
                $r['hora_apertura_semana'] ?? '07:00:00', 
                $r['hora_apertura_festivos'] ?? '08:00:00'
            );
            $r['hora_apertura_oficial']  = $horarioInfo['hora'];
            $r['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
            $r['label_horario_apertura'] = $horarioInfo['label'];

            $fIngreso  = new DateTime($r['fecha_ingreso']);
            $fApertura = new DateTime($fIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            if ($fIngreso < $fApertura) {
                $diffFarmaciaSeg = max(0, $ahora->getTimestamp() - $fApertura->getTimestamp());
                $r['minutos_transcurridos'] = round($diffFarmaciaSeg / 60, 1);
                $r['ingresado_antes_apertura'] = true;
            } else {
                $diffSeg = max(0, $ahora->getTimestamp() - $fIngreso->getTimestamp());
                $r['minutos_transcurridos'] = round($diffSeg / 60, 1);
                $r['ingresado_antes_apertura'] = false;
            }
        }

        return $rows;
    }

    public function getListaUltimasSalidas($sede_id = null, $limit = null) {
        $sql = "
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE i.fecha_salida IS NOT NULL
              AND DATE(i.fecha_salida) = CURDATE()
        ";

        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_salida DESC";
        if (!empty($limit)) {
            $sql .= " LIMIT " . intval($limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $horarioInfo = get_horario_apertura_dia(
                $r['fecha_ingreso'], 
                $r['hora_apertura_semana'] ?? '07:00:00', 
                $r['hora_apertura_festivos'] ?? '08:00:00'
            );
            $r['hora_apertura_oficial']  = $horarioInfo['hora'];
            $r['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
            $r['label_horario_apertura'] = $horarioInfo['label'];

            $fIngreso  = new DateTime($r['fecha_ingreso']);
            $fSalida   = new DateTime($r['fecha_salida']);
            $fApertura = new DateTime($fIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            if ($fIngreso < $fApertura) {
                $diffFarmaciaSeg = max(0, $fSalida->getTimestamp() - $fApertura->getTimestamp());
                $r['minutos_totales_sla'] = round($diffFarmaciaSeg / 60, 1);
                $r['ingresado_antes_apertura'] = true;
            } else {
                $diffSeg = max(0, $fSalida->getTimestamp() - $fIngreso->getTimestamp());
                $r['minutos_totales_sla'] = round($diffSeg / 60, 1);
                $r['ingresado_antes_apertura'] = false;
            }
        }

        return $rows;
    }

    public function getEstadisticasSalidas($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT 
                COUNT(i.id) AS total_salidas,
                COALESCE(AVG(TIMESTAMPDIFF(SECOND, i.fecha_ingreso, i.fecha_salida)/60), 0) AS promedio_sla,
                SUM(CASE WHEN TIMESTAMPDIFF(SECOND, i.fecha_ingreso, i.fecha_salida)/60 <= 20 THEN 1 ELSE 0 END) AS cumplen_meta,
                SUM(CASE WHEN i.prioridad IS NOT NULL AND i.prioridad != 'NORMAL' THEN 1 ELSE 0 END) AS preferenciales
            FROM ingresos i
            WHERE i.fecha_salida IS NOT NULL
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_salida >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_salida <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total_salidas' => intval($res['total_salidas'] ?? 0),
            'promedio_sla'  => round(floatval($res['promedio_sla'] ?? 0), 1),
            'cumplen_meta'  => intval($res['cumplen_meta'] ?? 0),
            'porcentaje_cumplimiento' => intval($res['total_salidas'] ?? 0) > 0 ? round((intval($res['cumplen_meta'] ?? 0) / intval($res['total_salidas'])) * 100) : 0,
            'preferenciales' => intval($res['preferenciales'] ?? 0)
        ];
    }

    public function countReporteSalidas($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "SELECT COUNT(i.id) FROM ingresos i WHERE i.fecha_salida IS NOT NULL";
        $params = [];
        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_salida >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_salida <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?: 0);
    }

    public function getReporteSalidas($fecha_desde = null, $fecha_hasta = null, $sede_id = null, $limit = null, $offset = null) {
        $sql = "
            SELECT i.id AS id, i.ticket_numero, i.fecha_ingreso, i.fecha_salida, i.observacion_salida, i.estado_tramite, i.prioridad,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   u_ori.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u_ori ON i.orientador_id = u_ori.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE i.fecha_salida IS NOT NULL
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_salida >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_salida <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_salida DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset !== null && $offset > 0) {
                $sql .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $horarioInfo = get_horario_apertura_dia(
                $r['fecha_ingreso'], 
                $r['hora_apertura_semana'] ?? '07:00:00', 
                $r['hora_apertura_festivos'] ?? '08:00:00'
            );
            $r['hora_apertura_oficial']  = $horarioInfo['hora'];
            $r['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
            $r['label_horario_apertura'] = $horarioInfo['label'];
            $r['es_festivo_o_finde']     = $horarioInfo['es_festivo_o_finde'];

            $fIngreso  = new DateTime($r['fecha_ingreso']);
            $fSalida   = new DateTime($r['fecha_salida']);
            $fApertura = new DateTime($fIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            $diffTotalSeg = max(0, $fSalida->getTimestamp() - $fIngreso->getTimestamp());
            $r['minutos_totales_reloj'] = round($diffTotalSeg / 60, 1);

            if ($fIngreso < $fApertura) {
                $diffFilaSeg = max(0, min($fSalida->getTimestamp(), $fApertura->getTimestamp()) - $fIngreso->getTimestamp());
                $r['tiempo_fila_externa_min'] = round($diffFilaSeg / 60, 1);
                
                $diffFarmaciaSeg = max(0, $fSalida->getTimestamp() - $fApertura->getTimestamp());
                $r['minutos_totales_sla'] = round($diffFarmaciaSeg / 60, 1);
                $r['ingresado_antes_apertura'] = true;
            } else {
                $r['tiempo_fila_externa_min'] = 0;
                $r['minutos_totales_sla'] = $r['minutos_totales_reloj'];
                $r['ingresado_antes_apertura'] = false;
            }
        }

        return $rows;
    }

    public function countReportePacientes($fecha_desde = null, $fecha_hasta = null, $eps = null, $estado = null, $sede_id = null) {
        $sql = "SELECT COUNT(i.id) FROM ingresos i JOIN pacientes p ON i.paciente_id = p.id WHERE 1=1";
        $params = [];
        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($eps)) {
            $sql .= " AND p.eps_nombre = :eps";
            $params[':eps'] = $eps;
        }
        if (!empty($estado)) {
            $sql .= " AND i.estado_tramite = :estado";
            $params[':estado'] = $estado;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?: 0);
    }

    public function getReportePacientes($fecha_desde = null, $fecha_hasta = null, $eps = null, $estado = null, $sede_id = null, $limit = null, $offset = null) {
        $sql = "
            SELECT p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono, p.email,
                   i.id, i.ticket_numero, i.fecha_ingreso, i.fecha_llamado_entrega, i.fecha_salida, i.estado_tramite, i.firma_paciente_url,
                   u.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($eps)) {
            $sql .= " AND p.eps_nombre = :eps";
            $params[':eps'] = $eps;
        }
        if (!empty($estado)) {
            $sql .= " AND i.estado_tramite = :estado";
            $params[':estado'] = $estado;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset !== null && $offset > 0) {
                $sql .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ahora = new DateTime();
        foreach ($results as &$r) {
            if (!empty($r['fecha_ingreso'])) {
                $fIngreso = new DateTime($r['fecha_ingreso']);
                $fFinal   = !empty($r['fecha_salida']) ? new DateTime($r['fecha_salida']) : $ahora;
                $diffSeg  = max(0, $fFinal->getTimestamp() - $fIngreso->getTimestamp());
                $r['minutos_totales_sla'] = round($diffSeg / 60, 1);
            } else {
                $r['minutos_totales_sla'] = 0;
            }
        }

        return $results;
    }

    public function countReporteTiemposSLA($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "SELECT COUNT(i.id) FROM ingresos i WHERE 1=1";
        $params = [];
        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?: 0);
    }

    public function getReporteTiemposSLA($fecha_desde = null, $fecha_hasta = null, $sede_id = null, $limit = null, $offset = null) {
        $sql = "
            SELECT i.id AS id, i.ticket_numero, i.fecha_ingreso, i.fecha_llamado_entrega, COALESCE(i.fecha_salida, i.updated_at) AS fecha_finalizacion, i.fecha_salida, i.salida_por_user_id, i.observacion_salida, i.estado_tramite, i.modulo_entrega_asignado, i.prioridad,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   u.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:20:00') AS hora_apertura_oficial
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE 1=1
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset !== null && $offset > 0) {
                $sql .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ahora = new DateTime();
        foreach ($results as &$r) {
            try {
                $fechaIngreso = new DateTime($r['fecha_ingreso']);
                $fechaFinal   = !empty($r['fecha_finalizacion']) ? new DateTime($r['fecha_finalizacion']) : $ahora;
                
                // Horario dinámico: L-V 7:00 AM | Sábados, Domingos y Festivos 8:00 AM
                $horarioInfo = get_horario_apertura_dia($r['fecha_ingreso'], $r['hora_apertura_oficial'] ?? '07:00:00', '08:00:00');
                $r['hora_apertura_oficial']  = $horarioInfo['hora'];
                $r['tipo_dia_atencion']      = $horarioInfo['tipo_dia'];
                $r['label_horario_apertura'] = $horarioInfo['label'];
                $r['es_festivo_o_finde']     = $horarioInfo['es_festivo_o_finde'];

                $fechaApertura = new DateTime($fechaIngreso->format('Y-m-d') . ' ' . $horarioInfo['hora']);
                
                $diffTotalSeg = max(0, $fechaFinal->getTimestamp() - $fechaIngreso->getTimestamp());
                $r['tiempo_total_minutos'] = round($diffTotalSeg / 60, 1);
                
                if ($fechaIngreso < $fechaApertura) {
                    $diffFilaSeg = max(0, min($fechaFinal->getTimestamp(), $fechaApertura->getTimestamp()) - $fechaIngreso->getTimestamp());
                    $r['tiempo_fila_externa_min'] = round($diffFilaSeg / 60, 1);
                    
                    $diffFarmaciaSeg = max(0, $fechaFinal->getTimestamp() - $fechaApertura->getTimestamp());
                    $r['tiempo_tramite_farmacia_min'] = round($diffFarmaciaSeg / 60, 1);
                    $r['ingresado_antes_apertura'] = true;
                } else {
                    $r['tiempo_fila_externa_min'] = 0;
                    $r['tiempo_tramite_farmacia_min'] = $r['tiempo_total_minutos'];
                    $r['ingresado_antes_apertura'] = false;
                }
            } catch (Exception $ex) {
                $r['tiempo_total_minutos'] = 0;
                $r['tiempo_fila_externa_min'] = 0;
                $r['tiempo_tramite_farmacia_min'] = 0;
                $r['ingresado_antes_apertura'] = false;
            }
        }

        return $results;
    }

    public function countReportePendientes($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT COUNT(i.id)
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            WHERE (
                i.estado_tramite IN ('TRANSCRITO_PENDIENTE', 'SIN_STOCK') 
                OR (i.observaciones_pendientes IS NOT NULL AND TRIM(i.observaciones_pendientes) != '')
                OR (
                    i.faltantes_alistamiento IS NOT NULL 
                    AND TRIM(i.faltantes_alistamiento) != ''
                    AND i.faltantes_alistamiento NOT LIKE '%VERIFICACIÓN EXITOSA%'
                    AND i.faltantes_alistamiento NOT LIKE '%100% Coincide%'
                    AND i.faltantes_alistamiento NOT LIKE '%No hay novedades%'
                )
            )
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?: 0);
    }

    public function getEstadisticasTiemposSLA($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT 
                COUNT(i.id) AS total_atendidos,
                COALESCE(AVG(TIMESTAMPDIFF(SECOND, i.fecha_ingreso, COALESCE(i.fecha_salida, i.updated_at))/60), 0) AS promedio_farmacia,
                SUM(CASE WHEN TIMESTAMPDIFF(SECOND, i.fecha_ingreso, COALESCE(i.fecha_salida, i.updated_at))/60 <= 30 THEN 1 ELSE 0 END) AS cumplen_meta
            FROM ingresos i
            WHERE 1=1
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($res['total_atendidos'] ?? 0);
        $cumplen = intval($res['cumplen_meta'] ?? 0);
        return [
            'total_atendidos'  => $total,
            'promedio_farmacia'=> round(floatval($res['promedio_farmacia'] ?? 0), 1),
            'promedio_fila'    => 0,
            'cumplen_meta'     => $cumplen,
            'porcentaje_cumplimiento' => $total > 0 ? round(($cumplen / $total) * 100) : 0
        ];
    }

    public function getReportePendientes($fecha_desde = null, $fecha_hasta = null, $sede_id = null, $limit = null, $offset = null) {
        $sql = "
            SELECT p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   i.id, i.ticket_numero, i.fecha_ingreso, i.updated_at, i.estado_tramite, i.faltantes_alistamiento, i.observaciones_pendientes,
                   u.nombre_completo AS orientador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE (
                i.estado_tramite IN ('TRANSCRITO_PENDIENTE', 'SIN_STOCK') 
                OR (i.observaciones_pendientes IS NOT NULL AND TRIM(i.observaciones_pendientes) != '')
                OR (
                    i.faltantes_alistamiento IS NOT NULL 
                    AND TRIM(i.faltantes_alistamiento) != ''
                    AND i.faltantes_alistamiento NOT LIKE '%VERIFICACIÓN EXITOSA%'
                    AND i.faltantes_alistamiento NOT LIKE '%100% Coincide%'
                    AND i.faltantes_alistamiento NOT LIKE '%No hay novedades%'
                )
            )
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset !== null && $offset > 0) {
                $sql .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReporteProductividad($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT u.nombre_completo, r.nombre AS rol, COUNT(i.id) AS total_ingresos,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            LEFT JOIN sedes s ON u.sede_id = s.id
            LEFT JOIN ingresos i ON i.orientador_id = u.id
        ";
        $params = [];
        $where = [];

        if (!empty($fecha_desde)) {
            $where[] = "i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $where[] = "i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $where[] = "(i.sede_id = :sede_id_ing OR u.sede_id = :sede_id_usr)";
            $params[':sede_id_ing'] = $sede_id;
            $params[':sede_id_usr'] = $sede_id;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY u.id, u.nombre_completo, r.nombre, s.nombre_sede ORDER BY total_ingresos DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReportePorEPS($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT p.eps_nombre, COUNT(i.id) AS total_pacientes
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id)) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " GROUP BY p.eps_nombre ORDER BY total_pacientes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReporteTicketsPorDiaYSede($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT 
                DATE(i.fecha_ingreso) AS fecha,
                i.sede_id,
                COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                COALESCE(s.codigo_sede, 'PRD') AS codigo_sede,
                COUNT(i.id) AS total_creados,
                SUM(CASE WHEN i.fecha_salida IS NOT NULL THEN 1 ELSE 0 END) AS total_cerrados,
                SUM(CASE WHEN i.fecha_salida IS NULL AND i.estado_tramite != 'CANCELADO' THEN 1 ELSE 0 END) AS total_pendientes,
                SUM(CASE WHEN i.estado_tramite = 'CANCELADO' THEN 1 ELSE 0 END) AS total_cancelados,
                COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE 1=1
        ";

        $params = [];
        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id) && $sede_id !== 'TODAS') {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " GROUP BY DATE(i.fecha_ingreso), i.sede_id, COALESCE(s.nombre_sede, 'Sede Principal'), COALESCE(s.codigo_sede, 'PRD'),
                           COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00'),
                           COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00')
                  ORDER BY fecha DESC, nombre_sede ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para calcular el promedio exacto normalizado por fecha y sede
        $sqlSLA = "
            SELECT DATE(i.fecha_ingreso) AS fecha, i.sede_id, i.fecha_ingreso, i.fecha_salida,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE i.fecha_salida IS NOT NULL
        ";
        $paramsSLA = [];
        if (!empty($fecha_desde)) {
            $sqlSLA .= " AND i.fecha_ingreso >= :f_desde";
            $paramsSLA[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sqlSLA .= " AND i.fecha_ingreso <= :f_hasta";
            $paramsSLA[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id) && $sede_id !== 'TODAS') {
            $sqlSLA .= " AND i.sede_id = :sede_id";
            $paramsSLA[':sede_id'] = $sede_id;
        }

        $stmtSLA = $this->db->prepare($sqlSLA);
        $stmtSLA->execute($paramsSLA);
        $tiquetesCerrados = $stmtSLA->fetchAll(PDO::FETCH_ASSOC);

        $acumuladoDiaSede = [];
        foreach ($tiquetesCerrados as $tc) {
            $k = $tc['fecha'] . '_' . ($tc['sede_id'] ?? 0);
            if (!isset($acumuladoDiaSede[$k])) {
                $acumuladoDiaSede[$k] = ['suma' => 0, 'count' => 0];
            }
            $horarioInfo = get_horario_apertura_dia($tc['fecha_ingreso'], $tc['hora_apertura_semana'], $tc['hora_apertura_festivos']);
            $fIng = new DateTime($tc['fecha_ingreso']);
            $fSal = new DateTime($tc['fecha_salida']);
            $fAp  = new DateTime($fIng->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            if ($fIng < $fAp) {
                $diffSeg = max(0, $fSal->getTimestamp() - $fAp->getTimestamp());
            } else {
                $diffSeg = max(0, $fSal->getTimestamp() - $fIng->getTimestamp());
            }
            $acumuladoDiaSede[$k]['suma'] += ($diffSeg / 60);
            $acumuladoDiaSede[$k]['count'] += 1;
        }

        foreach ($rows as &$r) {
            $creados = (int)$r['total_creados'];
            $cerrados = (int)$r['total_cerrados'];
            $r['porcentaje_cierre'] = $creados > 0 ? round(($cerrados / $creados) * 100, 1) : 0;
            
            $k = $r['fecha'] . '_' . ($r['sede_id'] ?? 0);
            if (isset($acumuladoDiaSede[$k]) && $acumuladoDiaSede[$k]['count'] > 0) {
                $r['avg_minutos_sla'] = round($acumuladoDiaSede[$k]['suma'] / $acumuladoDiaSede[$k]['count'], 1);
            } else {
                $r['avg_minutos_sla'] = null;
            }

            // Información del tipo de día
            $horarioInfo = get_horario_apertura_dia($r['fecha'], $r['hora_apertura_semana'], $r['hora_apertura_festivos']);
            $r['tipo_dia'] = $horarioInfo['tipo_dia'];
            $r['label_horario'] = $horarioInfo['label'];
            $r['es_festivo_o_finde'] = $horarioInfo['es_festivo_o_finde'];
        }

        return $rows;
    }

    public function getConsolidadoTicketsSedes($fecha_desde = null, $fecha_hasta = null, $sede_id = null) {
        $sql = "
            SELECT 
                COALESCE(s.id, 0) AS sede_id,
                COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                COALESCE(s.codigo_sede, 'PRD') AS codigo_sede,
                COUNT(i.id) AS total_creados,
                SUM(CASE WHEN i.fecha_salida IS NOT NULL THEN 1 ELSE 0 END) AS total_cerrados,
                SUM(CASE WHEN i.fecha_salida IS NULL AND i.estado_tramite != 'CANCELADO' THEN 1 ELSE 0 END) AS total_pendientes,
                SUM(CASE WHEN i.estado_tramite = 'CANCELADO' THEN 1 ELSE 0 END) AS total_cancelados,
                COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE 1=1
        ";

        $params = [];
        if (!empty($fecha_desde)) {
            $sql .= " AND i.fecha_ingreso >= :f_desde";
            $params[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND i.fecha_ingreso <= :f_hasta";
            $params[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id) && $sede_id !== 'TODAS') {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " GROUP BY COALESCE(s.id, 0), COALESCE(s.nombre_sede, 'Sede Principal'), COALESCE(s.codigo_sede, 'PRD'),
                           COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00'),
                           COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00')
                  ORDER BY total_creados DESC, nombre_sede ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para calcular el promedio de SLA normalizado exacto por sede (respetando hora apertura y festivos)
        $sqlSLA = "
            SELECT i.sede_id, i.fecha_ingreso, i.fecha_salida,
                   COALESCE(s.hora_apertura_atencion, ec.hora_apertura_atencion, '07:00:00') AS hora_apertura_semana,
                   COALESCE(s.hora_apertura_festivos, ec.hora_apertura_festivos, '08:00:00') AS hora_apertura_festivos
            FROM ingresos i
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresa_config ec ON ec.id = 1
            WHERE i.fecha_salida IS NOT NULL
        ";
        $paramsSLA = [];
        if (!empty($fecha_desde)) {
            $sqlSLA .= " AND i.fecha_ingreso >= :f_desde";
            $paramsSLA[':f_desde'] = strlen($fecha_desde) === 10 ? ($fecha_desde . ' 00:00:00') : $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sqlSLA .= " AND i.fecha_ingreso <= :f_hasta";
            $paramsSLA[':f_hasta'] = strlen($fecha_hasta) === 10 ? ($fecha_hasta . ' 23:59:59') : $fecha_hasta;
        }
        if (!empty($sede_id) && $sede_id !== 'TODAS') {
            $sqlSLA .= " AND i.sede_id = :sede_id";
            $paramsSLA[':sede_id'] = $sede_id;
        }

        $stmtSLA = $this->db->prepare($sqlSLA);
        $stmtSLA->execute($paramsSLA);
        $tiquetesCerrados = $stmtSLA->fetchAll(PDO::FETCH_ASSOC);

        $acumuladoSLA = [];
        foreach ($tiquetesCerrados as $tc) {
            $sid = $tc['sede_id'] ?? 0;
            if (!isset($acumuladoSLA[$sid])) {
                $acumuladoSLA[$sid] = ['suma' => 0, 'count' => 0];
            }
            $horarioInfo = get_horario_apertura_dia($tc['fecha_ingreso'], $tc['hora_apertura_semana'], $tc['hora_apertura_festivos']);
            $fIng = new DateTime($tc['fecha_ingreso']);
            $fSal = new DateTime($tc['fecha_salida']);
            $fAp  = new DateTime($fIng->format('Y-m-d') . ' ' . $horarioInfo['hora']);

            if ($fIng < $fAp) {
                $diffSeg = max(0, $fSal->getTimestamp() - $fAp->getTimestamp());
            } else {
                $diffSeg = max(0, $fSal->getTimestamp() - $fIng->getTimestamp());
            }
            $acumuladoSLA[$sid]['suma'] += ($diffSeg / 60);
            $acumuladoSLA[$sid]['count'] += 1;
        }

        foreach ($rows as &$r) {
            $creados = (int)$r['total_creados'];
            $cerrados = (int)$r['total_cerrados'];
            $r['porcentaje_cierre'] = $creados > 0 ? round(($cerrados / $creados) * 100, 1) : 0;
            
            $sid = $r['sede_id'] ?? 0;
            if (isset($acumuladoSLA[$sid]) && $acumuladoSLA[$sid]['count'] > 0) {
                $r['avg_minutos_sla'] = round($acumuladoSLA[$sid]['suma'] / $acumuladoSLA[$sid]['count'], 1);
            } else {
                $r['avg_minutos_sla'] = null;
            }
        }

        return $rows;
    }

    public function getDetalleTicketsDiaSede($fecha, $sede_id = null) {
        $sql = "
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   u_ori.nombre_completo AS orientador_nombre,
                   u_sal.nombre_completo AS usuario_salida_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u_ori ON i.orientador_id = u_ori.id
            LEFT JOIN usuarios u_sal ON i.salida_por_user_id = u_sal.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE DATE(i.fecha_ingreso) = :fecha
        ";
        $params = [':fecha' => $fecha];

        if (!empty($sede_id) && $sede_id !== 'TODAS') {
            $sql .= " AND (i.sede_id = :sede_id OR (i.sede_id IS NULL AND :sede_id_null = 1))";
            $params[':sede_id'] = $sede_id;
            $params[':sede_id_null'] = $sede_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ahora = new DateTime();
        foreach ($rows as &$r) {
            $fIngreso = new DateTime($r['fecha_ingreso']);
            $fSalida  = !empty($r['fecha_salida']) ? new DateTime($r['fecha_salida']) : $ahora;
            $diffSeg  = max(0, $fSalida->getTimestamp() - $fIngreso->getTimestamp());
            $r['minutos_sla'] = round($diffSeg / 60, 1);
            $r['esta_cerrado'] = !empty($r['fecha_salida']);
        }

        return $rows;
    }

    public function encolarDocumentosParaIA($ingreso_id, $documento_id = null) {
        if ($documento_id) {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO ingreso_formulas_ia (ingreso_id, documento_id, estado_ia, created_at)
                VALUES (:ingreso_id, :doc_id, 'PENDIENTE', NOW())
            ");
            return $stmt->execute([':ingreso_id' => $ingreso_id, ':doc_id' => $documento_id]);
        }

        $stmtDocs = $this->db->prepare("
            SELECT id FROM ingreso_documentos 
            WHERE ingreso_id = :ingreso_id 
              AND (tipo_documento IN ('ORDEN_MEDICA', 'MIPRES') OR nombre_original LIKE '%formula%' OR nombre_original LIKE '%orden%')
        ");
        $stmtDocs->execute([':ingreso_id' => $ingreso_id]);
        $docs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsert = $this->db->prepare("
            INSERT INTO ingreso_formulas_ia (ingreso_id, documento_id, estado_ia, created_at)
            SELECT :ingreso_id, :doc_id, 'PENDIENTE', NOW()
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM ingreso_formulas_ia WHERE ingreso_id = :ingreso_id2 AND documento_id = :doc_id2
            )
        ");

        foreach ($docs as $d) {
            $stmtInsert->execute([
                ':ingreso_id'  => $ingreso_id,
                ':doc_id'      => $d['id'],
                ':ingreso_id2' => $ingreso_id,
                ':doc_id2'     => $d['id']
            ]);
        }
        return true;
    }

    public function getColaIA($estado = '', $sede_id = null, $limit = 50) {
        $sql = "
            SELECT ifa.*, 
                   i.ticket_numero, i.fecha_ingreso, i.estado_tramite, i.prioridad, i.ips_remite,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   d.ruta_archivo, d.nombre_original, d.tipo_documento AS tipo_doc_adjunto,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingreso_formulas_ia ifa
            JOIN ingresos i ON ifa.ingreso_id = i.id
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN ingreso_documentos d ON ifa.documento_id = d.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($estado)) {
            $sql .= " AND ifa.estado_ia = :estado";
            $params[':estado'] = $estado;
        } else {
            $sql .= " AND ifa.estado_ia NOT IN ('DISPENSADO', 'CANCELADO') AND i.estado_tramite NOT IN ('ENTREGADO', 'CANCELADO')";
        }

        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
            $params[':sede_id'] = intval($sede_id);
        }

        $sql .= " ORDER BY (ifa.estado_ia = 'PENDIENTE') DESC, ifa.id DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelarItemColaIA($id_ia, $motivo = 'Cancelado desde la cola por el usuario') {
        $stmt = $this->db->prepare("
            UPDATE ingreso_formulas_ia SET
                estado_ia = 'CANCELADO',
                observaciones = :obs,
                procesado_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'  => intval($id_ia),
            ':obs' => trim($motivo)
        ]);
    }

    public function reactivarItemColaIA($id_ia) {
        $stmt = $this->db->prepare("
            UPDATE ingreso_formulas_ia SET
                estado_ia = 'PENDIENTE',
                observaciones = 'Reactivado en cola',
                procesado_at = NULL
            WHERE id = :id
        ");
        return $stmt->execute([':id' => intval($id_ia)]);
    }

    public function eliminarItemColaIA($id_ia) {
        $stmt = $this->db->prepare("DELETE FROM ingreso_formulas_ia WHERE id = :id");
        return $stmt->execute([':id' => intval($id_ia)]);
    }

    public function actualizarResultadoIA($id_ia, $estado, $datos_json, $tiempo = 0, $motor = 'SISPAM Vision AI', $obs = '') {
        $totalMed = 0;
        if (!empty($datos_json)) {
            $decoded = is_string($datos_json) ? json_decode($datos_json, true) : $datos_json;
            if (isset($decoded['medicamentos']) && is_array($decoded['medicamentos'])) {
                $totalMed = count($decoded['medicamentos']);
            }
            if (is_array($datos_json)) {
                $datos_json = json_encode($datos_json, JSON_UNESCAPED_UNICODE);
            }
        }

        $stmt = $this->db->prepare("
            UPDATE ingreso_formulas_ia SET
                estado_ia = :estado,
                datos_extraidos_json = :datos_json,
                tiempo_segundos = :tiempo,
                motor_utilizado = :motor,
                total_medicamentos = :total_med,
                observaciones = :obs,
                procesado_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'         => intval($id_ia),
            ':estado'     => $estado,
            ':datos_json' => $datos_json,
            ':tiempo'     => floatval($tiempo),
            ':motor'      => $motor,
            ':total_med'  => intval($totalMed),
            ':obs'        => trim($obs)
        ]);
    }

    public function adjuntarDocumentoEscaneado($ingreso_id, $fileInfo, $tipo_doc = 'ORDEN_MEDICA') {
        $stmtI = $this->db->prepare("
            SELECT i.id, i.ticket_numero, p.tipo_documento, p.numero_documento
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            WHERE i.id = :id
        ");
        $stmtI->execute([':id' => intval($ingreso_id)]);
        $ingreso = $stmtI->fetch(PDO::FETCH_ASSOC);
        if (!$ingreso) return false;

        $fecha_folder = date('dmy');
        $folder_name = $ingreso['numero_documento'] . '_' . $fecha_folder;
        $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/' . $folder_name . '/';
        $full_dir = public_path() . '/' . $rel_dir;

        if (!file_exists($full_dir)) {
            mkdir($full_dir, 0755, true);
        }

        $nombreOriginal = $fileInfo instanceof UploadedFile
            ? $fileInfo->getClientOriginalName()
            : ($fileInfo['name'] ?? 'formula_escaneada.pdf');
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (empty($ext)) $ext = 'jpg';

        $clean_filename = strtolower($tipo_doc) . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target_file = $full_dir . $clean_filename;
        $ruta_relativa = $rel_dir . $clean_filename;

        $guardado = false;
        if ($fileInfo instanceof UploadedFile && $fileInfo->isValid()) {
            $guardado = $this->moverArchivo($fileInfo, $target_file);
        } elseif (isset($fileInfo['base64_data'])) {
            $data = $fileInfo['base64_data'];
            if (preg_match('/^data:(image\/\w+|application\/pdf);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
            }
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                $guardado = file_put_contents($target_file, $decoded) !== false;
            }
        } elseif (isset($fileInfo['file_path']) && file_exists($fileInfo['file_path'])) {
            $guardado = copy($fileInfo['file_path'], $target_file);
        }

        if ($guardado) {
            $stmtDoc = $this->db->prepare("
                INSERT INTO ingreso_documentos (ingreso_id, tipo_documento, ruta_archivo, nombre_original)
                VALUES (:ingreso_id, :tipo_doc, :ruta, :nombre_orig)
            ");
            $stmtDoc->execute([
                ':ingreso_id'   => $ingreso_id,
                ':tipo_doc'     => $tipo_doc,
                ':ruta'         => $ruta_relativa,
                ':nombre_orig'  => $nombreOriginal
            ]);
            $docId = $this->db->lastInsertId();

            // Encolar de inmediato para IA
            $this->encolarDocumentosParaIA($ingreso_id, $docId);

            return [
                'status'        => 'ok',
                'documento_id'  => $docId,
                'ruta_archivo'  => $ruta_relativa,
                'ticket_numero' => $ingreso['ticket_numero']
            ];
        }

        return ['status' => 'error', 'message' => 'No se pudo almacenar el archivo digitalizado.'];
    }

    public function getPacienteIdByIngreso($ingresoId) {
        $stmt = $this->db->prepare("SELECT paciente_id FROM ingresos WHERE id = :id");
        $stmt->execute([':id' => intval($ingresoId)]);
        return intval($stmt->fetchColumn() ?: 0);
    }

    public function getListaTrabajoEscaner($sede_id = null, $limite = 35) {
        $sql = "
            SELECT i.id AS ingreso_id, i.ticket_numero, i.estado_tramite, i.prioridad, i.created_at, i.ips_remite,
                   p.id AS paciente_id, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   (SELECT COUNT(*) FROM ingreso_documentos d WHERE d.ingreso_id = i.id) AS total_docs,
                   (SELECT COUNT(*) FROM ingreso_formulas_ia ifa WHERE ifa.ingreso_id = i.id) AS total_ia,
                   (SELECT ifa.estado_ia FROM ingreso_formulas_ia ifa WHERE ifa.ingreso_id = i.id ORDER BY ifa.id DESC LIMIT 1) AS ultimo_estado_ia
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite NOT IN ('ENTREGADO', 'CANCELADO', 'DISPENSADO')
              AND i.fecha_salida IS NULL
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id IS NULL OR i.sede_id = 0 OR i.sede_id = :sede_id)";
            $params[':sede_id'] = intval($sede_id);
        }
        $sql .= " ORDER BY (total_docs = 0) DESC, i.id DESC LIMIT " . intval($limite);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarParaEscaner($termino, $sede_id = null) {
        $termino = trim($termino);
        if (empty($termino)) return [];

        $sql = "
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   p.ciudad_residencia, p.direccion_residencia,
                   u.nombre_completo AS orientador_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE (TRIM(i.ticket_numero) = :termExacto 
                   OR i.ticket_numero LIKE :termLike 
                   OR TRIM(p.numero_documento) = :termDoc 
                   OR p.numero_documento LIKE :termDocLike
                   OR p.nombres LIKE :termNombreLike
                   OR p.apellidos LIKE :termApellidoLike
                   OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :termNombreCompLike)
              AND i.estado_tramite NOT IN ('ENTREGADO', 'CANCELADO', 'DISPENSADO')
              AND i.fecha_salida IS NULL
        ";
        $params = [
            ':termExacto'         => $termino,
            ':termLike'           => '%' . $termino . '%',
            ':termDoc'            => $termino,
            ':termDocLike'        => '%' . $termino . '%',
            ':termNombreLike'     => '%' . $termino . '%',
            ':termApellidoLike'   => '%' . $termino . '%',
            ':termNombreCompLike' => '%' . $termino . '%'
        ];

        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id IS NULL OR i.sede_id = 0 OR i.sede_id = :sede_id)";
            $params[':sede_id'] = $sede_id;
        }

        $sql .= " ORDER BY i.id DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$ingreso) {
            $stmtDocs = $this->db->prepare("SELECT * FROM ingreso_documentos WHERE ingreso_id = :id");
            $stmtDocs->execute([':id' => $ingreso['id']]);
            $ingreso['documentos'] = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ingreso);

        return $resultados;
    }

    public function obtenerModuloEquitativo($sede_id = null) {
        $modulos = $this->modulosActivos($sede_id);
        
        if (empty($modulos)) {
            return ['modulo' => 'MÓDULO 1', 'cola_actual' => 0];
        }

        $sql = "
            SELECT modulo_entrega_asignado, COUNT(*) as total 
            FROM ingresos 
            WHERE estado_tramite = 'ALISTADO'
        ";
        $params = [];
        if (!empty($sede_id)) {
            $sql .= " AND sede_id = :sede_id";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " GROUP BY modulo_entrega_asignado";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $colas = [];
        foreach ($modulos as $m) {
            $mod = $m['nombre_modulo'] ?? $m['nombre'];
            $colas[$mod] = intval($filas[$mod] ?? 0);
        }

        $min_pacientes = min($colas);
        $modulos_menos_cargados = array_keys(array_filter($colas, fn($cant) => $cant === $min_pacientes));
        $modulo_seleccionado = $modulos_menos_cargados[array_rand($modulos_menos_cargados)];

        return [
            'modulo' => $modulo_seleccionado,
            'cola_actual' => $min_pacientes
        ];
    }

    public function guardarAlistamiento($ingreso_id, $pdf_file = null, $faltantes_text = '', $user_id = null, $modulo_entrega = 'AUTO') {
        $ingreso = $this->getById($ingreso_id);
        $sede_id = $ingreso['sede_id'] ?? (sesion('active_sede_id') ?? (sesion('sede_id') ?? 1));
        $ruta_pdf = $ingreso['pdf_alistamiento'] ?? null;

        if ($pdf_file && $pdf_file instanceof UploadedFile && $pdf_file->isValid()) {
            $rel_dir = 'assets/uploads/pacientes/' . $ingreso['tipo_documento'] . '_' . $ingreso['numero_documento'] . '/alistamientos/';
            $full_dir = public_path() . '/' . $rel_dir;
            if (!file_exists($full_dir)) {
                mkdir($full_dir, 0755, true);
            }
            $filename = 'alistamiento_' . $ingreso['ticket_numero'] . '_' . time() . '.pdf';
            if ($this->moverArchivo($pdf_file, $full_dir . $filename)) {
                $ruta_pdf = $rel_dir . $filename;
            }
        }

        if (empty($modulo_entrega) || strtoupper(trim($modulo_entrega)) === 'AUTO') {
            $bal = $this->obtenerModuloEquitativo($sede_id);
            $modulo_entrega = $bal['modulo'];
        }

        $stmt = $this->db->prepare("
            UPDATE ingresos SET 
                estado_tramite = 'ALISTADO', 
                pdf_alistamiento = :pdf, 
                faltantes_alistamiento = :faltantes,
                alistado_por_user_id = :user_id,
                fecha_alistado = NOW(),
                modulo_entrega_asignado = :modulo,
                locked_by_user_id = NULL,
                locked_at = NULL
            WHERE id = :id
        ");
        $result = $stmt->execute([
            ':pdf' => $ruta_pdf,
            ':faltantes' => $faltantes_text ?: null,
            ':user_id' => $user_id,
            ':modulo' => $modulo_entrega,
            ':id' => $ingreso_id
        ]);
        if ($result) {
            registrar_log_auditoria('ALISTAMIENTO', 'GUARDAR_ALISTAMIENTO', $ingreso_id, "Medicamentos alistados en estación de picking. Asignado a: {$modulo_entrega}. Tiquete: " . ($ingreso['ticket_numero'] ?? 'N/A'));
        }
        return $result ? $modulo_entrega : false;
    }
}
