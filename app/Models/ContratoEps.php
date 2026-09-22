<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

class ContratoEps {
    private PDO $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM contratos_eps_config ORDER BY eps_nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM contratos_eps_config WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByEps(string $eps_nombre): ?array {
        $cleanEps = trim($eps_nombre);
        if (empty($cleanEps)) return null;
        $stmt = $this->db->prepare("
            SELECT * FROM contratos_eps_config 
            WHERE estado_activo = 1 
              AND (UPPER(eps_nombre) LIKE :eps 
                OR UPPER(codigo_eapb) = :eapb 
                OR :eps2 LIKE CONCAT('%', UPPER(eps_nombre), '%')
                OR :eps3 LIKE CONCAT('%', UPPER(codigo_eapb), '%'))
            LIMIT 1
        ");
        $stmt->execute([
            ':eps'  => "%" . strtoupper($cleanEps) . "%",
            ':eapb' => strtoupper($cleanEps),
            ':eps2' => strtoupper($cleanEps),
            ':eps3' => strtoupper($cleanEps)
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerContratoPorEps($eps_id = null, string $eps_nombre = ''): ?array {
        if (!empty($eps_nombre)) {
            $c = $this->getByEps($eps_nombre);
            if ($c) return $c;
        }
        if (!empty($eps_id)) {
            return $this->getById(intval($eps_id));
        }
        return null;
    }

    public function guardar(array $data): bool {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("
                UPDATE contratos_eps_config SET
                    eps_nombre = :eps_nombre,
                    codigo_eapb = :codigo_eapb,
                    numero_contrato = :numero_contrato,
                    modalidad_pago = :modalidad_pago,
                    facturacion_automatica = :facturacion_automatica,
                    aplica_copago_regulado = :aplica_copago_regulado,
                    estado_activo = :estado_activo
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id' => $data['id'],
                ':eps_nombre' => trim($data['eps_nombre']),
                ':codigo_eapb' => trim($data['codigo_eapb'] ?? ''),
                ':numero_contrato' => trim($data['numero_contrato'] ?? ''),
                ':modalidad_pago' => $data['modalidad_pago'] ?? 'CAPITACION',
                ':facturacion_automatica' => $data['facturacion_automatica'] ?? 'CONSOLIDADA_FIN_MES',
                ':aplica_copago_regulado' => intval($data['aplica_copago_regulado'] ?? 1),
                ':estado_activo' => intval($data['estado_activo'] ?? 1)
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO contratos_eps_config
                (empresa_id, eps_nombre, codigo_eapb, numero_contrato, modalidad_pago, facturacion_automatica, aplica_copago_regulado, estado_activo)
                VALUES
                (1, :eps_nombre, :codigo_eapb, :numero_contrato, :modalidad_pago, :facturacion_automatica, :aplica_copago_regulado, :estado_activo)
            ");
            return $stmt->execute([
                ':eps_nombre' => trim($data['eps_nombre']),
                ':codigo_eapb' => trim($data['codigo_eapb'] ?? ''),
                ':numero_contrato' => trim($data['numero_contrato'] ?? ''),
                ':modalidad_pago' => $data['modalidad_pago'] ?? 'CAPITACION',
                ':facturacion_automatica' => $data['facturacion_automatica'] ?? 'CONSOLIDADA_FIN_MES',
                ':aplica_copago_regulado' => intval($data['aplica_copago_regulado'] ?? 1),
                ':estado_activo' => intval($data['estado_activo'] ?? 1)
            ]);
        }
    }
}
