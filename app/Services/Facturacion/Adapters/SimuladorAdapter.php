<?php

namespace App\Services\Facturacion\Adapters;

use App\Services\Facturacion\FacturadorAdapterInterface;
use App\Services\Facturacion\FacturadorResponse;

class SimuladorAdapter implements FacturadorAdapterInterface {

    public function getCodigo(): string {
        return 'SIMULADOR';
    }

    public function getNombre(): string {
        return 'Simulador Interno DIAN (FEV-RIPS Pruebas)';
    }

    public function emitirFactura(array $datosFactura, array $configProveedor): FacturadorResponse {
        $prefijo = $datosFactura['prefijo'] ?? ($configProveedor['prefijo'] ?? 'SETP');
        $numero = $datosFactura['numero_factura'] ?? (string)($configProveedor['consecutivo_actual'] ?? rand(100, 99999));
        $numCompleto = $prefijo . $numero;

        // Generar CUFE SHA-384 simulado con algoritmo estándar DIAN
        $fecha = date('Y-m-d\TH:i:sP');
        $valTotal = number_format($datosFactura['total_neto'] ?? 0, 2, '.', '');
        $nitEmisor = $configProveedor['nit_emisor'] ?? '900123456';
        $docAdquiriente = $datosFactura['paciente_documento'] ?? '222222222222';
        $claveTecnica = $configProveedor['clave_tecnica'] ?? 'fc8eac422eba16e122fc8eac422eba16e12';

        $cadenaCufe = "NumFac={$numCompleto}&FecFac={$fecha}&ValFac={$valTotal}&CodImp1=01&ValImp1=0.00&NitOfe={$nitEmisor}&DocAdq={$docAdquiriente}&ClvTec={$claveTecnica}&TipoAmb=2";
        $cufe = hash('sha384', $cadenaCufe);

        // Cadena QR estándar DIAN
        $qrCadena = "https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey={$cufe}";
        $trackId = 'SIM-TRACK-' . strtoupper(bin2hex(random_bytes(8)));

        return new FacturadorResponse(
            success: true,
            message: "Factura electrónica {$numCompleto} validada exitosamente por el simulador DIAN.",
            numero_factura: $numero,
            prefijo: $prefijo,
            cufe: $cufe,
            qr_cadena: $qrCadena,
            track_id: $trackId,
            xml_url: "assets/uploads/facturas/xml/{$numCompleto}_signed.xml",
            pdf_url: "assets/uploads/facturas/pdf/{$numCompleto}.pdf",
            estado_dian: 'VALIDADA_DIAN',
            raw_data: [
                'ambiente' => $configProveedor['ambiente'] ?? 'PRUEBAS',
                'emisor' => $nitEmisor,
                'cadena_cufe_raw' => $cadenaCufe,
                'fecha_validacion' => date('Y-m-d H:i:s'),
                'codigo_respuesta' => '0',
                'descripcion_dian' => 'Documento procesado y aceptado con éxito.'
            ]
        );
    }

    public function consultarEstado(string $trackId, array $configProveedor): FacturadorResponse {
        return new FacturadorResponse(
            success: true,
            message: "Documento con TrackId {$trackId} verificado en estado ACEPTADO por la DIAN.",
            estado_dian: 'VALIDADA_DIAN',
            raw_data: ['track_id' => $trackId, 'estado' => 'Aceptado']
        );
    }

    public function anularFactura(string $numeroFactura, string $cufe, string $motivo, array $configProveedor): FacturadorResponse {
        $ncNumero = 'NC-' . $numeroFactura;
        return new FacturadorResponse(
            success: true,
            message: "Nota Crédito {$ncNumero} generada y validada por la DIAN por motivo: {$motivo}.",
            numero_factura: $ncNumero,
            estado_dian: 'VALIDADA_DIAN',
            raw_data: ['nota_credito' => $ncNumero, 'factura_afectada' => $numeroFactura, 'cufe_afectado' => $cufe]
        );
    }

    public function generarRipsJson(array $datosFactura): array {
        $ripsMedicamentos = [];
        $consecutivo = 1;

        foreach ($datosFactura['detalles'] ?? [] as $det) {
            $ripsMedicamentos[] = [
                'codPrestador' => $datosFactura['codigo_prestador'] ?? '050010000101',
                'numAutorizacion' => $datosFactura['numero_autorizacion'] ?? 'AUT-000',
                'idMIPRES' => $det['numero_prescripcion_mipres'] ?? '',
                'fechaDispensacion' => date('Y-m-d H:i'),
                'codDiagnosticoPrincipal' => $datosFactura['diagnostico_cie10'] ?? 'Z760',
                'codDiagnosticoRelacionado' => '',
                'tipoMedicamento' => $det['tipo_medicamento_rips'] ?? 'PBS',
                'codTecnologiaSalud' => $det['codigo_cums'] ?? 'CUMS-GENERICO',
                'nomTecnologiaSalud' => $det['nombre_medicamento'] ?? '',
                'concentracionMedicamento' => $det['concentracion'] ?? '',
                'unidadMedida' => $det['forma_farmaceutica'] ?? '',
                'formaFarmaceutica' => $det['forma_farmaceutica'] ?? '',
                'unidadMinimaDispensacion' => 'UNIDAD',
                'cantidadMedicamento' => intval($det['cantidad'] ?? 1),
                'diasTratamiento' => intval($det['duracion_dias'] ?? 30),
                'vrUnitMedicamento' => floatval($det['valor_unitario'] ?? 0),
                'vrServicio' => floatval($det['valor_total'] ?? 0),
                'tipoPagoModerador' => !empty($det['copago_aplicado']) ? 'Cuota Moderadora' : 'Ninguno',
                'valorPagoModerador' => floatval($det['copago_aplicado'] ?? 0),
                'numFEVPagoModerador' => '',
                'consecutivo' => $consecutivo++
            ];
        }

        return [
            'numDocumentoIdObligado' => $datosFactura['nit_empresa'] ?? '900123456',
            'numFactura' => ($datosFactura['prefijo'] ?? '') . ($datosFactura['numero_factura'] ?? ''),
            'tipoNota' => null,
            'numNota' => null,
            'medicamentos' => $ripsMedicamentos
        ];
    }
}
