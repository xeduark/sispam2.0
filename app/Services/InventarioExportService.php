<?php

namespace App\Services;

use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

/** Exportaciones CSV (kardex, catálogo, saldos) y vista previa. Migrado de inventario/exportar.php. */
class InventarioExportService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    /** Devuelve [nombre, callback(csvWriter)] o null si el tipo no existe. */
    public function csv(string $tipo, array $f): ?array
    {
        $ts = date('Ymd_His');

        return match ($tipo) {
            'kardex' => ["sispam_kardex_movimientos_{$f['desde']}_{$f['hasta']}_{$ts}.csv", fn ($csv) => $this->kardex($csv, $f)],
            'catalogo' => ["Maestro_Articulos_Export_{$ts}.csv", fn ($csv) => $this->catalogo($csv)],
            'saldos' => ["sispam_toma_inventario_saldos_{$ts}.csv", fn ($csv) => $this->saldos($csv, $f['bodega'])],
            default => null,
        };
    }

    private function filtros(array $f, string &$sql, array &$params): void
    {
        if (!empty($f['bodega'])) { $sql .= " AND k.bodega_id = :bodega_id"; $params[':bodega_id'] = $f['bodega']; }
        if (!empty($f['tipo'])) { $sql .= " AND k.tipo_movimiento = :tipo_mov"; $params[':tipo_mov'] = $f['tipo']; }
        if (!empty($f['desde'])) { $sql .= " AND DATE(k.created_at) >= :fecha_desde"; $params[':fecha_desde'] = $f['desde']; }
        if (!empty($f['hasta'])) { $sql .= " AND DATE(k.created_at) <= :fecha_hasta"; $params[':fecha_hasta'] = $f['hasta']; }
        if (!empty($f['buscar'])) {
            $sql .= " AND (p.nombre_generico LIKE :b1 OR p.nombre_comercial LIKE :b2 OR p.codigo_sku LIKE :b3 OR il.numero_lote LIKE :b4 OR k.referencia_documento LIKE :b5)";
            foreach (['b1', 'b2', 'b3', 'b4', 'b5'] as $b) { $params[":$b"] = '%' . $f['buscar'] . '%'; }
        }
    }

    private function kardex(callable $csv, array $f): void
    {
        $sql = "
            SELECT k.id AS movimiento_id,
                   k.tipo_movimiento,
                   k.cantidad,
                   k.stock_anterior,
                   k.stock_nuevo,
                   k.costo_unitario,
                   k.costo_total,
                   k.referencia_documento,
                   k.observaciones,
                   k.created_at AS fecha_hora_movimiento,
                   p.codigo_sku AS idarticulo,
                   p.nombre_comercial AS descripcion_medicamento,
                   p.nombre_generico,
                   COALESCE(p.codigo_cums, '') AS codigo_cums,
                   COALESCE(p.registro_invima, '') AS registro_invima,
                   COALESCE(il.numero_lote, 'S/L') AS lote,
                   COALESCE(il.fabricante_laboratorio, p.fabricante_laboratorio, 'N/A') AS laboratorio_marca,
                   COALESCE(il.fecha_vencimiento, '') AS fecha_vencimiento,
                   b.nombre_bodega,
                   COALESCE(s.nombre_sede, 'Almacén Central') AS nombre_sede,
                   COALESCE(u.nombre_completo, 'SISTEMA') AS usuario_responsable
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            JOIN bodegas b ON k.bodega_id = b.id
            LEFT JOIN sedes s ON b.sede_id = s.id
            LEFT JOIN usuarios u ON k.usuario_id = u.id
            WHERE 1=1
        ";
        $params = [];
        $this->filtros($f, $sql, $params);
        $sql .= " ORDER BY k.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $csv([
            'ID_MOVIMIENTO', 'IDARTICULO', 'DESCRIPCION_MEDICAMENTO', 'NOMBRE_GENERICO',
            'LABORATORIO_MARCA', 'LOTE', 'FECHA_VENCIMIENTO', 'TIPO_MOVIMIENTO',
            'CANTIDAD', 'STOCK_ANTERIOR', 'STOCK_NUEVO', 'COSTO_UNITARIO', 'VALOR_TOTAL',
            'DOC_REFERENCIA_TICKET', 'FECHA_HORA_MOVIMIENTO', 'BODEGA', 'SEDE',
            'USUARIO_RESPONSABLE', 'CUMS', 'REGISTRO_INVIMA', 'OBSERVACIONES',
        ]);
        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $csv([
                $r['movimiento_id'],
                $r['idarticulo'],
                $r['descripcion_medicamento'],
                $r['nombre_generico'],
                $r['laboratorio_marca'],
                $r['lote'],
                $r['fecha_vencimiento'],
                $r['tipo_movimiento'],
                $r['cantidad'],
                $r['stock_anterior'],
                $r['stock_nuevo'],
                number_format($r['costo_unitario'], 2, '.', ''),
                number_format($r['costo_total'], 2, '.', ''),
                $r['referencia_documento'],
                $r['fecha_hora_movimiento'],
                $r['nombre_bodega'],
                $r['nombre_sede'],
                $r['usuario_responsable'],
                $r['codigo_cums'],
                $r['registro_invima'],
                $r['observaciones']
            ]);
        }
    }

    private function catalogo(callable $csv): void
    {
        $stmt = $this->db->query("
            SELECT p.*,
                   (SELECT COALESCE(SUM(il.cantidad_actual), 0) FROM inventario_lotes il WHERE il.producto_id = p.id AND il.estado_lote = 'DISPONIBLE') AS stock_total_disponible
            FROM productos_medicamentos p
            ORDER BY p.nombre_generico ASC
        ");
        $csv([
            'IDARTICULO', 'DESCRIPCION', 'MANEJA_IVA', 'ARTICULO_PRINCIPAL', 'IDITAR',
            'DESCTIPOARTICULO', 'ESTADO', 'NIT', 'RAZONSOCIAL', 'IDCLASE',
            'DESCLASE', 'IDSUBCLASE', 'DESCSUBCLASE', 'IDGRUPO', 'DESCGRUPO',
            'IDPRINACTIVO', 'DESCPRINCIPIO', 'IDFORFARM', 'DESCFORMAFAR', 'IDUNIDAD',
            'DESCUNIDAD', 'PCOSTO', 'PRECIO_REGULADO', 'IDGENERICO', 'DESCGENERICO',
            'CODCUM', 'REGINVIMA', 'UNID_VENTA', 'ALTO_COSTO', 'CONTROLADO',
            'REGULADO', 'NOPOS', 'USO_INSTITUCIONAL', 'BIOLOGICO', 'SKU',
            'REQUIERE_CADENA_FRIO', 'FECHA_VIGENCIA_INVIMA', 'CODIGO_IUM', 'DE_MARCA', 'OBSERVACION'
        ]);
        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $nomGen = trim($r['nombre_generico'] ?? '');
            $nomCom = trim($r['nombre_comercial'] ?? '');
            $esMarca = (!empty($nomCom) && mb_strtoupper($nomCom, 'UTF-8') !== mb_strtoupper($nomGen, 'UTF-8')) ? 'Si' : 'No';
            $noPos = (isset($r['es_pos']) && intval($r['es_pos']) == 1) ? '0' : '1';
            $estadoTxt = ($r['estado_activo'] === 'Activo' || $r['estado_activo'] == '1') ? 'Activo' : 'Inactivo';

            $csv([
                $r['codigo_sku'],                                                 // [0] IDARTICULO
                $nomCom ?: $nomGen,                                                // [1] DESCRIPCION
                'No',                                                             // [2] MANEJA_IVA
                'Si',                                                             // [3] ARTICULO_PRINCIPAL
                '01',                                                             // [4] IDITAR
                'MEDICAMENTOS',                                                   // [5] DESCTIPOARTICULO
                $estadoTxt,                                                       // [6] ESTADO
                $r['laboratorio_nit'] ?? '',                                      // [7] NIT
                $r['fabricante_laboratorio'] ?? '',                               // [8] RAZONSOCIAL
                '',                                                               // [9] IDCLASE
                $r['clase_terapeutica'] ?? '',                                    // [10] DESCLASE
                '',                                                               // [11] IDSUBCLASE
                '',                                                               // [12] DESCSUBCLASE
                '',                                                               // [13] IDGRUPO
                '',                                                               // [14] DESCGRUPO
                '',                                                               // [15] IDPRINACTIVO
                $r['principio_activo'] ?: $nomGen,                                // [16] DESCPRINCIPIO
                '',                                                               // [17] IDFORFARM
                $r['forma_farmaceutica'] ?? '',                                   // [18] DESCFORMAFAR
                '',                                                               // [19] IDUNIDAD
                $r['concentracion'] ?? '',                                        // [20] DESCUNIDAD
                number_format((float)($r['precio_referencia'] ?? 0), 2, '.', ''), // [21] PCOSTO
                '0',                                                              // [22] PRECIO_REGULADO
                $r['codigo_atc'] ?? '',                                           // [23] IDGENERICO
                $nomGen,                                                          // [24] DESCGENERICO
                $r['codigo_cums'] ?? '',                                          // [25] CODCUM
                $r['registro_invima'] ?? '',                                      // [26] REGINVIMA
                $r['presentacion_comercial'] ?: 'UNIDAD',                         // [27] UNID_VENTA
                !empty($r['es_alto_costo']) ? '1' : '0',                          // [28] ALTO_COSTO
                !empty($r['es_control_especial']) ? '1' : '0',                    // [29] CONTROLADO
                '0',                                                              // [30] REGULADO
                $noPos,                                                           // [31] NOPOS
                !empty($r['uso_institucional']) ? '1' : '0',                      // [32] USO_INSTITUCIONAL
                !empty($r['es_biologico']) ? '1' : '0',                           // [33] BIOLOGICO
                $r['codigo_sku'] ?? '',                                           // [34] SKU
                !empty($r['requiere_cadena_frio']) ? '1' : '0',                   // [35] REQUIERE_CADENA_FRIO
                $r['fecha_vigencia_invima'] ?? '',                                // [36] FECHA_VIGENCIA_INVIMA
                $r['codigo_ium'] ?? '',                                           // [37] CODIGO_IUM
                $esMarca,                                                         // [38] DE_MARCA
                $r['observaciones'] ?? ''                                         // [39] OBSERVACION
            ]);
        }
    }

    private function saldos(callable $csv, ?int $bodegaId): void
    {
        $stockRows = (new Inventario())->getStockGeneralPorBodega($bodegaId);
        $csv([
            'BODEGA', 'SEDE', 'IDARTICULO', 'DESCRIPCION_COMERCIAL', 'NOMBRE_GENERICO',
            'LABORATORIO_MARCA', 'LOTE', 'FECHA_VENCIMIENTO', 'SEMAFORO_FEFO',
            'DIAS_PARA_VENCIMIENTO', 'STOCK_ACTUAL', 'COSTO_UNITARIO', 'VALOR_TOTAL_STOCK',
            'CUMS', 'INVIMA', 'CADENA_FRIO', 'CONTROL_ESPECIAL',
        ]);
        foreach ($stockRows as $r) {
            $cant = intval($r['cantidad_actual']);
            $costo = floatval($r['costo_unitario']);
            $csv([
                $r['nombre_bodega'],
                $r['nombre_sede'],
                $r['codigo_sku'],
                $r['nombre_comercial'],
                $r['nombre_generico'],
                $r['fabricante_laboratorio'],
                $r['numero_lote'],
                $r['fecha_vencimiento'],
                $r['semaforo']['codigo'] ?? 'N/A',
                $r['semaforo']['dias'] ?? 0,
                $cant,
                number_format($costo, 2, '.', ''),
                number_format($cant * $costo, 2, '.', ''),
                $r['codigo_cums'],
                $r['registro_invima'],
                $r['requiere_cadena_frio'] ? 'SI' : 'NO',
                $r['es_control_especial'] ? 'SI' : 'NO'
            ]);
        }
    }

    /** Vista previa (50 filas) + totales del filtro. */
    public function preview(array $f): array
    {
        $sql = "
            SELECT k.*,
                   p.codigo_sku AS idarticulo,
                   p.nombre_generico, p.nombre_comercial,
                   COALESCE(il.numero_lote, 'S/L') AS lote,
                   COALESCE(il.fabricante_laboratorio, p.fabricante_laboratorio, 'N/A') AS laboratorio_marca,
                   COALESCE(il.fecha_vencimiento, '') AS fecha_vencimiento,
                   b.nombre_bodega,
                   u.nombre_completo AS usuario_nombre
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            JOIN bodegas b ON k.bodega_id = b.id
            LEFT JOIN usuarios u ON k.usuario_id = u.id
            WHERE 1=1
        ";
        $params = [];
        $this->filtros($f, $sql, $params);
        $stmt = $this->db->prepare($sql . " ORDER BY k.id DESC LIMIT 50");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $sql = "
            SELECT COUNT(*) AS total_count, COALESCE(SUM(k.cantidad), 0) AS total_unidades, COALESCE(SUM(k.costo_total), 0) AS total_valor
            FROM movimientos_kardex k
            JOIN productos_medicamentos p ON k.producto_id = p.id
            LEFT JOIN inventario_lotes il ON k.lote_id = il.id
            WHERE 1=1
        ";
        $params = [];
        $this->filtros($f, $sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [$rows, $stmt->fetch(\PDO::FETCH_ASSOC)];
    }
}
