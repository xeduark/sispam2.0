<?php

namespace App\Models;

use App\Services\Facturacion\FacturadorManager;
use Exception;
use Illuminate\Support\Facades\DB;
use PDO;

class Factura {
    private PDO $db;
    private FacturadorManager $facturador;
    private ContratoEps $contratoModel;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
        $this->facturador = new FacturadorManager($this->db);
        $this->contratoModel = new ContratoEps();
    }

    /**
     * Calcula automáticamente el valor de cuota moderadora o copago según normatividad colombiana
     */
    public function calcularCopagoOCuotaModeradora(array $paciente, array $medicamentos, ?array $contrato = null): array {
        $tipoAfiliado = strtoupper(trim($paciente['tipo_afiliado'] ?? ''));
        $nivelSocio = strtoupper(trim($paciente['nivel_socioeconomico'] ?? ''));
        $grupoPoblacional = strtoupper(trim($paciente['grupo_poblacional'] ?? ''));
        $esAltoCosto = intval($paciente['es_alto_costo'] ?? 0);

        // 1. Verificación de Exenciones de Ley (100% Exento)
        $exento = false;
        $motivoExencion = '';

        if (str_contains($grupoPoblacional, 'VICTIMA') || str_contains($grupoPoblacional, 'CONFLICTO') || str_contains($grupoPoblacional, 'DESPLAZADO')) {
            $exento = true;
            $motivoExencion = 'Exento por Ley 1448 (Víctimas del Conflicto Armado)';
        } elseif (str_contains($grupoPoblacional, 'INDIGENA') || str_contains($grupoPoblacional, 'ROM')) {
            $exento = true;
            $motivoExencion = 'Exento Población Étnica Protegida';
        } elseif ($esAltoCosto == 1) {
            $exento = true;
            $motivoExencion = 'Exento por Programa de Patología de Alto Costo / Huérfana';
        }

        if ($contrato && intval($contrato['aplica_copago_regulado'] ?? 1) === 0) {
            $exento = true;
            $motivoExencion = 'Exento por Estipulación Contractual con EPS';
        }

        if ($exento) {
            return [
                'aplica' => false,
                'concepto' => 'EXENTO',
                'valor' => 0.00,
                'motivo' => $motivoExencion
            ];
        }

        // 2. Régimen Subsidiado (Decreto 780 de 2016)
        // En el Régimen Subsidiado NO se cobran cuotas moderadoras por medicamentos ambulatorios PBS.
        if (str_contains($tipoAfiliado, 'SUBSIDIADO')) {
            return [
                'aplica' => false,
                'concepto' => 'SUBSIDIADO_EXENTO_PBS',
                'valor' => 0.00,
                'motivo' => 'Régimen Subsidiado Exento de Cuota Moderadora en Medicamentos Ambulatorios (Decreto 780/2016)'
            ];
        }

        // 3. Régimen Contributivo: Cuotas Moderadoras por Rango (Circular MinSalud / UVT)
        // Rango A (< 2 SMMLV): ~$4.500
        // Rango B (2 a 5 SMMLV): ~$18.200
        // Rango C (> 5 SMMLV): ~$47.700
        $valorCuota = 4500.00; // Default Rango A
        $categoria = 'RANGO A';

        if (str_contains($nivelSocio, 'CATEGORIA B') || str_contains($nivelSocio, 'RANGO B') || str_contains($nivelSocio, 'NIVEL 2')) {
            $valorCuota = 18200.00;
            $categoria = 'RANGO B';
        } elseif (str_contains($nivelSocio, 'CATEGORIA C') || str_contains($nivelSocio, 'RANGO C') || str_contains($nivelSocio, 'NIVEL 3')) {
            $valorCuota = 47700.00;
            $categoria = 'RANGO C';
        }

        return [
            'aplica' => true,
            'concepto' => 'CUOTA_MODERADORA',
            'valor' => $valorCuota,
            'categoria' => $categoria,
            'motivo' => "Régimen Contributivo {$categoria} (Aplica Cuota Moderadora por Fórmula)"
        ];
    }

    /**
     * Emite una Factura Individual Inmediata para una dispensación
     */
    public function crearFacturaIndividual(int $ingreso_id, int $user_id, array $opciones = []): array {
        // 1. Obtener datos del ingreso y paciente
        $stmt = $this->db->prepare("
            SELECT i.*, p.nombres, p.apellidos, p.tipo_documento, p.numero_documento,
                   p.telefono, p.email, p.direccion_residencia, p.eps_nombre,
                   p.tipo_afiliado, p.nivel_socioeconomico, p.grupo_poblacional,
                   s.nombre_sede, s.codigo_habilitacion, e.razon_social, e.nit AS nit_empresa
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN empresas e ON i.empresa_id = e.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $ingreso_id]);
        $ingreso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ingreso) {
            return ['success' => false, 'message' => 'No se encontró el registro de ingreso/dispensación.'];
        }

        // 2. Obtener los medicamentos dispensados
        $stmtMed = $this->db->prepare("
            SELECT d.*, pm.codigo_cums, pm.codigo_ium, pm.nombre_generico, pm.nombre_comercial,
                   pm.concentracion, pm.forma_farmaceutica, pm.precio_referencia,
                   l.numero_lote, l.fecha_vencimiento
            FROM ingreso_medicamentos_dispensados d
            JOIN productos_medicamentos pm ON d.producto_id = pm.id
            LEFT JOIN inventario_lotes l ON d.lote_id = l.id
            WHERE d.ingreso_id = :id
        ");
        $stmtMed->execute([':id' => $ingreso_id]);
        $dispensados = $stmtMed->fetchAll(PDO::FETCH_ASSOC);

        if (empty($dispensados)) {
            return ['success' => false, 'message' => 'No hay medicamentos dispensados registrados en esta orden.'];
        }

        // 3. Buscar contrato EPS
        $contrato = $this->contratoModel->getByEps($ingreso['eps_nombre'] ?? '');
        $configProv = $this->facturador->getActiveConfig();

        // 4. Calcular totales y copagos
        $subtotal = 0.00;
        $detallesFactura = [];

        foreach ($dispensados as $m) {
            $precioUnit = floatval($m['precio_referencia'] > 0 ? $m['precio_referencia'] : 2500.00);
            $cant = intval($m['cantidad_entregada'] > 0 ? $m['cantidad_entregada'] : $m['cantidad_prescrita']);
            $totItem = $precioUnit * $cant;
            $subtotal += $totItem;

            $nombreMed = !empty($m['nombre_comercial']) ? "{$m['nombre_comercial']} ({$m['nombre_generico']})" : $m['nombre_generico'];

            $detallesFactura[] = [
                'dispensacion_id' => $m['id'],
                'producto_id' => $m['producto_id'],
                'codigo_cums' => $m['codigo_cums'] ?: 'CUMS-GENERICO',
                'codigo_ium' => $m['codigo_ium'] ?: '',
                'nombre_medicamento' => $nombreMed,
                'concentracion' => $m['concentracion'] ?: '',
                'forma_farmaceutica' => $m['forma_farmaceutica'] ?: 'TABLETA',
                'lote_numero' => $m['numero_lote'] ?: 'LOTE-DEFAULT',
                'cantidad' => $cant,
                'duracion_dias' => $m['duracion_dias'] ?: 30,
                'valor_unitario' => $precioUnit,
                'valor_total' => $totItem,
                'copago_aplicado' => 0.00,
                'numero_prescripcion_mipres' => $ingreso['pdf_mipres_url'] ? 'MIPRES-' . $ingreso['ticket_numero'] : '',
                'id_entrega_mipres' => '',
                'tipo_medicamento_rips' => ($ingreso['es_alto_costo'] == 1) ? 'ALTO_COSTO' : 'PBS'
            ];
        }

        // Cálculo normativo de Cuota Moderadora / Copago
        $infoCopago = $this->calcularCopagoOCuotaModeradora($ingreso, $detallesFactura, $contrato);
        $totalCopago = floatval($infoCopago['valor'] ?? 0.00);
        $totalNeto = max(0, $subtotal - $totalCopago);

        // Consecutivo de Factura
        $prefijo = $configProv['prefijo'] ?? 'SETP';
        $consecutivo = intval($configProv['consecutivo_actual'] ?? 1);
        $numFactura = (string)$consecutivo;

        // Preparar DTO para el adaptador de facturación electrónica
        $datosParaEmision = [
            'prefijo' => $prefijo,
            'numero_factura' => $numFactura,
            'nit_empresa' => $ingreso['nit_empresa'] ?: '900123456',
            'paciente_tipo_doc' => $ingreso['tipo_documento'],
            'paciente_documento' => $ingreso['numero_documento'],
            'paciente_nombre' => trim("{$ingreso['nombres']} {$ingreso['apellidos']}"),
            'paciente_email' => $ingreso['email'],
            'paciente_telefono' => $ingreso['telefono'],
            'paciente_direccion' => $ingreso['direccion_residencia'],
            'eps_nombre' => $ingreso['eps_nombre'],
            'codigo_eapb' => $contrato['codigo_eapb'] ?? 'EPS000',
            'numero_contrato' => $contrato['numero_contrato'] ?? 'CTO-DISP',
            'subtotal' => $subtotal,
            'descuento' => 0.00,
            'iva' => 0.00,
            'total_copago_cuota' => $totalCopago,
            'total_neto' => $totalNeto,
            'detalles' => $detallesFactura
        ];

        // 5. Emitir ante el Proveedor Tecnológico DIAN
        $respuestaDian = $this->facturador->emitir($datosParaEmision);

        // 6. Guardar en Base de Datos (Transacción segura)
        $this->db->beginTransaction();
        try {
            $stmtFact = $this->db->prepare("
                INSERT INTO facturas (
                    empresa_id, sede_id, contrato_id, proveedor_config_id, tipo_factura,
                    prefijo, numero_factura, fecha_emision, fecha_vencimiento,
                    paciente_id, eps_nombre, codigo_eapb, subtotal, descuento, iva,
                    total_copago_cuota, total_neto, estado_factura, cufe, qr_cadena,
                    track_id_proveedor, mensaje_respuesta_dian, xml_dian_url, pdf_factura_url,
                    elaborado_por_user_id
                ) VALUES (
                    :empresa_id, :sede_id, :contrato_id, :proveedor_config_id, :tipo_factura,
                    :prefijo, :numero_factura, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY),
                    :paciente_id, :eps_nombre, :codigo_eapb, :subtotal, :descuento, :iva,
                    :total_copago_cuota, :total_neto, :estado_factura, :cufe, :qr_cadena,
                    :track_id_proveedor, :mensaje_respuesta_dian, :xml_dian_url, :pdf_factura_url,
                    :elaborado_por_user_id
                )
            ");

            $tipoFactura = ($ingreso['es_alto_costo'] == 1) ? 'ALTO_COSTO_MIPRES' : 'INDIVIDUAL';

            $stmtFact->execute([
                ':empresa_id' => $ingreso['empresa_id'] ?? 1,
                ':sede_id' => $ingreso['sede_id'] ?? 1,
                ':contrato_id' => $contrato['id'] ?? null,
                ':proveedor_config_id' => $configProv['id'] ?? null,
                ':tipo_factura' => $tipoFactura,
                ':prefijo' => $prefijo,
                ':numero_factura' => $numFactura,
                ':paciente_id' => $ingreso['paciente_id'],
                ':eps_nombre' => $ingreso['eps_nombre'],
                ':codigo_eapb' => $contrato['codigo_eapb'] ?? '',
                ':subtotal' => $subtotal,
                ':descuento' => 0.00,
                ':iva' => 0.00,
                ':total_copago_cuota' => $totalCopago,
                ':total_neto' => $totalNeto,
                ':estado_factura' => $respuestaDian->estado_dian,
                ':cufe' => $respuestaDian->cufe,
                ':qr_cadena' => $respuestaDian->qr_cadena,
                ':track_id_proveedor' => $respuestaDian->track_id,
                ':mensaje_respuesta_dian' => $respuestaDian->message,
                ':xml_dian_url' => $respuestaDian->xml_url,
                ':pdf_factura_url' => $respuestaDian->pdf_url,
                ':elaborado_por_user_id' => $user_id
            ]);

            $facturaId = $this->db->lastInsertId();

            // Guardar Detalles de Factura
            $stmtDet = $this->db->prepare("
                INSERT INTO factura_detalles (
                    factura_id, ingreso_id, paciente_id, dispensacion_id, producto_id,
                    codigo_cums, codigo_ium, nombre_medicamento, concentracion,
                    forma_farmaceutica, lote_numero, cantidad, valor_unitario, valor_total,
                    copago_aplicado, numero_prescripcion_mipres, id_entrega_mipres, tipo_medicamento_rips
                ) VALUES (
                    :factura_id, :ingreso_id, :paciente_id, :dispensacion_id, :producto_id,
                    :codigo_cums, :codigo_ium, :nombre_medicamento, :concentracion,
                    :forma_farmaceutica, :lote_numero, :cantidad, :valor_unitario, :valor_total,
                    :copago_aplicado, :numero_prescripcion_mipres, :id_entrega_mipres, :tipo_medicamento_rips
                )
            ");

            foreach ($detallesFactura as $d) {
                $stmtDet->execute([
                    ':factura_id' => $facturaId,
                    ':ingreso_id' => $ingreso_id,
                    ':paciente_id' => $ingreso['paciente_id'],
                    ':dispensacion_id' => $d['dispensacion_id'],
                    ':producto_id' => $d['producto_id'],
                    ':codigo_cums' => $d['codigo_cums'],
                    ':codigo_ium' => $d['codigo_ium'],
                    ':nombre_medicamento' => $d['nombre_medicamento'],
                    ':concentracion' => $d['concentracion'],
                    ':forma_farmaceutica' => $d['forma_farmaceutica'],
                    ':lote_numero' => $d['lote_numero'],
                    ':cantidad' => $d['cantidad'],
                    ':valor_unitario' => $d['valor_unitario'],
                    ':valor_total' => $d['valor_total'],
                    ':copago_aplicado' => $d['copago_aplicado'],
                    ':numero_prescripcion_mipres' => $d['numero_prescripcion_mipres'],
                    ':id_entrega_mipres' => $d['id_entrega_mipres'],
                    ':tipo_medicamento_rips' => $d['tipo_medicamento_rips']
                ]);
            }

            // Actualizar estado de las dispensaciones
            $stmtUpdDisp = $this->db->prepare("
                UPDATE ingreso_medicamentos_dispensados 
                SET factura_id = :factura_id, estado_facturacion = 'FACTURADO_INDIVIDUAL'
                WHERE ingreso_id = :ingreso_id
            ");
            $stmtUpdDisp->execute([':factura_id' => $facturaId, ':ingreso_id' => $ingreso_id]);

            // Incrementar consecutivo
            if (!empty($configProv['id'])) {
                $this->db->prepare("UPDATE facturas_config_proveedor SET consecutivo_actual = consecutivo_actual + 1 WHERE id = :id")
                         ->execute([':id' => $configProv['id']]);
            }

            // Si hay copago y se solicitó recibo automático
            $reciboId = null;
            if ($totalCopago > 0 && !empty($opciones['generar_recibo_caja'])) {
                $reciboModel = new ReciboCaja();
                $resRec = $reciboModel->generarReciboCopago(
                    $ingreso_id,
                    $ingreso['paciente_id'],
                    $totalCopago,
                    'CUOTA_MODERADORA',
                    $opciones['metodo_pago'] ?? 'EFECTIVO',
                    $opciones['referencia_pago'] ?? '',
                    $user_id,
                    "Recaudo cuota moderadora Factura {$prefijo}{$numFactura}"
                );
                if ($resRec['success']) {
                    $reciboId = $resRec['recibo_id'];
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'factura_id' => $facturaId,
                'numero_factura' => "{$prefijo}{$numFactura}",
                'cufe' => $respuestaDian->cufe,
                'qr_cadena' => $respuestaDian->qr_cadena,
                'total_neto' => $totalNeto,
                'total_copago' => $totalCopago,
                'recibo_id' => $reciboId,
                'estado_dian' => $respuestaDian->estado_dian,
                'message' => "Factura Electrónica {$prefijo}{$numFactura} emitida y validada exitosamente."
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al guardar factura: ' . $e->getMessage()];
        }
    }

    /**
     * Emite una Factura Consolidada Multiusuario (Capitación / PGP) para un periodo de EPS
     */
    public function crearFacturaConsolidada(string $eps_nombre, string $fecha_desde, string $fecha_hasta, int $user_id, array $opciones = []): array {
        $contrato = $this->contratoModel->getByEps($eps_nombre);
        $configProv = $this->facturador->getActiveConfig();

        // 1. Obtener todas las dispensaciones pendientes de consolidar para esta EPS
        $sql = "
            SELECT d.*, i.paciente_id, i.ticket_numero, i.fecha_ingreso,
                   p.tipo_documento, p.numero_documento, p.nombres, p.apellidos,
                   pm.codigo_cums, pm.codigo_ium, pm.nombre_generico, pm.nombre_comercial,
                   pm.concentracion, pm.forma_farmaceutica, pm.precio_referencia,
                   l.numero_lote
            FROM ingreso_medicamentos_dispensados d
            JOIN ingresos i ON d.ingreso_id = i.id
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN productos_medicamentos pm ON d.producto_id = pm.id
            LEFT JOIN inventario_lotes l ON d.lote_id = l.id
            WHERE (d.factura_id IS NULL OR d.estado_facturacion = 'PENDIENTE_CONSOLIDACION')
              AND (
                  UPPER(TRIM(p.eps_nombre)) = UPPER(TRIM(:eps))
                  OR UPPER(TRIM(p.eps_nombre)) = UPPER(TRIM(:eapb))
                  OR UPPER(TRIM(p.eps_nombre)) LIKE CONCAT('%', UPPER(TRIM(:eps2)), '%')
                  OR UPPER(TRIM(p.eps_nombre)) LIKE CONCAT('%', UPPER(TRIM(:eapb2)), '%')
              )
              AND DATE(d.created_at) BETWEEN :desde AND :hasta
            ORDER BY d.created_at ASC
        ";

        $codigoEapb = $contrato['codigo_eapb'] ?? $eps_nombre;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':eps'   => trim($eps_nombre),
            ':eapb'  => trim($codigoEapb),
            ':eps2'  => trim($eps_nombre),
            ':eapb2' => trim($codigoEapb),
            ':desde' => $fecha_desde,
            ':hasta' => $fecha_hasta
        ]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($filas)) {
            return ['success' => false, 'message' => "No se encontraron actas de dispensación pendientes para consolidar con {$eps_nombre} en las fechas especificadas."];
        }

        // 2. Liquidar subtotal y detalles
        $subtotal = 0.00;
        $detallesFactura = [];
        $idsDispensaciones = [];
        $usuariosAtendidosSet = [];

        foreach ($filas as $m) {
            $idsDispensaciones[] = $m['id'];
            $usuariosAtendidosSet[$m['paciente_id']] = true;

            $precioUnit = floatval($m['precio_referencia'] > 0 ? $m['precio_referencia'] : 2500.00);
            $cant = intval($m['cantidad_entregada'] > 0 ? $m['cantidad_entregada'] : $m['cantidad_prescrita']);
            $totItem = $precioUnit * $cant;
            $subtotal += $totItem;

            $nombreMed = !empty($m['nombre_comercial']) ? "{$m['nombre_comercial']} ({$m['nombre_generico']})" : $m['nombre_generico'];

            $detallesFactura[] = [
                'dispensacion_id' => $m['id'],
                'ingreso_id' => $m['ingreso_id'],
                'paciente_id' => $m['paciente_id'],
                'producto_id' => $m['producto_id'],
                'codigo_cums' => $m['codigo_cums'] ?: 'CUMS-GENERICO',
                'codigo_ium' => $m['codigo_ium'] ?: '',
                'nombre_medicamento' => $nombreMed,
                'concentracion' => $m['concentracion'] ?: '',
                'forma_farmaceutica' => $m['forma_farmaceutica'] ?: 'TABLETA',
                'lote_numero' => $m['numero_lote'] ?: 'LOTE-DEFAULT',
                'cantidad' => $cant,
                'duracion_dias' => $m['duracion_dias'] ?: 30,
                'valor_unitario' => $precioUnit,
                'valor_total' => $totItem,
                'copago_aplicado' => 0.00,
                'numero_prescripcion_mipres' => '',
                'id_entrega_mipres' => '',
                'tipo_medicamento_rips' => 'PBS'
            ];
        }

        $totalUsuarios = count($usuariosAtendidosSet);
        $totalItems = count($detallesFactura);
        $totalNeto = $subtotal;

        // Consecutivo de Factura
        $prefijo = $configProv['prefijo'] ?? 'SETP';
        $consecutivo = intval($configProv['consecutivo_actual'] ?? 1);
        $numFactura = (string)$consecutivo;

        // DTO para emisión
        $datosParaEmision = [
            'prefijo' => $prefijo,
            'numero_factura' => $numFactura,
            'nit_empresa' => '900123456',
            'paciente_tipo_doc' => 'NI',
            'paciente_documento' => $contrato['codigo_eapb'] ?: '800123456',
            'paciente_nombre' => $eps_nombre,
            'eps_nombre' => $eps_nombre,
            'codigo_eapb' => $contrato['codigo_eapb'] ?? 'EPS000',
            'numero_contrato' => $contrato['numero_contrato'] ?? 'CTO-CAP-CONSOL',
            'subtotal' => $subtotal,
            'descuento' => 0.00,
            'iva' => 0.00,
            'total_copago_cuota' => 0.00,
            'total_neto' => $totalNeto,
            'detalles' => $detallesFactura
        ];

        $modPago = strtoupper($contrato['modalidad_pago'] ?? 'CAPITACION');
        $tipoFactura = 'MULTIUSUARIO_CAPITADA';
        if ($modPago === 'EVENTO') {
            $tipoFactura = 'CONSOLIDADA_EVENTO';
        } elseif ($modPago === 'PGP') {
            $tipoFactura = 'CONSOLIDADA_PGP';
        }

        // 3. Emitir ante Proveedor Tecnológico DIAN
        $respuestaDian = $this->facturador->emitir($datosParaEmision);

        // 4. Guardar en Base de Datos
        $this->db->beginTransaction();
        try {
            $stmtFact = $this->db->prepare("
                INSERT INTO facturas (
                    empresa_id, sede_id, contrato_id, proveedor_config_id, tipo_factura,
                    prefijo, numero_factura, fecha_emision, fecha_vencimiento,
                    paciente_id, eps_nombre, codigo_eapb, periodo_corte_desde, periodo_corte_hasta,
                    subtotal, descuento, iva, total_copago_cuota, total_neto, estado_factura,
                    cufe, qr_cadena, track_id_proveedor, mensaje_respuesta_dian, xml_dian_url, pdf_factura_url,
                    elaborado_por_user_id
                ) VALUES (
                    1, 1, :contrato_id, :proveedor_config_id, :tipo_factura,
                    :prefijo, :numero_factura, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY),
                    NULL, :eps_nombre, :codigo_eapb, :desde, :hasta,
                    :subtotal, :descuento, :iva, 0.00, :total_neto, :estado_factura,
                    :cufe, :qr_cadena, :track_id_proveedor, :mensaje_respuesta_dian, :xml_dian_url, :pdf_factura_url,
                    :elaborado_por_user_id
                )
            ");

            $stmtFact->execute([
                ':contrato_id' => $contrato['id'] ?? null,
                ':proveedor_config_id' => $configProv['id'] ?? null,
                ':tipo_factura' => $tipoFactura,
                ':prefijo' => $prefijo,
                ':numero_factura' => $numFactura,
                ':eps_nombre' => $eps_nombre,
                ':codigo_eapb' => $contrato['codigo_eapb'] ?? '',
                ':desde' => $fecha_desde,
                ':hasta' => $fecha_hasta,
                ':subtotal' => $subtotal,
                ':descuento' => 0.00,
                ':iva' => 0.00,
                ':total_neto' => $totalNeto,
                ':estado_factura' => $respuestaDian->estado_dian,
                ':cufe' => $respuestaDian->cufe,
                ':qr_cadena' => $respuestaDian->qr_cadena,
                ':track_id_proveedor' => $respuestaDian->track_id,
                ':mensaje_respuesta_dian' => $respuestaDian->message,
                ':xml_dian_url' => $respuestaDian->xml_url,
                ':pdf_factura_url' => $respuestaDian->pdf_url,
                ':elaborado_por_user_id' => $user_id
            ]);

            $facturaId = $this->db->lastInsertId();

            // Guardar Detalles
            $stmtDet = $this->db->prepare("
                INSERT INTO factura_detalles (
                    factura_id, ingreso_id, paciente_id, dispensacion_id, producto_id,
                    codigo_cums, codigo_ium, nombre_medicamento, concentracion,
                    forma_farmaceutica, lote_numero, cantidad, valor_unitario, valor_total,
                    copago_aplicado, numero_prescripcion_mipres, id_entrega_mipres, tipo_medicamento_rips
                ) VALUES (
                    :factura_id, :ingreso_id, :paciente_id, :dispensacion_id, :producto_id,
                    :codigo_cums, :codigo_ium, :nombre_medicamento, :concentracion,
                    :forma_farmaceutica, :lote_numero, :cantidad, :valor_unitario, :valor_total,
                    0.00, '', '', 'PBS'
                )
            ");

            foreach ($detallesFactura as $d) {
                $stmtDet->execute([
                    ':factura_id' => $facturaId,
                    ':ingreso_id' => $d['ingreso_id'],
                    ':paciente_id' => $d['paciente_id'],
                    ':dispensacion_id' => $d['dispensacion_id'],
                    ':producto_id' => $d['producto_id'],
                    ':codigo_cums' => $d['codigo_cums'],
                    ':codigo_ium' => $d['codigo_ium'],
                    ':nombre_medicamento' => $d['nombre_medicamento'],
                    ':concentracion' => $d['concentracion'],
                    ':forma_farmaceutica' => $d['forma_farmaceutica'],
                    ':lote_numero' => $d['lote_numero'],
                    ':cantidad' => $d['cantidad'],
                    ':valor_unitario' => $d['valor_unitario'],
                    ':valor_total' => $d['valor_total']
                ]);
            }

            // Actualizar estado de las dispensaciones
            if (!empty($idsDispensaciones)) {
                $inQuery = implode(',', array_map('intval', $idsDispensaciones));
                $this->db->exec("
                    UPDATE ingreso_medicamentos_dispensados 
                    SET factura_id = {$facturaId}, estado_facturacion = 'FACTURADO_CONSOLIDADO'
                    WHERE id IN ({$inQuery})
                ");
            }

            // Incrementar consecutivo
            if (!empty($configProv['id'])) {
                $this->db->prepare("UPDATE facturas_config_proveedor SET consecutivo_actual = consecutivo_actual + 1 WHERE id = :id")
                         ->execute([':id' => $configProv['id']]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'factura_id' => $facturaId,
                'numero_factura' => "{$prefijo}{$numFactura}",
                'cufe' => $respuestaDian->cufe,
                'total_usuarios' => $totalUsuarios,
                'total_items' => $totalItems,
                'total_neto' => $totalNeto,
                'message' => "Factura Multiusuario {$prefijo}{$numFactura} consolidada exitosamente ({$totalUsuarios} pacientes, {$totalItems} medicamentos)."
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al consolidar factura multiusuario: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene listado de facturas con filtros
     */
    public function obtenerFacturas(array $filtros = []): array {
        $sql = "
            SELECT f.*, 
                   CONCAT(p.nombres, ' ', p.apellidos) AS paciente_nombre_completo,
                   p.numero_documento AS paciente_documento,
                   u.nombre_completo AS facturador_nombre
            FROM facturas f
            LEFT JOIN pacientes p ON f.paciente_id = p.id
            LEFT JOIN usuarios u ON f.elaborado_por_user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['tipo_factura'])) {
            $sql .= " AND f.tipo_factura = :tipo";
            $params[':tipo'] = $filtros['tipo_factura'];
        }
        if (!empty($filtros['eps_nombre'])) {
            $sql .= " AND UPPER(f.eps_nombre) LIKE :eps";
            $params[':eps'] = "%" . strtoupper(trim($filtros['eps_nombre'])) . "%";
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(f.fecha_emision) >= :desde";
            $params[':desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(f.fecha_emision) <= :hasta";
            $params[':hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['estado_factura'])) {
            $sql .= " AND f.estado_factura = :estado";
            $params[':estado'] = $filtros['estado_factura'];
        }

        $sql .= " ORDER BY f.id DESC LIMIT 150";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene detalle completo de una factura y sus líneas
     */
    public function obtenerDetalleFactura(int $factura_id): ?array {
        $stmt = $this->db->prepare("
            SELECT f.*, 
                   CONCAT(p.nombres, ' ', p.apellidos) AS paciente_nombre_completo,
                   p.tipo_documento AS paciente_tipo_doc,
                   p.numero_documento AS paciente_documento,
                   p.direccion_residencia AS paciente_direccion,
                   p.telefono AS paciente_telefono,
                   p.email AS paciente_email,
                   u.nombre_completo AS facturador_nombre,
                   s.nombre_sede, s.direccion AS sede_direccion, s.telefono AS sede_telefono,
                   e.razon_social AS empresa_nombre, e.nit AS empresa_nit, e.direccion AS empresa_direccion,
                   c.numero_contrato, c.modalidad_pago, c.codigo_eapb AS contrato_codigo_eapb
            FROM facturas f
            LEFT JOIN pacientes p ON f.paciente_id = p.id
            LEFT JOIN usuarios u ON f.elaborado_por_user_id = u.id
            LEFT JOIN sedes s ON f.sede_id = s.id
            LEFT JOIN empresas e ON f.empresa_id = e.id
            LEFT JOIN contratos_eps_config c ON (
                f.contrato_id = c.id 
                OR UPPER(TRIM(c.eps_nombre)) = UPPER(TRIM(f.eps_nombre))
                OR UPPER(TRIM(c.codigo_eapb)) = UPPER(TRIM(f.codigo_eapb))
            )
            WHERE f.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $factura_id]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$factura) return null;

        $stmtDet = $this->db->prepare("
            SELECT fd.*, i.ticket_numero,
                   CONCAT(p.nombres, ' ', p.apellidos) AS usuario_paciente_nombre,
                   p.tipo_documento AS usuario_paciente_td,
                   p.numero_documento AS usuario_paciente_doc
            FROM factura_detalles fd
            LEFT JOIN ingresos i ON fd.ingreso_id = i.id
            LEFT JOIN pacientes p ON fd.paciente_id = p.id
            WHERE fd.factura_id = :id
            ORDER BY fd.id ASC
        ");
        $stmtDet->execute([':id' => $factura_id]);
        $factura['items'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $factura;
    }

    /**
     * Consulta actas de dispensación listas para ser consolidadas en factura multiusuario
     */
    public function obtenerActasPendientesConsolidacion(string $eps_nombre = '', string $fecha_desde = '', string $fecha_hasta = ''): array {
        $sql = "
            SELECT 
                COALESCE(c.eps_nombre, p.eps_nombre) AS eps_nombre,
                COALESCE(c.codigo_eapb, p.eps_nombre) AS codigo_eapb,
                COALESCE(c.modalidad_pago, 'CAPITACION') AS modalidad_pago,
                COALESCE(c.numero_contrato, 'SIN-CONTRATO') AS numero_contrato,
                COUNT(DISTINCT i.id) AS total_ordenes,
                COUNT(DISTINCT p.id) AS total_pacientes,
                COUNT(d.id) AS total_medicamentos,
                SUM(d.cantidad_entregada * COALESCE(pm.precio_referencia, 2500.00)) AS subtotal_estimado,
                MIN(DATE(d.created_at)) AS fecha_primera_entrega,
                MAX(DATE(d.created_at)) AS fecha_ultima_entrega
            FROM ingreso_medicamentos_dispensados d
            JOIN ingresos i ON d.ingreso_id = i.id
            JOIN pacientes p ON i.paciente_id = p.id
            JOIN productos_medicamentos pm ON d.producto_id = pm.id
            LEFT JOIN contratos_eps_config c ON (
                UPPER(TRIM(c.eps_nombre)) COLLATE utf8mb4_general_ci = UPPER(TRIM(p.eps_nombre)) COLLATE utf8mb4_general_ci
                OR UPPER(TRIM(c.codigo_eapb)) COLLATE utf8mb4_general_ci = UPPER(TRIM(p.eps_nombre)) COLLATE utf8mb4_general_ci
                OR UPPER(TRIM(p.eps_nombre)) COLLATE utf8mb4_general_ci LIKE CONCAT('%', UPPER(TRIM(c.eps_nombre)) COLLATE utf8mb4_general_ci, '%')
                OR UPPER(TRIM(p.eps_nombre)) COLLATE utf8mb4_general_ci LIKE CONCAT('%', UPPER(TRIM(c.codigo_eapb)) COLLATE utf8mb4_general_ci, '%')
            ) AND c.estado_activo = 1
            WHERE (d.factura_id IS NULL OR d.estado_facturacion = 'PENDIENTE_CONSOLIDACION')
        ";
        $params = [];

        if (!empty($eps_nombre)) {
            $sql .= " AND (UPPER(p.eps_nombre) LIKE :eps OR UPPER(c.eps_nombre) LIKE :eps2 OR UPPER(c.codigo_eapb) LIKE :eps3)";
            $params[':eps'] = "%" . strtoupper(trim($eps_nombre)) . "%";
            $params[':eps2'] = "%" . strtoupper(trim($eps_nombre)) . "%";
            $params[':eps3'] = "%" . strtoupper(trim($eps_nombre)) . "%";
        }
        if (!empty($fecha_desde)) {
            $sql .= " AND DATE(d.created_at) >= :desde";
            $params[':desde'] = $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND DATE(d.created_at) <= :hasta";
            $params[':hasta'] = $fecha_hasta;
        }

        $sql .= " GROUP BY COALESCE(c.eps_nombre, p.eps_nombre), COALESCE(c.codigo_eapb, p.eps_nombre), c.modalidad_pago, c.numero_contrato ORDER BY subtotal_estimado DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
