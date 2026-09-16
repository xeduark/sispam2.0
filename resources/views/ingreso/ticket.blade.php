@php
    $paciente = $ingreso->paciente;
    $lineasRawbt = implode("\n", array_filter([
        '--------------------------------',
        '   '.$config->razon_social,
        '   NIT: '.$config->nit,
        '   '.$config->direccion,
        '   Tel: '.$config->telefono,
        '--------------------------------',
        '       TIQUETE DE TURNO',
        '         '.$ingreso->ticket_numero,
        '--------------------------------',
        'Fecha: '.$ingreso->fecha_ingreso?->format('d/m/Y h:i A'),
        'Paciente: '.$paciente->nombres.' '.$paciente->apellidos,
        'Documento: '.$paciente->tipo_documento.' '.$paciente->numero_documento,
        'EPS: '.$paciente->eps_nombre,
        'Orientador: '.$ingreso->orientador?->nombre_completo,
        $ingreso->contiene_mipres === 'SI' ? '*** MEDICAMENTO DE ALTO COSTO ***' : null,
        '--------------------------------',
        $config->pie_tiquete,
        "SISPAM - Gestion Farmaceutica\n\n\n\n",
    ], fn ($linea) => $linea !== null));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tiquete {{ $ingreso->ticket_numero }}</title>
    <style>
        @page { size: 80mm auto; margin: 0mm; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 2mm;
            color: #000;
            background: #fff;
        }
        .header, .footer { text-align: center; }
        .header h3 { margin: 0; font-size: 15px; font-weight: bold; }
        .ticket-num {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            border: 2px dashed #000;
            padding: 8px 4px;
            margin: 10px 0;
        }
        .info-row { margin-bottom: 4px; font-size: 12px; line-height: 1.3; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .no-print-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #0d6efd;
            color: #fff;
            text-align: center;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 10px;
            border-radius: 8px;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-rawbt { background: #ff9800; color: #000; }
        @media print {
            html, body { width: 80mm; margin: 0; padding: 0; }
            .no-print-btn, .btn-rawbt, .no-print-btn-container { display: none !important; }
            #ticket-print-area { width: 72mm; margin: 0 auto; padding: 2mm 0; }
        }
    </style>
</head>
<body>

<div style="max-width: 340px; margin: 0 auto;" class="no-print-btn-container">
    <button type="button" onclick="imprimirRawBTTablet()" class="no-print-btn btn-rawbt">
        📱 IMPRIMIR EN TABLET (RAWBT / BLUETOOTH)
    </button>
    <button type="button" onclick="window.print()" class="no-print-btn">
        🖨️ IMPRIMIR NATIVO (WIFI / RED / PC)
    </button>
</div>

<div id="ticket-print-area">
    <div class="header">
        <h3>{{ $config->razon_social }}</h3>
        <div>NIT: {{ $config->nit }}</div>
        <div>{{ $config->direccion }}</div>
        <div>Tel: {{ $config->telefono }}</div>
    </div>

    <div class="divider"></div>

    <div class="ticket-num">
        TIQUETE DE TURNO<br>
        {{ $ingreso->ticket_numero }}
    </div>

    <div class="info-row"><span class="bold">Fecha/Hora:</span> {{ $ingreso->fecha_ingreso?->format('d/m/Y h:i A') }}</div>
    <div class="info-row"><span class="bold">Paciente:</span> {{ $paciente->nombres }} {{ $paciente->apellidos }}</div>
    <div class="info-row"><span class="bold">Documento:</span> {{ $paciente->tipo_documento }} {{ $paciente->numero_documento }}</div>
    <div class="info-row"><span class="bold">EPS:</span> {{ $paciente->eps_nombre }}</div>
    <div class="info-row"><span class="bold">Orientador:</span> {{ $ingreso->orientador?->nombre_completo }}</div>

    @if ($ingreso->prioridad && $ingreso->prioridad !== 'NORMAL')
        <div style="border: 2px dashed #000; padding: 6px; text-align: center; font-weight: bold; margin: 10px 0; background-color: #f8f9fa;">
            *** ATENCIÓN PREFERENCIAL / PRIORITARIA ***<br>
            {{ strip_tags(get_prioridad_badge($ingreso->prioridad)) }}
            @if ($ingreso->prioridad_observacion)
                <br><small>Obs: {{ $ingreso->prioridad_observacion }}</small>
            @endif
        </div>
    @endif

    @if ($ingreso->contiene_mipres === 'SI')
        <div style="border: 2px solid #000; padding: 6px; text-align: center; font-weight: bold; margin: 10px 0;">
            ⚠ MEDICAMENTO DE ALTO COSTO ⚠
        </div>
    @endif

    @if ($ingreso->modulo_entrega_asignado)
        <div class="info-row"><span class="bold">Módulo Asignado:</span> {{ $ingreso->modulo_entrega_asignado }}</div>
    @endif

    <div class="divider"></div>

    <div class="footer">
        <p>{!! nl2br(e($config->pie_tiquete)) !!}</p>
        <small>SISPAM - Sistema de Gestión Farmacéutica</small>
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
    if (window.innerWidth > 900) {
        window.print();
    }
};
</script>
</body>
</html>
