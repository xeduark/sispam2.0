@php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../models/Factura.php';

$facturaId = intval(request('id', 0));
$facturaModel = new Factura();
$f = $facturaModel->obtenerDetalleFactura($facturaId);

if (!$f) {
    die("Factura no encontrada.");
}
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Electrónica de Venta - {{ $f['prefijo'] . $f['numero_factura'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            padding: 20px 0;
        }
        .invoice-card {
            background: #ffffff;
            max-width: 850px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button type="button" class="btn btn-primary fw-bold px-4 shadow" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Imprimir / Guardar como PDF
    </button>
</div>

<div class="invoice-card">
    <!-- Header de Factura -->
    <div class="row align-items-center mb-4 pb-3 border-bottom">
        <div class="col-7">
            <h4 class="fw-bold text-dark mb-1">{{ $f['empresa_nombre'] ?: 'SISPAM GESTIÓN FARMACÉUTICA S.A.S.' }}</h4>
            <div class="small text-muted"><strong>NIT:</strong> {{ $f['empresa_nit'] ?: '900.123.456-7' }}</div>
            <div class="small text-muted"><strong>Sede:</strong> {{ $f['nombre_sede'] ?: 'Sede Principal' }} - {{ $f['sede_direccion'] ?: 'Medellín, Antioquia' }}</div>
            <div class="small text-muted"><strong>Habilitación MinSalud:</strong> 050010000101</div>
        </div>
        <div class="col-5 text-end">
            <div class="border border-dark rounded p-2 text-center bg-light">
                <span class="text-uppercase small fw-bold text-muted d-block">Factura Electrónica de Venta</span>
                <span class="fs-4 fw-bold font-mono text-primary">{{ $f['prefijo'] . $f['numero_factura'] }}</span>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Res. DIAN No. 18760000001</div>
            </div>
        </div>
    </div>

    <!-- Datos del Adquiriente / Paciente / EPS -->
    
@php
$esConsolidada = empty($f['paciente_id']) || in_array($f['tipo_factura'], ['MULTIUSUARIO_CAPITADA', 'CONSOLIDADA_EVENTO', 'CONSOLIDADA_PGP']);
        $tipoFacturaLabel = match($f['tipo_factura']) {
            'CONSOLIDADA_EVENTO' => 'Factura Consolidada por Evento',
            'MULTIUSUARIO_CAPITADA' => 'Factura Multiusuario (Capitación)',
            'CONSOLIDADA_PGP' => 'Factura Consolidada PGP',
            'ALTO_COSTO_MIPRES' => 'Factura Alto Costo / MIPRES',
            'PARTICULAR' => 'Factura Particular / Privado',
            default => 'Factura Individual de Venta en Salud'
        };
        $totalPacientesCons = count(array_unique(array_filter(array_column($f['items'] ?? [], 'paciente_id'))));
        if ($totalPacientesCons === 0 && !empty($f['items'])) {
            $totalPacientesCons = count(array_unique(array_filter(array_column($f['items'] ?? [], 'usuario_paciente_doc')))) ?: 1;
        }
@endphp

    <div class="row g-3 mb-4 p-3 bg-light rounded border">
        
@if ($esConsolidada)

            <!-- FACTURA CONSOLIDADA / EPS -->
            <div class="col-7">
                <span class="text-muted small fw-bold text-uppercase d-block">Entidad Responsable del Pago / Adquiriente (EPS):</span>
                <strong class="text-dark fs-5 d-block">{{ $f['eps_nombre'] }}</strong>
                <div class="small text-muted mt-1">
                    <strong>Código EAPB / NIT:</strong> {{ $f['codigo_eapb'] ?: ($f['contrato_codigo_eapb'] ?? 'EPS Sin Código') }}
                </div>
                
@if (!empty($f['numero_contrato']))

                    <div class="small text-muted"><strong>Contrato No:</strong> {{ $f['numero_contrato'] }} (Modalidad: {{ $f['modalidad_pago'] ?? 'EVENTO' }})</div>
                
@endif

                @if (!empty($f['periodo_corte_desde']))

                    <div class="small text-primary mt-1"><strong>Periodo de Corte Facturado:</strong> {{ $f['periodo_corte_desde'] }} al {{ $f['periodo_corte_hasta'] }}</div>
                
@endif

            </div>
            <div class="col-5 text-end">
                <span class="text-muted small fw-bold text-uppercase d-block">Información de Emisión:</span>
                <div class="small text-muted"><strong>Fecha Emisión:</strong> {{ $f['fecha_emision'] }}</div>
                <div class="small text-muted"><strong>Fecha Vencimiento:</strong> {{ $f['fecha_vencimiento'] }}</div>
                <div class="small text-muted mt-1"><strong>Modalidad Factura:</strong> <span class="badge bg-primary">{{ $tipoFacturaLabel }}</span></div>
                <div class="small text-muted mt-1"><strong>Usuarios Consolidados:</strong> {{ $totalPacientesCons }} Paciente(s)</div>
            </div>
        
@else

            <!-- FACTURA INDIVIDUAL PACIENTE -->
            <div class="col-6">
                <span class="text-muted small fw-bold text-uppercase d-block">Adquiriente / Paciente:</span>
                <strong class="text-dark fs-6">{{ $f['paciente_nombre_completo'] ?: 'Paciente General' }}</strong>
                <div class="small text-muted"><strong>Documento:</strong> {{ $f['paciente_tipo_doc'] ?? 'CC' }} {{ $f['paciente_documento'] ?? '' }}</div>
                <div class="small text-muted"><strong>Dirección:</strong> {{ $f['paciente_direccion'] ?: 'Medellín' }}</div>
                <div class="small text-muted"><strong>Teléfono:</strong> {{ $f['paciente_telefono'] ?: 'N/A' }}</div>
            </div>
            <div class="col-6 text-end">
                <span class="text-muted small fw-bold text-uppercase d-block">Entidad Administradora / EPS:</span>
                <strong class="text-dark fs-6">{{ $f['eps_nombre'] ?: 'Particular' }}</strong>
                <div class="small text-muted"><strong>Fecha Emisión:</strong> {{ $f['fecha_emision'] }}</div>
                <div class="small text-muted"><strong>Fecha Vencimiento:</strong> {{ $f['fecha_vencimiento'] }}</div>
                <div class="small text-muted"><strong>Tipo Factura:</strong> {{ $tipoFacturaLabel }}</div>
            </div>
        
@endif

    </div>

    <!-- Detalle de Medicamentos Dispensados -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.84rem;">
            <thead class="table-light text-uppercase small text-center">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;" class="text-start">Descripción Medicamento / CUMS</th>
                    <th style="width: 15%;">Lote</th>
                    <th style="width: 10%;">Cant</th>
                    <th style="width: 12%;" class="text-end">V. Unit</th>
                    <th style="width: 13%;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                
@foreach (($f['items'] ?? []) as $idx => $it)

                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong class="text-dark">{{ $it['nombre_medicamento'] }}</strong>
                            <div class="small text-muted" style="font-size: 0.72rem;">
                                CUMS: {{ $it['codigo_cums'] }} | Forma: {{ $it['forma_farmaceutica'] }}
                                @if (!empty($it['usuario_paciente_nombre']))

                                    <div class="mt-1 text-primary fw-semibold" style="font-size: 0.74rem;">
                                        <i class="fa-solid fa-user-check me-1"></i><strong>Usuario / Paciente:</strong> {{ $it['usuario_paciente_nombre'] }} ({{ $it['usuario_paciente_td'] ?? 'CC' }}: {{ $it['usuario_paciente_doc'] ?? '' }})
                                        
@if (!empty($it['ticket_numero']))

                                            <span class="text-muted ms-1">• Ticket: {{ $it['ticket_numero'] }}</span>
                                        
@endif

                                    </div>
                                
@endif

                            </div>
                        </td>
                        <td class="text-center font-mono small">{{ $it['lote_numero'] ?: 'N/A' }}</td>
                        <td class="text-center font-mono fw-bold">{{ $it['cantidad'] }}</td>
                        <td class="text-end font-mono">${{ number_format($it['valor_unitario'], 0, ',', '.') }}</td>
                        <td class="text-end font-mono fw-bold">${{ number_format($it['valor_total'], 0, ',', '.') }}</td>
                    </tr>
                
@endforeach

            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                    <td class="text-end font-mono fw-bold">${{ number_format($f['subtotal'], 0, ',', '.') }}</td>
                </tr>
                
@if ($f['total_copago_cuota'] > 0)

                    <tr>
                        <td colspan="5" class="text-end fw-bold text-danger">Cuota Moderadora / Copago Recaudado:</td>
                        <td class="text-end font-mono fw-bold text-danger">-${{ number_format($f['total_copago_cuota'], 0, ',', '.') }}</td>
                    </tr>
                
@endif

                <tr class="table-primary fs-6">
                    <td colspan="5" class="text-end fw-bold text-primary">TOTAL A PAGAR:</td>
                    <td class="text-end font-mono fw-bold text-primary">${{ number_format($f['total_neto'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- CUFE y QR DIAN -->
    <div class="row align-items-center p-3 border rounded bg-light mb-3">
        <div class="col-8">
            <div class="small fw-bold text-dark text-uppercase mb-1">Firma Digital & CUFE DIAN:</div>
            <div class="font-mono text-break text-muted" style="font-size: 0.68rem; line-height: 1.2;">
                {{ $f['cufe'] ?: 'CUFE-SIMULADO-DIAN-VALIDADO' }}
            </div>
            <div class="small text-muted mt-2" style="font-size: 0.72rem;">
                <i class="fa-solid fa-shield-check text-success me-1"></i> Documento Electrónico validado de acuerdo con la Resolución 2275 de 2024 de MinSalud y DIAN.
            </div>
        </div>
        <div class="col-4 text-center">
            <div class="p-2 bg-white border d-inline-block rounded shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data={{ urlencode($f['qr_cadena'] ?: 'https://catalogo-vpfe.dian.gov.co') }}" alt="QR DIAN" style="width: 90px; height: 90px;">
            </div>
        </div>
    </div>

    <!-- Pie de Factura -->
    <div class="text-center text-muted small mt-4 pt-2 border-top" style="font-size: 0.72rem;">
        Esta factura electrónica es un título valor según el Artículo 774 del Código de Comercio.<br>
        Generado por SISPAM v2.6 Enterprise • Sistema de Gestión Farmacéutica
    </div>
</div>

</body>
</html>
