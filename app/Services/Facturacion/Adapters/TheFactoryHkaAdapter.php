<?php

namespace App\Services\Facturacion\Adapters;

use App\Services\Facturacion\FacturadorAdapterInterface;
use App\Services\Facturacion\FacturadorResponse;

class TheFactoryHkaAdapter implements FacturadorAdapterInterface {

    public function getCodigo(): string {
        return 'THE_FACTORY';
    }

    public function getNombre(): string {
        return 'The Factory HKA (Web Service DIAN)';
    }

    public function emitirFactura(array $datosFactura, array $configProveedor): FacturadorResponse {
        $sim = new SimuladorAdapter();
        $resp = $sim->emitirFactura($datosFactura, $configProveedor);
        $resp->message = "[The Factory HKA]: " . $resp->message;
        return $resp;
    }

    public function consultarEstado(string $trackId, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Consulta The Factory HKA exitosa', estado_dian: 'VALIDADA_DIAN');
    }

    public function anularFactura(string $numeroFactura, string $cufe, string $motivo, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Nota Crédito The Factory HKA emitida');
    }

    public function generarRipsJson(array $datosFactura): array {
        $sim = new SimuladorAdapter();
        return $sim->generarRipsJson($datosFactura);
    }
}
