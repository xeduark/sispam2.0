<?php

namespace App\Services\Facturacion;

class FacturadorResponse {
    public bool $success;
    public string $message;
    public ?string $numero_factura;
    public ?string $prefijo;
    public ?string $cufe;
    public ?string $qr_cadena;
    public ?string $track_id;
    public ?string $xml_url;
    public ?string $pdf_url;
    public string $estado_dian; // VALIDADA_DIAN, RECHAZADA_DIAN, ENVIADA_DIAN
    public array $raw_data;

    public function __construct(
        bool $success = false,
        string $message = '',
        ?string $numero_factura = null,
        ?string $prefijo = null,
        ?string $cufe = null,
        ?string $qr_cadena = null,
        ?string $track_id = null,
        ?string $xml_url = null,
        ?string $pdf_url = null,
        string $estado_dian = 'BORRADOR',
        array $raw_data = []
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->numero_factura = $numero_factura;
        $this->prefijo = $prefijo;
        $this->cufe = $cufe;
        $this->qr_cadena = $qr_cadena;
        $this->track_id = $track_id;
        $this->xml_url = $xml_url;
        $this->pdf_url = $pdf_url;
        $this->estado_dian = $estado_dian;
        $this->raw_data = $raw_data;
    }

    public function toArray(): array {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'numero_factura' => $this->numero_factura,
            'prefijo' => $this->prefijo,
            'cufe' => $this->cufe,
            'qr_cadena' => $this->qr_cadena,
            'track_id' => $this->track_id,
            'xml_url' => $this->xml_url,
            'pdf_url' => $this->pdf_url,
            'estado_dian' => $this->estado_dian,
            'raw_data' => $this->raw_data
        ];
    }
}
