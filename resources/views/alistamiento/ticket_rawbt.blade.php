<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Print Alistamiento</title></head>
<body style="font-family: monospace;">
<center>
<h3>{{ $config->razon_social }}</h3>
NIT: {{ $config->nit }}<br>
--------------------------------<br>
<b>TIQUETE DE ALISTAMIENTO</b><br>
<h2>{{ $ingreso->ticket_numero }}</h2>
--------------------------------<br>
</center>
<b>Fecha:</b> {{ $ingreso->fecha_ingreso?->format('d/m/Y h:i A') }}<br>
<b>Paciente:</b> {{ $ingreso->paciente->nombres }} {{ $ingreso->paciente->apellidos }}<br>
<b>Documento:</b> {{ $ingreso->paciente->tipo_documento }} {{ $ingreso->paciente->numero_documento }}<br>
<b>EPS:</b> {{ $ingreso->paciente->eps_nombre }}<br>
<b>Estado:</b> EN ALISTAMIENTO<br>
--------------------------------<br>
<center>
<small>SISPAM - Sistema de Gestión Farmacéutica</small>
</center>
</body>
</html>
