@extends('layouts.app')

@section('titulo', 'Reportes & SLA - '.config('app.name'))

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-chart-pie me-2"></i> Módulo de Reportes, Analítica & Tiempos de Atención (SLA)</h4>
        <p class="text-muted small">Generación de informes de gestión, análisis de cuellos de botella en atención, registro de salidas y exportación a Excel.</p>
    </div>
</div>

<!-- Selector de Pestañas de Reporte -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'pacientes' ? 'active bg-primary' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=pacientes&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-users me-1"></i> Reporte de Pacientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'tiempos' ? 'active bg-primary' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=tiempos&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-stopwatch me-1"></i> Análisis de Tiempos (SLA)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'salidas' ? 'active bg-success text-white' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=salidas&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-door-open me-1"></i> Tiquetes Cerrados (Salidas)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'pendientes' ? 'active bg-warning text-dark' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=pendientes&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Medicamentos Faltantes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'productividad' ? 'active bg-success' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=productividad&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-user-check me-1"></i> Productividad Usuarios
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'eps' ? 'active bg-info text-dark' : 'bg-white border text-dark' }}" href="{{ route('reportes.index') }}?tab=eps&sede_id={{ urlencode($sede_filtro) }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}">
            <i class="fa-solid fa-hospital-user me-1"></i> Distribución por EPS
        </a>
    </li>
    <li class="nav-item ms-auto">
        <a class="nav-link fw-bold bg-dark text-white shadow-sm" href="{{ route('reportes.tickets_sede') }}">
            <i class="fa-solid fa-chart-column text-warning me-1"></i> Analítica de Tiquetes por Sede
        </a>
    </li>
</ul>

<!-- Filtros Globales de Fecha y Sede -->
<div class="card card-glass p-3 mb-4 border-0 shadow-sm">
    <form method="GET" action="{{ route('reportes.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="reportes">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="por_pagina" value="{{ $por_pagina }}">

        <div class="col-md-2">
            <label class="form-label small fw-semibold"><i class="fa-solid fa-calendar me-1 text-primary"></i> Fecha Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="{{ $fecha_desde }}">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold"><i class="fa-solid fa-calendar-check me-1 text-primary"></i> Fecha Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="{{ $fecha_hasta }}">
        </div>

        <!-- Filtro por Sede -->
        <div class="col-md-3">
            <label class="form-label small fw-semibold"><i class="fa-solid fa-location-dot text-warning me-1"></i> Filtrar por Sede</label>
            <select name="sede_id" class="form-select">
                <option value="">-- Todas las Sedes --</option>
                
@foreach ($sedes_disponibles as $sd)

                    <option value="{{ $sd['id'] }}" {{ $sede_filtro == $sd['id'] ? 'selected' : '' }}>
                        {{ $sd['nombre_sede'] }} ({{ $sd['empresa_nombre'] }})
                    </option>
                
@endforeach

            </select>
        </div>

        
@if ($tab === 'pacientes')

        <div class="col-md-2">
            <label class="form-label small fw-semibold"><i class="fa-solid fa-notes-medical me-1 text-info"></i> EPS</label>
            <select name="eps" class="form-select">
                <option value="">-- Todas las EPS --</option>
                
@foreach (config('sispam.eps_colombia') as $eps)

                    <option value="{{ $eps }}" {{ $eps_filtro === $eps ? 'selected' : '' }}>{{ $eps }}</option>
                
@endforeach

            </select>
        </div>
        
@endif


        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                <i class="fa-solid fa-filter me-1"></i> Filtrar
            </button>
            
@if (in_array($tab, ['pacientes', 'tiempos', 'salidas', 'pendientes']))

            <a href="{{ route('reportes.index') }}?tab={{ $tab }}&fecha_desde={{ $fecha_desde }}&fecha_hasta={{ $fecha_hasta }}&sede_id={{ urlencode($sede_filtro) }}&eps={{ urlencode($eps_filtro) }}&export=csv" class="btn btn-success fw-bold" title="Exportar datos completos a Excel">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </a>
            
@endif

        </div>
    </form>
</div>

<!-- CONTENIDO DE REPORTES POR PESTAÑA -->

@if ($tab === 'pacientes')

    @php
$totalPacientes = $ingresoModel->countReportePacientes($fecha_desde, $fecha_hasta, $eps_filtro, $estado_filtro, $sede_filtro);
        $totalPaginasPac = max(1, ceil($totalPacientes / $por_pagina));
        $listaPacientes = $ingresoModel->getReportePacientes($fecha_desde, $fecha_hasta, $eps_filtro, $estado_filtro, $sede_filtro, $por_pagina, $offset);
@endphp

    
    <div class="card card-glass border-0 shadow-sm mb-3 p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted small"><i class="fa-solid fa-list-ol me-1"></i> Ver por página</span>
                    <select class="form-select" onchange="location.href=this.value">
                        
@foreach ([25, 50, 100, 200] as $n)

                            <option value="{{ getUrlPaginacionRep(1, $n) }}" {{ $por_pagina === $n ? 'selected' : '' }}>{{ $n }} registros</option>
                        
@endforeach

                    </select>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-primary fs-6 p-2 shadow-sm">
                    <i class="fa-solid fa-users me-1"></i> Total: {{ number_format($totalPacientes) }} Pacientes
                </span>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users me-2 text-primary"></i> Pacientes Atendidos en el Periodo</h5>
            <span class="badge bg-light text-dark border">Página {{ $pagina }} de {{ $totalPaginasPac }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Sede de Atención</th>
                            <th>Paciente</th>
                            <th>Identificación</th>
                            <th>EPS</th>
                            <th>Fecha Ingreso</th>
                            <th>Fecha/Hora Salida</th>
                            <th>Tiempo SLA</th>
                            <th>Estado Actual</th>
                            <th>Orientador / Salida</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaPacientes))

                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-user-slash fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    No se encontraron pacientes para el periodo seleccionado.
                                </td>
                            </tr>
                        
@else

                            @foreach ($listaPacientes as $r)

                            @php
$minSla = $r['minutos_totales_sla'] ?? 0;
                                $slaClass = $minSla <= 20 ? 'bg-success' : ($minSla <= 45 ? 'bg-warning text-dark' : 'bg-danger');
@endphp

                            <tr>
                                <td class="ps-3 fw-bold text-primary">{{ $r['ticket_numero'] }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $r['nombre_sede'] ?? 'Sede Principal' }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ $r['nombres'] . ' ' . $r['apellidos'] }}</td>
                                <td>{{ $r['tipo_documento'] . ' ' . $r['numero_documento'] }}</td>
                                <td><span class="badge bg-info text-dark">{{ $r['eps_nombre'] }}</span></td>
                                <td>
                                    <div class="fw-bold">{{ date('h:i A', strtotime($r['fecha_ingreso'])) }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($r['fecha_ingreso'])) }}</div>
                                </td>
                                <td>
                                    
@if (!empty($r['fecha_llamado_entrega']))

                                        <span class="badge bg-primary"><i class="fa-solid fa-tv me-1"></i> {{ date('h:i A', strtotime($r['fecha_llamado_entrega'])) }}</span>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($r['fecha_llamado_entrega'])) }}</div>
                                    
@else

                                        <span class="badge bg-light text-muted border"><i class="fa-solid fa-hourglass-start me-1"></i> En Espera</span>
                                    
@endif

                                </td>
                                <td>
                                    
@if (!empty($r['fecha_salida']))

                                        <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> {{ date('h:i A', strtotime($r['fecha_salida'])) }}</span>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($r['fecha_salida'])) }}</div>
                                    
@else

                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> En Proceso</span>
                                    
@endif

                                </td>
                                <td>
                                    <span class="badge {{ $slaClass }} fw-bold px-2 py-1">
                                        <i class="fa-solid fa-stopwatch me-1"></i> {{ $minSla }} min
                                    </span>
                                </td>
                                <td>{!! get_estado_badge($r['estado_tramite']) !!}</td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $r['orientador_nombre'] ?? 'Sistema' }}</div>
                                    
@if (!empty($r['usuario_salida_nombre']))

                                        <div class="small text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-door-open me-1"></i> {{ $r['usuario_salida_nombre'] }}</div>
                                    
@endif

                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('ingreso.ticket', $r['id']) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Reimprimir Ticket">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        
@if ($r['estado_tramite'] === 'ENTREGADO' || !empty($r['firma_paciente_url']))

                                            <a href="{{ route('entrega.acta', $r['id']) }}" target="_blank" class="btn btn-sm btn-success fw-bold" title="Reimprimir Acta de Entrega Firmada">
                                                <i class="fa-solid fa-file-signature me-1"></i> Acta
                                            </a>
                                        
@endif

                                    </div>
                                </td>
                            </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
        {!! renderPaginacionReporte($pagina, $totalPaginasPac, $totalPacientes, $por_pagina, 'pacientes') !!}
    </div>

@php
elseif ($tab === 'tiempos'):
@endphp

    @php
$statsTiempos = $ingresoModel->getEstadisticasTiemposSLA($fecha_desde, $fecha_hasta, $sede_filtro);
        $totalTiempos = $statsTiempos['total_atendidos'];
        $totalPaginasTiempos = max(1, ceil($totalTiempos / $por_pagina));
        $listaTiempos = $ingresoModel->getReporteTiemposSLA($fecha_desde, $fecha_hasta, $sede_filtro, $por_pagina, $offset);
@endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-glass border-start border-4 border-primary p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase">TOTAL ATENCIONES EN PERIODO</div>
                <div class="fs-2 fw-bold text-primary">{{ number_format($statsTiempos['total_atendidos']) }} Pacientes</div>
                <div class="small text-muted">Ingresos registrados en el rango</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass border-start border-4 border-success p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase"><i class="fa-solid fa-stopwatch me-1"></i> PROMEDIO TRÁMITE FARMACIA (SLA)</div>
                <div class="fs-2 fw-bold text-success">{{ $statsTiempos['promedio_farmacia'] }} Minutos</div>
                <div class="small text-muted">Contado desde ingreso oficial</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass border-start border-4 border-info p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase">CUMPLE OBJETIVO SLA (&le; 30 MIN)</div>
                <div class="fs-2 fw-bold text-info">{{ $statsTiempos['porcentaje_cumplimiento'] }}%</div>
                <div class="small text-muted">{{ number_format($statsTiempos['cumplen_meta']) }} de {{ number_format($statsTiempos['total_atendidos']) }} atenciones</div>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm mb-3 p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted small"><i class="fa-solid fa-list-ol me-1"></i> Ver por página</span>
                    <select class="form-select" onchange="location.href=this.value">
                        
@foreach ([25, 50, 100, 200] as $n)

                            <option value="{{ getUrlPaginacionRep(1, $n) }}" {{ $por_pagina === $n ? 'selected' : '' }}>{{ $n }} registros</option>
                        
@endforeach

                    </select>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-light text-dark border">
                    <i class="fa-solid fa-calendar-check me-1 text-success"></i> <strong>L-V:</strong> 07:00 AM | <strong>Sáb-Dom-Festivos:</strong> 08:00 AM
                </span>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-stopwatch me-2 text-primary"></i> Análisis Detallado de Tiempos de Atención por Paciente</h5>
            <span class="badge bg-light text-dark border">Página {{ $pagina }} de {{ $totalPaginasTiempos }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Sede</th>
                            <th>Paciente / EPS</th>
                            <th>1. Ingreso</th>
                            <th>2. Entrega (TV 2)</th>
                            <th>3. Salida</th>
                            <th>⌛ Fila Exterior</th>
                            <th>⏱️ Trámite Farmacia (SLA)</th>
                            <th>Evaluación SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaTiempos))

                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-stopwatch fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    No se encontraron registros para el periodo seleccionado.
                                </td>
                            </tr>
                        
@else

                            @foreach ($listaTiempos as $t)

                            @php
$minSla = floatval($t['tiempo_tramite_farmacia_min'] ?? 0); 
                                $minFila = floatval($t['tiempo_fila_externa_min'] ?? 0); 
                                $tipoDia = $t['tipo_dia_atencion'] ?? 'HABIL';
@endphp

                            <tr>
                                <td class="ps-3 fw-bold text-primary fs-6">{{ $t['ticket_numero'] }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $t['nombre_sede'] ?? 'Sede Principal' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $t['nombres'] . ' ' . $t['apellidos'] }}</div>
                                    <span class="badge bg-info text-dark small">{{ $t['eps_nombre'] }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ date('h:i A', strtotime($t['fecha_ingreso'])) }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($t['fecha_ingreso'])) }}</div>
                                    
@if ($tipoDia === 'FESTIVO')

                                        <span class="badge bg-danger text-white mt-1"><i class="fa-solid fa-flag me-1"></i> Festivo</span>
                                    
@php
elseif ($tipoDia === 'SABADO' || $tipoDia === 'DOMINGO'):
@endphp

                                        <span class="badge bg-info text-dark mt-1"><i class="fa-solid fa-calendar-day me-1"></i> {{ $tipoDia === 'SABADO' ? 'Sáb' : 'Dom' }}</span>
                                    
@endif

                                </td>
                                <td>
                                    
@if (!empty($t['fecha_llamado_entrega']))

                                        <span class="badge bg-primary"><i class="fa-solid fa-tv me-1"></i> {{ date('h:i A', strtotime($t['fecha_llamado_entrega'])) }}</span>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($t['fecha_llamado_entrega'])) }}</div>
                                    
@else

                                        <span class="badge bg-light text-muted border"><i class="fa-solid fa-hourglass-start me-1"></i> En Espera</span>
                                    
@endif

                                </td>
                                <td>
                                    
@if (!empty($t['fecha_salida']))

                                        <span class="badge bg-success"><i class="fa-solid fa-door-open me-1"></i> {{ date('h:i A', strtotime($t['fecha_salida'])) }}</span>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($t['fecha_salida'])) }}</div>
                                    
@else

                                        <span class="badge bg-secondary"><i class="fa-solid fa-clock me-1"></i> En Proceso</span>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($t['fecha_finalizacion'])) }}</div>
                                    
@endif

                                </td>
                                <td>
                                    
@if ($minFila > 0)

                                        <span class="badge bg-light text-dark border">{{ $minFila }} min</span>
                                    
@else

                                        <span class="text-muted small">0 min</span>
                                    
@endif

                                </td>
                                <td class="fw-bold text-dark fs-6">
                                    <span class="badge bg-success fs-6 p-2">{{ $minSla }} min</span>
                                </td>
                                <td>
                                    
@if ($minSla <= 15)

                                        <span class="badge bg-success p-2"><i class="fa-solid fa-bolt me-1"></i> Excelente (&le; 15 min)</span>
                                    
@php
elseif ($minSla <= 30):
@endphp

                                        <span class="badge bg-primary p-2"><i class="fa-solid fa-circle-check me-1"></i> Cumple (&le; 30 min)</span>
                                    
@php
elseif ($minSla <= 45):
@endphp

                                        <span class="badge bg-warning text-dark p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tolerable (&le; 45 min)</span>
                                    
@else

                                        <span class="badge bg-danger p-2"><i class="fa-solid fa-circle-xmark me-1"></i> Fuera de SLA (> 45 min)</span>
                                    
@endif

                                    @if (!empty($t['usuario_salida_nombre']))

                                        <div class="small text-muted mt-1" style="font-size: 0.75rem;"><i class="fa-solid fa-door-open me-1"></i> {{ $t['usuario_salida_nombre'] }}</div>
                                    
@endif

                                </td>
                            </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
        {!! renderPaginacionReporte($pagina, $totalPaginasTiempos, $totalTiempos, $por_pagina, 'atenciones') !!}
    </div>

@php
elseif ($tab === 'salidas'):
@endphp

    @php
$statsSalidas = $ingresoModel->getEstadisticasSalidas($fecha_desde, $fecha_hasta, $sede_filtro);
        $totalSalidas = $statsSalidas['total_salidas'];
        $totalPaginasSalidas = max(1, ceil($totalSalidas / $por_pagina));
        $listaSalidas = $ingresoModel->getReporteSalidas($fecha_desde, $fecha_hasta, $sede_filtro, $por_pagina, $offset);
@endphp

    
    <!-- KPI CARDS SALIDAS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-success p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase"><i class="fa-solid fa-door-open text-success me-1"></i> Total Salidas Cerradas</div>
                <div class="fs-2 fw-bold text-success">{{ number_format($totalSalidas) }} Pacientes</div>
                <div class="small text-muted">Tiquetes concluidos en el periodo</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-primary p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase"><i class="fa-solid fa-stopwatch text-primary me-1"></i> Promedio Total SLA</div>
                <div class="fs-2 fw-bold text-primary">{{ $statsSalidas['promedio_sla'] }} Minutos</div>
                <div class="small text-muted">Desde ingreso hasta registro de salida</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-info p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase"><i class="fa-solid fa-circle-check text-info me-1"></i> SLA en Meta (&le; 20 min)</div>
                <div class="fs-2 fw-bold text-info">{{ $statsSalidas['porcentaje_cumplimiento'] }}%</div>
                <div class="small text-muted">{{ number_format($statsSalidas['cumplen_meta']) }} de {{ number_format($totalSalidas) }} atenciones</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-warning p-3 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase"><i class="fa-solid fa-person-cane text-warning me-1"></i> Atención Preferencial</div>
                <div class="fs-2 fw-bold text-warning">{{ number_format($statsSalidas['preferenciales']) }} Pacientes</div>
                <div class="small text-muted">Adulto mayor / Embarazo / Discapacidad</div>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm mb-3 p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted small"><i class="fa-solid fa-list-ol me-1"></i> Ver por página</span>
                    <select class="form-select" onchange="location.href=this.value">
                        
@foreach ([25, 50, 100, 200] as $n)

                            <option value="{{ getUrlPaginacionRep(1, $n) }}" {{ $por_pagina === $n ? 'selected' : '' }}>{{ $n }} registros</option>
                        
@endforeach

                    </select>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-success fs-6 p-2 shadow-sm">
                    <i class="fa-solid fa-door-open me-1"></i> Total: {{ number_format($totalSalidas) }} Salidas
                </span>
            </div>
        </div>
    </div>

    <!-- TABLA DE SALIDAS CERRADAS -->
    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-door-open me-2 text-success"></i> Detalle de Salidas y Tiquetes Concluidos</h5>
            <span class="badge bg-light text-dark border">Página {{ $pagina }} de {{ $totalPaginasSalidas }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Prioridad</th>
                            <th>Sede</th>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>EPS</th>
                            <th>Hora Ingreso</th>
                            <th>Hora Salida</th>
                            <th>Tiempo Total SLA</th>
                            <th>Cerrado Por</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaSalidas))

                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox display-4 d-block mb-2 text-secondary opacity-50"></i>
                                    No se registran salidas en el rango de fechas seleccionado.
                                </td>
                            </tr>
                        
@else

                            @foreach ($listaSalidas as $sal)

                                @php
$minSla = $sal['minutos_totales_sla'] ?? 0;
                                    $slaBadgeClass = $minSla <= 20 ? 'bg-success text-white' : ($minSla <= 45 ? 'bg-warning text-dark' : 'bg-danger text-white');
@endphp

                                <tr>
                                    <td class="ps-3 fw-bold text-primary fs-6">{{ $sal['ticket_numero'] }}</td>
                                    <td>{!! get_prioridad_badge($sal['prioridad'] ?? 'NORMAL') ?: '<span class="badge bg-secondary">Normal</span>' !!}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $sal['nombre_sede'] ?? 'Sede Principal' }}
                                        </span>
                                    </td>
                                    <td><div class="fw-bold">{{ $sal['nombres'] . ' ' . $sal['apellidos'] }}</div></td>
                                    <td><span class="text-muted">{{ $sal['tipo_documento'] . ' ' . $sal['numero_documento'] }}</span></td>
                                    <td><span class="badge bg-info text-dark">{{ $sal['eps_nombre'] ?? 'Particular' }}</span></td>
                                    <td class="small">
                                        <div>{{ date('h:i A', strtotime($sal['fecha_ingreso'])) }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($sal['fecha_ingreso'])) }}</div>
                                    </td>
                                    <td class="small text-success fw-bold">
                                        <div><i class="fa-solid fa-circle-check me-1"></i> {{ date('h:i A', strtotime($sal['fecha_salida'])) }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($sal['fecha_salida'])) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $slaBadgeClass }} fw-bold px-2 py-1 fs-6 shadow-sm">
                                            <i class="fa-solid fa-stopwatch me-1"></i> {{ $minSla }} min
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <i class="fa-solid fa-user me-1"></i> {{ $sal['usuario_salida_nombre'] ?? ($sal['orientador_nombre'] ?? 'Sistema') }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $sal['observacion_salida'] ?: 'Sin novedades' }}
                                    </td>
                                </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
        {!! renderPaginacionReporte($pagina, $totalPaginasSalidas, $totalSalidas, $por_pagina, 'salidas') !!}
    </div>

@php
elseif ($tab === 'pendientes'):
@endphp

    @php
$totalPendientes = $ingresoModel->countReportePendientes($fecha_desde, $fecha_hasta, $sede_filtro);
        $totalPaginasPend = max(1, ceil($totalPendientes / $por_pagina));
        $listaPendientes = $ingresoModel->getReportePendientes($fecha_desde, $fecha_hasta, $sede_filtro, $por_pagina, $offset);
@endphp


    <div class="card card-glass border-0 shadow-sm mb-3 p-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted small"><i class="fa-solid fa-list-ol me-1"></i> Ver por página</span>
                    <select class="form-select" onchange="location.href=this.value">
                        
@foreach ([25, 50, 100, 200] as $n)

                            <option value="{{ getUrlPaginacionRep(1, $n) }}" {{ $por_pagina === $n ? 'selected' : '' }}>{{ $n }} registros</option>
                        
@endforeach

                    </select>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-warning text-dark fs-6 p-2 shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Total: {{ number_format($totalPendientes) }} Casos
                </span>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm border-start border-4 border-warning">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i> Reporte de Pacientes con Medicamentos Faltantes / Entregas Parciales</h5>
            <span class="badge bg-light text-dark border">Página {{ $pagina }} de {{ $totalPaginasPend }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Sede</th>
                            <th>Paciente / Identificación</th>
                            <th>EPS</th>
                            <th>Fecha Ingreso / Entrega</th>
                            <th>Estado Trámite</th>
                            <th>Detalle de Medicamentos Faltantes / Novedades</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaPendientes))

                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-circle-info fs-4 me-2 text-warning"></i> No se encontraron registros de medicamentos faltantes o pendientes en el rango de fechas seleccionado.
                                </td>
                            </tr>
                        
@else

                            @foreach ($listaPendientes as $p)

                            @php
$detalleFaltantes = !empty($p['faltantes_alistamiento']) ? $p['faltantes_alistamiento'] : ($p['observaciones_pendientes'] ?? '');
@endphp

                            <tr>
                                <td class="ps-3 fw-bold text-primary fs-6">{{ $p['ticket_numero'] }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $p['nombre_sede'] ?? 'Sede Principal' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $p['nombres'] . ' ' . $p['apellidos'] }}</div>
                                    <small class="text-muted">{{ ($p['tipo_documento'] ?? '') . ' ' . ($p['numero_documento'] ?? '') }}</small>
                                </td>
                                <td><span class="badge bg-info text-dark">{{ $p['eps_nombre'] }}</span></td>
                                <td>
                                    <div><i class="fa-solid fa-calendar-plus text-primary me-1"></i> {{ date('d/m/Y h:i A', strtotime($p['fecha_ingreso'])) }}</div>
                                    
@if (!empty($p['updated_at']))

                                        <small class="text-muted"><i class="fa-solid fa-hand-holding-medical text-success me-1"></i> {{ date('d/m/Y h:i A', strtotime($p['updated_at'])) }}</small>
                                    
@endif

                                </td>
                                <td>{!! get_estado_badge($p['estado_tramite']) !!}</td>
                                <td style="min-width: 280px; max-width: 450px;">
                                    <div class="p-2 bg-light rounded border border-warning text-dark small font-monospace" style="white-space: pre-wrap; max-height: 140px; overflow-y: auto;">
                                        {{ $detalleFaltantes }}
                                    </div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        
@if (!empty($p['numero_documento']))

                                            <a href="{{ route('expedientes.index') }}?buscar={{ urlencode($p['numero_documento']) }}" class="btn btn-sm btn-outline-primary" title="Ver Expediente Completo">
                                                <i class="fa-solid fa-folder-open me-1"></i> Expediente
                                            </a>
                                        
@endif

                                        @if ($p['estado_tramite'] === 'ENTREGADO' || !empty($p['firma_paciente_url']))

                                            <a href="{{ route('entrega.acta', $p['id']) }}" target="_blank" class="btn btn-sm btn-success fw-bold" title="Reimprimir Acta de Entrega">
                                                <i class="fa-solid fa-file-signature"></i>
                                            </a>
                                        
@endif

                                    </div>
                                </td>
                            </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
        {!! renderPaginacionReporte($pagina, $totalPaginasPend, $totalPendientes, $por_pagina, 'pendientes') !!}
    </div>

@php
elseif ($tab === 'productividad'):
@endphp

    @php
$listaProd = $ingresoModel->getReporteProductividad($fecha_desde, $fecha_hasta, $sede_filtro);
@endphp

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-check me-2 text-success"></i> Reporte de Productividad por Usuario</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nombre del Usuario</th>
                            <th>Sede Asignada</th>
                            <th>Perfil / Rol</th>
                            <th class="text-end pe-3">Total de Ingresos / Atenciones Registradas</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaProd))

                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No se encontraron registros de productividad en el rango seleccionado.</td>
                            </tr>
                        
@else

                            @foreach ($listaProd as $pr)

                            <tr>
                                <td class="ps-3 fw-bold">{{ $pr['nombre_completo'] }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $pr['nombre_sede'] ?? 'Sede Principal' }}
                                    </span>
                                </td>
                                <td><span class="badge bg-primary">{{ $pr['rol'] }}</span></td>
                                <td class="text-end pe-3 fw-bold fs-5 text-success">{{ $pr['total_ingresos'] }} Atenciones</td>
                            </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>

@php
elseif ($tab === 'eps'):
@endphp

    @php
$listaEPS = $ingresoModel->getReportePorEPS($fecha_desde, $fecha_hasta, $sede_filtro);
@endphp

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-hospital-user me-2 text-info"></i> Distribución de Pacientes Atendidos por EPS</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Entidad Prestadora de Salud (EPS)</th>
                            <th class="text-end pe-3">Cantidad de Pacientes Atendidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($listaEPS))

                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted">No se encontraron registros de EPS en el rango seleccionado.</td>
                            </tr>
                        
@else

                            @foreach ($listaEPS as $ep)

                            <tr>
                                <td class="ps-3 fw-bold text-dark"><i class="fa-solid fa-notes-medical me-2 text-info"></i> {{ $ep['eps_nombre'] }}</td>
                                <td class="text-end pe-3 fw-bold fs-5 text-primary">{{ $ep['total_pacientes'] }} Pacientes</td>
                            </tr>
                            
@endforeach

                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
