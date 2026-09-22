<?php

namespace App\Models;

use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Modelo de Gestión Integral de Inventarios Farmacéuticos (Normativa Colombiana Res. 1403/2007)
 */
class Inventario {
    private $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    // =========================================================================
    // 1. GESTIÓN DE BODEGAS
    // =========================================================================
    // 1. GESTIÓN DE BODEGAS (CENTRAL, SEDES, VENTANILLAS)
    // =========================================================================

    public function getBodegas($sede_id = null, $solo_activas = true) {
        $sql = "
            SELECT b.*, 
                   COALESCE(s.nombre_sede, 'Almacén Central Principal') AS nombre_sede,
                   s.codigo_sede,
                   u.nombre_completo AS responsable_nombre,
                   (SELECT COUNT(DISTINCT il.producto_id) FROM inventario_lotes il WHERE il.bodega_id = b.id AND il.cantidad_actual > 0) AS total_productos_distintos,
                   (SELECT COALESCE(SUM(il.cantidad_actual), 0) FROM inventario_lotes il WHERE il.bodega_id = b.id) AS total_unidades_stock
            FROM bodegas b
            LEFT JOIN sedes s ON b.sede_id = s.id
            LEFT JOIN usuarios u ON b.responsable_user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if ($solo_activas) {
            $sql .= " AND b.es_activa = 1";
        }
        if (!empty($sede_id)) {
            $sql .= " AND (b.sede_id = :sede_id OR b.sede_id IS NULL OR b.tipo_bodega = 'PRINCIPAL')";
            $params[':sede_id'] = $sede_id;
        }
        $sql .= " ORDER BY (b.tipo_bodega = 'PRINCIPAL') DESC, b.nombre_bodega ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBodegasConDetalles($filtros = []) {
        $sql = "
            SELECT b.*, 
                   COALESCE(s.nombre_sede, 'Almacén Central Principal') AS nombre_sede,
                   s.codigo_sede,
                   u.nombre_completo AS responsable_nombre,
                   (SELECT COUNT(DISTINCT il.producto_id) FROM inventario_lotes il WHERE il.bodega_id = b.id AND il.cantidad_actual > 0) AS total_productos_distintos,
                   (SELECT COALESCE(SUM(il.cantidad_actual), 0) FROM inventario_lotes il WHERE il.bodega_id = b.id) AS total_unidades_stock,
                   (SELECT COALESCE(SUM(il.cantidad_actual * il.costo_unitario), 0) FROM inventario_lotes il WHERE il.bodega_id = b.id) AS valor_total_inventario
            FROM bodegas b
            LEFT JOIN sedes s ON b.sede_id = s.id
            LEFT JOIN usuarios u ON b.responsable_user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if (isset($filtros['es_activa']) && $filtros['es_activa'] !== '') {
            $sql .= " AND b.es_activa = :es_activa";
            $params[':es_activa'] = intval($filtros['es_activa']);
        }
        if (!empty($filtros['sede_id'])) {
            $sql .= " AND b.sede_id = :sede_id";
            $params[':sede_id'] = intval($filtros['sede_id']);
        }
        if (!empty($filtros['tipo_bodega'])) {
            $sql .= " AND b.tipo_bodega = :tipo_bodega";
            $params[':tipo_bodega'] = $filtros['tipo_bodega'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (b.nombre_bodega LIKE :q1 OR b.codigo_bodega LIKE :q2 OR b.ubicacion_fisica LIKE :q3)";
            $qVal = '%' . trim($filtros['buscar']) . '%';
            $params[':q1'] = $qVal;
            $params[':q2'] = $qVal;
            $params[':q3'] = $qVal;
        }
        $sql .= " ORDER BY (b.tipo_bodega = 'PRINCIPAL') DESC, b.nombre_bodega ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBodegasParaUsuario($user_id = null, $active_sede_id = null) {
        $sql = "
            SELECT DISTINCT b.*, 
                   COALESCE(s.nombre_sede, 'Almacén Central Principal') AS nombre_sede,
                   s.codigo_sede,
                   u.nombre_completo AS responsable_nombre,
                   (SELECT COUNT(DISTINCT il.producto_id) FROM inventario_lotes il WHERE il.bodega_id = b.id AND il.cantidad_actual > 0) AS total_productos_distintos,
                   (SELECT COALESCE(SUM(il.cantidad_actual), 0) FROM inventario_lotes il WHERE il.bodega_id = b.id) AS total_unidades_stock
            FROM bodegas b
            LEFT JOIN sedes s ON b.sede_id = s.id
            LEFT JOIN usuarios u ON b.responsable_user_id = u.id
            LEFT JOIN usuario_sedes us ON us.sede_id = b.sede_id
            WHERE b.es_activa = 1
              AND (
                  b.tipo_bodega = 'PRINCIPAL' 
                  OR b.sede_id IS NULL
        ";
        
        $params = [];
        if (!empty($active_sede_id)) {
            $sql .= " OR b.sede_id = :active_sede ";
            $params[':active_sede'] = intval($active_sede_id);
        }
        if (!empty($user_id)) {
            $sql .= " OR us.usuario_id = :user_id ";
            $params[':user_id'] = intval($user_id);
        }
        
        $sql .= " ) ";
        
        if (!empty($active_sede_id)) {
            $sql .= " ORDER BY (b.sede_id = :active_sede_order) DESC, (b.tipo_bodega = 'PRINCIPAL') DESC, b.nombre_bodega ASC";
            $params[':active_sede_order'] = intval($active_sede_id);
        } else {
            $sql .= " ORDER BY (b.tipo_bodega = 'PRINCIPAL') DESC, b.nombre_bodega ASC";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($res)) {
            return $this->getBodegas(null, true);
        }
        return $res;
    }

    public function cambiarEstadoBodega($id, $nuevo_estado) {
        $stmt = $this->db->prepare("UPDATE bodegas SET es_activa = :estado WHERE id = :id");
        return $stmt->execute([
            ':estado' => intval($nuevo_estado),
            ':id'     => intval($id)
        ]);
    }

    public function eliminarBodega($id) {
        $stmtStock = $this->db->prepare("SELECT COALESCE(SUM(cantidad_actual), 0) AS stock FROM inventario_lotes WHERE bodega_id = :id");
        $stmtStock->execute([':id' => intval($id)]);
        $stock = intval($stmtStock->fetchColumn() ?: 0);
        if ($stock > 0) {
            throw new Exception("No se puede eliminar la bodega porque tiene {$stock} unidades en inventario. Primero traslade o liquide el stock.");
        }
        $stmtKardex = $this->db->prepare("SELECT COUNT(*) FROM movimientos_kardex WHERE bodega_id = :id");
        $stmtKardex->execute([':id' => intval($id)]);
        $movs = intval($stmtKardex->fetchColumn() ?: 0);
        if ($movs > 0) {
            return $this->cambiarEstadoBodega($id, 0);
        }
        $stmt = $this->db->prepare("DELETE FROM bodegas WHERE id = :id");
        return $stmt->execute([':id' => intval($id)]);
    }

    public function getBodegaById($id) {
        $stmt = $this->db->prepare("
            SELECT b.*, COALESCE(s.nombre_sede, 'Almacén Central Principal') AS nombre_sede
            FROM bodegas b
            LEFT JOIN sedes s ON b.sede_id = s.id
            WHERE b.id = :id
        ");
        $stmt->execute([':id' => intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarBodega($datos) {
        $id = intval($datos['id'] ?? 0);
        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE bodegas SET
                    sede_id = :sede_id,
                    codigo_bodega = :codigo_bodega,
                    nombre_bodega = :nombre_bodega,
                    tipo_bodega = :tipo_bodega,
                    responsable_user_id = :responsable_user_id,
                    ubicacion_fisica = :ubicacion_fisica,
                    es_activa = :es_activa
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id'                  => $id,
                ':sede_id'             => !empty($datos['sede_id']) ? intval($datos['sede_id']) : null,
                ':codigo_bodega'       => trim($datos['codigo_bodega']),
                ':nombre_bodega'       => trim($datos['nombre_bodega']),
                ':tipo_bodega'         => $datos['tipo_bodega'] ?? 'SATELITE_SEDE',
                ':responsable_user_id' => !empty($datos['responsable_user_id']) ? intval($datos['responsable_user_id']) : null,
                ':ubicacion_fisica'    => trim($datos['ubicacion_fisica'] ?? ''),
                ':es_activa'           => isset($datos['es_activa']) ? intval($datos['es_activa']) : 1
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO bodegas 
                (empresa_id, sede_id, codigo_bodega, nombre_bodega, tipo_bodega, responsable_user_id, ubicacion_fisica, es_activa)
                VALUES (1, :sede_id, :codigo_bodega, :nombre_bodega, :tipo_bodega, :responsable_user_id, :ubicacion_fisica, :es_activa)
            ");
            return $stmt->execute([
                ':sede_id'             => !empty($datos['sede_id']) ? intval($datos['sede_id']) : null,
                ':codigo_bodega'       => trim($datos['codigo_bodega']),
                ':nombre_bodega'       => trim($datos['nombre_bodega']),
                ':tipo_bodega'         => $datos['tipo_bodega'] ?? 'SATELITE_SEDE',
                ':responsable_user_id' => !empty($datos['responsable_user_id']) ? intval($datos['responsable_user_id']) : null,
                ':ubicacion_fisica'    => trim($datos['ubicacion_fisica'] ?? ''),
                ':es_activa'           => isset($datos['es_activa']) ? intval($datos['es_activa']) : 1
            ]);
        }
    }

    // =========================================================================
    // 2. CATÁLOGO MAESTRO DE MEDICAMENTOS (CUMS, INVIMA, ATC, SKU)
    // =========================================================================

    public function getProductosCount($filtros = []) {
        $sql = "SELECT COUNT(*) FROM productos_medicamentos p WHERE 1=1";
        $params = [];

        if (!empty($filtros['buscar'])) {
            $b = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombre_generico LIKE :b1 OR p.nombre_comercial LIKE :b2 OR p.codigo_sku LIKE :b3 OR p.codigo_cums LIKE :b4 OR p.registro_invima LIKE :b5 OR p.fabricante_laboratorio LIKE :b6 OR p.principio_activo LIKE :b7)";
            $params[':b1'] = $b;
            $params[':b2'] = $b;
            $params[':b3'] = $b;
            $params[':b4'] = $b;
            $params[':b5'] = $b;
            $params[':b6'] = $b;
            $params[':b7'] = $b;
        }

        if (!empty($filtros['cadena_frio'])) {
            $sql .= " AND p.requiere_cadena_frio = 1";
        }
        if (!empty($filtros['control_especial'])) {
            $sql .= " AND p.es_control_especial = 1";
        }
        if (!empty($filtros['alto_costo'])) {
            $sql .= " AND p.es_alto_costo = 1";
        }
        if (!empty($filtros['tipo_producto'])) {
            $sql .= " AND p.tipo_producto = :tipo_producto";
            $params[':tipo_producto'] = $filtros['tipo_producto'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado_activo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getOpcionesCatalogos() {
        $labs = $this->db->query("
            SELECT DISTINCT fabricante_laboratorio, COALESCE(laboratorio_nit, '') AS laboratorio_nit 
            FROM productos_medicamentos 
            WHERE fabricante_laboratorio IS NOT NULL AND fabricante_laboratorio != '' 
            ORDER BY fabricante_laboratorio ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $clases = $this->db->query("
            SELECT DISTINCT clase_terapeutica 
            FROM productos_medicamentos 
            WHERE clase_terapeutica IS NOT NULL AND clase_terapeutica != '' 
            ORDER BY clase_terapeutica ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        $formas = $this->db->query("
            SELECT DISTINCT forma_farmaceutica 
            FROM productos_medicamentos 
            WHERE forma_farmaceutica IS NOT NULL AND forma_farmaceutica != '' 
            ORDER BY forma_farmaceutica ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        $presentaciones = $this->db->query("
            SELECT DISTINCT presentacion_comercial 
            FROM productos_medicamentos 
            WHERE presentacion_comercial IS NOT NULL AND presentacion_comercial != '' 
            ORDER BY presentacion_comercial ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'laboratorios'         => $labs,
            'clases_terapeuticas'  => $clases,
            'formas_farmaceuticas' => $formas,
            'presentaciones'       => $presentaciones
        ];
    }

    public function getProductos($filtros = [], $limit = 25, $offset = 0) {
        $sql = "
            SELECT p.*,
                   (SELECT COALESCE(SUM(il.cantidad_actual), 0) FROM inventario_lotes il WHERE il.producto_id = p.id AND il.estado_lote = 'DISPONIBLE') AS stock_total_disponible,
                   (SELECT COUNT(*) FROM inventario_lotes il WHERE il.producto_id = p.id AND il.cantidad_actual > 0 AND il.fecha_vencimiento > CURDATE()) AS lotes_activos_count
            FROM productos_medicamentos p
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['buscar'])) {
            $b = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombre_generico LIKE :b1 OR p.nombre_comercial LIKE :b2 OR p.codigo_sku LIKE :b3 OR p.codigo_cums LIKE :b4 OR p.registro_invima LIKE :b5 OR p.fabricante_laboratorio LIKE :b6 OR p.principio_activo LIKE :b7)";
            $params[':b1'] = $b;
            $params[':b2'] = $b;
            $params[':b3'] = $b;
            $params[':b4'] = $b;
            $params[':b5'] = $b;
            $params[':b6'] = $b;
            $params[':b7'] = $b;
        }

        if (!empty($filtros['cadena_frio'])) {
            $sql .= " AND p.requiere_cadena_frio = 1";
        }
        if (!empty($filtros['control_especial'])) {
            $sql .= " AND p.es_control_especial = 1";
        }
        if (!empty($filtros['alto_costo'])) {
            $sql .= " AND p.es_alto_costo = 1";
        }
        if (!empty($filtros['tipo_producto'])) {
            $sql .= " AND p.tipo_producto = :tipo_producto";
            $params[':tipo_producto'] = $filtros['tipo_producto'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado_activo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $sql .= " ORDER BY p.nombre_generico ASC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductoById($id) {
        $stmt = $this->db->prepare("SELECT * FROM productos_medicamentos WHERE id = :id");
        $stmt->execute([':id' => intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getTiposProducto() {
        return [
            'MEDICAMENTOS' => [
                'label' => 'Medicamento',
                'badge' => 'bg-primary text-white',
                'icon'  => 'fa-pills'
            ],
            'DISPOSITIVOS MEDICOS' => [
                'label' => 'Dispositivo Médico',
                'badge' => 'text-white',
                'style' => 'background-color: #6f42c1;',
                'icon'  => 'fa-stethoscope'
            ],
            'INSUMOS' => [
                'label' => 'Insumo / Quirúrgico',
                'badge' => 'bg-info text-dark',
                'icon'  => 'fa-box-tissue'
            ],
            'MEDICAMENTOS UNIRS' => [
                'label' => 'Medicamento UNIRS',
                'badge' => 'bg-warning text-dark',
                'icon'  => 'fa-prescription'
            ]
        ];
    }

    public function guardarProducto($datos) {
        $id = intval($datos['id'] ?? 0);
        $tiposValidos = ['MEDICAMENTOS', 'DISPOSITIVOS MEDICOS', 'INSUMOS', 'MEDICAMENTOS UNIRS'];
        $tipoProd = trim($datos['tipo_producto'] ?? 'MEDICAMENTOS');
        if (!in_array($tipoProd, $tiposValidos)) {
            $tipoProd = 'MEDICAMENTOS';
        }

        $sku = trim($datos['codigo_sku'] ?? '');
        if (empty($sku)) {
            $prefijo = ($tipoProd === 'DISPOSITIVOS MEDICOS' || $tipoProd === 'INSUMOS') ? 'DM-' : 'MED-';
            $sku = $prefijo . str_pad(rand(100, 9999), 5, '0', STR_PAD_LEFT);
        }

        $nombreGen = mb_strtoupper(trim($datos['nombre_generico'] ?? ''), 'UTF-8');
        $nombreCom = mb_strtoupper(trim($datos['nombre_comercial'] ?? ''), 'UTF-8');
        if (empty($nombreGen) && ($tipoProd === 'DISPOSITIVOS MEDICOS' || $tipoProd === 'INSUMOS')) {
            $nombreGen = 'NO APLICA';
        }

        $params = [
            ':codigo_sku'             => $sku,
            ':tipo_producto'          => $tipoProd,
            ':codigo_barras'          => trim($datos['codigo_barras'] ?? ''),
            ':codigo_cums'            => trim($datos['codigo_cums'] ?? ''),
            ':codigo_ium'             => trim($datos['codigo_ium'] ?? ''),
            ':registro_invima'        => trim($datos['registro_invima'] ?? ''),
            ':fecha_vigencia_invima'  => !empty($datos['fecha_vigencia_invima']) ? $datos['fecha_vigencia_invima'] : null,
            ':codigo_atc'             => trim($datos['codigo_atc'] ?? ''),
            ':nombre_generico'        => $nombreGen,
            ':nombre_comercial'       => $nombreCom,
            ':fabricante_laboratorio' => mb_strtoupper(trim($datos['fabricante_laboratorio'] ?? ''), 'UTF-8'),
            ':laboratorio_nit'        => trim($datos['laboratorio_nit'] ?? ''),
            ':clase_terapeutica'      => trim($datos['clase_terapeutica'] ?? ''),
            ':principio_activo'       => trim($datos['principio_activo'] ?? ''),
            ':concentracion'          => trim($datos['concentracion'] ?? ''),
            ':forma_farmaceutica'     => trim($datos['forma_farmaceutica'] ?? ''),
            ':presentacion_comercial' => trim($datos['presentacion_comercial'] ?? ''),
            ':via_administracion'     => trim($datos['via_administracion'] ?? 'ORAL'),
            ':requiere_cadena_frio'   => !empty($datos['requiere_cadena_frio']) ? 1 : 0,
            ':es_control_especial'    => !empty($datos['es_control_especial']) ? 1 : 0,
            ':es_alto_costo'          => !empty($datos['es_alto_costo']) ? 1 : 0,
            ':es_pos'                 => isset($datos['es_pos']) ? intval($datos['es_pos']) : 1,
            ':uso_institucional'      => !empty($datos['uso_institucional']) ? 1 : 0,
            ':es_biologico'           => !empty($datos['es_biologico']) ? 1 : 0,
            ':precio_referencia'      => floatval($datos['precio_referencia'] ?? 0),
            ':stock_minimo_alerta'    => intval($datos['stock_minimo_alerta'] ?? 10),
            ':stock_maximo'           => intval($datos['stock_maximo'] ?? 1000),
            ':estado_activo'          => $datos['estado_activo'] ?? 'Activo',
            ':observaciones'          => trim($datos['observaciones'] ?? '')
        ];

        if ($id > 0) {
            $params[':id'] = $id;
            $stmt = $this->db->prepare("
                UPDATE productos_medicamentos SET
                    codigo_sku = :codigo_sku,
                    tipo_producto = :tipo_producto,
                    codigo_barras = :codigo_barras,
                    codigo_cums = :codigo_cums,
                    codigo_ium = :codigo_ium,
                    registro_invima = :registro_invima,
                    fecha_vigencia_invima = :fecha_vigencia_invima,
                    codigo_atc = :codigo_atc,
                    nombre_generico = :nombre_generico,
                    nombre_comercial = :nombre_comercial,
                    fabricante_laboratorio = :fabricante_laboratorio,
                    laboratorio_nit = :laboratorio_nit,
                    clase_terapeutica = :clase_terapeutica,
                    principio_activo = :principio_activo,
                    concentracion = :concentracion,
                    forma_farmaceutica = :forma_farmaceutica,
                    presentacion_comercial = :presentacion_comercial,
                    via_administracion = :via_administracion,
                    requiere_cadena_frio = :requiere_cadena_frio,
                    es_control_especial = :es_control_especial,
                    es_alto_costo = :es_alto_costo,
                    es_pos = :es_pos,
                    uso_institucional = :uso_institucional,
                    es_biologico = :es_biologico,
                    precio_referencia = :precio_referencia,
                    stock_minimo_alerta = :stock_minimo_alerta,
                    stock_maximo = :stock_maximo,
                    estado_activo = :estado_activo,
                    observaciones = :observaciones
                WHERE id = :id
            ");
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO productos_medicamentos
                (codigo_sku, tipo_producto, codigo_barras, codigo_cums, codigo_ium, registro_invima, fecha_vigencia_invima, codigo_atc, nombre_generico, nombre_comercial, fabricante_laboratorio, laboratorio_nit, clase_terapeutica, principio_activo, concentracion, forma_farmaceutica, presentacion_comercial, via_administracion, requiere_cadena_frio, es_control_especial, es_alto_costo, es_pos, uso_institucional, es_biologico, precio_referencia, stock_minimo_alerta, stock_maximo, estado_activo, observaciones)
                VALUES
                (:codigo_sku, :tipo_producto, :codigo_barras, :codigo_cums, :codigo_ium, :registro_invima, :fecha_vigencia_invima, :codigo_atc, :nombre_generico, :nombre_comercial, :fabricante_laboratorio, :laboratorio_nit, :clase_terapeutica, :principio_activo, :concentracion, :forma_farmaceutica, :presentacion_comercial, :via_administracion, :requiere_cadena_frio, :es_control_especial, :es_alto_costo, :es_pos, :uso_institucional, :es_biologico, :precio_referencia, :stock_minimo_alerta, :stock_maximo, :estado_activo, :observaciones)
            ");
            return $stmt->execute($params);
        }
    }

    /**
     * Mapeo y Búsqueda Inteligente de Medicamentos por Principio Activo, Nombre Comercial, Genérico o SKU
     * Prioriza existencias físicas en la bodega de la sede activa.
     */
    public function buscarMedicamentoInteligente($textoPrescripcion, $bodega_id = null, $sede_id = null) {
        $clean = mb_strtoupper(trim($textoPrescripcion), 'UTF-8');
        if (empty($clean)) return null;

        $unaccent = strtr($clean, [
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'Ñ'=>'N',
            'á'=>'A', 'é'=>'E', 'í'=>'I', 'ó'=>'O', 'ú'=>'U', 'ñ'=>'N'
        ]);

        // Separar números pegados a unidades: "20MG" -> "20 MG", "500MG" -> "500 MG"
        $unaccent = preg_replace('/(\d+)\s*(MG|ML|MCG|GR|G|UI|MMOL|MEQ)\b/i', '$1 $2', $unaccent);

        $labStopWords = [
            'ECAR', 'GENFAR', 'MK', 'LAFRANCOL', 'MEGALABS', 'LAPROFF', 'NOVAMED', 'LEGRAND',
            'PROCAPS', 'SANOFI', 'PFIZER', 'NOVARTIS', 'BAYER', 'ABBOTT', 'RICHMOND', 'ZORATOMIN',
            'INFLAXEN', 'CALTRATE', 'OROCAL', 'ARAMAX', 'COZAAR', 'AMIGARD', 'HALEON', 'HUMAN',
            'HB', 'COLOMBIA', 'SAS', 'SA', 'LABORATORIOS', 'LABS', 'LAB', 'DROGAS', 'PRODUCTOS',
            'DELTA', 'BLASKOV', 'VITALIS', 'SYNTHESIS', 'BUSSIE', 'CHALVER', 'TECNOQUIMICAS',
            'SEVEN', 'PHARMA', 'FARMA', 'MEDELLIN', 'BOGOTA', 'CALI'
        ];

        $stopWords = array_merge($labStopWords, [
            'DE', 'LA', 'EL', 'EN', 'POR', 'CON', 'SIN', 'PARA', 'DEL', 'LOS', 'LAS', 'X',
            'MG', 'ML', 'MCG', 'GR', 'G', 'UI', 'TABLETA', 'TABLETAS', 'TAB', 'TABS',
            'CAPSULA', 'CAPSULAS', 'CAP', 'CAPS', 'COMPRIMIDO', 'COMPRIMIDOS', 'COMP',
            'SOLUCION', 'SOL', 'JARABE', 'SUSPENSION', 'SUSP', 'INYECTABLE', 'AMPOLLA',
            'FRASCO', 'FCO', 'SOBRE', 'SOBRES', 'CREMA', 'GEL', 'GOTAS', 'ORAL', 'TOPICO',
            'RECUBIERTA', 'RECUBIERTAS', 'LIBERACION', 'PROLONGADA', 'INS', 'REG', 'GENERICO',
            'DURA', 'BLANDA', 'ENTERICA', 'RETARDADA', 'RECIBIERTAS', 'MILIGRAMOS', 'MILILITROS',
            'MALEATO', 'MALEAT', 'CLORHIDRATO', 'HCL', 'SODICO', 'SODICA', 'POTASICO', 'POTASICA',
            'SUCCINATO', 'BESILATO', 'TARTRATO', 'BROMATO', 'FOSFATO', 'SULFATO', 'FUMARATO',
            'ACETATO', 'VALERATO', 'NITRATO', 'CITRATO', 'GLUCONATO', 'PROPIONATO', 'LACTATO',
            'MONOHIDRATO', 'DIHIDRATO', 'TRIHIDRATO', 'ANHIDRO', 'BASE', 'CALCICO', 'CALCICA',
            'MAGNESICO', 'MAGNESICA', 'ZINC', 'BROMURO', 'MESILATO', 'EDISILATO', 'PALMITATO',
            'ESTEARATO', 'BENZOATO', 'SAL', 'SALES'
        ]);

        $rawTokens = preg_split('/[\s,\.\-\+\/\*\(\)]+/', $unaccent);
        $drugTokens = [];
        $numTokens = [];

        foreach ($rawTokens as $tok) {
            $tok = trim($tok);
            if ($tok === '') continue;
            if (is_numeric($tok)) {
                $numTokens[] = $tok;
            } elseif (!in_array($tok, $stopWords) && strlen($tok) >= 3) {
                $drugTokens[] = $tok;
            }
        }

        // Si todos fueron filtrados por marcas de laboratorio, recuperar los nombres no genéricos
        if (empty($drugTokens)) {
            foreach ($rawTokens as $tok) {
                $tok = trim($tok);
                if (!is_numeric($tok) && strlen($tok) >= 3 && !in_array($tok, ['TABLETA','CAPSULA','TABLETAS','CAPSULAS','SOLUCION','MG','ML','MCG','INS','REG'])) {
                    $drugTokens[] = $tok;
                }
            }
        }

        // Sinónimos farmacéuticos cruzados (Principio Activo <-> Nombre Comercial / Común)
        $synonyms = [];
        foreach ($drugTokens as $dt) {
            if ($dt === 'ACETAMINOFEN' || $dt === 'DOLEX' || $dt === 'TYLENOL') {
                $synonyms[] = 'PARACETAMOL';
            } elseif ($dt === 'PARACETAMOL') {
                $synonyms[] = 'ACETAMINOFEN';
            } elseif ($dt === 'ASPIRINA' || $dt === 'ASA') {
                $synonyms[] = 'ACIDO ACETILSALICILICO';
            } elseif ($dt === 'LEVOTIROXINA') {
                $synonyms[] = 'EUTHYROX';
            }
        }

        $allSearchTerms = array_unique(array_merge($drugTokens, $synonyms));
        if (empty($allSearchTerms)) {
            return null;
        }

        $targetBodega = intval($bodega_id ?: 2);
        $params = [':bodega_id' => $targetBodega];
        $whereParts = [];
        $termIdx = 0;

        foreach ($allSearchTerms as $term) {
            $k1 = ":c_{$termIdx}";
            $k2 = ":g_{$termIdx}";
            $k3 = ":p_{$termIdx}";
            $k4 = ":s_{$termIdx}";
            $whereParts[] = "(p.nombre_comercial LIKE $k1 OR p.nombre_generico LIKE $k2 OR p.principio_activo LIKE $k3 OR p.codigo_sku LIKE $k4)";
            $params[$k1] = '%' . $term . '%';
            $params[$k2] = '%' . $term . '%';
            $params[$k3] = '%' . $term . '%';
            $params[$k4] = '%' . $term . '%';
            $termIdx++;
        }

        $whereSql = implode(' OR ', $whereParts);

        $sql = "
            SELECT p.id, p.codigo_sku, p.codigo_cums, p.codigo_ium, p.nombre_comercial, p.nombre_generico, p.principio_activo, 
                   p.concentracion, p.forma_farmaceutica, p.fabricante_laboratorio, p.registro_invima,
                   COALESCE((
                       SELECT SUM(il.cantidad_actual) 
                       FROM inventario_lotes il 
                       WHERE il.producto_id = p.id AND il.bodega_id = :bodega_id AND il.cantidad_actual > 0
                   ), 0) AS stock_bodega,
                   COALESCE((
                       SELECT SUM(il2.cantidad_actual) 
                       FROM inventario_lotes il2 
                       WHERE il2.producto_id = p.id AND il2.cantidad_actual > 0
                   ), 0) AS stock_total
            FROM productos_medicamentos p
            WHERE (p.estado_activo = 'Activo' OR p.estado_activo = '1' OR p.estado_activo = 1)
              AND ($whereSql)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            return null;
        }

        // Detectar si la prescripción es combinación explícita
        $prescripcionEsCombinacion = (
            strpos($unaccent, '+') !== false ||
            strpos($unaccent, ' MAS ') !== false ||
            strpos($unaccent, ' Y ') !== false
        );

        $validMatches = [];

        foreach ($candidates as $cand) {
            $com = strtr(mb_strtoupper($cand['nombre_comercial'], 'UTF-8'), ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $gen = strtr(mb_strtoupper($cand['nombre_generico'], 'UTF-8'), ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $pri = strtr(mb_strtoupper($cand['principio_activo'] ?? '', 'UTF-8'), ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $sku = strtoupper($cand['codigo_sku']);

            // 1. VERIFICACIÓN ESTRICTA DE PALABRA COMPLETA (\bWORD\b)
            $matchedStrictly = false;
            $matchedToken = '';

            foreach ($drugTokens as $dt) {
                $pattern = '/\b' . preg_quote($dt, '/') . '\b/u';
                if (preg_match($pattern, $com) || preg_match($pattern, $gen) || preg_match($pattern, $pri) || $sku === $dt) {
                    $matchedStrictly = true;
                    $matchedToken = $dt;
                    break;
                }
            }

            if (!$matchedStrictly) {
                foreach ($synonyms as $syn) {
                    $pattern = '/\b' . preg_quote($syn, '/') . '\b/u';
                    if (preg_match($pattern, $com) || preg_match($pattern, $gen) || preg_match($pattern, $pri)) {
                        $matchedStrictly = true;
                        $matchedToken = $syn;
                        break;
                    }
                }
            }

            if (!$matchedStrictly) {
                continue;
            }

            // 2. REGLA CLÍNICA DE ORO: MONOFÁRMACO VS COMBINACIÓN
            $productoEsCombinacion = (
                strpos($com, '+') !== false || 
                strpos($gen, '+') !== false || 
                strpos($gen, 'COMBINACIONES') !== false ||
                strpos($gen, ' AND ') !== false
            );

            if (!$prescripcionEsCombinacion && $productoEsCombinacion) {
                // El médico formuló solo ROSUVASTATINA o solo OMEPRAZOL o solo ACETAMINOFEN -> JAMÁS devolver combinaciones
                continue;
            }

            if ($prescripcionEsCombinacion && !$productoEsCombinacion) {
                continue;
            }

            // 3. CALCULAR SCORE
            $score = 100;

            // Match en Nombre Comercial y Principio Activo
            foreach ($drugTokens as $dt) {
                $pattern = '/\b' . preg_quote($dt, '/') . '\b/u';
                if (preg_match($pattern, $com)) $score += 60;
                if (preg_match($pattern, $gen)) $score += 40;
                if (preg_match($pattern, $pri)) $score += 50;
            }

            // Match numérico exacto de concentración
            if (!empty($numTokens)) {
                foreach ($numTokens as $num) {
                    if (preg_match('/\b' . preg_quote($num, '/') . '\s*(?:MG|ML|MCG|GR|G|UI)?\b/i', $com)) {
                        $score += 150;
                    } else {
                        $score -= 30;
                    }
                }
            }

            // 4. Bonus por stock físico disponible en Bodega o en Red/Central
            if ($cand['stock_bodega'] > 0) {
                $score += 200;
            } elseif ($cand['stock_total'] > 0) {
                $score += 50;
            }

            $cand['match_score'] = $score;
            $validMatches[] = $cand;
        }

        if (empty($validMatches)) {
            return null;
        }

        usort($validMatches, function($a, $b) {
            if ($a['match_score'] === $b['match_score']) {
                return $b['stock_bodega'] <=> $a['stock_bodega'];
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return $validMatches[0];
    }

    // =========================================================================
    // 3. TRAZABILIDAD DE LOTES CON CRITERIO FEFO Y SEMÁFORO DE VENCIMIENTO
    // =========================================================================

    public function calcularSemaforoLote($fechaVencimiento) {
        $hoy = new DateTime();
        $venc = new DateTime($fechaVencimiento);
        $diff = $hoy->diff($venc);
        $dias = (int)$diff->format('%r%a');

        if ($dias <= 0) {
            return ['codigo' => 'VENCIDO', 'clase' => 'bg-dark text-white', 'icono' => 'fa-skull-crossbones', 'dias' => $dias, 'texto' => 'VENCIDO (Bloqueado)'];
        } elseif ($dias <= 90) {
            return ['codigo' => 'ROJO', 'clase' => 'bg-danger text-white', 'icono' => 'fa-triangle-exclamation', 'dias' => $dias, 'texto' => 'Alerta Crítica (< 3 meses)'];
        } elseif ($dias <= 180) {
            return ['codigo' => 'AMARILLO', 'clase' => 'bg-warning text-dark', 'icono' => 'fa-clock-rotate-left', 'dias' => $dias, 'texto' => 'Rotación Prioritaria (3-6 meses)'];
        } else {
            return ['codigo' => 'VERDE', 'clase' => 'bg-success text-white', 'icono' => 'fa-circle-check', 'dias' => $dias, 'texto' => 'Vigente (> 6 meses)'];
        }
    }

    public function getLotesPorProducto($producto_id, $bodega_id = null) {
        $sql = "
            SELECT il.*, b.nombre_bodega, b.codigo_bodega, p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.codigo_sku, p.codigo_cums
            FROM inventario_lotes il
            JOIN bodegas b ON il.bodega_id = b.id
            JOIN productos_medicamentos p ON il.producto_id = p.id
            WHERE il.producto_id = :producto_id
        ";
        $params = [':producto_id' => intval($producto_id)];

        if (!empty($bodega_id)) {
            $sql .= " AND il.bodega_id = :bodega_id";
            $params[':bodega_id'] = intval($bodega_id);
        }

        // Criterio FEFO: Primero en Vencer, Primero en Salir (fecha_vencimiento ASC)
        $sql .= " ORDER BY il.fecha_vencimiento ASC, il.cantidad_actual DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lotes as &$l) {
            $l['semaforo'] = $this->calcularSemaforoLote($l['fecha_vencimiento']);
        }
        return $lotes;
    }

    public function getLotesDisponiblesFEFO($producto_id, $sede_id = null) {
        $this->limpiarReservasExpiradas();

        $sql = "
            SELECT il.*, b.nombre_bodega, b.codigo_bodega, b.tipo_bodega,
                   p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.registro_invima,
                   COALESCE((
                       SELECT SUM(r.cantidad_reservada) 
                       FROM inventario_reservas_temporales r 
                       WHERE r.lote_id = il.id 
                         AND r.estado_reserva = 'ACTIVA' 
                         AND r.expires_at > NOW()
                   ), 0) AS cantidad_reservada,
                   (il.cantidad_actual - COALESCE((
                       SELECT SUM(r.cantidad_reservada) 
                       FROM inventario_reservas_temporales r 
                       WHERE r.lote_id = il.id 
                         AND r.estado_reserva = 'ACTIVA' 
                         AND r.expires_at > NOW()
                   ), 0)) AS cantidad_disponible_neta
            FROM inventario_lotes il
            JOIN bodegas b ON il.bodega_id = b.id
            JOIN productos_medicamentos p ON il.producto_id = p.id
            WHERE il.producto_id = :producto_id
              AND il.cantidad_actual > 0
              AND il.estado_lote = 'DISPONIBLE'
              AND il.fecha_vencimiento > CURDATE()
            HAVING cantidad_disponible_neta > 0
        ";
        $params = [':producto_id' => intval($producto_id)];

        if (!empty($sede_id)) {
            $sql .= " AND (b.sede_id = :sede_id OR b.sede_id IS NULL OR b.tipo_bodega = 'PRINCIPAL')";
            $params[':sede_id'] = intval($sede_id);
        }

        // FEFO estricto
        $sql .= " ORDER BY (b.sede_id = :sede_prioritaria) DESC, il.fecha_vencimiento ASC";
        $params[':sede_prioritaria'] = intval($sede_id);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lotes as &$l) {
            $l['semaforo'] = $this->calcularSemaforoLote($l['fecha_vencimiento']);
        }
        return $lotes;
    }

    public function limpiarReservasExpiradas() {
        try {
            $this->db->exec("
                UPDATE inventario_reservas_temporales 
                SET estado_reserva = 'EXPIRADA' 
                WHERE estado_reserva = 'ACTIVA' AND expires_at <= NOW()
            ");
        } catch (Exception $e) {}
    }

    public function getMinutosReservaConfig() {
        try {
            $stmt = $this->db->query("SELECT minutos_reserva_stock FROM empresa_config ORDER BY id ASC LIMIT 1");
            $mins = intval($stmt->fetchColumn() ?: 15);
            return $mins > 0 ? $mins : 15;
        } catch (Exception $e) {
            return 15;
        }
    }

    public function reservarLotesTemporales($ingreso_id, $itemsReservar, $user_id, $session_token) {
        $this->limpiarReservasExpiradas();
        $minutos = $this->getMinutosReservaConfig();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"));

        // Liberar reservas previas de este mismo ingreso si existen
        $stmtLib = $this->db->prepare("
            UPDATE inventario_reservas_temporales 
            SET estado_reserva = 'LIBERADA' 
            WHERE ingreso_id = :ingreso_id AND estado_reserva = 'ACTIVA'
        ");
        $stmtLib->execute([':ingreso_id' => $ingreso_id]);

        $stmtIns = $this->db->prepare("
            INSERT INTO inventario_reservas_temporales
            (ingreso_id, producto_id, lote_id, bodega_id, cantidad_reservada, usuario_id, session_token, estado_reserva, expires_at)
            VALUES
            (:ingreso_id, :prod_id, :lote_id, :bodega_id, :cant, :user_id, :token, 'ACTIVA', :exp)
        ");

        $reservados = 0;
        foreach ($itemsReservar as $item) {
            $prodId = intval($item['producto_id'] ?? 0);
            $loteId = intval($item['lote_id'] ?? 0);
            $bodegaId = intval($item['bodega_id'] ?? 0);
            $cant = intval($item['cantidad'] ?? 0);

            if ($prodId > 0 && $loteId > 0 && $cant > 0) {
                $stmtIns->execute([
                    ':ingreso_id' => $ingreso_id,
                    ':prod_id'    => $prodId,
                    ':lote_id'    => $loteId,
                    ':bodega_id'  => $bodegaId,
                    ':cant'       => $cant,
                    ':user_id'    => $user_id,
                    ':token'      => $session_token,
                    ':exp'        => $expiresAt
                ]);
                $reservados++;
            }
        }
        return ['status' => 'ok', 'total_reservados' => $reservados, 'expires_at' => $expiresAt];
    }

    public function consolidarReservas($ingreso_id) {
        $stmt = $this->db->prepare("
            UPDATE inventario_reservas_temporales 
            SET estado_reserva = 'CONSOLIDADA' 
            WHERE ingreso_id = :id AND estado_reserva = 'ACTIVA'
        ");
        return $stmt->execute([':id' => $ingreso_id]);
    }

    public function liberarReservasPorIngreso($ingreso_id) {
        $stmt = $this->db->prepare("
            UPDATE inventario_reservas_temporales 
            SET estado_reserva = 'LIBERADA' 
            WHERE ingreso_id = :id AND estado_reserva = 'ACTIVA'
        ");
        return $stmt->execute([':id' => $ingreso_id]);
    }

    /**
     * Detector Anti-Duplicidad de Medicamentos Entregados al Mismo Paciente en el Mes Actual
     */
    public function verificarDuplicidadEntregasMes($paciente_id, $producto_id_o_nombre) {
        if (empty($paciente_id)) return [];

        $sql = "
            SELECT imd.id AS disp_id, imd.cantidad_entregada, imd.created_at AS fecha_dispensacion,
                   i.ticket_numero, i.fecha_ingreso,
                   p.nombre_generico, p.nombre_comercial, p.concentracion, p.codigo_cums,
                   u.nombre_completo AS dispensado_por_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingreso_medicamentos_dispensados imd
            JOIN ingresos i ON imd.ingreso_id = i.id
            JOIN productos_medicamentos p ON imd.producto_id = p.id
            LEFT JOIN usuarios u ON imd.entregado_por_user_id = u.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.paciente_id = :paciente_id
              AND (
                  imd.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                  OR imd.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              )
        ";
        $params = [':paciente_id' => intval($paciente_id)];

        if (is_numeric($producto_id_o_nombre) && intval($producto_id_o_nombre) > 0) {
            $sql .= " AND imd.producto_id = :prod_id";
            $params[':prod_id'] = intval($producto_id_o_nombre);
        } else if (!empty($producto_id_o_nombre)) {
            $sql .= " AND (p.nombre_generico LIKE :nom1 OR p.nombre_comercial LIKE :nom2)";
            $busq = '%' . trim($producto_id_o_nombre) . '%';
            $params[':nom1'] = $busq;
            $params[':nom2'] = $busq;
        }

        $sql .= " ORDER BY imd.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildStockGeneralWhere($bodega_id = null, $criterio = '', $filtroSemaforo = '', $sede_id = null) {
        $where = " WHERE (p.estado_activo = 'Activo' OR p.estado_activo = '1' OR p.estado_activo = 1) ";
        $params = [];

        if (!empty($bodega_id)) {
            $where .= " AND il.bodega_id = :bodega_id ";
            $params[':bodega_id'] = intval($bodega_id);
        } elseif (!empty($sede_id)) {
            $where .= " AND (b.sede_id = :sede_id OR b.tipo_bodega = 'PRINCIPAL') ";
            $params[':sede_id'] = intval($sede_id);
        }

        if (!empty($criterio)) {
            $c = '%' . trim($criterio) . '%';
            $where .= " AND (p.nombre_generico LIKE :c1 OR p.nombre_comercial LIKE :c2 OR p.codigo_sku LIKE :c3 OR p.codigo_cums LIKE :c4 OR il.numero_lote LIKE :c5) ";
            $params[':c1'] = $c;
            $params[':c2'] = $c;
            $params[':c3'] = $c;
            $params[':c4'] = $c;
            $params[':c5'] = $c;
        }

        if (!empty($filtroSemaforo)) {
            if ($filtroSemaforo === 'VERDE') {
                $where .= " AND il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) > 180 ";
            } elseif ($filtroSemaforo === 'AMARILLO') {
                $where .= " AND il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) BETWEEN 91 AND 180 ";
            } elseif ($filtroSemaforo === 'ROJO') {
                $where .= " AND il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) BETWEEN 1 AND 90 ";
            } elseif ($filtroSemaforo === 'VENCIDO') {
                $where .= " AND il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) <= 0 ";
            } elseif ($filtroSemaforo === 'AGOTADO') {
                $where .= " AND il.cantidad_actual <= 0 ";
            }
        }

        return [$where, $params];
    }

    public function getStockGeneralCount($bodega_id = null, $criterio = '', $filtroSemaforo = '', $sede_id = null) {
        list($where, $params) = $this->buildStockGeneralWhere($bodega_id, $criterio, $filtroSemaforo, $sede_id);
        $sql = "
            SELECT COUNT(*)
            FROM inventario_lotes il
            JOIN productos_medicamentos p ON il.producto_id = p.id
            JOIN bodegas b ON il.bodega_id = b.id
            LEFT JOIN sedes s ON b.sede_id = s.id
            {$where}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function getStockGeneralPorBodega($bodega_id = null, $criterio = '', $filtroSemaforo = '', $sede_id = null, $limit = null, $offset = 0) {
        list($where, $params) = $this->buildStockGeneralWhere($bodega_id, $criterio, $filtroSemaforo, $sede_id);
        $sql = "
            SELECT il.id AS lote_id, il.numero_lote, il.fecha_fabricacion, il.fecha_vencimiento, il.fabricante_laboratorio,
                   il.cantidad_actual, il.costo_unitario, il.estado_lote,
                   p.id AS producto_id, p.codigo_sku, p.codigo_cums, p.codigo_barras, p.registro_invima,
                   p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.presentacion_comercial,
                   p.requiere_cadena_frio, p.es_control_especial, p.es_alto_costo, p.stock_minimo_alerta,
                   b.id AS bodega_id, b.nombre_bodega, b.codigo_bodega,
                   COALESCE(s.nombre_sede, 'Almacén Central') AS nombre_sede
            FROM inventario_lotes il
            JOIN productos_medicamentos p ON il.producto_id = p.id
            JOIN bodegas b ON il.bodega_id = b.id
            LEFT JOIN sedes s ON b.sede_id = s.id
            {$where}
            ORDER BY (il.cantidad_actual = 0) ASC, il.fecha_vencimiento ASC, p.nombre_generico ASC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT :lim OFFSET :off ";
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        if ($limit !== null) {
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($rows as $r) {
            $cant = intval($r['cantidad_actual']);

            if ($cant <= 0) {
                $sem = [
                    'codigo' => 'AGOTADO',
                    'clase' => 'bg-danger text-white',
                    'icono' => 'fa-triangle-exclamation',
                    'dias' => 0,
                    'texto' => '🔴 SIN STOCK / AGOTADO'
                ];
            } elseif (empty($r['fecha_vencimiento'])) {
                $sem = [
                    'codigo' => 'SIN_FECHA',
                    'clase' => 'bg-secondary text-white',
                    'icono' => 'fa-circle-question',
                    'dias' => 0,
                    'texto' => 'Sin Fecha Venc.'
                ];
            } else {
                $sem = $this->calcularSemaforoLote($r['fecha_vencimiento']);
            }
            $r['semaforo'] = $sem;
            $resultado[] = $r;
        }
        return $resultado;
    }

    public function getResumenKPIs($sede_id = null) {
        $params = [];
        $whereSede = "";
        if (!empty($sede_id)) {
            $whereSede = " AND (b.sede_id = :sede_id OR b.tipo_bodega = 'PRINCIPAL') ";
            $params[':sede_id'] = intval($sede_id);
        }

        $sql = "
            SELECT 
                COUNT(CASE WHEN il.cantidad_actual > 0 THEN 1 END) AS total_lotes,
                COALESCE(SUM(CASE WHEN il.cantidad_actual > 0 THEN il.cantidad_actual ELSE 0 END), 0) AS total_unidades,
                COALESCE(SUM(CASE WHEN il.cantidad_actual > 0 THEN il.cantidad_actual * il.costo_unitario ELSE 0 END), 0) AS valorizado_total,
                COUNT(CASE WHEN il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) > 180 THEN 1 END) AS semaforo_verde,
                COUNT(CASE WHEN il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) BETWEEN 91 AND 180 THEN 1 END) AS semaforo_amarillo,
                COUNT(CASE WHEN il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) BETWEEN 1 AND 90 THEN 1 END) AS semaforo_rojo,
                COUNT(CASE WHEN il.cantidad_actual > 0 AND DATEDIFF(il.fecha_vencimiento, CURDATE()) <= 0 THEN 1 END) AS semaforo_vencido,
                COALESCE(SUM(CASE WHEN il.cantidad_actual > 0 AND p.requiere_cadena_frio = 1 THEN il.cantidad_actual ELSE 0 END), 0) AS cadena_frio_stock,
                COALESCE(SUM(CASE WHEN il.cantidad_actual > 0 AND p.es_control_especial = 1 THEN il.cantidad_actual ELSE 0 END), 0) AS control_esp_stock
            FROM inventario_lotes il
            JOIN bodegas b ON il.bodega_id = b.id
            JOIN productos_medicamentos p ON il.producto_id = p.id
            WHERE (p.estado_activo = 'Activo' OR p.estado_activo = '1' OR p.estado_activo = 1) {$whereSede}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Agotados en bodega satélite: Solo productos que la bodega ha operado (tienen lote asignado) pero su saldo actual es 0
        $paramsAgotados = [];
        $whereSedeExt = "";
        $whereSedeSub = "";
        if (!empty($sede_id)) {
            $whereSedeExt = " AND (b.sede_id = :s_ext OR b.tipo_bodega = 'PRINCIPAL') ";
            $whereSedeSub = " AND (b3.sede_id = :s_sub OR b3.tipo_bodega = 'PRINCIPAL') ";
            $paramsAgotados[':s_ext'] = intval($sede_id);
            $paramsAgotados[':s_sub'] = intval($sede_id);
        }

        $sqlAgotados = "
            SELECT COUNT(DISTINCT il2.producto_id) 
            FROM inventario_lotes il2 
            JOIN bodegas b ON il2.bodega_id = b.id 
            WHERE 1=1 {$whereSedeExt}
              AND il2.producto_id NOT IN (
                  SELECT DISTINCT il3.producto_id 
                  FROM inventario_lotes il3 
                  JOIN bodegas b3 ON il3.bodega_id = b3.id 
                  WHERE il3.cantidad_actual > 0 {$whereSedeSub}
              )
        ";
        $stmtAgotados = $this->db->prepare($sqlAgotados);
        $stmtAgotados->execute($paramsAgotados);
        $agotados = intval($stmtAgotados->fetchColumn() ?: 0);

        // Total fármacos en catálogo maestro nacional
        $totalCatalogo = intval($this->db->query("SELECT COUNT(*) FROM productos_medicamentos WHERE (estado_activo = 'Activo' OR estado_activo = '1' OR estado_activo = 1)")->fetchColumn() ?: 0);

        return [
            'total_lotes'        => intval($res['total_lotes'] ?? 0),
            'total_unidades'     => intval($res['total_unidades'] ?? 0),
            'valorizado_total'   => floatval($res['valorizado_total'] ?? 0),
            'semaforo_verde'     => intval($res['semaforo_verde'] ?? 0),
            'semaforo_amarillo'  => intval($res['semaforo_amarillo'] ?? 0),
            'semaforo_rojo'      => intval($res['semaforo_rojo'] ?? 0),
            'semaforo_vencido'   => intval($res['semaforo_vencido'] ?? 0),
            'total_agotados'     => $agotados,
            'total_catalogo'     => $totalCatalogo,
            'cadena_frio_stock'  => intval($res['cadena_frio_stock'] ?? 0),
            'control_esp_stock'  => intval($res['control_esp_stock'] ?? 0)
        ];
    }

    // =========================================================================
    // 4. KARDEX Y MOVIMIENTOS ATÓMICOS
    // =========================================================================

    public function registrarMovimientoKardex($bodega_id, $producto_id, $lote_id, $tipo, $cantidad, $costo_unitario, $doc_ref, $user_id, $obs = '') {
        $stmtStock = $this->db->prepare("SELECT cantidad_actual FROM inventario_lotes WHERE id = :id");
        $stmtStock->execute([':id' => $lote_id]);
        $stockActual = (int)$stmtStock->fetchColumn();

        $stockAnterior = $stockActual;
        $esEntrada = in_array($tipo, ['ENTRADA_COMPRA', 'TRASLADO_ENTRADA', 'AJUSTE_POSITIVO', 'DEVOLUCION']);
        $stockNuevo = $esEntrada ? ($stockActual + $cantidad) : ($stockActual - $cantidad);
        if ($stockNuevo < 0) $stockNuevo = 0;

        $stmt = $this->db->prepare("
            INSERT INTO movimientos_kardex
            (bodega_id, producto_id, lote_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, costo_unitario, costo_total, referencia_documento, usuario_id, observaciones)
            VALUES
            (:bodega_id, :producto_id, :lote_id, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :costo, :total, :doc_ref, :user_id, :obs)
        ");
        $stmt->execute([
            ':bodega_id'       => $bodega_id,
            ':producto_id'     => $producto_id,
            ':lote_id'         => $lote_id,
            ':tipo'            => $tipo,
            ':cantidad'        => $cantidad,
            ':stock_anterior'  => $stockAnterior,
            ':stock_nuevo'     => $stockNuevo,
            ':costo'           => $costo_unitario,
            ':total'           => ($cantidad * $costo_unitario),
            ':doc_ref'         => $doc_ref,
            ':user_id'         => $user_id,
            ':obs'             => $obs
        ]);

        // Actualizar inventario_lotes
        $stmtUpdate = $this->db->prepare("UPDATE inventario_lotes SET cantidad_actual = :cant WHERE id = :id");
        $stmtUpdate->execute([':cant' => $stockNuevo, ':id' => $lote_id]);

        return true;
    }

    public function getKardexMovimientos($filtros = [], $limit = 25, $offset = 0) {
        $sql = "
            SELECT k.*, 
                   p.nombre_generico, p.nombre_comercial, p.concentracion, p.codigo_sku, p.codigo_cums,
                   il.numero_lote, il.fecha_vencimiento,
                   b.nombre_bodega, b.codigo_bodega,
                   u.nombre_completo AS usuario_nombre
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            JOIN bodegas b ON k.bodega_id = b.id
            LEFT JOIN usuarios u ON k.usuario_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $sql .= " AND k.producto_id = :producto_id";
            $params[':producto_id'] = intval($filtros['producto_id']);
        }
        if (!empty($filtros['bodega_id'])) {
            $sql .= " AND k.bodega_id = :bodega_id";
            $params[':bodega_id'] = intval($filtros['bodega_id']);
        }
        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND k.tipo_movimiento = :tipo";
            $params[':tipo'] = $filtros['tipo_movimiento'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (p.nombre_generico LIKE :busq1 OR p.nombre_comercial LIKE :busq2 OR il.numero_lote LIKE :busq3 OR k.referencia_documento LIKE :busq4 OR k.observaciones LIKE :busq5 OR p.codigo_sku LIKE :busq6)";
            $busqVal = '%' . trim($filtros['buscar']) . '%';
            $params[':busq1'] = $busqVal;
            $params[':busq2'] = $busqVal;
            $params[':busq3'] = $busqVal;
            $params[':busq4'] = $busqVal;
            $params[':busq5'] = $busqVal;
            $params[':busq6'] = $busqVal;
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(k.created_at) >= :f_desde";
            $params[':f_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(k.created_at) <= :f_hasta";
            $params[':f_hasta'] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY k.id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getKardexCount($filtros = []) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            JOIN bodegas b ON k.bodega_id = b.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $sql .= " AND k.producto_id = :producto_id";
            $params[':producto_id'] = intval($filtros['producto_id']);
        }
        if (!empty($filtros['bodega_id'])) {
            $sql .= " AND k.bodega_id = :bodega_id";
            $params[':bodega_id'] = intval($filtros['bodega_id']);
        }
        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND k.tipo_movimiento = :tipo";
            $params[':tipo'] = $filtros['tipo_movimiento'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (p.nombre_generico LIKE :busq1 OR p.nombre_comercial LIKE :busq2 OR il.numero_lote LIKE :busq3 OR k.referencia_documento LIKE :busq4 OR k.observaciones LIKE :busq5 OR p.codigo_sku LIKE :busq6)";
            $busqVal = '%' . trim($filtros['buscar']) . '%';
            $params[':busq1'] = $busqVal;
            $params[':busq2'] = $busqVal;
            $params[':busq3'] = $busqVal;
            $params[':busq4'] = $busqVal;
            $params[':busq5'] = $busqVal;
            $params[':busq6'] = $busqVal;
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(k.created_at) >= :f_desde";
            $params[':f_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(k.created_at) <= :f_hasta";
            $params[':f_hasta'] = $filtros['fecha_hasta'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    }

    public function getKardexResumen($filtros = []) {
        $sql = "
            SELECT 
                COUNT(*) AS total_movimientos,
                SUM(CASE WHEN k.tipo_movimiento IN ('ENTRADA_COMPRA', 'TRASLADO_ENTRADA', 'AJUSTE_POSITIVO', 'DEVOLUCION') THEN k.cantidad ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN k.tipo_movimiento NOT IN ('ENTRADA_COMPRA', 'TRASLADO_ENTRADA', 'AJUSTE_POSITIVO', 'DEVOLUCION') THEN k.cantidad ELSE 0 END) AS total_salidas,
                SUM(COALESCE(k.costo_total, 0)) AS valor_total
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            JOIN bodegas b ON k.bodega_id = b.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $sql .= " AND k.producto_id = :producto_id";
            $params[':producto_id'] = intval($filtros['producto_id']);
        }
        if (!empty($filtros['bodega_id'])) {
            $sql .= " AND k.bodega_id = :bodega_id";
            $params[':bodega_id'] = intval($filtros['bodega_id']);
        }
        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND k.tipo_movimiento = :tipo";
            $params[':tipo'] = $filtros['tipo_movimiento'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (p.nombre_generico LIKE :busq1 OR p.nombre_comercial LIKE :busq2 OR il.numero_lote LIKE :busq3 OR k.referencia_documento LIKE :busq4 OR k.observaciones LIKE :busq5 OR p.codigo_sku LIKE :busq6)";
            $busqVal = '%' . trim($filtros['buscar']) . '%';
            $params[':busq1'] = $busqVal;
            $params[':busq2'] = $busqVal;
            $params[':busq3'] = $busqVal;
            $params[':busq4'] = $busqVal;
            $params[':busq5'] = $busqVal;
            $params[':busq6'] = $busqVal;
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(k.created_at) >= :f_desde";
            $params[':f_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(k.created_at) <= :f_hasta";
            $params[':f_hasta'] = $filtros['fecha_hasta'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_movimientos' => 0,
            'total_entradas'    => 0,
            'total_salidas'     => 0,
            'valor_total'       => 0
        ];
    }

    // =========================================================================
    // 5. RECEPCIÓN TÉCNICA Y COMPRAS (ENTRADAS DE PROVEEDOR)
    // =========================================================================

    public function registrarEntradaCompra($datos, $items = [], $user_id = 1) {
        $this->db->beginTransaction();
        try {
            $stmtC = $this->db->prepare("
                INSERT INTO compras_entradas
                (numero_factura, proveedor_id, bodega_destino_id, fecha_factura, recibido_por_user_id, cumple_empaque, cumple_rotulacion, temperatura_llegada, total_factura, observaciones)
                VALUES
                (:factura, :proveedor_id, :bodega_id, :fecha_factura, :user_id, :empaque, :rotulacion, :temp, :total, :obs)
            ");
            $stmtC->execute([
                ':factura'       => trim($datos['numero_factura']),
                ':proveedor_id'  => intval($datos['proveedor_id']),
                ':bodega_id'     => intval($datos['bodega_destino_id']),
                ':fecha_factura' => $datos['fecha_factura'],
                ':user_id'       => $user_id,
                ':empaque'       => !empty($datos['cumple_empaque']) ? 1 : 0,
                ':rotulacion'    => !empty($datos['cumple_rotulacion']) ? 1 : 0,
                ':temp'          => trim($datos['temperatura_llegada'] ?? '21°C'),
                ':total'         => floatval($datos['total_factura'] ?? 0),
                ':obs'           => trim($datos['observaciones'] ?? '')
            ]);
            $compraId = $this->db->lastInsertId();

            foreach ($items as $it) {
                $prodId = intval($it['producto_id']);
                $loteNum = trim($it['numero_lote']);
                $venc = $it['fecha_vencimiento'];
                $cant = intval($it['cantidad']);
                $costo = floatval($it['costo_unitario']);
                $subtotal = $cant * $costo;
                $lab = trim($it['fabricante_laboratorio'] ?? 'GENÉRICO');
                $invima = trim($it['registro_invima'] ?? '');

                // Detalle de compra
                $stmtDet = $this->db->prepare("
                    INSERT INTO compras_detalle
                    (compra_id, producto_id, numero_lote, fecha_vencimiento, fabricante_laboratorio, registro_invima, cantidad_comprada, costo_unitario, subtotal)
                    VALUES
                    (:compra_id, :producto_id, :lote, :venc, :lab, :invima, :cant, :costo, :subtotal)
                ");
                $stmtDet->execute([
                    ':compra_id'    => $compraId,
                    ':producto_id'  => $prodId,
                    ':lote'         => $loteNum,
                    ':venc'         => $venc,
                    ':lab'          => $lab,
                    ':invima'       => $invima,
                    ':cant'         => $cant,
                    ':costo'        => $costo,
                    ':subtotal'     => $subtotal
                ]);

                // Buscar o crear lote en la bodega destino
                $stmtLote = $this->db->prepare("
                    SELECT id FROM inventario_lotes 
                    WHERE bodega_id = :bodega_id AND producto_id = :prod_id AND numero_lote = :lote
                ");
                $stmtLote->execute([
                    ':bodega_id' => intval($datos['bodega_destino_id']),
                    ':prod_id'   => $prodId,
                    ':lote'      => $loteNum
                ]);
                $loteId = $stmtLote->fetchColumn();

                if (!$loteId) {
                    $stmtNewLote = $this->db->prepare("
                        INSERT INTO inventario_lotes
                        (bodega_id, producto_id, numero_lote, fecha_vencimiento, fabricante_laboratorio, cantidad_actual, costo_unitario, estado_lote)
                        VALUES
                        (:bodega_id, :prod_id, :lote, :venc, :lab, 0, :costo, 'DISPONIBLE')
                    ");
                    $stmtNewLote->execute([
                        ':bodega_id' => intval($datos['bodega_destino_id']),
                        ':prod_id'   => $prodId,
                        ':lote'      => $loteNum,
                        ':venc'      => $venc,
                        ':lab'       => $lab,
                        ':costo'     => $costo
                    ]);
                    $loteId = $this->db->lastInsertId();
                }

                // Registrar en Kardex y sumar stock
                $this->registrarMovimientoKardex(
                    intval($datos['bodega_destino_id']),
                    $prodId,
                    $loteId,
                    'ENTRADA_COMPRA',
                    $cant,
                    $costo,
                    'FACT-' . trim($datos['numero_factura']),
                    $user_id,
                    "Recepción técnica de compra #{$compraId}"
                );
            }

            $this->db->commit();
            return ['status' => 'ok', 'compra_id' => $compraId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getComprasEntradas($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.razon_social AS proveedor_nombre, p.nit AS proveedor_nit,
                   b.nombre_bodega AS bodega_destino_nombre,
                   u.nombre_completo AS recibido_por_nombre,
                   (SELECT COUNT(*) FROM compras_detalle cd WHERE cd.compra_id = c.id) AS total_items
            FROM compras_entradas c
            JOIN proveedores p ON c.proveedor_id = p.id
            JOIN bodegas b ON c.bodega_destino_id = b.id
            LEFT JOIN usuarios u ON c.recibido_por_user_id = u.id
            ORDER BY c.id DESC LIMIT " . intval($limit)
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 6. TRASLADOS ENTRE BODEGAS Y SEDES
    // =========================================================================

    public function registrarTraslado($datos, $items = [], $user_id = 1) {
        $this->db->beginTransaction();
        try {
            $numTraslado = 'TRAS-' . date('ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $bodOrig = intval($datos['bodega_origen_id']);
            $bodDest = intval($datos['bodega_destino_id']);

            if ($bodOrig === $bodDest) {
                throw new Exception("La bodega de origen no puede ser igual a la de destino.");
            }

            $stmtT = $this->db->prepare("
                INSERT INTO traslados_bodegas
                (numero_traslado, bodega_origen_id, bodega_destino_id, solicitado_por_user_id, despachado_por_user_id, fecha_despacho, estado, observaciones)
                VALUES
                (:num, :orig, :dest, :user_sol, :user_desp, NOW(), 'EN_TRANSITO', :obs)
            ");
            $stmtT->execute([
                ':num'       => $numTraslado,
                ':orig'      => $bodOrig,
                ':dest'      => $bodDest,
                ':user_sol'  => $user_id,
                ':user_desp' => $user_id,
                ':obs'       => trim($datos['observaciones'] ?? '')
            ]);
            $trasladoId = $this->db->lastInsertId();

            foreach ($items as $it) {
                $loteOrigId = intval($it['lote_id']);
                $cant = intval($it['cantidad']);

                // Validar lote origen y stock
                $stmtL = $this->db->prepare("SELECT * FROM inventario_lotes WHERE id = :id");
                $stmtL->execute([':id' => $loteOrigId]);
                $loteOrig = $stmtL->fetch(PDO::FETCH_ASSOC);

                if (!$loteOrig || $loteOrig['cantidad_actual'] < $cant) {
                    throw new Exception("Stock insuficiente en el lote #{$loteOrigId} para trasladar {$cant} unidades.");
                }

                // Guardar detalle
                $stmtDet = $this->db->prepare("
                    INSERT INTO traslados_detalle (traslado_id, producto_id, lote_id, cantidad_trasladada)
                    VALUES (:t_id, :p_id, :l_id, :cant)
                ");
                $stmtDet->execute([
                    ':t_id' => $trasladoId,
                    ':p_id' => $loteOrig['producto_id'],
                    ':l_id' => $loteOrigId,
                    ':cant' => $cant
                ]);

                // Descontar de Bodega Origen y registrar Kardex
                $this->registrarMovimientoKardex(
                    $bodOrig,
                    $loteOrig['producto_id'],
                    $loteOrigId,
                    'TRASLADO_SALIDA',
                    $cant,
                    $loteOrig['costo_unitario'],
                    $numTraslado,
                    $user_id,
                    "Salida por traslado hacia Bodega Destino #{$bodDest}"
                );
            }

            $this->db->commit();
            return ['status' => 'ok', 'traslado_id' => $trasladoId, 'numero_traslado' => $numTraslado];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function confirmarRecepcionTraslado($traslado_id, $user_id = 1) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM traslados_bodegas WHERE id = :id AND estado = 'EN_TRANSITO'");
            $stmt->execute([':id' => intval($traslado_id)]);
            $traslado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$traslado) {
                throw new Exception("El traslado no se encuentra en estado 'EN_TRANSITO' o no existe.");
            }

            $stmtDet = $this->db->prepare("SELECT * FROM traslados_detalle WHERE traslado_id = :id");
            $stmtDet->execute([':id' => intval($traslado_id)]);
            $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            $bodDest = $traslado['bodega_destino_id'];

            foreach ($detalles as $d) {
                // Obtener datos del lote de origen
                $stmtLOrig = $this->db->prepare("SELECT * FROM inventario_lotes WHERE id = :id");
                $stmtLOrig->execute([':id' => $d['lote_id']]);
                $lOrig = $stmtLOrig->fetch(PDO::FETCH_ASSOC);

                // Buscar o crear lote en Bodega Destino
                $stmtLDest = $this->db->prepare("
                    SELECT id FROM inventario_lotes 
                    WHERE bodega_id = :b_id AND producto_id = :p_id AND numero_lote = :lote
                ");
                $stmtLDest->execute([
                    ':b_id' => $bodDest,
                    ':p_id' => $d['producto_id'],
                    ':lote' => $lOrig['numero_lote']
                ]);
                $loteDestId = $stmtLDest->fetchColumn();

                if (!$loteDestId) {
                    $stmtNew = $this->db->prepare("
                        INSERT INTO inventario_lotes
                        (bodega_id, producto_id, numero_lote, fecha_fabricacion, fecha_vencimiento, fabricante_laboratorio, cantidad_actual, costo_unitario, estado_lote)
                        VALUES
                        (:b_id, :p_id, :lote, :fab, :venc, :lab, 0, :costo, 'DISPONIBLE')
                    ");
                    $stmtNew->execute([
                        ':b_id'  => $bodDest,
                        ':p_id'  => $d['producto_id'],
                        ':lote'  => $lOrig['numero_lote'],
                        ':fab'   => $lOrig['fecha_fabricacion'],
                        ':venc'  => $lOrig['fecha_vencimiento'],
                        ':lab'   => $lOrig['fabricante_laboratorio'],
                        ':costo' => $lOrig['costo_unitario']
                    ]);
                    $loteDestId = $this->db->lastInsertId();
                }

                // Sumar a bodega destino y registrar Kardex
                $this->registrarMovimientoKardex(
                    $bodDest,
                    $d['producto_id'],
                    $loteDestId,
                    'TRASLADO_ENTRADA',
                    $d['cantidad_trasladada'],
                    $lOrig['costo_unitario'],
                    $traslado['numero_traslado'],
                    $user_id,
                    "Recepción de traslado #{$traslado['numero_traslado']} proveniente de Bodega #{$traslado['bodega_origen_id']}"
                );
            }

            // Marcar traslado como recibido
            $stmtUp = $this->db->prepare("
                UPDATE traslados_bodegas 
                SET estado = 'RECIBIDO', recibido_por_user_id = :user_id, fecha_recepcion = NOW() 
                WHERE id = :id
            ");
            $stmtUp->execute([':user_id' => $user_id, ':id' => intval($traslado_id)]);

            $this->db->commit();
            return ['status' => 'ok', 'message' => 'Traslado recibido y stock acreditado exitosamente.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getTraslados($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   bo.nombre_bodega AS bodega_origen_nombre,
                   bd.nombre_bodega AS bodega_destino_nombre,
                   us.nombre_completo AS solicitado_por_nombre,
                   ur.nombre_completo AS recibido_por_nombre,
                   (SELECT COUNT(*) FROM traslados_detalle td WHERE td.traslado_id = t.id) AS total_items
            FROM traslados_bodegas t
            JOIN bodegas bo ON t.bodega_origen_id = bo.id
            JOIN bodegas bd ON t.bodega_destino_id = bd.id
            LEFT JOIN usuarios us ON t.solicitado_por_user_id = us.id
            LEFT JOIN usuarios ur ON t.recibido_por_user_id = ur.id
            ORDER BY t.id DESC LIMIT " . intval($limit)
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 7. GESTIÓN DE MEDICAMENTOS PENDIENTES Y DESPACHOS A DOMICILIO
    // =========================================================================

    public function getPendientesDomicilio($filtros = []) {
        $sql = "
            SELECT mp.*,
                   i.ticket_numero, i.fecha_ingreso, i.ips_remite,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos,
                   p.telefono, p.email, p.direccion_residencia, p.ciudad_residencia, p.eps_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingreso_medicamentos_pendientes mp
            JOIN ingresos i ON mp.ingreso_id = i.id
            JOIN pacientes p ON mp.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND mp.estado_domicilio = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['sede_id'])) {
            $sql .= " AND i.sede_id = :sede_id";
            $params[':sede_id'] = intval($filtros['sede_id']);
        }
        if (!empty($filtros['buscar'])) {
            $b = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombres LIKE :b1 OR p.apellidos LIKE :b2 OR p.numero_documento LIKE :b3 OR i.ticket_numero LIKE :b4 OR mp.nombre_medicamento LIKE :b5)";
            $params[':b1'] = $b;
            $params[':b2'] = $b;
            $params[':b3'] = $b;
            $params[':b4'] = $b;
            $params[':b5'] = $b;
        }

        $sql .= " ORDER BY mp.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoDomicilio($id, $estado, $guia = '', $mensajeria = '', $obs = '') {
        $stmt = $this->db->prepare("
            UPDATE ingreso_medicamentos_pendientes SET
                estado_domicilio = :estado,
                guia_domicilio = :guia,
                empresa_mensajeria = :mensajeria,
                fecha_despacho = IF(:estado1 = 'EN_RUTA_DOMICILIO', NOW(), fecha_despacho),
                fecha_entrega_domicilio = IF(:estado2 = 'ENTREGADO_DOMICILIO', NOW(), fecha_entrega_domicilio),
                observaciones = :obs
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'         => intval($id),
            ':estado'     => $estado,
            ':estado1'    => $estado,
            ':estado2'    => $estado,
            ':guia'       => trim($guia),
            ':mensajeria' => trim($mensajeria),
            ':obs'        => trim($obs)
        ]);
    }

    // =========================================================================
    // 8. DISPENSACIÓN INTELIGENTE FEFO, DESCUENTO DE STOCK Y GENERACIÓN DE ACTAS
    // =========================================================================

    public function getOrdenesListasDispensacion($sede_id = null, $filtros = []) {
        $sql = "
            SELECT ifa.id AS formula_ia_id, ifa.estado_ia, ifa.total_medicamentos, ifa.procesado_at, ifa.tiempo_segundos, ifa.motor_utilizado,
                   i.id AS ingreso_id, i.ticket_numero, i.fecha_ingreso, i.estado_tramite, i.prioridad, i.ips_remite, i.es_alto_costo,
                   p.id AS paciente_id, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono,
                   d.id AS documento_id, d.ruta_archivo, d.nombre_original,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   (SELECT COUNT(*) FROM ingreso_medicamentos_dispensados imd WHERE imd.ingreso_id = i.id) AS total_dispensados,
                   (SELECT COUNT(*) FROM ingreso_medicamentos_pendientes imp WHERE imp.ingreso_id = i.id) AS total_faltantes
            FROM ingresos i
            JOIN (
                SELECT MAX(id) AS max_ifa_id, ingreso_id
                FROM ingreso_formulas_ia
                WHERE estado_ia NOT IN ('DISPENSADO', 'CANCELADO', 'DESCARTADO', 'ERROR')
                GROUP BY ingreso_id
            ) latest_ifa ON latest_ifa.ingreso_id = i.id
            JOIN ingreso_formulas_ia ifa ON ifa.id = latest_ifa.max_ifa_id
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN ingreso_documentos d ON ifa.documento_id = d.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.estado_tramite NOT IN ('ENTREGADO', 'CANCELADO')
        ";
        $params = [];

        if (!empty($sede_id)) {
            $sql .= " AND (i.sede_id = :sede_id OR i.sede_id IS NULL)";
            $params[':sede_id'] = intval($sede_id);
        }

        if (!empty($filtros['estado_ia'])) {
            $sql .= " AND ifa.estado_ia = :estado_ia";
            $params[':estado_ia'] = $filtros['estado_ia'];
        }

        if (!empty($filtros['buscar'])) {
            $b = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombres LIKE :b1 OR p.apellidos LIKE :b2 OR p.numero_documento LIKE :b3 OR i.ticket_numero LIKE :b4)";
            $params[':b1'] = $b;
            $params[':b2'] = $b;
            $params[':b3'] = $b;
            $params[':b4'] = $b;
        }

        $sql .= " ORDER BY (ifa.estado_ia = 'PROCESADO') DESC, (i.prioridad != 'NORMAL') DESC, i.id DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetalleOrdenDispensacion($ingreso_id, $sede_id = null) {
        $stmtI = $this->db->prepare("
            SELECT i.*, 
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre, p.telefono, p.email, p.direccion_residencia, p.ciudad_residencia,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.id = :id
        ");
        $stmtI->execute([':id' => intval($ingreso_id)]);
        $ingreso = $stmtI->fetch(PDO::FETCH_ASSOC);
        if (!$ingreso) return null;

        // Documentos escaneados
        $stmtDocs = $this->db->prepare("SELECT * FROM ingreso_documentos WHERE ingreso_id = :id ORDER BY id ASC");
        $stmtDocs->execute([':id' => intval($ingreso_id)]);
        $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        // Fórmula IA
        $stmtIA = $this->db->prepare("SELECT * FROM ingreso_formulas_ia WHERE ingreso_id = :id ORDER BY id DESC LIMIT 1");
        $stmtIA->execute([':id' => intval($ingreso_id)]);
        $formulaIA = $stmtIA->fetch(PDO::FETCH_ASSOC);

        // Bodega activa de la sede
        $bodegas = $this->getBodegas($sede_id ?: $ingreso['sede_id']);
        $bodegaActiva = !empty($bodegas) ? $bodegas[0] : null;
        $bodegaId = $bodegaActiva ? $bodegaActiva['id'] : 1;

        // Medicamentos previamente dispensados o faltantes
        $stmtDisp = $this->db->prepare("
            SELECT md.*, p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.codigo_sku, p.codigo_cums,
                   il.numero_lote, il.fecha_vencimiento, il.fabricante_laboratorio
            FROM ingreso_medicamentos_dispensados md
            JOIN productos_medicamentos p ON md.producto_id = p.id
            JOIN inventario_lotes il ON md.lote_id = il.id
            WHERE md.ingreso_id = :id
        ");
        $stmtDisp->execute([':id' => intval($ingreso_id)]);
        $dispensados = $stmtDisp->fetchAll(PDO::FETCH_ASSOC);

        $stmtFalt = $this->db->prepare("SELECT * FROM ingreso_medicamentos_pendientes WHERE ingreso_id = :id");
        $stmtFalt->execute([':id' => intval($ingreso_id)]);
        $faltantes = $stmtFalt->fetchAll(PDO::FETCH_ASSOC);

        // Extraer medicamentos del JSON si existen
        $medsExtraidos = [];
        if (!empty($formulaIA['datos_extraidos_json'])) {
            $parsedIA = json_decode($formulaIA['datos_extraidos_json'], true);
            if (!empty($parsedIA['medicamentos']) && is_array($parsedIA['medicamentos'])) {
                $medsExtraidos = $parsedIA['medicamentos'];
            } elseif (is_array($parsedIA)) {
                $medsExtraidos = $parsedIA;
            }
        }

        return [
            'ingreso'                => $ingreso,
            'documentos'             => $documentos,
            'formula_ia'             => $formulaIA,
            'ia_record'              => $formulaIA,
            'medicamentos_extraidos' => $medsExtraidos,
            'bodega_activa'          => $bodegaActiva,
            'dispensados'            => $dispensados,
            'faltantes'              => $faltantes
        ];
    }

    public function sugerirLoteFEFO($producto_id, $bodega_id = null) {
        $lotes = $this->getLotesDisponiblesFEFO($producto_id, null);
        if (empty($lotes)) return null;

        // Filtrar por bodega específica si se indica
        if (!empty($bodega_id)) {
            foreach ($lotes as $l) {
                if ($l['bodega_id'] == $bodega_id && $l['cantidad_actual'] > 0) {
                    return $l;
                }
            }
        }
        return $lotes[0]; // Retorna el más próximo a vencer disponible
    }

    public function dispensarMedicamentosIngreso($ingreso_id, $medicamentos_dispensados, $medicamentos_faltantes, $user_id, $observaciones = '', $firma_data = '', $foto_data = '') {
        $this->db->beginTransaction();
        try {
            $stmtI = $this->db->prepare("SELECT id, paciente_id, ticket_numero, sede_id, estado_tramite FROM ingresos WHERE id = :id");
            $stmtI->execute([':id' => intval($ingreso_id)]);
            $ingreso = $stmtI->fetch(PDO::FETCH_ASSOC);
            if (!$ingreso) {
                throw new Exception("El ingreso especificado no existe.");
            }

            // GUARDIA DE IDEMPOTENCIA: Si ya fue entregado y ya tiene registros dispensados, no duplicar ni descontar doble inventario
            $stmtCheckDisp = $this->db->prepare("SELECT COUNT(*) FROM ingreso_medicamentos_dispensados WHERE ingreso_id = :id");
            $stmtCheckDisp->execute([':id' => intval($ingreso_id)]);
            $cantYaDispensada = intval($stmtCheckDisp->fetchColumn());

            if ($ingreso['estado_tramite'] === 'ENTREGADO' && $cantYaDispensada > 0) {
                $this->db->commit();
                return [
                    'status'        => 'ok',
                    'message'       => 'Esta orden ya fue dispensada previamente.',
                    'ticket_numero' => $ingreso['ticket_numero'],
                    'ya_entregado'  => true
                ];
            }

            // 1. Procesar Medicamentos a Dispensar (Descuento de Stock & Kardex)
            $stmtInsertDisp = $this->db->prepare("
                INSERT INTO ingreso_medicamentos_dispensados
                (ingreso_id, producto_id, lote_id, bodega_id, cantidad_prescrita, cantidad_entregada, dosis_indicacion, duracion_dias, entregado_por_user_id)
                VALUES
                (:ingreso_id, :producto_id, :lote_id, :bodega_id, :cant_presc, :cant_entregada, :dosis, :duracion, :user_id)
            ");

            foreach ($medicamentos_dispensados as $item) {
                $producto_id    = intval($item['producto_id'] ?? 0);
                $lote_id        = intval($item['lote_id'] ?? 0);
                $bodega_id      = intval($item['bodega_id'] ?? 1);
                $cant_prescrita = intval($item['cantidad_prescrita'] ?? 1);
                $cant_entregar  = intval($item['cantidad_entregar'] ?? 1);
                $dosis          = trim($item['posologia'] ?? $item['dosis_indicacion'] ?? '');
                $duracion       = intval($item['duracion_dias'] ?? 30);

                if ($cant_entregar <= 0) continue;

                $lote = null;

                // 1. Si tenemos lote_id específico > 0
                if ($lote_id > 0) {
                    $stmtLote = $this->db->prepare("
                        SELECT id, bodega_id, cantidad_actual, costo_unitario, numero_lote, fecha_vencimiento
                        FROM inventario_lotes 
                        WHERE id = :id FOR UPDATE
                    ");
                    $stmtLote->execute([':id' => $lote_id]);
                    $lote = $stmtLote->fetch(PDO::FETCH_ASSOC);
                }

                // 2. Si no vino lote_id o no existe, resolver por FEFO automático para el producto
                if (!$lote && $producto_id > 0) {
                    $stmtLoteAuto = $this->db->prepare("
                        SELECT id, bodega_id, cantidad_actual, costo_unitario, numero_lote, fecha_vencimiento
                        FROM inventario_lotes
                        WHERE producto_id = :prod_id AND estado_lote = 'ACTIVO' AND cantidad_actual > 0
                        ORDER BY fecha_vencimiento ASC
                        LIMIT 1 FOR UPDATE
                    ");
                    $stmtLoteAuto->execute([':prod_id' => $producto_id]);
                    $lote = $stmtLoteAuto->fetch(PDO::FETCH_ASSOC);
                    if ($lote) {
                        $lote_id = intval($lote['id']);
                        $bodega_id = intval($lote['bodega_id']);
                    }
                }

                // 3. Si se encontró lote con inventario
                if ($lote) {
                    $cantDescontar = min($cant_entregar, $lote['cantidad_actual']);
                    if ($cantDescontar > 0) {
                        $this->registrarMovimientoKardex(
                            $lote['bodega_id'],
                            $producto_id,
                            $lote_id,
                            'SALIDA_DISPENSACION',
                            $cantDescontar,
                            $lote['costo_unitario'],
                            $ingreso['ticket_numero'],
                            $user_id,
                            "Dispensación Tiquete #{$ingreso['ticket_numero']} al Paciente ID #{$ingreso['paciente_id']}"
                        );
                    }
                    $bodega_id = $lote['bodega_id'];
                } else {
                    // Fallback seguro: obtener el primer lote del sistema para registro en histórico sin bloquear firma
                    $stmtPrimerLote = $this->db->query("SELECT id, bodega_id FROM inventario_lotes ORDER BY id ASC LIMIT 1");
                    $primerLote = $stmtPrimerLote->fetch(PDO::FETCH_ASSOC);
                    $lote_id = $primerLote ? intval($primerLote['id']) : 1;
                    $bodega_id = $primerLote ? intval($primerLote['bodega_id']) : 1;
                }

                // Insertar en historial de dispensados
                $stmtInsertDisp->execute([
                    ':ingreso_id'       => $ingreso_id,
                    ':producto_id'      => $producto_id > 0 ? $producto_id : 1,
                    ':lote_id'          => $lote_id,
                    ':bodega_id'        => $bodega_id,
                    ':cant_presc'       => $cant_prescrita,
                    ':cant_entregada'   => $cant_entregar,
                    ':dosis'            => $dosis,
                    ':duracion'         => $duracion,
                    ':user_id'          => $user_id
                ]);
            }

            // 2. Procesar Medicamentos Faltantes (Para Domicilio / Compra)
            if (!empty($medicamentos_faltantes)) {
                // Prevenir duplicados eliminando registros previos de faltantes para este ingreso
                $this->db->prepare("DELETE FROM ingreso_medicamentos_pendientes WHERE ingreso_id = :iid")->execute([':iid' => $ingreso_id]);

                $stmtFaltante = $this->db->prepare("
                    INSERT INTO ingreso_medicamentos_pendientes
                    (ingreso_id, paciente_id, producto_id, nombre_medicamento, concentracion, forma_farmaceutica, cantidad_solicitada, cantidad_pendiente, motivo_faltante, estado_domicilio, observaciones)
                    VALUES
                    (:ingreso_id, :paciente_id, :producto_id, :nombre, :concentracion, :forma, :cant_sol, :cant_pend, 'SIN_STOCK_BODEGA', 'PENDIENTE_ALISTAR', :obs)
                ");

                foreach ($medicamentos_faltantes as $falt) {
                    $cantPend = intval($falt['cantidad_pendiente'] ?? 1);
                    if ($cantPend <= 0) continue;

                    $stmtFaltante->execute([
                        ':ingreso_id'      => $ingreso_id,
                        ':paciente_id'     => $ingreso['paciente_id'],
                        ':producto_id'     => !empty($falt['producto_id']) ? intval($falt['producto_id']) : null,
                        ':nombre'          => trim($falt['nombre_medicamento'] ?? 'MEDICAMENTO FORMULADO'),
                        ':concentracion'   => trim($falt['concentracion'] ?? ''),
                        ':forma'           => trim($falt['forma_farmaceutica'] ?? ''),
                        ':cant_sol'        => intval($falt['cantidad_solicitada'] ?? $cantPend),
                        ':cant_pend'       => $cantPend,
                        ':obs'             => trim($falt['observaciones'] ?? 'Faltante registrado automáticamente en dispensación.')
                    ]);
                }
            }

            // 3. Guardar Firma Digital si se suministra
            $firmaUrl = null;
            $pac = null;
            if (!empty($firma_data)) {
                $firma_data = trim($firma_data);
                if (strpos($firma_data, 'data:image') !== 0 && strlen($firma_data) > 50) {
                    $firma_data = 'data:image/png;base64,' . $firma_data;
                }
            }
            if (!empty($firma_data) && strpos($firma_data, 'data:image') === 0) {
                $stmtP = $this->db->prepare("SELECT tipo_documento, numero_documento FROM pacientes WHERE id = :id");
                $stmtP->execute([':id' => $ingreso['paciente_id']]);
                $pac = $stmtP->fetch(PDO::FETCH_ASSOC);

                $folder = public_path() . '/assets/uploads/pacientes/' . ($pac['tipo_documento'] ?? 'CC') . '_' . ($pac['numero_documento'] ?? '0') . '/firmas/';
                if (!file_exists($folder)) mkdir($folder, 0755, true);

                $firmaImg = preg_replace('#^data:image/\w+;base64,#i', '', $firma_data);
                $firmaImg = str_replace(' ', '+', $firmaImg);
                $firmaDecoded = base64_decode($firmaImg);

                $fileName = 'firma_' . $ingreso['ticket_numero'] . '_' . time() . '.png';
                file_put_contents($folder . $fileName, $firmaDecoded);
                $firmaUrl = 'assets/uploads/pacientes/' . ($pac['tipo_documento'] ?? 'CC') . '_' . ($pac['numero_documento'] ?? '0') . '/firmas/' . $fileName;
            }

            // 3.1 Guardar Foto del Paciente / Receptor si se suministra
            $fotoUrl = null;
            if (!empty($foto_data) && strpos($foto_data, 'data:image') === 0) {
                if (empty($pac)) {
                    $stmtP = $this->db->prepare("SELECT tipo_documento, numero_documento FROM pacientes WHERE id = :id");
                    $stmtP->execute([':id' => $ingreso['paciente_id']]);
                    $pac = $stmtP->fetch(PDO::FETCH_ASSOC);
                }
                $folderFotos = public_path() . '/assets/uploads/pacientes/' . ($pac['tipo_documento'] ?? 'CC') . '_' . ($pac['numero_documento'] ?? '0') . '/fotos/';
                if (!file_exists($folderFotos)) mkdir($folderFotos, 0755, true);

                $fotoImg = preg_replace('#^data:image/\w+;base64,#i', '', $foto_data);
                $fotoImg = str_replace(' ', '+', $fotoImg);
                $fotoDecoded = base64_decode($fotoImg);

                $fileNameFoto = 'foto_entrega_' . $ingreso['ticket_numero'] . '_' . time() . '.jpg';
                file_put_contents($folderFotos . $fileNameFoto, $fotoDecoded);
                $fotoUrl = 'assets/uploads/pacientes/' . ($pac['tipo_documento'] ?? 'CC') . '_' . ($pac['numero_documento'] ?? '0') . '/fotos/' . $fileNameFoto;
            }

            // 4. Actualizar Estado del Ingreso (CERRAR TIQUETE) y de la Fórmula IA
            $stmtUpIngreso = $this->db->prepare("
                UPDATE ingresos SET
                    estado_tramite = 'ENTREGADO',
                    fecha_salida = NOW(),
                    salida_por_user_id = :user_id,
                    observacion_salida = :obs,
                    locked_by_user_id = NULL,
                    locked_at = NULL,
                    firma_paciente_url = COALESCE(:firma_url, firma_paciente_url),
                    foto_paciente_url = COALESCE(:foto_url, foto_paciente_url)
                WHERE id = :id
            ");
            $stmtUpIngreso->execute([
                ':user_id'   => $user_id,
                ':obs'       => trim($observaciones ?: 'Dispensación electrónica, descuento de inventario y cierre de tiquete.'),
                ':firma_url' => $firmaUrl,
                ':foto_url'  => $fotoUrl,
                ':id'        => $ingreso_id
            ]);

            // Marcar fórmula IA como dispensada
            $stmtUpIA = $this->db->prepare("UPDATE ingreso_formulas_ia SET estado_ia = 'DISPENSADO' WHERE ingreso_id = :id");
            $stmtUpIA->execute([':id' => $ingreso_id]);

            $this->db->commit();
            return [
                'status'        => 'ok',
                'message'       => 'Dispensación registrada, stock descontado y acta generada con éxito.',
                'ticket_numero' => $ingreso['ticket_numero'],
                'firma_url'     => $firmaUrl,
                'foto_url'      => $fotoUrl
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 8. RESERVAS TEMPORALES DE STOCK (ALISTAMIENTO & PICKING FEFO)
    // =========================================================================

    public function reservarStockTemporal($ingreso_id, $items_a_reservar = [], $user_id = 1, $minutos_reserva = 120) {
        $ingreso_id = intval($ingreso_id);
        if ($ingreso_id <= 0 || empty($items_a_reservar)) {
            return false;
        }

        try {
            // Liberar cualquier reserva activa previa de este ingreso
            $this->liberarReservaTemporal($ingreso_id);

            $stmtIns = $this->db->prepare("
                INSERT INTO inventario_reservas_temporales 
                (ingreso_id, producto_id, lote_id, bodega_id, cantidad_reservada, usuario_id, session_token, estado_reserva, expires_at)
                VALUES 
                (:ingreso_id, :prod_id, :lote_id, :bodega_id, :cant, :uid, :token, 'ACTIVA', DATE_ADD(NOW(), INTERVAL :minutos MINUTE))
            ");

            $sessionToken = bin2hex(random_bytes(16));

            foreach ($items_a_reservar as $item) {
                $prodId = intval($item['producto_id'] ?? 0);
                $loteId = intval($item['lote_id'] ?? 0);
                $bodegaId = intval($item['bodega_id'] ?? 1);
                $cant = intval($item['cantidad_entregar'] ?? $item['cantidad_prescrita'] ?? 1);

                if ($cant <= 0) continue;

                // Si no vino lote específico, resolver el lote FEFO
                if ($loteId <= 0 && $prodId > 0) {
                    $stmtL = $this->db->prepare("
                        SELECT id, bodega_id FROM inventario_lotes 
                        WHERE producto_id = :p AND (estado_lote = 'DISPONIBLE' OR estado_lote = 'ACTIVO' OR estado_lote IS NULL) AND cantidad_actual > 0 
                        ORDER BY fecha_vencimiento ASC LIMIT 1
                    ");
                    $stmtL->execute([':p' => $prodId]);
                    $lMatch = $stmtL->fetch(PDO::FETCH_ASSOC);
                    if ($lMatch) {
                        $loteId = intval($lMatch['id']);
                        $bodegaId = intval($lMatch['bodega_id']);
                    } else {
                        $loteId = 1;
                    }
                }

                $stmtIns->execute([
                    ':ingreso_id' => $ingreso_id,
                    ':prod_id'    => $prodId > 0 ? $prodId : 1,
                    ':lote_id'    => $loteId > 0 ? $loteId : 1,
                    ':bodega_id'  => $bodegaId > 0 ? $bodegaId : 1,
                    ':cant'       => $cant,
                    ':uid'        => $user_id,
                    ':token'      => $sessionToken,
                    ':minutos'    => intval($minutos_reserva)
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error al reservar stock temporal: " . $e->getMessage());
            return false;
        }
    }

    public function liberarReservaTemporal($ingreso_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE inventario_reservas_temporales 
                SET estado_reserva = 'LIBERADA' 
                WHERE ingreso_id = :id AND estado_reserva = 'ACTIVA'
            ");
            return $stmt->execute([':id' => intval($ingreso_id)]);
        } catch (Exception $e) {
            return false;
        }
    }
}