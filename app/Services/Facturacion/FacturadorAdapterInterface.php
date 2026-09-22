<?php

namespace App\Services\Facturacion;

interface FacturadorAdapterInterface {
    /**
     * Obtiene el código identificador único del adaptador (ej. FACTURATECH, SIIGO, ALEGRA, SIMULADOR)
     */
    public function getCodigo(): string;

    /**
     * Obtiene el nombre descriptivo del proveedor
     */
    public function getNombre(): string;

    /**
     * Emite una factura electrónica ante el proveedor tecnológico / DIAN
     * @param array $datosFactura Cabecera, detalles de medicamentos, datos del paciente y EPS
     * @param array $configProveedor Credenciales, ambiente y resolución DIAN
     * @return FacturadorResponse
     */
    public function emitirFactura(array $datosFactura, array $configProveedor): FacturadorResponse;

    /**
     * Consulta el estado de una factura previamente enviada usando su TrackId o CUFE
     */
    public function consultarEstado(string $trackId, array $configProveedor): FacturadorResponse;

    /**
     * Anula una factura electrónica generando la respectiva Nota Crédito
     */
    public function anularFactura(string $numeroFactura, string $cufe, string $motivo, array $configProveedor): FacturadorResponse;

    /**
     * Genera la estructura de RIPS JSON (Resolución 2275 de 2024) para la factura
     */
    public function generarRipsJson(array $datosFactura): array;
}
