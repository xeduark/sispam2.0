<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

class ReciboCaja {
    private PDO $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, p.nombres, p.apellidos, p.tipo_documento, p.numero_documento,
                   i.ticket_numero, u.nombre_completo AS cajero_nombre, s.nombre_sede
            FROM recibos_caja_copagos r
            JOIN pacientes p ON r.paciente_id = p.id
            JOIN ingresos i ON r.ingreso_id = i.id
            JOIN usuarios u ON r.cajero_user_id = u.id
            LEFT JOIN sedes s ON r.sede_id = s.id
            WHERE r.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByIngreso(int $ingreso_id): array {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nombre_completo AS cajero_nombre
            FROM recibos_caja_copagos r
            JOIN usuarios u ON r.cajero_user_id = u.id
            WHERE r.ingreso_id = :ingreso_id AND r.estado = 'PAGADO'
            ORDER BY r.id DESC
        ");
        $stmt->execute([':ingreso_id' => $ingreso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarReciboCopago(
        int $ingreso_id,
        int $paciente_id,
        float $valor,
        string $concepto = 'CUOTA_MODERADORA',
        string $metodo_pago = 'EFECTIVO',
        string $referencia = '',
        int $cajero_id = 1,
        string $observaciones = ''
    ): array {
        if ($valor <= 0) {
            return ['success' => false, 'message' => 'El valor del recaudo debe ser mayor a cero.'];
        }

        // Obtener datos del paciente e ingreso
        $stmtIng = $this->db->prepare("SELECT sede_id, empresa_id FROM ingresos WHERE id = :id");
        $stmtIng->execute([':id' => $ingreso_id]);
        $ing = $stmtIng->fetch(PDO::FETCH_ASSOC);

        $sede_id = $ing['sede_id'] ?? 1;
        $empresa_id = $ing['empresa_id'] ?? 1;

        $stmtPac = $this->db->prepare("SELECT tipo_afiliado, nivel_socioeconomico FROM pacientes WHERE id = :id");
        $stmtPac->execute([':id' => $paciente_id]);
        $pac = $stmtPac->fetch(PDO::FETCH_ASSOC);

        // Generar consecutivo de recibo de caja
        $consecutivo = 'RC-' . date('Ym') . '-' . str_pad((string)rand(100, 99999), 5, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO recibos_caja_copagos (
                empresa_id, sede_id, numero_recibo, ingreso_id, paciente_id,
                concepto, regimen_paciente, categoria_paciente, valor_recaudado,
                metodo_pago, referencia_transaccion, cajero_user_id, estado, fecha_pago, observaciones
            ) VALUES (
                :empresa_id, :sede_id, :numero_recibo, :ingreso_id, :paciente_id,
                :concepto, :regimen_paciente, :categoria_paciente, :valor_recaudado,
                :metodo_pago, :referencia_transaccion, :cajero_user_id, 'PAGADO', NOW(), :observaciones
            )
        ");

        $ok = $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':sede_id' => $sede_id,
            ':numero_recibo' => $consecutivo,
            ':ingreso_id' => $ingreso_id,
            ':paciente_id' => $paciente_id,
            ':concepto' => $concepto,
            ':regimen_paciente' => $pac['tipo_afiliado'] ?? 'Subsidiado',
            ':categoria_paciente' => $pac['nivel_socioeconomico'] ?? 'CATEGORIA A',
            ':valor_recaudado' => $valor,
            ':metodo_pago' => $metodo_pago,
            ':referencia_transaccion' => $referencia,
            ':cajero_user_id' => $cajero_id,
            ':observaciones' => $observaciones
        ]);

        if ($ok) {
            $reciboId = $this->db->lastInsertId();
            return [
                'success' => true,
                'recibo_id' => $reciboId,
                'numero_recibo' => $consecutivo,
                'valor' => $valor,
                'message' => "Recibo de caja {$consecutivo} registrado exitosamente por \$" . number_format($valor, 0, ',', '.')
            ];
        }

        return ['success' => false, 'message' => 'No se pudo registrar el recibo de caja en base de datos.'];
    }
}
