@php
    $paciente = $ingreso->paciente;
    $lineasRawbt = implode("\n", [
        '--------------------------------',
        '   '.$config->razon_social,
        '   NIT: '.$config->nit,
        '--------------------------------',
        '     TIQUETE ALISTAMIENTO',
        '         '.$ingreso->ticket_numero,
        '--------------------------------',
        'Fecha: '.$ingreso->fecha_ingreso?->format('d/m/Y H:i'),
        'Paciente: '.$paciente->nombres.' '.$paciente->apellidos,
        'Documento: '.$paciente->tipo_documento.' '.$paciente->numero_documento,
        'EPS: '.$paciente->eps_nombre,
        'Ventanilla: '.($ingreso->modulo_entrega_asignado ?: 'Ventanilla General'),
        '--------------------------------',
        "SISPAM - Gestion Farmaceutica\n\n\n\n",
    ]);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiquete Alistamiento - {{ $ingreso->ticket_numero }}</title>
    <style>
        @page { size: 80mm auto; margin: 0mm; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000000;
            background-color: #ffffff;
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 2mm;
        }
        .ticket-container { width: 100%; margin: 0 auto; border: 1px dashed #000000; padding: 10px 5px; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000000; margin: 8px 0; }
        .ticket-num { font-size: 24px; font-weight: bold; margin: 5px 0; }
        .btn-print {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: #0d6efd;
            color: #ffffff;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            border: none;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-rawbt { background-color: #ff9800; color: #000000; }
        @media print {
            html, body { width: 80mm; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .ticket-container { border: none; padding: 2mm 0; }
        }
    </style>
</head>
<body>

<div style="max-width: 340px; margin: 0 auto;">
    <button type="button" class="btn-print btn-rawbt no-print" onclick="imprimirRawBTTablet()">📱 IMPRIMIR EN TABLET CON RAWBT (BLUETOOTH / OTG)</button>
    <button class="btn-print no-print" onclick="window.print()">🖨️ IMPRIMIR NATIVO (WIFI / RED / PC)</button>
</div>

<div class="ticket-container">
    <div class="text-center">
        <div class="text-bold">{{ $config->razon_social }}</div>
        <div>NIT: {{ $config->nit }}</div>
        <div>{{ $ingreso->sede?->nombre_sede ?? 'SEDE PRINCIPAL' }}</div>
    </div>

    <div class="divider"></div>

    <div class="text-center">
        <small>TIQUETE DE ALISTAMIENTO</small>
        <div class="ticket-num">{{ $ingreso->ticket_numero }}</div>
        <div><strong>Prioridad:</strong> {{ $ingreso->prioridad ?? 'NORMAL' }}</div>
    </div>

    <div class="divider"></div>

    <div>
        <div><strong>PACIENTE:</strong></div>
        <div class="text-bold">{{ $paciente->nombres }} {{ $paciente->apellidos }}</div>
        <div><strong>DOC:</strong> {{ $paciente->tipo_documento }} {{ $paciente->numero_documento }}</div>
        <div><strong>EPS:</strong> {{ $paciente->eps_nombre }}</div>
    </div>

    <div class="divider"></div>

    <div>
        <div><strong>VENTANILLA ASIGNADA:</strong></div>
        <div class="text-bold text-center" style="font-size: 16px;">{{ $ingreso->modulo_entrega_asignado ?: 'Ventanilla General' }}</div>
    </div>

    @if ($ingreso->faltantes_alistamiento)
    <div class="divider"></div>
    <div>
        <div><strong>⚠️ NOVEDADES / FALTANTES:</strong></div>
        <small>{!! nl2br(e($ingreso->faltantes_alistamiento)) !!}</small>
    </div>
    @endif

    <div class="divider"></div>

    <div class="text-center" style="font-size: 11px;">
        <div>Fecha Ingreso: {{ $ingreso->fecha_ingreso?->format('d/m/Y H:i') }}</div>
        <div>Fecha Alistado: {{ now()->format('d/m/Y H:i') }}</div>
        <div style="margin-top: 8px;">Por favor espere a ser llamado en pantalla TV.</div>
    </div>
</div>

<script>
function imprimirRawBTTablet() {
    const ticketText = @json($lineasRawbt);

    try {
        const base64Data = btoa(unescape(encodeURIComponent(ticketText)));
        const rawbtBase64Uri = 'rawbt:base64,' + base64Data;

        fetch('http://127.0.0.1:40213/print?base64=' + base64Data, { mode: 'no-cors' })
            .then(function() {
                console.log("Enviado por socket local a RawBT");
            })
            .catch(function() {
                window.location.href = rawbtBase64Uri;
            });

        setTimeout(function() {
            window.location.href = rawbtBase64Uri;
        }, 150);
    } catch (e) {
        window.location.href = @json('rawbt:'.$rawbtUrl);
    }
}

window.onload = function() {
    if (window.location.search.includes('auto_print=1') || window.innerWidth > 900) {
        window.print();
    }
};
</script>
</body>
</html>
