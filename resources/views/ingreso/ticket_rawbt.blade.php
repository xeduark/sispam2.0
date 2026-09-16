<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Print</title></head>
<body style="font-family: monospace;">
<center>
<h3>{{ $config->razon_social }}</h3>
NIT: {{ $config->nit }}<br>
{{ $config->direccion }}<br>
Tel: {{ $config->telefono }}<br>
--------------------------------<br>
<b>TIQUETE DE TURNO</b><br>
<h2>{{ $ingreso->ticket_numero }}</h2>
--------------------------------<br>
</center>
<b>Fecha:</b> {{ $ingreso->fecha_ingreso?->format('d/m/Y h:i A') }}<br>
<b>Paciente:</b> {{ $ingreso->paciente->nombres }} {{ $ingreso->paciente->apellidos }}<br>
<b>Documento:</b> {{ $ingreso->paciente->tipo_documento }} {{ $ingreso->paciente->numero_documento }}<br>
<b>EPS:</b> {{ $ingreso->paciente->eps_nombre }}<br>
<b>Orientador:</b> {{ $ingreso->orientador?->nombre_completo }}<br>
@if ($ingreso->prioridad && $ingreso->prioridad !== 'NORMAL')
<center>
--------------------------------<br>
<b>*** ATENCIÓN PREFERENCIAL ***</b><br>
{{ strip_tags(get_prioridad_badge($ingreso->prioridad)) }}<br>
</center>
@endif
@if ($ingreso->contiene_mipres === 'SI')
<center>
--------------------------------<br>
<b>*** MEDICAMENTO DE ALTO COSTO ***</b><br>
</center>
@endif
--------------------------------<br>
<center>
{{ $config->pie_tiquete }}<br>
<small>SISPAM - Sistema de Gestión Farmacéutica</small>
</center>
</body>
</html>
