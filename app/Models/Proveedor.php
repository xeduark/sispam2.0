<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

class Proveedor {
    private $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    public function getProveedores($solo_activos = true) {
        $sql = "SELECT * FROM proveedores";
        if ($solo_activos) {
            $sql .= " WHERE estado = 'Activo'";
        }
        $sql .= " ORDER BY razon_social ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardar($datos) {
        $id = intval($datos['id'] ?? 0);
        $params = [
            ':nit'                => trim($datos['nit']),
            ':razon_social'       => mb_strtoupper(trim($datos['razon_social']), 'UTF-8'),
            ':nombre_contacto'    => trim($datos['nombre_contacto'] ?? ''),
            ':telefono'           => trim($datos['telefono'] ?? ''),
            ':email'              => trim($datos['email'] ?? ''),
            ':ciudad'             => trim($datos['ciudad'] ?? 'MEDELLIN'),
            ':direccion'          => trim($datos['direccion'] ?? ''),
            ':registro_camara'    => trim($datos['registro_camara'] ?? ''),
            ':concepto_sanitario' => trim($datos['concepto_sanitario'] ?? 'FAVORABLE VIGENTE'),
            ':estado'             => $datos['estado'] ?? 'Activo'
        ];

        if ($id > 0) {
            $params[':id'] = $id;
            $stmt = $this->db->prepare("
                UPDATE proveedores SET
                    nit = :nit,
                    razon_social = :razon_social,
                    nombre_contacto = :nombre_contacto,
                    telefono = :telefono,
                    email = :email,
                    ciudad = :ciudad,
                    direccion = :direccion,
                    registro_camara = :registro_camara,
                    concepto_sanitario = :concepto_sanitario,
                    estado = :estado
                WHERE id = :id
            ");
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO proveedores
                (nit, razon_social, nombre_contacto, telefono, email, ciudad, direccion, registro_camara, concepto_sanitario, estado)
                VALUES
                (:nit, :razon_social, :nombre_contacto, :telefono, :email, :ciudad, :direccion, :registro_camara, :concepto_sanitario, :estado)
            ");
            return $stmt->execute($params);
        }
    }
}