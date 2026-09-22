@php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../models/ReciboCaja.php';

$reciboId = intval(request('id', 0));
$reciboModel = new ReciboCaja();
$r = $reciboModel->getById($reciboId);

if (!$r) {
    die("Recibo de caja no encontrado.");
}
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Caja - {{ $r['numero_recibo'] }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            color: #000000;
        }
        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000000; margin: 6px 0; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print text-center" style="margin-bottom: 15px;">
    <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold; cursor: pointer;">Imprimir Recibo</button>
</div>

<div class="ticket">
    <div class="text-center">
        <strong style="font-size: 14px;">SISPAM GESTIÓN FARMACÉUTICA</strong><br>
        NIT: 900.123.456-7<br>
        Sede: {{ $r['nombre_sede'] ?: 'Sede Prado' }}<br>
        <div class="divider"></div>
        <strong style="font-size: 13px;">COMPROBANTE DE RECAUDO</strong><br>
        <strong>No. {{ $r['numero_recibo'] }}</strong><br>
        Fecha: {{ $r['fecha_pago'] }}<br>
    </div>

    <div class="divider"></div>

    <div>
        <strong>Paciente:</strong> {{ $r['nombres'] . ' ' . $r['apellidos'] }}<br>
        <strong>Doc:</strong> {{ $r['tipo_documento'] . ' ' . $r['numero_documento'] }}<br>
        <strong>Tiquete:</strong> {{ $r['ticket_numero'] }}<br>
        <strong>Régimen:</strong> {{ $r['regimen_paciente'] ?: 'Contributivo' }}<br>
        <strong>Categoría:</strong> {{ $r['categoria_paciente'] ?: 'Rango A' }}<br>
    </div>

    <div class="divider"></div>

    <div>
        <strong>Concepto:</strong> {{ str_replace('_', ' ', $r['concepto']) }}<br>
        <strong>Método Pago:</strong> {{ $r['metodo_pago'] }}<br>
        
@if (!empty($r['referencia_transaccion']))

            <strong>Ref:</strong> {{ $r['referencia_transaccion'] }}<br>
        
@endif

    </div>

    <div class="divider"></div>

    <div class="text-end" style="font-size: 14px;">
        <strong>TOTAL RECAUDADO:</strong><br>
        <strong style="font-size: 16px;">${{ number_format($r['valor_recaudado'], 0, ',', '.') }} COP</strong>
    </div>

    <div class="divider"></div>

    <div style="font-size: 10px;">
        <strong>Cajero:</strong> {{ $r['cajero_nombre'] ?: 'Orientador Farmacia' }}<br>
        Estado: {{ $r['estado'] }}<br>
        {{ !empty($r['observaciones']) ? 'Obs: ' . htmlspecialchars($r['observaciones']) . '<br>' : '' }}
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px;">
        Conserve este comprobante para cualquier trámite o reclamación.<br>
        ¡Gracias por su visita!
    </div>
</div>

</body>
</html>
