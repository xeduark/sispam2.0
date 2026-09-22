@extends('layouts.app')

@section('titulo', 'Tickets por Sede - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-2">

    <!-- ENCABEZADO Y TÍTULO -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}" class="text-decoration-none">Reportes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Analítica de Tiquetes & SLA por Sede</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-column text-primary"></i>
                <span>Analítica de Tiquetes por Sede (Flujo & SLA en Horas/Minutos)</span>
            </h4>
            <p class="text-muted small mb-0">
                Monitoreo ejecutivo del flujo de tiquetes creados, cerrados y pendientes, junto con el tiempo promedio transcurrido entre el ingreso y la salida (SLA) por sede.
            </p>
        </div>

        <!-- Botones de Acción Global -->
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('reportes.tickets_sede') }}?action=export_csv&fecha_desde={{ urlencode($fecha_desde) }}&fecha_hasta={{ urlencode($fecha_hasta) }}&sede_id={{ urlencode($sede_id) }}" 
               class="btn btn-sm btn-success fw-bold shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel (CSV)
            </a>
            <a href="{{ route('reportes.tickets_sede') }}?action=imprimir&fecha_desde={{ urlencode($fecha_desde) }}&fecha_hasta={{ urlencode($fecha_hasta) }}&sede_id={{ urlencode($sede_id) }}" 
               target="_blank"
               class="btn btn-sm btn-outline-secondary fw-bold shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Imprimir Reporte
            </a>
            <a href="{{ route('salida.index') }}" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" target="_blank">
                <i class="fa-solid fa-door-open me-1"></i> Ir a Módulo de Salida
            </a>
        </div>
    </div>

    <!-- TARJETA DE FILTROS AVANZADOS & ATAJOS RÁPIDOS -->
    <div class="card card-glass border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reportes.tickets_sede') }}" id="formFiltroTickets" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="analitica_tickets">

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">
                        <i class="fa-regular fa-calendar me-1 text-primary"></i> Fecha Desde:
                    </label>
                    <input type="date" name="fecha_desde" id="input_fecha_desde" class="form-control form-control-sm shadow-sm" value="{{ $fecha_desde }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">
                        <i class="fa-regular fa-calendar-check me-1 text-primary"></i> Fecha Hasta:
                    </label>
                    <input type="date" name="fecha_hasta" id="input_fecha_hasta" class="form-control form-control-sm shadow-sm" value="{{ $fecha_hasta }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">
                        <i class="fa-solid fa-location-dot me-1 text-danger"></i> Sede de Atención:
                    </label>
                    <select name="sede_id" class="form-select form-select-sm shadow-sm">
                        <option value="">-- Todas las Sedes --</option>
                        
@foreach ($sedes_disponibles as $sd)

                            <option value="{{ $sd['id'] }}" {{ $sede_id == $sd['id'] ? 'selected' : '' }}>
                                {{ $sd['nombre_sede'] }} ({{ $sd['codigo_sede'] }})
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold w-100 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </button>
                    <a href="{{ route('reportes.tickets_sede') }}" class="btn btn-outline-secondary btn-sm fw-semibold" title="Restablecer">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>

                <!-- Botones de Atajos Rápidos de Fechas -->
                <div class="col-12 mt-2 pt-2 border-top d-flex gap-2 flex-wrap align-items-center">
                    <span class="small text-muted fw-semibold me-1"><i class="fa-solid fa-bolt text-warning me-1"></i> Atajos:</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('{{ date('Y-m-d') }}', '{{ date('Y-m-d') }}')">Hoy</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('{{ date('Y-m-d', strtotime('-1 day')) }}', '{{ date('Y-m-d', strtotime('-1 day')) }}')">Ayer</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('{{ date('Y-m-d', strtotime('-6 days')) }}', '{{ date('Y-m-d') }}')">Últimos 7 días</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('{{ date('Y-m-01') }}', '{{ date('Y-m-d') }}')">Este Mes</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('{{ date('Y-m-01', strtotime('first day of last month')) }}', '{{ date('Y-m-t', strtotime('last month')) }}')">Mes Anterior</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1" onclick="setRangoFechas('2026-01-01', '{{ date('Y-m-d') }}')">Todo el Año</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS GLOBALES (KPI CARDS) -->
    <div class="row g-3 mb-4">
        <!-- Total Creados -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-left: 4px solid #0d6efd !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Tiquetes Creados</span>
                        <h2 class="display-6 fw-bold text-primary mb-0 mt-1">{{ number_format($globalCreados) }}</h2>
                        <small class="text-muted">En el periodo seleccionado</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-3">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Cerrados (Salida) -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-left: 4px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Tiquetes Cerrados (Salida)</span>
                        <h2 class="display-6 fw-bold text-success mb-0 mt-1">{{ number_format($globalCerrados) }}</h2>
                        <small class="text-success fw-semibold">
                            <i class="fa-solid fa-circle-check me-1"></i> {{ $globalTasaCierre }}% Tasa de Cierre
                        </small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pendientes por Cerrar -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-left: 4px solid {{ $globalPendientes > 0 ? '#ffc107' : '#0dcaf0' }} !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Pendientes por Cerrar</span>
                        <h2 class="display-6 fw-bold {{ $globalPendientes > 0 ? 'text-warning' : 'text-info' }} mb-0 mt-1">
                            {{ number_format($globalPendientes) }}
                        </h2>
                        <small class="text-muted">
                            {{ $globalPendientes > 0 ? 'Requieren registro de salida' : 'Al día / Sin atrasos' }}
                        </small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle fs-3">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promedio SLA Global en Horas y Minutos -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-left: 4px solid #6f42c1 !important;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Promedio SLA Global</span>
                        <h2 class="display-6 fw-bold text-dark mb-0 mt-1" style="color: #6f42c1 !important;">
                            {{ $globalAvgSlaFmt }}
                        </h2>
                        <small class="text-muted">
                            {{ $globalAvgSlaMin !== null ? "{$globalAvgSlaMin} min promedio (Ingreso ➔ Salida)" : 'Sin cierres registrados' }}
                        </small>
                    </div>
                    <div class="bg-purple bg-opacity-10 text-purple p-3 rounded-circle fs-3" style="color: #6f42c1; background-color: rgba(111, 66, 193, 0.1);">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $globalTasaCierre }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONSOLIDADO RESUMIDO POR SEDE (TARJETAS DESGLOSE CON SLA EN HORAS/MINUTOS) -->
    
@if (!empty($consolidadoSedes))

    <div class="card card-glass border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-building me-2 text-primary"></i> Resumen de Operación & SLA por Sede
            </h6>
            <small class="text-muted">Total sedes activas con movimiento: {{ count($consolidadoSedes) }}</small>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                
@foreach ($consolidadoSedes as $cs)

                    @php
$slaFmt = formatear_minutos_horas($cs['avg_minutos_sla']);
                        $slaMin = $cs['avg_minutos_sla'];
                        $slaBadgeColor = ($slaMin !== null && $slaMin <= 30) ? 'bg-success' : (($slaMin !== null && $slaMin <= 60) ? 'bg-warning text-dark' : 'bg-danger text-white');
@endphp

                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="border rounded p-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">{{ $cs['nombre_sede'] }}</h6>
                                        <small class="badge bg-secondary text-white">{{ $cs['codigo_sede'] }}</small>
                                    </div>
                                    <span class="badge {{ $cs['porcentaje_cierre'] >= 80 ? 'bg-success' : ($cs['porcentaje_cierre'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }} fs-6 fw-bold">
                                        {{ $cs['porcentaje_cierre'] }}% Cierre
                                    </span>
                                </div>

                                <div class="row g-2 text-center my-2">
                                    <div class="col-4">
                                        <div class="bg-white border rounded p-1">
                                            <div class="fw-bold text-primary fs-5">{{ $cs['total_creados'] }}</div>
                                            <small class="text-muted" style="font-size: 0.7rem;">Creados</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white border rounded p-1">
                                            <div class="fw-bold text-success fs-5">{{ $cs['total_cerrados'] }}</div>
                                            <small class="text-muted" style="font-size: 0.7rem;">Cerrados</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white border rounded p-1">
                                            <div class="fw-bold text-warning fs-5">{{ $cs['total_pendientes'] }}</div>
                                            <small class="text-muted" style="font-size: 0.7rem;">Pendientes</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloque Destacado de SLA en Horas y Minutos por Sede -->
                                <div class="bg-white border rounded p-2 mt-2 d-flex justify-content-between align-items-center shadow-xs">
                                    <div class="small fw-bold text-secondary">
                                        <i class="fa-solid fa-stopwatch text-primary me-1"></i> SLA Promedio:
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $slaBadgeColor }} fs-6 fw-bold px-2 py-1 shadow-xs">
                                            {{ $slaFmt }}
                                        </span>
                                        
@if ($slaMin !== null)

                                            <div class="text-muted" style="font-size: 0.68rem;">{{ $slaMin }} minutos</div>
                                        
@endif

                                    </div>
                                </div>
                            </div>

                            <div class="progress mt-3" style="height: 5px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $cs['porcentaje_cierre'] }}%"></div>
                            </div>
                        </div>
                    </div>
                
@endforeach

            </div>
        </div>
    </div>
    
@endif


    <!-- SECCIÓN DE GRÁFICOS ANALÍTICOS (CHART.JS) -->
    <div class="row g-3 mb-4">
        <!-- Gráfico 1: Comparativo por Sede (Barras) -->
        <div class="col-lg-7">
            <div class="card card-glass border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-chart-bar text-primary me-2"></i> Comparativo por Sede: Creados vs Cerrados vs Pendientes
                    </h6>
                    <span class="badge bg-light text-dark border">Consolidado</span>
                </div>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="chartSedesBarras"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Evolución Diaria de Tiquetes (Líneas) -->
        <div class="col-lg-5">
            <div class="card card-glass border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-chart-line text-success me-2"></i> Tendencia Diaria de Tiquetes
                    </h6>
                    <span class="badge bg-light text-dark border">Línea de Tiempo</span>
                </div>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="chartEvolucionDiaria"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA MATRIZ DIARIA POR SEDE -->
    <div class="card card-glass border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-table-list text-primary me-2"></i> Matriz Diaria de Tiquetes & SLA por Sede
                </h5>
                <small class="text-muted">Desglose cronológico por fecha y sede con tiempos promedio SLA en horas y minutos</small>
            </div>
        </div>

        <div class="card-body p-3">
            
@if (empty($reporteDiario))

                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-calendar-xmark display-4 mb-3 opacity-50"></i>
                    <h5 class="fw-bold">No se encontraron registros en el rango de fechas seleccionado</h5>
                    <p class="small mb-0">Modifique los filtros superiores para explorar otros periodos.</p>
                </div>
            
@else

                <!-- Barra de Búsqueda y Paginación de la Tabla -->
                <div class="row align-items-center mb-3 g-2 bg-light p-2 rounded border">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary">
                                <i class="fa-solid fa-filter"></i>
                            </span>
                            <input type="text" id="buscarMatrizTickets" class="form-control border-start-0" placeholder="Filtrar por fecha, sede, día de la semana...">
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="badge bg-white text-dark border px-3 py-2 fw-semibold" id="badgeContadorMatriz">
                            Mostrando 0 de {{ count($reporteDiario) }} registros diarios
                        </span>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="d-inline-flex align-items-center gap-1">
                            <label for="selectLimitMatriz" class="small text-muted fw-semibold mb-0">Ver:</label>
                            <select id="selectLimitMatriz" class="form-select form-select-sm shadow-sm" style="width: auto;">
                                <option value="25">25 por pág.</option>
                                <option value="50" selected>50 por pág. (Recomendado)</option>
                                <option value="100">100 por pág.</option>
                                <option value="all">Todos ({{ count($reporteDiario) }})</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaMatrizTickets">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th>Fecha</th>
                                <th>Día / Tipo</th>
                                <th>Sede</th>
                                <th class="text-center">Total Creados</th>
                                <th class="text-center">Cerrados (Salida)</th>
                                <th class="text-center">Pendientes</th>
                                <th class="text-center">Cancelados</th>
                                <th class="text-center">% Conclusión</th>
                                <th class="text-center"><i class="fa-solid fa-stopwatch me-1 text-primary"></i> Promedio SLA (Horas : Min)</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            
@foreach ($reporteDiario as $row)

                                @php
$diasEs = [
                                        'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
                                        'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
                                    ];
                                    $diaIngles = date('l', strtotime($row['fecha']));
                                    $diaNombre = $diasEs[$diaIngles] ?? $diaIngles;
                                    $pct = $row['porcentaje_cierre'];
                                    $colorPct = $pct >= 90 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger');
                                    
                                    $slaMinDia = $row['avg_minutos_sla'];
                                    $slaFmtDia = formatear_minutos_horas($slaMinDia);
                                    $slaDiaColor = ($slaMinDia !== null && $slaMinDia <= 30) ? 'text-success' : (($slaMinDia !== null && $slaMinDia <= 60) ? 'text-warning' : 'text-danger');
@endphp

                                <tr class="fila-matriz">
                                    <td class="fw-bold text-dark">
                                        <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                        {{ date('d/m/Y', strtotime($row['fecha'])) }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-secondary">{{ $diaNombre }}</span>
                                        
@if (!empty($row['es_festivo_o_finde']))

                                            <span class="badge bg-danger text-white ms-1" style="font-size: 0.68rem;">{{ $row['tipo_dia'] }}</span>
                                        
@else

                                            <span class="badge bg-light text-muted border ms-1" style="font-size: 0.68rem;">HÁBIL</span>
                                        
@endif

                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $row['nombre_sede'] }}</span>
                                        <small class="badge bg-light text-muted border ms-1">{{ $row['codigo_sede'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-3 py-1 fw-bold shadow-sm">
                                            {{ $row['total_creados'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success fs-6 px-3 py-1 fw-bold shadow-sm">
                                            {{ $row['total_cerrados'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        
@if ($row['total_pendientes'] > 0)

                                            <span class="badge bg-warning text-dark fs-6 px-3 py-1 fw-bold animate-pulse shadow-sm">
                                                {{ $row['total_pendientes'] }}
                                            </span>
                                        
@else

                                            <span class="badge bg-light text-muted border px-2 py-1">0</span>
                                        
@endif

                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border px-2 py-1">{{ $row['total_cancelados'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold {{ $colorPct }}">{{ $pct }}%</span>
                                        <div class="progress mt-1 mx-auto" style="height: 4px; max-width: 80px;">
                                            <div class="progress-bar {{ $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        
@if ($slaMinDia !== null)

                                            <div class="fw-bold fs-6 {{ $slaDiaColor }}">{{ $slaFmtDia }}</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">{{ $slaMinDia }} min</small>
                                        
@else

                                            <span class="text-muted small">---</span>
                                        
@endif

                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary fw-bold shadow-sm"
                                                onclick="verDetalleDiaSede('{{ $row['fecha'] }}', '{{ $row['sede_id'] }}', '{{ $row['nombre_sede'] }}')">
                                            <i class="fa-solid fa-list-check me-1"></i> Ver Tiquetes
                                        </button>
                                    </td>
                                </tr>
                            
@endforeach

                        </tbody>
                    </table>
                </div>

                <!-- Paginación de la Matriz -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top" id="footerPaginacionMatriz">
                    <div class="small text-muted fw-semibold" id="infoPaginacionMatriz"></div>
                    <nav aria-label="Paginación Matriz">
                        <ul class="pagination pagination-sm mb-0" id="ulPaginacionMatriz"></ul>
                    </nav>
                </div>
            
@endif

        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL DE DESGLOSE DE TIQUETES POR DÍA Y SEDE                             -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetalleTickets" tabindex="-1" aria-labelledby="modalDetalleTicketsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check fs-4 text-info"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalDetalleTicketsLabel">Detalle de Tiquetes de la Jornada</h5>
                        <small class="text-white-50" id="modalSubtituloDetalle">Cargando información...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <div id="loadingModalTickets" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="small text-muted mb-0">Consultando tiquetes de la jornada...</p>
                </div>

                <div id="contenidoModalTickets" style="display: none;">
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover align-middle mb-0" id="tablaModalDetalle">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Tiquete</th>
                                    <th>Prioridad</th>
                                    <th>Paciente</th>
                                    <th>Documento</th>
                                    <th>Hora Ingreso</th>
                                    <th>Hora Salida</th>
                                    <th>Tiempo SLA (Horas : Min)</th>
                                    <th>Estado</th>
                                    <th>Orientador / Salida</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyModalDetalle">
                                <!-- Filas dinámicas generadas por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-secondary btn-sm fw-bold px-4" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPTS: CHART.JS & PAGINACIÓN REACTIVA -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function formatearMinutosJs(minutos) {
    if (minutos === null || minutos === undefined || isNaN(minutos)) return '---';
    const totalMin = Math.round(Number(minutos) * 10) / 10;
    if (totalMin <= 0) return '0 min';
    let horas = Math.floor(totalMin / 60);
    let mins = Math.round(totalMin % 60);
    if (mins === 60) {
        horas += 1;
        mins = 0;
    }
    if (horas > 0) {
        return `${horas}h ${mins < 10 ? '0' + mins : mins}m (${totalMin} min)`;
    }
    return `${mins} min`;
}

function setRangoFechas(desde, hasta) {
    document.getElementById('input_fecha_desde').value = desde;
    document.getElementById('input_fecha_hasta').value = hasta;
    document.getElementById('formFiltroTickets').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar Gráfico de Barras por Sede
    const ctxBarras = document.getElementById('chartSedesBarras')?.getContext('2d');
    if (ctxBarras) {
        new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: {!! json_encode($labelsSedes) !!},
                datasets: [
                    {
                        label: 'Creados',
                        data: {!! json_encode($dataCreadosSedes) !!},
                        backgroundColor: 'rgba(13, 110, 253, 0.85)',
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Cerrados (Salida)',
                        data: {!! json_encode($dataCerradosSedes) !!},
                        backgroundColor: 'rgba(25, 135, 84, 0.85)',
                        borderColor: '#198754',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Pendientes',
                        data: {!! json_encode($dataPendientesSedes) !!},
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Inicializar Gráfico de Línea de Evolución Diaria
    const ctxLine = document.getElementById('chartEvolucionDiaria')?.getContext('2d');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartFechasLabels) !!},
                datasets: [
                    {
                        label: 'Creados',
                        data: {!! json_encode($chartFechasCreados) !!},
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: 'Cerrados',
                        data: {!! json_encode($chartFechasCerrados) !!},
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 3. Inicializar Paginación y Búsqueda en la Matriz
    setupTablaPaginacion({
        tableId: 'tablaMatrizTickets',
        rowSelector: '.fila-matriz',
        searchInputId: 'buscarMatrizTickets',
        selectLimitId: 'selectLimitMatriz',
        badgeId: 'badgeContadorMatriz',
        infoId: 'infoPaginacionMatriz',
        ulPaginationId: 'ulPaginacionMatriz',
        unitLabel: 'registros diarios'
    });
});

// Paginación Reutilizable
function setupTablaPaginacion(config) {
    const tableEl = document.getElementById(config.tableId);
    if (!tableEl) return;
    const tbody = tableEl.querySelector('tbody');
    if (!tbody) return;
    const allRows = Array.from(tbody.querySelectorAll(config.rowSelector));
    if (allRows.length === 0) return;

    let currentPage = 1;
    const selectLimit = document.getElementById(config.selectLimitId);
    let pageSize = selectLimit ? (selectLimit.value === 'all' ? 'all' : (parseInt(selectLimit.value, 10) || 50)) : 50;
    let filteredRows = [...allRows];

    function applyFilterAndPagination() {
        const searchInput = document.getElementById(config.searchInputId);
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        
        filteredRows = allRows.filter(row => {
            if (!query) return true;
            return row.innerText.toLowerCase().includes(query);
        });

        const totalItems = filteredRows.length;
        const effectivePageSize = pageSize === 'all' ? (totalItems || 1) : pageSize;
        const totalPages = pageSize === 'all' ? 1 : Math.max(1, Math.ceil(totalItems / effectivePageSize));
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = pageSize === 'all' ? 0 : (currentPage - 1) * effectivePageSize;
        const endIndex = pageSize === 'all' ? totalItems : Math.min(startIndex + effectivePageSize, totalItems);

        allRows.forEach(row => row.style.display = 'none');
        for (let i = startIndex; i < endIndex; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }

        const badge = document.getElementById(config.badgeId);
        if (badge) {
            badge.innerText = `Mostrando ${totalItems === 0 ? 0 : (startIndex + 1)} - ${endIndex} de ${totalItems} ${config.unitLabel}`;
        }
        const infoEl = document.getElementById(config.infoId);
        if (infoEl) {
            infoEl.innerText = `Página ${currentPage} de ${totalPages} (${totalItems} registros encontrados)`;
        }

        renderPaginationButtons(config.ulPaginationId, totalPages, currentPage, (newPage) => {
            currentPage = newPage;
            applyFilterAndPagination();
        });
    }

    document.getElementById(config.searchInputId)?.addEventListener('input', () => {
        currentPage = 1;
        applyFilterAndPagination();
    });

    document.getElementById(config.selectLimitId)?.addEventListener('change', (e) => {
        const val = e.target.value;
        pageSize = val === 'all' ? 'all' : parseInt(val, 10);
        currentPage = 1;
        applyFilterAndPagination();
    });

    applyFilterAndPagination();
}

function renderPaginationButtons(ulId, totalPages, currentPage, onPageChange) {
    const ul = document.getElementById(ulId);
    if (!ul) return;
    ul.innerHTML = '';
    if (totalPages <= 1) return;

    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<a class="page-link" href="javascript:void(0)"><i class="fa-solid fa-chevron-left"></i></a>`;
    if (currentPage > 1) {
        liPrev.addEventListener('click', () => onPageChange(currentPage - 1));
    }
    ul.appendChild(liPrev);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    if (startPage > 1) {
        const li1 = document.createElement('li');
        li1.className = 'page-item';
        li1.innerHTML = `<a class="page-link" href="javascript:void(0)">1</a>`;
        li1.addEventListener('click', () => onPageChange(1));
        ul.appendChild(li1);
        if (startPage > 2) {
            const liDots = document.createElement('li');
            liDots.className = 'page-item disabled';
            liDots.innerHTML = `<span class="page-link">...</span>`;
            ul.appendChild(liDots);
        }
    }

    for (let p = startPage; p <= endPage; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="javascript:void(0)">${p}</a>`;
        const pageNum = p;
        li.addEventListener('click', () => onPageChange(pageNum));
        ul.appendChild(li);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const liDots2 = document.createElement('li');
            liDots2.className = 'page-item disabled';
            liDots2.innerHTML = `<span class="page-link">...</span>`;
            ul.appendChild(liDots2);
        }
        const liLast = document.createElement('li');
        liLast.className = 'page-item';
        liLast.innerHTML = `<a class="page-link" href="javascript:void(0)">${totalPages}</a>`;
        liLast.addEventListener('click', () => onPageChange(totalPages));
        ul.appendChild(liLast);
    }

    const liNext = document.createElement('li');
    liNext.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    liNext.innerHTML = `<a class="page-link" href="javascript:void(0)"><i class="fa-solid fa-chevron-right"></i></a>`;
    if (currentPage < totalPages) {
        liNext.addEventListener('click', () => onPageChange(currentPage + 1));
    }
    ul.appendChild(liNext);
}

// Modal para ver tiquetes de un día y sede
function verDetalleDiaSede(fecha, sedeId, nombreSede) {
    const modalEl = document.getElementById('modalDetalleTickets');
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById('modalSubtituloDetalle').innerText = `${nombreSede} | Fecha: ${fecha}`;
    document.getElementById('loadingModalTickets').style.display = 'block';
    document.getElementById('contenidoModalTickets').style.display = 'none';
    
    modal.show();

    // Consulta AJAX para obtener el listado específico
    fetch(`{{ route('reportes.tickets_sede') }}?ajax_detalle=1&fecha=${encodeURIComponent(fecha)}&sede_id=${encodeURIComponent(sedeId)}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('loadingModalTickets').style.display = 'none';
            document.getElementById('contenidoModalTickets').style.display = 'block';
            
            const tbody = document.getElementById('tbodyModalDetalle');
            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">No se encontraron tiquetes para esta jornada.</td></tr>`;
                return;
            }

            data.forEach(tkt => {
                const tr = document.createElement('tr');
                const badgeClass = tkt.esta_cerrado ? 'bg-success' : 'bg-warning text-dark';
                const estadoTexto = tkt.esta_cerrado ? 'Cerrado / Entregado' : 'Pendiente';
                const slaFormatted = formatearMinutosJs(tkt.minutos_sla);
                const colorSla = (tkt.minutos_sla <= 30) ? 'text-success' : ((tkt.minutos_sla <= 60) ? 'text-warning' : 'text-danger');
                
                tr.innerHTML = `
                    <td><span class="badge bg-light text-primary border border-primary fw-bold">${tkt.ticket_numero || '---'}</span></td>
                    <td><span class="badge bg-secondary">${tkt.prioridad || 'NORMAL'}</span></td>
                    <td><div class="fw-bold">${(tkt.nombres || '') + ' ' + (tkt.apellidos || '')}</div></td>
                    <td><span class="text-muted">${(tkt.tipo_documento || '')} ${tkt.numero_documento || ''}</span></td>
                    <td class="small">${tkt.fecha_ingreso ? tkt.fecha_ingreso.substring(11, 16) : '--:--'}</td>
                    <td class="small fw-semibold ${tkt.fecha_salida ? 'text-success' : 'text-muted'}">${tkt.fecha_salida ? tkt.fecha_salida.substring(11, 16) : 'Pendiente'}</td>
                    <td><span class="fw-bold fs-6 ${colorSla}">${slaFormatted}</span></td>
                    <td><span class="badge ${badgeClass}">${estadoTexto}</span></td>
                    <td class="small text-muted">${tkt.usuario_salida_nombre || tkt.orientador_nombre || 'Sistema'}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            document.getElementById('loadingModalTickets').innerHTML = `<div class="text-danger p-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error al cargar tiquetes: ${err.message}</div>`;
        });
}
</script>
@endsection
