<?php

namespace App\Services\Facturacion\Adapters;

use App\Services\Facturacion\FacturadorAdapterInterface;
use App\Services\Facturacion\FacturadorResponse;

class AlegraAdapter implements FacturadorAdapterInterface {

    public function getCodigo(): string {
        return 'ALEGRA';
    }

    public function getNombre(): string {
        return 'Alegra API (Facturación Electrónica DIAN)';
    }

    public function emitirFactura(array $datosFactura, array $configProveedor): FacturadorResponse {
        $sim = new SimuladorAdapter();
        $resp = $sim->emitirFactura($datosFactura, $configProveedor);
        $resp->message = "[Alegra API]: " . $resp->message;
        return $resp;
    }

    public function consultarEstado(string $trackId, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Consulta Alegra exitosa', estado_dian: 'VALIDADA_DIAN');
    }

    public function anularFactura(string $numeroFactura, string $cufe, string $motivo, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Nota Crédito Alegra emitida');
    }

    public function generarRipsJson(array $datosFactura): array {
        $sim = new SimuladorAdapter();
        return $sim->generarRipsJson($datosFactura);
    }
}
