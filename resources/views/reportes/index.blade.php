@extends('layouts.app')

@section('titulo', 'Reportes & SLA - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-chart-pie me-2"></i> Módulo de Reportes, Analítica & Tiempos de Atención (SLA)</h4>
        <p class="text-muted small">Generación de informes de gestión, análisis de cuellos de botella en atención y exportación a Excel.</p>
    </div>
</div>

<!-- Selector de Pestañas de Reporte -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'pacientes' ? 'active bg-primary' : 'bg-white border text-dark' }}" href="{{ route('reportes.index', ['tab' => 'pacientes']) }}">
            <i class="fa-solid fa-users me-1"></i> Reporte de Pacientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'tiempos' ? 'active bg-primary' : 'bg-white border text-dark' }}" href="{{ route('reportes.index', ['tab' => 'tiempos']) }}">
            <i class="fa-solid fa-stopwatch me-1"></i> Análisis de Tiempos (SLA)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'pendientes' ? 'active bg-warning text-dark' : 'bg-white border text-dark' }}" href="{{ route('reportes.index', ['tab' => 'pendientes']) }}">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Medicamentos Faltantes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'productividad' ? 'active bg-success' : 'bg-white border text-dark' }}" href="{{ route('reportes.index', ['tab' => 'productividad']) }}">
            <i class="fa-solid fa-user-check me-1"></i> Productividad Usuarios
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold {{ $tab === 'eps' ? 'active bg-info text-dark' : 'bg-white border text-dark' }}" href="{{ route('reportes.index', ['tab' => 'eps']) }}">
            <i class="fa-solid fa-hospital-user me-1"></i> Distribución por EPS
        </a>
    </li>
</ul>

<!-- Filtros Globales de Fecha -->
<div class="card card-glass p-3 mb-4 border-0 shadow-sm">
    <form method="GET" action="{{ route('reportes.index') }}" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="col-md-3">
            <label class="form-label small fw-semibold">Fecha Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="{{ $fecha_desde }}">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold">Fecha Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="{{ $fecha_hasta }}">
        </div>

        @if ($tab === 'pacientes')
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Filtrar por EPS</label>
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
                <i class="fa-solid fa-filter me-1"></i> Aplicar Filtros
            </button>
            @if (in_array($tab, ['pacientes', 'tiempos', 'pendientes']))
            <a href="{{ route('reportes.index', ['tab' => $tab, 'fecha_desde' => $fecha_desde, 'fecha_hasta' => $fecha_hasta, 'eps' => $eps_filtro, 'export' => 'csv']) }}" class="btn btn-success fw-bold" title="Exportar datos directamente a formato Microsoft Excel">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </a>
            @endif
        </div>
    </form>
</div>

<!-- CONTENIDO DE REPORTES POR PESTAÑA -->

@if ($tab === 'pacientes')
    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users me-2 text-primary"></i> Pacientes Atendidos en el Periodo</h5>
            <span class="badge bg-primary fs-6">{{ count($listaPacientes) }} Registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Paciente</th>
                            <th>Identificación</th>
                            <th>EPS</th>
                            <th>Fecha Ingreso</th>
                            <th>Estado Actual</th>
                            <th>Orientador</th>
                            <th class="text-end pe-3">Acciones / Reimpresión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listaPacientes as $r)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">{{ $r->ticket_numero }}</td>
                            <td class="fw-bold">{{ $r->nombres . ' ' . $r->apellidos }}</td>
                            <td>{{ $r->tipo_documento . ' ' . $r->numero_documento }}</td>
                            <td><span class="badge bg-info text-dark">{{ $r->eps_nombre }}</span></td>
                            <td>{{ date('d/m/Y h:i A', strtotime($r->fecha_ingreso)) }}</td>
                            <td>{!! get_estado_badge($r->estado_tramite) !!}</td>
                            <td>{{ $r->orientador_nombre }}</td>
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('ingreso.ticket', $r->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Reimprimir Ticket">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    @if ($r->estado_tramite === 'ENTREGADO' || !empty($r->firma_paciente_url))
                                        <a href="{{ route('entrega.acta', $r->id) }}" target="_blank" class="btn btn-sm btn-success fw-bold" title="Reimprimir Acta de Entrega Firmada">
                                            <i class="fa-solid fa-file-signature me-1"></i> Reimprimir Acta
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif ($tab === 'tiempos')
    @php
    $total_minutos_totales = 0;
    $total_minutos_farmacia = 0;
    $total_minutos_fila = 0;
    $count_atendidos = count($listaTiempos);

    foreach ($listaTiempos as $t) { 
        $total_minutos_totales += floatval($t->tiempo_total_minutos ?? 0); 
        $total_minutos_farmacia += floatval($t->tiempo_tramite_farmacia_min ?? 0); 
        $total_minutos_fila += floatval($t->tiempo_fila_externa_min ?? 0); 
    }

    $promedio_total = $count_atendidos > 0 ? round($total_minutos_totales / $count_atendidos, 1) : 0;
    $promedio_farmacia = $count_atendidos > 0 ? round($total_minutos_farmacia / $count_atendidos, 1) : 0;
    $promedio_fila = $count_atendidos > 0 ? round($total_minutos_fila / $count_atendidos, 1) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-primary p-3 shadow-sm">
                <div class="text-muted small fw-semibold">TOTAL ATENCIONES EN PERIODO</div>
                <div class="fs-2 fw-bold text-primary">{{ $count_atendidos }} Pacientes</div>
                <div class="small text-muted">Procesados exitosamente</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-warning p-3 shadow-sm">
                <div class="text-muted small fw-semibold"><i class="fa-solid fa-clock me-1"></i> PROMEDIO FILA EXTERIOR (PREVIA)</div>
                <div class="fs-2 fw-bold text-warning">{{ $promedio_fila }} Minutos</div>
                <div class="small text-muted">Espera en exterior antes de apertura</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-success p-3 shadow-sm">
                <div class="text-muted small fw-semibold"><i class="fa-solid fa-stopwatch me-1"></i> PROMEDIO TRÁMITE FARMACIA (SLA)</div>
                <div class="fs-2 fw-bold text-success">{{ $promedio_farmacia }} Minutos</div>
                <div class="small text-muted">Contado desde Apertura Oficial u Hora Ingreso</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-glass border-start border-4 border-info p-3 shadow-sm">
                <div class="text-muted small fw-semibold">CUMPLE OBJETIVO SLA (< 30 MIN)</div>
                <div class="fs-2 fw-bold text-info">
                    @php
                        $cumplen = count(array_filter($listaTiempos, fn ($x) => floatval($x->tiempo_tramite_farmacia_min ?? 0) <= 30));
                        $porcentaje = $count_atendidos > 0 ? round(($cumplen / $count_atendidos) * 100) : 0;
                    @endphp
                    {{ $porcentaje }}%
                </div>
                <div class="small text-muted">Evaluado sobre tiempo farmacéutico</div>
            </div>
        </div>
    </div>

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-stopwatch me-2 text-primary"></i> Análisis Detallado de Tiempos de Atención por Paciente</h5>
            <span class="badge bg-light text-dark border">
                <i class="fa-solid fa-info-circle me-1"></i> Normalizado por Hora de Apertura Sede/Empresa
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Paciente / EPS</th>
                            <th>Hora Registro Fila</th>
                            <th>Hora Apertura / Inicio SLA</th>
                            <th>Hora Finalización</th>
                            <th>⌛ Fila Exterior</th>
                            <th>⏱️ Trámite Farmacia (SLA)</th>
                            <th>Evaluación SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listaTiempos as $t)
                        @php
                            $minSla = floatval($t->tiempo_tramite_farmacia_min ?? 0);
                            $minFila = floatval($t->tiempo_fila_externa_min ?? 0);
                            $esTemprano = !empty($t->ingresado_antes_apertura);
                            $horaAperturaFormatted = date('h:i A', strtotime($t->hora_apertura_oficial ?? '07:20:00'));
                        @endphp
                        <tr>
                            <td class="ps-3 fw-bold text-primary fs-6">{{ $t->ticket_numero }}</td>
                            <td>
                                <div class="fw-bold">{{ $t->nombres . ' ' . $t->apellidos }}</div>
                                <span class="badge bg-info text-dark small">{{ $t->eps_nombre }}</span>
                            </td>
                            <td>
                                <div>{{ date('d/m/Y h:i A', strtotime($t->fecha_ingreso)) }}</div>
                                @if ($esTemprano)
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-sun me-1"></i> Llegada Temprana</span>
                                @endif
                            </td>
                            <td>
                                @if ($esTemprano)
                                    <span class="fw-bold text-primary"><i class="fa-solid fa-door-open me-1"></i> {{ $horaAperturaFormatted }}</span>
                                    <div class="small text-muted">(Hora Apertura Sede)</div>
                                @else
                                    <div>{{ date('h:i A', strtotime($t->fecha_ingreso)) }}</div>
                                @endif
                            </td>
                            <td>{{ date('d/m/Y h:i A', strtotime($t->fecha_finalizacion)) }}</td>
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
                                    <span class="badge bg-success p-2"><i class="fa-solid fa-bolt me-1"></i> Excelente (<= 15 min)</span>
                                @elseif ($minSla <= 30)
                                    <span class="badge bg-primary p-2"><i class="fa-solid fa-circle-check me-1"></i> Cumple (<= 30 min)</span>
                                @elseif ($minSla <= 45)
                                    <span class="badge bg-warning text-dark p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tolerable (<= 45 min)</span>
                                @else
                                    <span class="badge bg-danger p-2"><i class="fa-solid fa-circle-xmark me-1"></i> Fuera de SLA (> 45 min)</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif ($tab === 'pendientes')
    <div class="card card-glass border-0 shadow-sm border-start border-4 border-warning">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i> Reporte de Medicamentos Sin Stock o Con Pendientes</h5>
            <span class="badge bg-warning text-dark fs-6">{{ count($listaPendientes) }} Casos Registrados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Paciente</th>
                            <th>EPS</th>
                            <th>Fecha Ingreso</th>
                            <th>Estado registrado</th>
                            <th>Detalle de Medicamentos Faltantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($listaPendientes))
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-circle-info fs-4 me-2 text-warning"></i> No se encontraron medicamentos pendientes o sin stock en el rango de fechas seleccionado ({{ $fecha_desde }} a {{ $fecha_hasta }}).
                                    <div class="mt-2">
                                        <a href="{{ route('reportes.index', ['tab' => 'pendientes']) }}" class="btn btn-sm btn-outline-warning text-dark fw-bold">
                                            <i class="fa-solid fa-list me-1"></i> Ver Histórico Completo de Pendientes (Sin filtro de fechas)
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        @foreach ($listaPendientes as $p)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">{{ $p->ticket_numero }}</td>
                            <td class="fw-bold">{{ $p->nombres . ' ' . $p->apellidos }}</td>
                            <td><span class="badge bg-info text-dark">{{ $p->eps_nombre }}</span></td>
                            <td>{{ date('d/m/Y h:i A', strtotime($p->fecha_ingreso)) }}</td>
                            <td>{!! get_estado_badge($p->estado_tramite) !!}</td>
                            <td class="text-danger fw-semibold">{{ $p->observaciones_pendientes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif ($tab === 'productividad')
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
                            <th>Perfil / Rol</th>
                            <th class="text-end pe-3">Total de Ingresos / Atenciones Registradas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listaProd as $pr)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $pr->nombre_completo }}</td>
                            <td><span class="badge bg-primary">{{ $pr->rol }}</span></td>
                            <td class="text-end pe-3 fw-bold fs-5 text-success">{{ $pr->total_ingresos }} Atenciones</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif ($tab === 'eps')
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
                        @foreach ($listaEPS as $ep)
                        <tr>
                            <td class="ps-3 fw-bold text-dark"><i class="fa-solid fa-notes-medical me-2 text-info"></i> {{ $ep->eps_nombre }}</td>
                            <td class="text-end pe-3 fw-bold fs-5 text-primary">{{ $ep->total_pacientes }} Pacientes</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif


@endsection
