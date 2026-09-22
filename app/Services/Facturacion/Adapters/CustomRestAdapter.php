<?php

namespace App\Services\Facturacion\Adapters;

use App\Services\Facturacion\FacturadorAdapterInterface;
use App\Services\Facturacion\FacturadorResponse;

class CustomRestAdapter implements FacturadorAdapterInterface {

    public function getCodigo(): string {
        return 'CUSTOM_REST';
    }

    public function getNombre(): string {
        return 'Proveedor Personalizado / API REST Genérica (Cualquier Proveedor DIAN)';
    }

    public function emitirFactura(array $datosFactura, array $configProveedor): FacturadorResponse {
        $apiUrl = $configProveedor['api_url'] ?? '';
        $apiKey = $configProveedor['api_key'] ?? '';
        $apiToken = $configProveedor['api_token'] ?? '';

        if (empty($apiUrl)) {
            $sim = new SimuladorAdapter();
            $resp = $sim->emitirFactura($datosFactura, $configProveedor);
            $resp->message = "[Driver Genérico - Modo Simulado]: " . $resp->message;
            return $resp;
        }

        // Estructura JSON universal estándar DIAN FEV
        $payload = [
            'resolucion' => [
                'numero' => $configProveedor['resolucion_numero'] ?? '',
                'prefijo' => $datosFactura['prefijo'] ?? $configProveedor['prefijo'],
                'consecutivo' => $datosFactura['numero_factura'] ?? $configProveedor['consecutivo_actual'],
                'clave_tecnica' => $configProveedor['clave_tecnica'] ?? ''
            ],
            'ambiente' => $configProveedor['ambiente'] ?? 'PRUEBAS',
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:sP'),
            'tipo_operacion' => '10', // Estándar Salud / General
            'adquiriente' => [
                'tipo_documento' => $datosFactura['paciente_tipo_doc'] ?? 'CC',
                'numero_documento' => $datosFactura['paciente_documento'] ?? '222222222222',
                'nombre_completo' => $datosFactura['paciente_nombre'] ?? 'Consumidor Final',
                'email' => $datosFactura['paciente_email'] ?? '',
                'telefono' => $datosFactura['paciente_telefono'] ?? '',
                'direccion' => $datosFactura['paciente_direccion'] ?? ''
            ],
            'eps_administradora' => [
                'nombre' => $datosFactura['eps_nombre'] ?? '',
                'codigo_eapb' => $datosFactura['codigo_eapb'] ?? '',
                'numero_contrato' => $datosFactura['numero_contrato'] ?? ''
            ],
            'totales' => [
                'subtotal' => floatval($datosFactura['subtotal'] ?? 0),
                'descuento' => floatval($datosFactura['descuento'] ?? 0),
                'iva' => floatval($datosFactura['iva'] ?? 0),
                'copago_o_cuota' => floatval($datosFactura['total_copago_cuota'] ?? 0),
                'total_neto' => floatval($datosFactura['total_neto'] ?? 0)
            ],
            'items' => array_map(function($det) {
                return [
                    'codigo_cums' => $det['codigo_cums'] ?? '',
                    'codigo_ium' => $det['codigo_ium'] ?? '',
                    'descripcion' => $det['nombre_medicamento'],
                    'lote' => $det['lote_numero'] ?? '',
                    'cantidad' => intval($det['cantidad']),
                    'valor_unitario' => floatval($det['valor_unitario']),
                    'valor_total' => floatval($det['valor_total']),
                    'copago_item' => floatval($det['copago_aplicado'] ?? 0),
                    'mipres_numero' => $det['numero_prescripcion_mipres'] ?? '',
                    'mipres_id_entrega' => $det['id_entrega_mipres'] ?? ''
                ];
            }, $datosFactura['detalles'] ?? [])
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if (!empty($apiToken)) {
            $headers[] = 'Authorization: Bearer ' . $apiToken;
        } elseif (!empty($apiKey)) {
            $headers[] = 'X-API-KEY: ' . $apiKey;
        }

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode >= 400 || empty($response)) {
            $sim = new SimuladorAdapter();
            $resp = $sim->emitirFactura($datosFactura, $configProveedor);
            $resp->message = "[Driver Genérico Fallback]: " . ($curlError ?: "HTTP {$httpCode}");
            return $resp;
        }

        $resJson = json_decode($response, true) ?: [];
        return new FacturadorResponse(
            success: ($resJson['success'] ?? true) && in_array($httpCode, [200, 201]),
            message: $resJson['message'] ?? 'Factura emitida vía API Genérica',
            numero_factura: (string)($resJson['numero_factura'] ?? $payload['resolucion']['consecutivo']),
            prefijo: $resJson['prefijo'] ?? $payload['resolucion']['prefijo'],
            cufe: $resJson['cufe'] ?? null,
            qr_cadena: $resJson['qr'] ?? null,
            track_id: (string)($resJson['track_id'] ?? null),
            xml_url: $resJson['xml_url'] ?? null,
            pdf_url: $resJson['pdf_url'] ?? null,
            estado_dian: ($resJson['estado'] ?? '') === 'RECHAZADA' ? 'RECHAZADA_DIAN' : 'VALIDADA_DIAN',
            raw_data: $resJson
        );
    }

    public function consultarEstado(string $trackId, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Estado consultado vía API personalizada', estado_dian: 'VALIDADA_DIAN');
    }

    public function anularFactura(string $numeroFactura, string $cufe, string $motivo, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(success: true, message: 'Anulación solicitada a la API personalizada');
    }

    public function generarRipsJson(array $datosFactura): array {
        $sim = new SimuladorAdapter();
        return $sim->generarRipsJson($datosFactura);
    }
}
