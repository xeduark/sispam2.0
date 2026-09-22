<?php

namespace App\Services;

use App\Models\ContratoEps;
use App\Models\Factura;
use App\Models\Inventario;
use App\Models\QrystalosBarrio;
use App\Models\ReciboCaja;
use App\Models\TratamientoCronico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

/**
 * Entrega en ventanilla: detalle de lo alistado/faltante y confirmación con firma
 * (descuenta Kardex, liquida copago/factura y programa entregas crónicas).
 * Port fiel de los endpoints AJAX de views/entrega/index.php del sistema nativo.
 */
class EntregaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    /** Detalle de medicamentos por dispensar y faltantes para la entrega. */
    public function detalle(int $ingresoId): array
    {
        $stmt = $this->db->prepare("
            SELECT i.*, p.tipo_documento, p.numero_documento, p.nombres, p.apellidos, p.eps_nombre,
                   COALESCE(p.telefono, p.numero_celular) AS telefono, p.numero_celular,
                   p.direccion_residencia, p.barrio, p.ciudad_residencia,
                   COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede
            FROM ingresos i
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $ingresoId]);
        $ingreso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $ingreso) {
            return ['status' => 'error', 'message' => 'Ingreso no encontrado.'];
        }

        // Código de barrio (ej. B024) -> nombre real, con el catálogo de Qrystalos
        $barrio = trim($ingreso['barrio'] ?? '');
        $ingreso['barrio_codigo'] = $barrio;
        $ingreso['barrio_nombre'] = ($barrio !== '' ? QrystalosBarrio::where('idbarrio', $barrio)->value('nombre_barrio') : null) ?: $barrio;

        $stmtIa = $this->db->prepare('SELECT * FROM ingreso_formulas_ia WHERE ingreso_id = :id ORDER BY id DESC LIMIT 1');
        $stmtIa->execute([':id' => $ingresoId]);
        $iaRow = $stmtIa->fetch(PDO::FETCH_ASSOC);

        $dispensar = [];
        $faltantes = [];

        if ($iaRow && ! empty($iaRow['datos_extraidos_json'])) {
            $ia = json_decode($iaRow['datos_extraidos_json'], true) ?: [];

            if (isset($ia['alistamiento'])) {
                $dispensar = $ia['alistamiento']['items_dispensar'] ?? [];
                $faltantes = $ia['alistamiento']['items_faltantes'] ?? [];
            } elseif (! empty($ia['medicamentos'])) {
                $inventario = new Inventario();

                foreach ($ia['medicamentos'] as $med) {
                    $cantidad = (int) ($med['cantidad_dispensar'] ?? $med['cantidad_solicitada'] ?? 1);
                    $productoId = (int) ($med['producto_id'] ?? 0);
                    $nombre = $med['nombre_medicamento'] ?? $med['descripcion'] ?? $med['medicamento'] ?? 'MEDICAMENTO';

                    // Sin producto asignado: búsqueda inteligente por principio activo y nombre comercial
                    if ($productoId <= 0 && $nombre !== '') {
                        $fila = $inventario->buscarMedicamentoInteligente($nombre, null, $ingreso['sede_id'] ?? null);
                        $productoId = $fila ? (int) $fila['id'] : 0;
                    }

                    // Lote FEFO disponible para el producto
                    $lote = ['id' => 0, 'numero_lote' => 'FEFO', 'fecha_vencimiento' => ''];
                    if ($productoId > 0) {
                        $stmtL = $this->db->prepare("
                            SELECT id, numero_lote, fecha_vencimiento FROM inventario_lotes
                            WHERE producto_id = :pid AND (estado_lote = 'DISPONIBLE' OR estado_lote = 'ACTIVO') AND cantidad_actual > 0
                            ORDER BY fecha_vencimiento ASC LIMIT 1
                        ");
                        $stmtL->execute([':pid' => $productoId]);
                        $lote = $stmtL->fetch(PDO::FETCH_ASSOC) ?: $lote;
                    }

                    $dispensar[] = [
                        'producto_id' => $productoId > 0 ? $productoId : 1,
                        'nombre_medicamento' => $nombre,
                        'lote_id' => (int) $lote['id'],
                        'numero_lote' => $lote['numero_lote'],
                        'fecha_vencimiento' => $lote['fecha_vencimiento'],
                        'cantidad_prescrita' => $cantidad,
                        'cantidad_entregar' => $cantidad,
                        'posologia' => $med['posologia'] ?? $med['dosis'] ?? '',
                    ];
                }
            }
        }

        if (empty($faltantes)) {
            $stmtPend = $this->db->prepare('SELECT * FROM ingreso_medicamentos_pendientes WHERE ingreso_id = :id');
            $stmtPend->execute([':id' => $ingresoId]);
            $faltantes = $stmtPend->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return [
            'status' => 'ok',
            'ingreso' => $ingreso,
            'items_dispensar' => $dispensar,
            'items_faltantes' => $faltantes,
            'es_100_pendiente' => empty($dispensar) && ! empty($faltantes),
            'faltantes_texto' => $ingreso['faltantes_alistamiento'] ?? '',
        ];
    }

    /**
     * Confirma la entrega física con firma: descuenta Kardex y guarda firma/foto,
     * factura y copago según el contrato de la EPS, programa entregas crónicas y calcula el SLA.
     *
     * @param  array<string, mixed>  $domicilio  direccion, telefono, barrio (entrega a domicilio)
     */
    public function confirmarConFirma(int $ingresoId, array $dispensar, array $faltantes, string $observaciones, string $firma, string $foto, int $userId, array $domicilio = []): array
    {
        if (! empty($domicilio['direccion'])) {
            $tel = $domicilio['telefono'] ?? '';
            $barrio = $domicilio['barrio'] ?? '';
            $this->db->prepare("
                UPDATE pacientes p JOIN ingresos i ON i.paciente_id = p.id
                SET p.direccion_residencia = :dir,
                    p.telefono = CASE WHEN :tel != '' THEN :tel2 ELSE p.telefono END,
                    p.numero_celular = CASE WHEN :tel3 != '' THEN :tel4 ELSE p.numero_celular END,
                    p.barrio = CASE WHEN :barr != '' THEN :barr2 ELSE p.barrio END
                WHERE i.id = :iid
            ")->execute([':dir' => $domicilio['direccion'], ':tel' => $tel, ':tel2' => $tel, ':tel3' => $tel, ':tel4' => $tel, ':barr' => $barrio, ':barr2' => $barrio, ':iid' => $ingresoId]);
        }

        // 1. Descuento en Kardex, historial y guardado de firma y foto
        $res = (new Inventario())->dispensarMedicamentosIngreso($ingresoId, $dispensar, $faltantes, $userId, $observaciones, $firma, $foto);

        if (! ($res && ($res['status'] ?? null) === 'ok')) {
            return is_array($res) ? $res : ['status' => 'error', 'message' => 'No fue posible confirmar la entrega.'];
        }

        $stmt = $this->db->prepare("
            SELECT i.*, p.id AS p_id, p.nombres, p.apellidos, p.tipo_afiliado, p.nivel_socioeconomico,
                   p.grupo_poblacional, i.es_alto_costo, p.eps_nombre
            FROM ingresos i JOIN pacientes p ON i.paciente_id = p.id WHERE i.id = :id
        ");
        $stmt->execute([':id' => $ingresoId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $pacienteId = (int) ($info['paciente_id'] ?? $info['p_id'] ?? 0);

        // 2. Facturación y copagos normativos
        try {
            if ($info) {
                $contrato = (new ContratoEps())->obtenerContratoPorEps($info['eps_id'] ?? null, $info['eps_nombre'] ?? '');
                $regla = $contrato['regla_facturacion'] ?? 'CONSOLIDADA_FIN_MES';
                $modalidad = $contrato['modalidad_pago'] ?? 'CAPITACION';
                $factura = null;
                $facturaModel = new Factura();

                if ($regla === 'SI_INMEDIATA' || $modalidad === 'PARTICULAR' || $modalidad === 'EVENTO') {
                    $factura = $facturaModel->crearFacturaIndividual($ingresoId, $userId);
                }

                $copago = $facturaModel->calcularCopagoOCuotaModeradora($info, $dispensar, $contrato);
                $recibo = null;
                if (! empty($copago['aplica']) && (float) $copago['valor'] > 0) {
                    $recibo = (new ReciboCaja())->generarReciboCopago(
                        $ingresoId, (int) ($info['p_id'] ?? $pacienteId), (float) $copago['valor'],
                        $copago['concepto'] ?? 'CUOTA_MODERADORA', 'EFECTIVO', '', $userId,
                        $copago['motivo'] ?? 'Liquidación normativa automática'
                    );
                }

                $res['facturacion'] = [
                    'modalidad' => $modalidad,
                    'regla' => $regla,
                    'eps_nombre' => $info['eps_nombre'] ?? 'EPS Sin Especificar',
                    'factura_inmediata' => $factura && ! empty($factura['success']),
                    'factura_data' => $factura,
                    'copago_info' => $copago,
                    'recibo_caja' => $recibo,
                    'paciente_id' => $pacienteId,
                ];
            }
        } catch (Throwable $e) {
            $res['facturacion_error'] = $e->getMessage();
        }

        // 3. Programar próximas cuotas de tratamiento crónico (multimes)
        try {
            $stmtIa = $this->db->prepare('SELECT datos_extraidos_json FROM ingreso_formulas_ia WHERE ingreso_id = :id ORDER BY id DESC LIMIT 1');
            $stmtIa->execute([':id' => $ingresoId]);
            $fila = $stmtIa->fetch(PDO::FETCH_ASSOC);
            if ($fila && ! empty($fila['datos_extraidos_json'])) {
                (new TratamientoCronico())->programarEntregasFuturas($pacienteId, $ingresoId, json_decode($fila['datos_extraidos_json'], true) ?: [], 'DOMICILIO');
            }
        } catch (Throwable $e) {
            Log::warning('Error programando multimes en entrega: '.$e->getMessage());
        }

        // SLA en minutos
        $stmtSla = $this->db->prepare('SELECT fecha_ingreso, fecha_salida FROM ingresos WHERE id = :id');
        $stmtSla->execute([':id' => $ingresoId]);
        $sla = $stmtSla->fetch(PDO::FETCH_ASSOC);
        $res['sla_min'] = ($sla && ! empty($sla['fecha_salida']) && ! empty($sla['fecha_ingreso']))
            ? round(max(0, strtotime($sla['fecha_salida']) - strtotime($sla['fecha_ingreso'])) / 60, 1)
            : 0;
        $res['acta_url'] = route('entrega.acta', $ingresoId);

        return $res;
    }
}
