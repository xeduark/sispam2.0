<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Modelo de Gestión de Plantillas de Extracción IA por IPS (Few-Shot & Layout Patterns)
 */
class IPSPlantilla {
    private $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    public function getPlantillas($filtros = []) {
        $sql = "SELECT * FROM ips_plantillas_ia WHERE 1=1";
        $params = [];

        if (isset($filtros['es_activa']) && $filtros['es_activa'] !== '') {
            $sql .= " AND es_activa = :activa";
            $params[':activa'] = intval($filtros['es_activa']);
        }

        if (!empty($filtros['buscar'])) {
            $q = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (nombre_ips LIKE :q1 OR palabras_clave_detector LIKE :q2 OR ciudad LIKE :q3 OR codigo_habilitacion LIKE :q4)";
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
        }

        $sql .= " ORDER BY total_formulas_procesadas DESC, nombre_ips ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlantillaById($id) {
        $stmt = $this->db->prepare("SELECT * FROM ips_plantillas_ia WHERE id = :id");
        $stmt->execute([':id' => intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarPlantilla($datos, $fileMuestra = null) {
        $id          = intval($datos['id'] ?? 0);
        $nombre      = trim($datos['nombre_ips'] ?? '');
        $codHabilit  = trim($datos['codigo_habilitacion'] ?? '');
        $ciudad      = trim($datos['ciudad'] ?? 'MEDELLIN');
        $keywords    = trim($datos['palabras_clave_detector'] ?? '');
        $patron      = trim($datos['patron_codigos'] ?? 'MX');
        $instrucc    = trim($datos['instrucciones_layout'] ?? '');
        $ejemploJson = trim($datos['ejemplo_extraido_json'] ?? '');
        $activa      = isset($datos['es_activa']) ? intval($datos['es_activa']) : 1;

        if (empty($nombre) || empty($keywords)) {
            throw new Exception("El nombre de la IPS y las palabras clave de detección son obligatorios.");
        }

        // Manejo de archivo de muestra
        $rutaMuestra = $datos['imagen_muestra_url_actual'] ?? null;
        if (!empty($fileMuestra['tmp_name']) && is_uploaded_file($fileMuestra['tmp_name'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/ips_plantillas/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($fileMuestra['name'], PATHINFO_EXTENSION));
            $nombreArchivo = 'muestra_ips_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = $uploadDir . $nombreArchivo;
            if (move_uploaded_file($fileMuestra['tmp_name'], $destino)) {
                $rutaMuestra = 'assets/uploads/ips_plantillas/' . $nombreArchivo;
            }
        }

        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE ips_plantillas_ia SET
                    nombre_ips = :nombre,
                    codigo_habilitacion = :cod,
                    ciudad = :ciudad,
                    palabras_clave_detector = :keywords,
                    patron_codigos = :patron,
                    instrucciones_layout = :instrucc,
                    ejemplo_extraido_json = :ejemplo,
                    imagen_muestra_url = COALESCE(:muestra, imagen_muestra_url),
                    es_activa = :activa
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id'       => $id,
                ':nombre'   => $nombre,
                ':cod'      => $codHabilit,
                ':ciudad'   => $ciudad,
                ':keywords' => $keywords,
                ':patron'   => $patron,
                ':instrucc' => $instrucc,
                ':ejemplo'  => $ejemploJson,
                ':muestra'  => $rutaMuestra,
                ':activa'   => $activa
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO ips_plantillas_ia
                (empresa_id, nombre_ips, codigo_habilitacion, ciudad, palabras_clave_detector, patron_codigos, instrucciones_layout, ejemplo_extraido_json, imagen_muestra_url, es_activa)
                VALUES
                (1, :nombre, :cod, :ciudad, :keywords, :patron, :instrucc, :ejemplo, :muestra, :activa)
            ");
            return $stmt->execute([
                ':nombre'   => $nombre,
                ':cod'      => $codHabilit,
                ':ciudad'   => $ciudad,
                ':keywords' => $keywords,
                ':patron'   => $patron,
                ':instrucc' => $instrucc,
                ':ejemplo'  => $ejemploJson,
                ':muestra'  => $rutaMuestra,
                ':activa'   => $activa
            ]);
        }
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->db->prepare("UPDATE ips_plantillas_ia SET es_activa = :estado WHERE id = :id");
        return $stmt->execute([
            ':estado' => intval($estado),
            ':id'     => intval($id)
        ]);
    }

    public function eliminarPlantilla($id) {
        $stmt = $this->db->prepare("DELETE FROM ips_plantillas_ia WHERE id = :id");
        return $stmt->execute([':id' => intval($id)]);
    }

    public function incrementarContador($id) {
        $stmt = $this->db->prepare("UPDATE ips_plantillas_ia SET total_formulas_procesadas = total_formulas_procesadas + 1 WHERE id = :id");
        return $stmt->execute([':id' => intval($id)]);
    }

    /**
     * Auto-detecta si el texto o encabezado de la fórmula coincide con alguna IPS registrada
     */
    public function detectarPlantillaPorTexto($texto) {
        if (empty($texto)) return null;

        $plantillas = $this->getPlantillas(['es_activa' => 1]);
        $textoNorm = mb_strtoupper($texto, 'UTF-8');

        foreach ($plantillas as $pl) {
            $palabras = explode(',', $pl['palabras_clave_detector']);
            foreach ($palabras as $p) {
                $term = trim(mb_strtoupper($p, 'UTF-8'));
                if (!empty($term) && mb_strpos($textoNorm, $term) !== false) {
                    return $pl;
                }
            }
        }
        return null;
    }
}