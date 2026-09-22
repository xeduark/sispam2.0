<?php

namespace App\Services;

use App\Models\Inventario;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Importación masiva de catálogo maestro y saldos/lotes (CSV/XLSX). Migrado de inventario/importar.php.
 */
class InventarioImportService
{
    private \PDO $db;
    public array $resumen;

    public function __construct(private string $modo, private int $bodegaDestinoId, private string $tipoMovimiento, private bool $sobrescribir, private int $userId)
    {
        $this->db = DB::connection()->getPdo();
        $this->resumen = ['modo' => $modo, 'total_filas' => 0, 'insertados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'errores' => [], 'tiempo_inicio' => microtime(true)];
    }

    /** Procesa el archivo completo y devuelve el resumen. */
    public function procesar(string $path, string $ext): array
    {
        $fila = fn ($a) => $this->modo === 'catalogo' ? $this->procesarFilaCatalogo($a) : $this->procesarFilaSaldos($a);
        if ($ext === 'xlsx') {
            InventarioXlsxStreamReader::parse($path, function ($chunk) use ($fila) {
                foreach ($chunk as $f) { $fila($f); }
            }, 500);
        } else {
            $handle = fopen($path, 'r');
            $firstLine = fgets($handle);
            rewind($handle);
            $delim = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
            if (fread($handle, 3) !== "\xEF\xBB\xBF") { rewind($handle); }
            $headers = fgetcsv($handle, 0, $delim);
            if ($headers) {
                $headers = array_map(fn ($h) => strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $h))), $headers);
                while (($row = fgetcsv($handle, 0, $delim)) !== false) {
                    if (empty($row) || (count($row) === 1 && empty($row[0]))) continue;
                    $assoc = [];
                    foreach ($headers as $idx => $hName) {
                        if (!empty($hName)) { $assoc[$hName] = $row[$idx] ?? ''; }
                    }
                    $fila($assoc);
                }
            }
            fclose($handle);
        }
        $this->resumen['duracion'] = round(microtime(true) - $this->resumen['tiempo_inicio'], 2);
        return $this->resumen;
    }

    private function normalizarFecha($val)
    {
                if (empty($val)) return null;
                $val = trim((string)$val);
                if ($val === '' || $val === '0000-00-00' || in_array(strtoupper($val), ['N/A', 'NA', 'NULL', 'NO APLICA', 'INDEFINIDO', 'VIGENTE', 'NO REGISTRA', 'NONE'])) {
                    return null;
                }
                // Si viene en formato ISO 8601 ej: '2999-01-01T00:00:00.000Z' o '2028-12-31 00:00:00'
                if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})[T\s]/', $val, $m)) {
                    return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                }
                // Formato YYYY-MM-DD
                if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $val, $m)) {
                    return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                }
                // Formato DD/MM/YYYY o DD-MM-YYYY
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $val, $m)) {
                    return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                }
                // Formato numérico de Excel (días desde 1900)
                if (is_numeric($val) && intval($val) > 30000 && intval($val) < 100000) {
                    $unixTimestamp = ($val - 25569) * 86400;
                    return gmdate('Y-m-d', $unixTimestamp);
                }
                // Fallback con strtotime
                $t = strtotime($val);
                if ($t !== false && $t > 0) {
                    return date('Y-m-d', $t);
                }
                return null;
    }

    private function procesarFilaCatalogo($fila)
    {
                $this->resumen['total_filas']++;
                
                try {
                    // Mapeo flexible para columnas tanto de SISPAM como del formato legado (Maestro_Articulos)
                    $sku = trim($fila['idarticulo'] ?? $fila['codigo_sku'] ?? $fila['sku'] ?? $fila['codigo'] ?? '');
                    $nombreComercial = trim($fila['descripcion'] ?? $fila['nombre_comercial'] ?? $fila['producto'] ?? '');
                    $nombreGenerico = trim($fila['descgenerico'] ?? $fila['nombre_generico'] ?? $fila['generico'] ?? '');

                    // Extraer y normalizar tipo de producto (MEDICAMENTOS, DISPOSITIVOS MEDICOS, INSUMOS, MEDICAMENTOS UNIRS)
                    $rawTipo = trim($fila['desctipoarticulo'] ?? $fila['tipo'] ?? $fila['tipo_producto'] ?? $fila['tipo_articulo'] ?? '');
                    $tipoUpper = mb_strtoupper($rawTipo, 'UTF-8');
                    if (strpos($tipoUpper, 'DISPOSITIV') !== false) {
                        $tipoProd = 'DISPOSITIVOS MEDICOS';
                    } elseif (strpos($tipoUpper, 'INSUMO') !== false) {
                        $tipoProd = 'INSUMOS';
                    } elseif (strpos($tipoUpper, 'UNIRS') !== false) {
                        $tipoProd = 'MEDICAMENTOS UNIRS';
                    } elseif (strpos($tipoUpper, 'MEDICAMENT') !== false) {
                        $tipoProd = 'MEDICAMENTOS';
                    } else {
                        $tipoProd = (strpos($sku, 'DM') === 0 || strpos($fila['reginvima'] ?? '', 'DM-') !== false) ? 'DISPOSITIVOS MEDICOS' : 'MEDICAMENTOS';
                    }

                    if (empty($nombreGenerico)) {
                        $nombreGenerico = ($tipoProd === 'DISPOSITIVOS MEDICOS' || $tipoProd === 'INSUMOS') ? 'NO APLICA' : $nombreComercial;
                    }

                    $laboratorio = trim($fila['razonsocial'] ?? $fila['fabricante_laboratorio'] ?? $fila['laboratorio'] ?? $fila['marca'] ?? '');
                    $nit = trim($fila['nit'] ?? $fila['laboratorio_nit'] ?? '');
                    $cums = trim($fila['codcum'] ?? $fila['codigo_cums'] ?? $fila['cums'] ?? '');
                    $invima = trim($fila['reginvima'] ?? $fila['registro_invima'] ?? $fila['invima'] ?? '');
                    $vencInvima = $this->normalizarFecha($fila['fecha_vigencia_invima'] ?? $fila['vigencia_invima'] ?? $fila['fechavigencia'] ?? '');
                    $atc = trim($fila['idgenerico'] ?? $fila['codigo_atc'] ?? $fila['atc'] ?? '');
                    $forma = trim($fila['descformafar'] ?? $fila['forma_farmaceutica'] ?? $fila['forma'] ?? '');
                    $concentracion = trim($fila['descunidad'] ?? $fila['concentracion'] ?? '');
                    $costo = floatval(str_replace(',', '.', $fila['pcosto'] ?? $fila['precio_referencia'] ?? $fila['costo'] ?? 0));
                    $clase = trim($fila['desclase'] ?? $fila['clase_terapeutica'] ?? '');
                    $principio = trim($fila['descprincipio'] ?? $fila['principio_activo'] ?? '');
                    
                    $altoCosto = intval($fila['alto_costo'] ?? $fila['es_alto_costo'] ?? 0) > 0 ? 1 : 0;
                    $controlado = intval($fila['controlado'] ?? $fila['es_control_especial'] ?? 0) > 0 ? 1 : 0;
                    $esPos = isset($fila['nopos']) ? (intval($fila['nopos']) > 0 ? 0 : 1) : 1;
                    $usoInst = intval($fila['uso_institucional'] ?? 0) > 0 ? 1 : 0;
                    $biologico = intval($fila['biologico'] ?? $fila['es_biologico'] ?? 0) > 0 ? 1 : 0;
                    $frio = intval($fila['requiere_cadena_frio'] ?? $fila['cadena_frio'] ?? 0) > 0 ? 1 : 0;
                    $estado = (isset($fila['estado']) && $fila['estado'] == '0') ? 'Inactivo' : 'Activo';
                    $obs = trim($fila['observacion'] ?? $fila['observaciones'] ?? '');

                    // Truncado de seguridad para evitar errores de longitud en columnas MySQL
                    $nombreComercial = mb_substr($nombreComercial, 0, 495, 'UTF-8');
                    $nombreGenerico = mb_substr($nombreGenerico, 0, 495, 'UTF-8');
                    $principio = mb_substr($principio, 0, 495, 'UTF-8');
                    $laboratorio = mb_substr($laboratorio, 0, 245, 'UTF-8');
                    $clase = mb_substr($clase, 0, 245, 'UTF-8');
                    $forma = mb_substr($forma, 0, 245, 'UTF-8');
                    $concentracion = mb_substr($concentracion, 0, 245, 'UTF-8');
                    $invima = mb_substr($invima, 0, 95, 'UTF-8');
                    $cums = mb_substr($cums, 0, 45, 'UTF-8');

                    if (empty($sku) && empty($nombreGenerico)) {
                        $this->resumen['omitidos']++;
                        return;
                    }

                    if (empty($sku)) {
                        $pref = ($tipoProd === 'DISPOSITIVOS MEDICOS' || $tipoProd === 'INSUMOS') ? 'DM-' : 'MED-';
                        $sku = $pref . str_pad(rand(10000, 99999), 6, '0', STR_PAD_LEFT);
                    }

                    // Verificar si existe por SKU o CUMS
                    $stmtCheck = $this->db->prepare("SELECT id FROM productos_medicamentos WHERE codigo_sku = :sku OR (codigo_cums != '' AND codigo_cums = :cums) LIMIT 1");
                    $stmtCheck->execute([':sku' => $sku, ':cums' => $cums]);
                    $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existente) {
                        if ($this->sobrescribir) {
                            $stmtUpd = $this->db->prepare("
                                UPDATE productos_medicamentos SET
                                    tipo_producto = :tipo_prod,
                                    nombre_generico = :nombre_gen,
                                    nombre_comercial = :nombre_com,
                                    fabricante_laboratorio = :lab,
                                    laboratorio_nit = :nit,
                                    codigo_cums = :cums,
                                    registro_invima = :invima,
                                    fecha_vigencia_invima = :venc_invima,
                                    codigo_atc = :atc,
                                    forma_farmaceutica = :forma,
                                    concentracion = :conc,
                                    clase_terapeutica = :clase,
                                    principio_activo = :principio,
                                    precio_referencia = :costo,
                                    es_alto_costo = :alto_costo,
                                    es_control_especial = :controlado,
                                    es_pos = :es_pos,
                                    uso_institucional = :uso_inst,
                                    es_biologico = :biologico,
                                    requiere_cadena_frio = :frio,
                                    estado_activo = :estado,
                                    observaciones = :obs
                                WHERE id = :id
                            ");
                            $stmtUpd->execute([
                                ':tipo_prod'    => $tipoProd,
                                ':nombre_gen'   => mb_strtoupper($nombreGenerico, 'UTF-8'),
                                ':nombre_com'   => mb_strtoupper($nombreComercial, 'UTF-8'),
                                ':lab'          => mb_strtoupper($laboratorio, 'UTF-8'),
                                ':nit'          => $nit,
                                ':cums'         => $cums,
                                ':invima'       => $invima,
                                ':venc_invima'  => $vencInvima,
                                ':atc'          => $atc,
                                ':forma'        => $forma,
                                ':conc'         => $concentracion,
                                ':clase'        => $clase,
                                ':principio'    => $principio,
                                ':costo'        => $costo,
                                ':alto_costo'   => $altoCosto,
                                ':controlado'   => $controlado,
                                ':es_pos'       => $esPos,
                                ':uso_inst'     => $usoInst,
                                ':biologico'    => $biologico,
                                ':frio'         => $frio,
                                ':estado'       => $estado,
                                ':obs'          => $obs,
                                ':id'           => $existente['id']
                            ]);
                            $this->resumen['actualizados']++;
                        } else {
                            $this->resumen['omitidos']++;
                        }
                    } else {
                        $stmtIns = $this->db->prepare("
                            INSERT INTO productos_medicamentos
                            (codigo_sku, tipo_producto, nombre_generico, nombre_comercial, fabricante_laboratorio, laboratorio_nit,
                             codigo_cums, registro_invima, fecha_vigencia_invima, codigo_atc, forma_farmaceutica,
                             concentracion, clase_terapeutica, principio_activo, precio_referencia, es_alto_costo,
                             es_control_especial, es_pos, uso_institucional, es_biologico, requiere_cadena_frio,
                             estado_activo, observaciones)
                            VALUES
                            (:sku, :tipo_prod, :nombre_gen, :nombre_com, :lab, :nit,
                             :cums, :invima, :venc_invima, :atc, :forma,
                             :conc, :clase, :principio, :costo, :alto_costo,
                             :controlado, :es_pos, :uso_inst, :biologico, :frio,
                             :estado, :obs)
                        ");
                        $stmtIns->execute([
                            ':sku'          => $sku,
                            ':tipo_prod'    => $tipoProd,
                            ':nombre_gen'   => mb_strtoupper($nombreGenerico, 'UTF-8'),
                            ':nombre_com'   => mb_strtoupper($nombreComercial, 'UTF-8'),
                            ':lab'          => mb_strtoupper($laboratorio, 'UTF-8'),
                            ':nit'          => $nit,
                            ':cums'         => $cums,
                            ':invima'       => $invima,
                            ':venc_invima'  => $vencInvima,
                            ':atc'          => $atc,
                            ':forma'        => $forma,
                            ':conc'         => $concentracion,
                            ':clase'        => $clase,
                            ':principio'    => $principio,
                            ':costo'        => $costo,
                            ':alto_costo'   => $altoCosto,
                            ':controlado'   => $controlado,
                            ':es_pos'       => $esPos,
                            ':uso_inst'     => $usoInst,
                            ':biologico'    => $biologico,
                            ':frio'         => $frio,
                            ':estado'       => $estado,
                            ':obs'          => $obs
                        ]);
                        $this->resumen['insertados']++;
                    }
                } catch (\Throwable $eFila) {
                    $this->resumen['errores'][] = "Fila {$this->resumen['total_filas']} (SKU: " . ($sku ?? 'N/A') . "): " . $eFila->getMessage();
                }
    }

    private function procesarFilaSaldos($fila)
    {
                $this->resumen['total_filas']++;
                
                try {
                    $sku = trim($fila['idarticulo'] ?? $fila['codigo_sku'] ?? $fila['sku'] ?? $fila['codigo'] ?? '');
                    $cums = trim($fila['codcum'] ?? $fila['codigo_cums'] ?? $fila['cums'] ?? '');
                    $invima = trim($fila['rinvima'] ?? $fila['reginvima'] ?? $fila['registro_invima'] ?? '');
                    $nombreMed = trim($fila['articulo'] ?? $fila['nombre_medicamento'] ?? $fila['descripcion'] ?? $fila['nombre_generico'] ?? '');
                    $loteInterno = trim($fila['lote_interno'] ?? '');
                    $lote = trim($fila['n__lote'] ?? $fila['n_lote'] ?? $fila['numero_lote'] ?? $fila['lote'] ?? $loteInterno);
                    if (empty($lote)) {
                        $lote = 'LT-INICIAL';
                    }
                    
                    $venc = $this->normalizarFecha($fila['fechavence'] ?? $fila['fecha_vencimiento'] ?? $fila['vencimiento'] ?? $fila['fecha_vence'] ?? '') ?: date('Y-12-31', strtotime('+2 years'));
                    $lab = trim($fila['fabricante_laboratorio'] ?? $fila['laboratorio'] ?? $fila['marca'] ?? 'GENERICO');
                    $cantidad = intval($fila['existencia'] ?? $fila['cantidad'] ?? $fila['saldo'] ?? $fila['unidades'] ?? 0);
                    $costo = floatval(str_replace(',', '.', $fila['costo_uni_'] ?? $fila['costo_uni'] ?? $fila['costo_unitario'] ?? $fila['costo'] ?? $fila['pcosto'] ?? 0));
                    $ubicacion = trim($fila['ubicacion_estante'] ?? $fila['ubicacion'] ?? '');
                    $ium = trim($fila['ium'] ?? $fila['codigo_ium'] ?? '');

                    // Truncado de seguridad
                    $nombreMed = mb_substr($nombreMed, 0, 495, 'UTF-8');
                    $lab = mb_substr($lab, 0, 245, 'UTF-8');
                    $lote = mb_substr($lote, 0, 95, 'UTF-8');
                    $loteInterno = mb_substr($loteInterno, 0, 95, 'UTF-8');
                    $invima = mb_substr($invima, 0, 95, 'UTF-8');
                    $cums = mb_substr($cums, 0, 45, 'UTF-8');
                $stockMin = isset($fila['stockminimo']) && is_numeric($fila['stockminimo']) ? intval($fila['stockminimo']) : null;
                $stockMax = isset($fila['stockmaximo']) && is_numeric($fila['stockmaximo']) ? intval($fila['stockmaximo']) : null;

                // Resolver bodega de destino para este ítem (permite multi-bodega desde el mismo archivo)
                $codBodFila = trim($fila['id_bodega'] ?? $fila['idbodega'] ?? $fila['codigo_bodega'] ?? '');
                $nomBodFila = trim($fila['bodega'] ?? $fila['nombre_bodega'] ?? '');
                $bodegaIdItem = $this->bodegaDestinoId;

                if (!empty($codBodFila)) {
                    $stmtB = $this->db->prepare("SELECT id FROM bodegas WHERE codigo_bodega = :cod OR id = :id LIMIT 1");
                    $stmtB->execute([':cod' => $codBodFila, ':id' => intval($codBodFila)]);
                    $bFound = $stmtB->fetch(PDO::FETCH_ASSOC);
                    if ($bFound) {
                        $bodegaIdItem = intval($bFound['id']);
                    }
                } elseif (!empty($nomBodFila)) {
                    $stmtB = $this->db->prepare("SELECT id FROM bodegas WHERE nombre_bodega LIKE :nom LIMIT 1");
                    $stmtB->execute([':nom' => '%' . $nomBodFila . '%']);
                    $bFound = $stmtB->fetch(PDO::FETCH_ASSOC);
                    if ($bFound) {
                        $bodegaIdItem = intval($bFound['id']);
                    }
                }

                if ($cantidad <= 0 || (empty($sku) && empty($cums) && empty($nombreMed))) {
                    $this->resumen['omitidos']++;
                    return;
                }

                // Normalizar fecha de vencimiento (soporta d/m/Y, Y-m-d y timestamps Excel)
                if (empty($venc) || $venc === '0000-00-00') {
                    $venc = date('Y-12-31', strtotime('+2 years'));
                } elseif (is_numeric($venc) && intval($venc) > 30000) {
                    $unixTimestamp = ($venc - 25569) * 86400;
                    $venc = gmdate('Y-m-d', $unixTimestamp);
                } elseif (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $venc, $m)) {
                    $venc = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                } elseif (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $venc, $m)) {
                    $venc = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                } else {
                    $timeVenc = strtotime($venc);
                    $venc = $timeVenc ? date('Y-m-d', $timeVenc) : date('Y-12-31', strtotime('+2 years'));
                }

                // Buscar producto por SKU, CUMS o Nombre
                $stmtProd = $this->db->prepare("
                    SELECT id, precio_referencia, fabricante_laboratorio FROM productos_medicamentos 
                    WHERE codigo_sku = :sku OR (codigo_cums != '' AND codigo_cums = :cums) OR nombre_generico LIKE :nom1 OR nombre_comercial LIKE :nom2
                    LIMIT 1
                ");
                $stmtProd->execute([
                    ':sku'  => $sku,
                    ':cums' => $cums,
                    ':nom1' => '%' . $nombreMed . '%',
                    ':nom2' => '%' . $nombreMed . '%'
                ]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if (!$producto) {
                    $this->resumen['errores'][] = "Artículo no encontrado en catálogo maestro: SKU/Nombre '{$sku} - {$nombreMed}'. Fila omitida.";
                    $this->resumen['omitidos']++;
                    return;
                }

                $productoId = $producto['id'];
                if ($costo <= 0 && floatval($producto['precio_referencia']) > 0) {
                    $costo = floatval($producto['precio_referencia']);
                }
                if ($lab === 'GENERICO' && !empty($producto['fabricante_laboratorio'])) {
                    $lab = $producto['fabricante_laboratorio'];
                }

                // Actualizar atributos de producto si vienen en el archivo
                $cumsFinal = !empty($cums) ? $cums : ($producto['codigo_cums'] ?? null);
                $invimaFinal = !empty($invima) ? $invima : ($producto['registro_invima'] ?? null);
                $iumFinal = !empty($ium) ? $ium : ($producto['codigo_ium'] ?? null);
                $stockMinFinal = ($stockMin !== null && $stockMin !== '') ? intval($stockMin) : ($producto['stock_minimo_alerta'] ?? 10);
                $stockMaxFinal = ($stockMax !== null && $stockMax !== '') ? intval($stockMax) : ($producto['stock_maximo'] ?? 1000);

                $stmtUpdProd = $this->db->prepare("
                    UPDATE productos_medicamentos SET
                        codigo_cums = :cums,
                        registro_invima = :invima,
                        codigo_ium = :ium,
                        stock_minimo_alerta = :stock_min,
                        stock_maximo = :stock_max
                    WHERE id = :prod_id
                ");
                $stmtUpdProd->execute([
                    ':cums'      => $cumsFinal,
                    ':invima'    => $invimaFinal,
                    ':ium'       => $iumFinal,
                    ':stock_min' => $stockMinFinal,
                    ':stock_max' => $stockMaxFinal,
                    ':prod_id'   => $productoId
                ]);

                // Verificar si ya existe el lote en esa bodega
                $stmtCheckLote = $this->db->prepare("
                    SELECT id, cantidad_actual, lote_interno FROM inventario_lotes 
                    WHERE bodega_id = :bodega_id AND producto_id = :prod_id AND numero_lote = :lote
                    LIMIT 1
                ");
                $stmtCheckLote->execute([
                    ':bodega_id' => $bodegaIdItem,
                    ':prod_id'   => $productoId,
                    ':lote'      => $lote
                ]);
                $loteRow = $stmtCheckLote->fetch(PDO::FETCH_ASSOC);

                $docRef = 'IMPORT-EXCEL-' . date('Ymd-His');

                if ($loteRow) {
                    $loteId = $loteRow['id'];
                    $stockAnterior = intval($loteRow['cantidad_actual']);
                    $stockNuevo = $stockAnterior + $cantidad;
                    $loteIntFinal = !empty($loteInterno) ? $loteInterno : ($loteRow['lote_interno'] ?? null);

                    $stmtUpdLote = $this->db->prepare("
                        UPDATE inventario_lotes SET 
                            lote_interno = :lote_int,
                            cantidad_actual = :nueva_cant,
                            costo_unitario = :costo,
                            fabricante_laboratorio = :lab,
                            fecha_vencimiento = :venc,
                            estado_lote = 'DISPONIBLE'
                        WHERE id = :id
                    ");
                    $stmtUpdLote->execute([
                        ':lote_int'   => $loteIntFinal,
                        ':nueva_cant' => $stockNuevo,
                        ':costo'      => $costo,
                        ':lab'        => mb_strtoupper($lab, 'UTF-8'),
                        ':venc'       => $venc,
                        ':id'         => $loteId
                    ]);

                    // Registrar Kardex
                    $stmtK = $this->db->prepare("
                        INSERT INTO movimientos_kardex 
                        (bodega_id, producto_id, lote_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, costo_unitario, costo_total, referencia_documento, usuario_id, observaciones)
                        VALUES
                        (:bodega_id, :prod_id, :lote_id, :tipo, :cant, :ant, :nuevo, :costo, :total, :doc, :user_id, :obs)
                    ");
                    $stmtK->execute([
                        ':bodega_id' => $bodegaIdItem,
                        ':prod_id'   => $productoId,
                        ':lote_id'   => $loteId,
                        ':tipo'      => $this->tipoMovimiento,
                        ':cant'      => $cantidad,
                        ':ant'       => $stockAnterior,
                        ':nuevo'     => $stockNuevo,
                        ':costo'     => $costo,
                        ':total'     => ($cantidad * $costo),
                        ':doc'       => $docRef,
                        ':user_id'   => $this->userId,
                        ':obs'       => "Carga masiva Excel en bodega. Lote {$lote}" . (!empty($loteInterno) ? " (Interno: {$loteInterno})" : "")
                    ]);

                    $this->resumen['actualizados']++;
                } else {
                    $stmtInsLote = $this->db->prepare("
                        INSERT INTO inventario_lotes
                        (bodega_id, producto_id, numero_lote, lote_interno, fecha_vencimiento, fabricante_laboratorio, cantidad_actual, costo_unitario, estado_lote)
                        VALUES
                        (:bodega_id, :prod_id, :lote, :lote_int, :venc, :lab, :cant, :costo, 'DISPONIBLE')
                    ");
                    $stmtInsLote->execute([
                        ':bodega_id' => $bodegaIdItem,
                        ':prod_id'   => $productoId,
                        ':lote'      => $lote,
                        ':lote_int'  => $loteInterno,
                        ':venc'      => $venc,
                        ':lab'       => mb_strtoupper($lab, 'UTF-8'),
                        ':cant'      => $cantidad,
                        ':costo'     => $costo
                    ]);
                    $loteId = $this->db->lastInsertId();

                    // Registrar Kardex inicial
                    $stmtK = $this->db->prepare("
                        INSERT INTO movimientos_kardex 
                        (bodega_id, producto_id, lote_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, costo_unitario, costo_total, referencia_documento, usuario_id, observaciones)
                        VALUES
                        (:bodega_id, :prod_id, :lote_id, :tipo, :cant, 0, :nuevo, :costo, :total, :doc, :user_id, :obs)
                    ");
                    $stmtK->execute([
                        ':bodega_id' => $bodegaIdItem,
                        ':prod_id'   => $productoId,
                        ':lote_id'   => $loteId,
                        ':tipo'      => $this->tipoMovimiento,
                        ':cant'      => $cantidad,
                        ':nuevo'     => $cantidad,
                        ':costo'     => $costo,
                        ':total'     => ($cantidad * $costo),
                        ':doc'       => $docRef,
                        ':user_id'   => $this->userId,
                        ':obs'       => "Apertura inicial de lote desde Excel. Lote {$lote}" . (!empty($loteInterno) ? " (Interno: {$loteInterno})" : "")
                    ]);

                    $this->resumen['insertados']++;
                }
            } catch (\Throwable $eFila) {
                $this->resumen['errores'][] = "Fila {$this->resumen['total_filas']} (Artículo: " . ($sku ?: ($nombreMed ?? 'N/A')) . "): " . $eFila->getMessage();
            }
    }
}

class InventarioXlsxStreamReader
{
    public static function parse($filePath, $onChunkCallback, $chunkSize = 1000) {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        $sharedStrings = [];
        $sstXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sstXml !== false) {
            $reader = new \XMLReader();
            $reader->XML($sstXml);
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'si') {
                    $siXml = $reader->readOuterXML();
                    preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $siXml, $matches);
                    $sharedStrings[] = html_entity_decode(implode('', $matches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
            $reader->close();
            unset($sstXml);
        }

        $sheetStream = $zip->getStream('xl/worksheets/sheet1.xml');
        if (!$sheetStream) {
            $zip->close();
            return false;
        }

        $tempSheet = tempnam(sys_get_temp_dir(), 'sispam_inv_');
        $fp = fopen($tempSheet, 'w');
        while (!feof($sheetStream)) {
            fwrite($fp, fread($sheetStream, 65536));
        }
        fclose($fp);
        fclose($sheetStream);
        $zip->close();

        $reader = new \XMLReader();
        $reader->open($tempSheet);

        $rowIdx = 0;
        $headers = [];
        $batch = [];
        $totalProcesados = 0;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'row') {
                $rowXml = $reader->readOuterXML();
                $rowCells = self::extractCells($rowXml, $sharedStrings);
                $rowIdx++;

                if ($rowIdx === 1) {
                    $headers = array_map(function($h) {
                        return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $h)));
                    }, $rowCells);
                } else {
                    $assoc = [];
                    foreach ($headers as $colIdx => $headerName) {
                        if (!empty($headerName)) {
                            $assoc[$headerName] = $rowCells[$colIdx] ?? '';
                        }
                    }
                    $batch[] = $assoc;
                    if (count($batch) >= $chunkSize) {
                        $onChunkCallback($batch);
                        $totalProcesados += count($batch);
                        $batch = [];
                    }
                }
            }
        }

        if (!empty($batch)) {
            $onChunkCallback($batch);
            $totalProcesados += count($batch);
        }

        $reader->close();
        @unlink($tempSheet);
        return $totalProcesados;
    }

    private static function extractCells($rowXml, &$sharedStrings) {
        $cells = [];
        $xml = simplexml_load_string($rowXml);
        if ($xml === false) return $cells;

        $lastColNum = 0;
        foreach ($xml->c as $cell) {
            $r = (string)$cell['r'];
            preg_match('/([A-Z]+)(\d+)/', $r, $matches);
            $colLetters = $matches[1] ?? 'A';
            $colNum = self::lettersToColNumber($colLetters);

            while ($lastColNum + 1 < $colNum) {
                $cells[] = '';
                $lastColNum++;
            }

            $type = (string)$cell['t'];
            $val = (string)$cell->v;

            if ($type === 's' && isset($sharedStrings[(int)$val])) {
                $val = $sharedStrings[(int)$val];
            } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                $val = (string)$cell->is->t;
            }

            $cells[] = trim($val);
            $lastColNum = $colNum;
        }

        return $cells;
    }

    private static function lettersToColNumber($letters) {
        $num = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $num = $num * 26 + (ord($letters[$i]) - 64);
        }
        return $num;
    }
}
