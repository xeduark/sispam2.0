@extends('layouts.app')

@section('titulo', 'Módulo de Salida & Cierre - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-2">

    <!-- ENCABEZADO PRINCIPAL -->
    <div class="row align-items-center mb-3 g-2">
        <div class="col-md-7">
            <h4 class="fw-bold text-primary mb-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-door-open text-success fs-3"></i>
                <span>Módulo de Salida & Cierre de Tiquetes</span>
            </h4>
            <p class="text-muted small mb-0">
                Registro ágil de salida del paciente por número de cédula o tiquete del día actual. Almacena fecha/hora de conclusión y alimenta el reporte SLA.
            </p>
        </div>

        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end align-items-center flex-wrap">
            <form method="GET" action="{{ route('salida.index') }}" class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-semibold text-nowrap"><i class="fa-solid fa-location-dot text-danger me-1"></i> Sede:</label>
                <select name="sede_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()" style="max-width: 220px;">
                    <option value="">-- Todas las Sedes --</option>
                    
@foreach ($sedes_disponibles as $sd)

                        <option value="{{ $sd['id'] }}" {{ $sede_filtro == $sd['id'] ? 'selected' : '' }}>
                            {{ $sd['nombre_sede'] }}
                        </option>
                    
@endforeach

                </select>
            </form>
            <a href="{{ route('reportes.index') }}?tab=salidas" class="btn btn-sm btn-outline-success fw-bold shadow-sm" target="_blank" title="Ver historial de tiquetes cerrados y analítica SLA">
                <i class="fa-solid fa-chart-line me-1"></i> Ver Salidas & SLA
            </a>
        </div>
    </div>

    <!-- ALERTAS DE MENSAJE O ERROR -->
    
@if ($mensaje)

        <div class="alert alert-success alert-dismissible fade show shadow-sm fw-semibold small mb-3">
            <i class="fa-solid fa-circle-check me-2 fs-5 align-middle"></i> {!! $mensaje !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if ($infoMsg)

        <div class="alert alert-info alert-dismissible fade show shadow-sm fw-semibold small mb-3">
            <i class="fa-solid fa-circle-info me-2 fs-5 align-middle"></i> {!! $infoMsg !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if ($error)

        <div class="alert alert-danger alert-dismissible fade show shadow-sm fw-semibold small mb-3">
            <i class="fa-solid fa-triangle-exclamation me-2 fs-5 align-middle"></i> {!! $error !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    <!-- TARJETA PRINCIPAL DE BÚSQUEDA RÁPIDA (HERO SEARCH) -->
    <div class="card card-glass border-0 shadow mb-4 p-3 p-md-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%); border-left: 5px solid #0d6efd !important;">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <label for="inputBuscarSalida" class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="fa-solid fa-id-card text-primary me-2"></i> Digite el Número de Cédula del Paciente o Código de Tiquete:
                </label>
                <form method="GET" action="{{ route('salida.index') }}" id="formBusquedaSalida" class="input-group input-group-lg shadow-sm">
                    
@if ($sede_filtro)

                        <input type="hidden" name="sede_id" value="{{ $sede_filtro }}">
                    
@endif

                    
                    <span class="input-group-text bg-white border-end-0 text-primary">
                        <i class="fa-solid fa-barcode fs-4"></i>
                    </span>
                    <input type="text" 
                           name="buscar" 
                           id="inputBuscarSalida" 
                           class="form-control border-start-0 fs-5 fw-bold" 
                           placeholder="Ej: 1036780004 o TK-260901-0001" 
                           value="{{ $criterioBusqueda }}" 
                           autocomplete="off" 
                           autofocus>
                    <button class="btn btn-primary px-4 fw-bold" type="submit" id="btnBuscarSalida">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> Buscar & Cerrar
                    </button>
                </form>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="switchModoPistola" onchange="toggleModoPistola(this)" style="cursor: pointer; transform: scale(1.15); margin-right: 6px;">
                        <label class="form-check-label fw-bold text-dark small" for="switchModoPistola" style="cursor: pointer;">
                            <i class="fa-solid fa-barcode text-primary me-1"></i> Modo Pistola Lectora (Cierre Express en 1 Escaneo)
                        </label>
                    </div>
                    <span class="badge bg-light text-muted border small" id="badgeModoStatus">
                        <i class="fa-solid fa-hand me-1"></i> Modo Manual / Confirmación Activo
                    </span>
                </div>
                <div id="bannerSalidaExpress" style="display: none;" class="mt-2"></div>
                <small class="text-muted mt-1 d-block">
                    <i class="fa-solid fa-lightbulb text-warning me-1"></i> Con la pistola lectora o digitando el tiquete, presione <kbd>Enter</kbd> para procesar. Si activa el <em>Modo Pistola</em>, se cerrará de inmediato sin necesidad de abrir modales.
                </small>
            </div>

            <!-- Resumen de Métricas Rápidas -->
            <div class="col-lg-4">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="bg-white border rounded p-2 shadow-sm">
                            <div class="text-warning display-6 fw-bold mb-0">{{ count($pendientesSalida) }}</div>
                            <div class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">En Proceso / Espera</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white border rounded p-2 shadow-sm">
                            <div class="text-success display-6 fw-bold mb-0">{{ count($ultimasSalidas) }}</div>
                            <div class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">Cerrados Hoy</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE TABLAS Y GESTIÓN -->
    <div class="card card-glass border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <ul class="nav nav-tabs card-header-tabs" id="salidaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark" id="tab-pendientes" data-bs-toggle="tab" data-bs-target="#panel-pendientes" type="button" role="tab">
                        <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Tiquetes en Curso ({{ count($pendientesSalida) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="tab-cerrados" data-bs-toggle="tab" data-bs-target="#panel-cerrados" type="button" role="tab">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> Salidas Concluidas ({{ count($ultimasSalidas) }})
                    </button>
                </li>
            </ul>

            <span class="small text-muted">
                <i class="fa-solid fa-bolt text-warning me-1"></i> Actualización automática en tiempo real
            </span>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="salidaTabsContent">
                
                <!-- PESTAÑA 1: TIQUETES PENDIENTES DE SALIDA -->
                <div class="tab-pane fade show active p-3" id="panel-pendientes" role="tabpanel">
                    
@if (empty($pendientesSalida))

                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-circle-check display-4 text-success mb-3 opacity-50"></i>
                            <h5 class="fw-bold">No hay tiquetes pendientes de salida en este momento</h5>
                            <p class="small mb-0">Todos los pacientes ingresados han completado su atención o no hay órdenes activas.</p>
                        </div>
                    
@else

                        <!-- Barra de Controles y Filtro Rápido de Pendientes -->
                        <div class="row align-items-center mb-3 g-2 bg-light p-2 rounded border">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-primary">
                                        <i class="fa-solid fa-filter"></i>
                                    </span>
                                    <input type="text" 
                                           id="buscarTablaPendientes" 
                                           class="form-control border-start-0" 
                                           placeholder="Filtrar por cédula, nombre, tiquete, EPS o sede...">
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <span class="badge bg-white text-dark border px-3 py-2 fw-semibold" id="badgeContadorPendientes">
                                    Mostrando 0 de {{ count($pendientesSalida) }} tiquetes en espera
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <label for="selectLimitPendientes" class="small text-muted fw-semibold mb-0">Ver:</label>
                                    <select id="selectLimitPendientes" class="form-select form-select-sm shadow-sm" style="width: auto;">
                                        <option value="25">25 por pág.</option>
                                        <option value="50" selected>50 por pág. (Recomendado)</option>
                                        <option value="100">100 por pág.</option>
                                        <option value="all">Todos ({{ count($pendientesSalida) }})</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaPendientesSalida">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th>Tiquete</th>
                                        <th>Prioridad</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>EPS</th>
                                        <th>Hora Ingreso</th>
                                        <th>Tiempo en Sala (SLA)</th>
                                        <th>Sede</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    
@foreach ($pendientesSalida as $item)

                                        @php
$min = $item['minutos_transcurridos'] ?? 0;
                                            $slaBadgeClass = 'bg-success text-white';
                                            if ($min > 45) {
                                                $slaBadgeClass = 'bg-danger text-white animate-pulse';
                                            } else if ($min >= 20) {
                                                $slaBadgeClass = 'bg-warning text-dark';
                                            }
@endphp

                                        <tr class="fila-pendiente">
                                            <td>
                                                <span class="badge bg-light text-primary border border-primary fs-6 fw-bold px-2 py-1">
                                                    {{ $item['ticket_numero'] }}
                                                </span>
                                            </td>
                                            <td>
                                                {!! get_prioridad_badge($item['prioridad'] ?? 'NORMAL') ?: '<span class="badge bg-secondary">Normal</span>' !!}
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $item['nombres'] . ' ' . $item['apellidos'] }}</div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-secondary">{{ $item['tipo_documento'] . ' ' . $item['numero_documento'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $item['eps_nombre'] ?? 'Particular' }}</span>
                                            </td>
                                            <td class="small text-muted">
                                                <i class="fa-regular fa-clock me-1"></i>
                                                {{ date('h:i A', strtotime($item['fecha_ingreso'])) }}
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ date('d/m/Y', strtotime($item['fecha_ingreso'])) }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $slaBadgeClass }} px-2 py-1 fs-6 fw-bold shadow-sm">
                                                    <i class="fa-solid fa-stopwatch me-1"></i> {{ $min }} min
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ $item['nombre_sede'] ?? 'Principal' }}
                                            </td>
                                            <td class="text-center">
                                                <button type="button" 
                                                        class="btn btn-sm btn-success fw-bold shadow-sm px-3"
                                                        onclick="abrirModalCierreDirecto({{ json_encode($item) }})">
                                                    <i class="fa-solid fa-door-open me-1"></i> Cerrar Salida
                                                </button>
                                            </td>
                                        </tr>
                                    
@endforeach

                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación de Pendientes -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top" id="footerPaginacionPendientes">
                            <div class="small text-muted fw-semibold" id="infoPaginacionPendientes"></div>
                            <nav aria-label="Paginación Pendientes">
                                <ul class="pagination pagination-sm mb-0" id="ulPaginacionPendientes"></ul>
                            </nav>
                        </div>
                    
@endif

                </div>

                <!-- PESTAÑA 2: HISTORIAL DE SALIDAS CERRADAS HOY -->
                <div class="tab-pane fade p-3" id="panel-cerrados" role="tabpanel">
                    
@if (empty($ultimasSalidas))

                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox display-4 text-muted mb-3 opacity-50"></i>
                            <h5 class="fw-bold">Aún no se han registrado salidas en esta jornada</h5>
                            <p class="small mb-0">Los tiquetes que vaya cerrando aparecerán listados aquí con su tiempo total SLA.</p>
                        </div>
                    
@else

                        <!-- Barra de Controles y Filtro Rápido de Salidas Cerradas -->
                        <div class="row align-items-center mb-3 g-2 bg-light p-2 rounded border">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-success">
                                        <i class="fa-solid fa-filter"></i>
                                    </span>
                                    <input type="text" 
                                           id="buscarTablaCerrados" 
                                           class="form-control border-start-0" 
                                           placeholder="Filtrar por cédula, nombre, tiquete, responsable o sede...">
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <span class="badge bg-white text-dark border px-3 py-2 fw-semibold" id="badgeContadorCerrados">
                                    Mostrando 0 de {{ count($ultimasSalidas) }} salidas concluidas
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <label for="selectLimitCerrados" class="small text-muted fw-semibold mb-0">Ver:</label>
                                    <select id="selectLimitCerrados" class="form-select form-select-sm shadow-sm" style="width: auto;">
                                        <option value="25">25 por pág.</option>
                                        <option value="50" selected>50 por pág. (Recomendado)</option>
                                        <option value="100">100 por pág.</option>
                                        <option value="all">Todos ({{ count($ultimasSalidas) }})</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaCerradosSalida">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th>Tiquete</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>EPS</th>
                                        <th>Hora Ingreso</th>
                                        <th>Hora Salida</th>
                                        <th>Tiempo Total SLA</th>
                                        <th>Atendido Por</th>
                                        <th>Sede</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    
@foreach ($ultimasSalidas as $sal)

                                        @php
$minTot = $sal['minutos_totales_sla'] ?? 0;
                                            $slaClase = $minTot <= 20 ? 'text-success' : ($minTot <= 45 ? 'text-warning' : 'text-danger');
@endphp

                                        <tr class="fila-cerrada">
                                            <td>
                                                <span class="badge bg-light text-dark border fs-6 fw-bold">
                                                    {{ $sal['ticket_numero'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $sal['nombres'] . ' ' . $sal['apellidos'] }}</div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $sal['tipo_documento'] . ' ' . $sal['numero_documento'] }}</span>
                                            </td>
                                            <td>
                                                <span class="small">{{ $sal['eps_nombre'] ?? 'Particular' }}</span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ date('h:i A', strtotime($sal['fecha_ingreso'])) }}
                                                <div style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($sal['fecha_ingreso'])) }}</div>
                                            </td>
                                            <td class="small text-success fw-bold">
                                                <i class="fa-solid fa-circle-check me-1"></i>
                                                {{ date('h:i A', strtotime($sal['fecha_salida'])) }}
                                                <div class="text-muted" style="font-size: 0.72rem;">{{ date('d/m/Y', strtotime($sal['fecha_salida'])) }}</div>
                                            </td>
                                            <td>
                                                <span class="fw-bold fs-6 {{ $slaClase }}">
                                                    <i class="fa-solid fa-stopwatch me-1"></i> {{ $minTot }} min
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ $sal['usuario_salida_nombre'] ?? 'Sistema' }}
                                            </td>
                                            <td class="small text-muted">
                                                {{ $sal['nombre_sede'] ?? 'Principal' }}
                                            </td>
                                        </tr>
                                    
@endforeach

                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación de Cerrados -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top" id="footerPaginacionCerrados">
                            <div class="small text-muted fw-semibold" id="infoPaginacionCerrados"></div>
                            <nav aria-label="Paginación Cerrados">
                                <ul class="pagination pagination-sm mb-0" id="ulPaginacionCerrados"></ul>
                            </nav>
                        </div>
                    
@endif

                </div>

            </div>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL BOOTSTRAP: CIERRE DE TIQUETE & REGISTRO DE SALIDA                    -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCierreTicket" tabindex="-1" aria-labelledby="modalCierreTicketLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-door-open fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCierreTicketLabel">Cierre de Tiquete & Salida de Paciente</h5>
                        <small class="text-white-50">Confirme la conclusión de la atención para registrar la fecha y hora final en el SLA</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('salida.cerrar') }}" id="formConfirmarCierreSalida" onsubmit="return handleFormSalidaSubmit(this, event)">
@csrf
                <input type="hidden" name="sede_id" value="{{ $sede_filtro }}">
                <input type="hidden" name="action" value="cerrar_ticket">
                <input type="hidden" name="ingreso_id" id="modal_ingreso_id" value="">

                <div class="modal-body p-4 bg-light">
                    
                    <!-- Fila Superior: Tiquete y Prioridad -->
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                        <div class="row align-items-center g-3">
                            <div class="col-md-6 text-center text-md-start">
                                <span class="text-muted small text-uppercase fw-semibold d-block">Número de Tiquete:</span>
                                <span class="fs-2 fw-bold text-primary" id="modal_ticket_numero">TK-000000-0000</span>
                            </div>
                            <div class="col-md-6 text-center text-md-end">
                                <span class="text-muted small text-uppercase fw-semibold d-block">Tipo de Atención / Prioridad:</span>
                                <span id="modal_prioridad_badge" class="badge bg-secondary fs-6 px-3 py-2">Normal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fila Media: Datos del Paciente y Métricas de Tiempo -->
                    <div class="row g-3 mb-3">
                        <!-- Datos del Paciente -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-3 bg-white h-100">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">
                                    <i class="fa-solid fa-user me-2 text-primary"></i> Información del Paciente
                                </h6>
                                <div class="mb-1">
                                    <small class="text-muted">Nombre Completo:</small>
                                    <div class="fw-bold text-dark fs-6" id="modal_paciente_nombre">---</div>
                                </div>
                                <div class="mb-1">
                                    <small class="text-muted">Documento de Identidad:</small>
                                    <div class="fw-semibold text-secondary" id="modal_paciente_documento">---</div>
                                </div>
                                <div class="mb-1">
                                    <small class="text-muted">Aseguradora / EPS:</small>
                                    <div class="fw-semibold text-dark" id="modal_paciente_eps">---</div>
                                </div>
                                <div>
                                    <small class="text-muted">Teléfono / Celular:</small>
                                    <div class="fw-semibold text-dark" id="modal_paciente_telefono">---</div>
                                </div>
                            </div>
                        </div>

                        <!-- Métricas de Tiempos & SLA -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-3 bg-white h-100">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">
                                    <i class="fa-solid fa-stopwatch me-2 text-primary"></i> Tiempos de Atención & SLA
                                </h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Hora Ingreso:</small>
                                        <div class="fw-bold text-dark" id="modal_hora_ingreso">--:--</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Inicio Oficial SLA:</small>
                                        <div class="fw-bold text-primary" id="modal_hora_apertura_sla">07:00 AM</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Hora de Cierre:</small>
                                        <div class="fw-bold text-success" id="modal_hora_salida">{{ date('h:i A') }}</div>
                                    </div>
                                    <div class="col-6" id="modal_fila_externa_container" style="display: none;">
                                        <small class="text-muted">Fila Exterior Previa:</small>
                                        <div class="fw-bold text-warning" id="modal_fila_externa">0 min</div>
                                    </div>
                                </div>

                                <div class="p-2 border rounded bg-light text-center mb-2">
                                    <small class="text-muted text-uppercase fw-semibold d-block">Trámite Real Farmacia (SLA):</small>
                                    <div class="display-6 fw-bold text-primary my-1" id="modal_tiempo_transcurrido">0.0 min</div>
                                    <span id="modal_sla_status_badge" class="badge bg-success px-3 py-1 fw-bold">SLA en Meta (&lt; 20 min)</span>
                                </div>

                                <div class="small text-muted" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-calendar-check text-success me-1"></i> <span id="modal_label_apertura">L-V: 07:00 AM | Sáb-Dom-Festivos: 08:00 AM</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fila Inferior: Observación / Novedad de Cierre (Opcional) -->
                    <div class="card border-0 shadow-sm p-3 bg-white">
                        <label class="form-label fw-bold text-secondary mb-1">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Observación o Novedad de Salida (Opcional):
                        </label>
                        <input type="text" 
                               name="observacion_salida" 
                               id="modal_observacion_salida" 
                               class="form-control" 
                               placeholder="Ej: Entrega completa de fórmula, paciente se retira satisfecho...">
                    </div>

                </div>

                <!-- Pie del Modal: Botones de Acción -->
                <div class="modal-footer bg-white border-top py-3">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btnSubmitSalida" onclick="mostrarSpinnerSalida(this)" class="btn btn-success btn-lg fw-bold px-5 shadow">
                        <i class="fa-solid fa-circle-check me-2"></i> Confirmar Cierre & Salida
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- SCRIPT JS DE CONTROL DEL MODAL, PAGINACIÓN (50 POR PÁGINA) Y AUTO-APERTURA -->
<script>
// Manejo de Modo Pistola Lectora
function toggleModoPistola(el) {
    const isPistola = el.checked;
    localStorage.setItem('sispam_modo_pistola_salida', isPistola ? '1' : '0');
    const badge = document.getElementById('badgeModoStatus');
    if (badge) {
        if (isPistola) {
            badge.className = 'badge bg-success text-white border small';
            badge.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Modo Pistola Activo (Cierre Directo en 1 Escaneo)';
        } else {
            badge.className = 'badge bg-light text-muted border small';
            badge.innerHTML = '<i class="fa-solid fa-hand me-1"></i> Modo Manual / Confirmación Activo';
        }
    }
}

// Generador de Sonidos Sintetizados (Web Audio API)
function playBeepSalida(type = 'success') {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        if (type === 'success') {
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.2);
        } else if (type === 'warning') {
            osc.frequency.setValueAtTime(440, ctx.currentTime);
            osc.frequency.setValueAtTime(330, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.25);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, ctx.currentTime);
            osc.frequency.setValueAtTime(180, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        }
    } catch (e) {
        console.warn('Audio Context no disponible:', e);
    }
}

// Cierre Directo / Express con Pistola Lectora
async function ejecutarCierreConPistola(criterio) {
    criterio = (criterio || '').trim();
    if (!criterio) return;

    const banner = document.getElementById('bannerSalidaExpress');
    const inputBuscar = document.getElementById('inputBuscarSalida');

    if (banner) {
        banner.style.display = 'block';
        banner.innerHTML = `
            <div class="alert alert-primary py-2 mb-0 shadow-sm d-flex align-items-center gap-2">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>Buscando y procesando tiquete <strong>${criterio}</strong>...</span>
            </div>
        `;
    }

    try {
        const sedeParam = {!! json_encode($sede_filtro ?? '') !!};
        const resSearch = await fetch(`{{ route('salida.index') }}?ajax_buscar_salida=1&criterio=${encodeURIComponent(criterio)}&sede_id=${encodeURIComponent(sedeParam)}`);
        const dataSearch = await resSearch.json();

        if (dataSearch.status !== 'ok' || !dataSearch.data) {
            playBeepSalida('error');
            if (banner) {
                banner.innerHTML = `
                    <div class="alert alert-danger py-2 mb-0 shadow-sm d-flex align-items-center justify-content-between">
                        <div><i class="fa-solid fa-triangle-exclamation me-2 fs-5 align-middle"></i> No se encontró ningún tiquete activo hoy para el criterio: <strong>${criterio}</strong></div>
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
            }
            if (inputBuscar) {
                inputBuscar.value = '';
                inputBuscar.focus();
            }
            return;
        }

        const tktData = dataSearch.data;

        if (tktData.ya_cerrado) {
            playBeepSalida('warning');
            const horaC = tktData.fecha_salida ? tktData.fecha_salida.split(' ')[1] || tktData.fecha_salida : '';
            if (banner) {
                banner.innerHTML = `
                    <div class="alert alert-info py-2 mb-0 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa-solid fa-circle-info me-2 fs-5 align-middle"></i> 
                            El paciente <strong>${(tktData.nombres || '') + ' ' + (tktData.apellidos || '')}</strong> (Tiquete <strong>${tktData.ticket_numero}</strong>) ya fue cerrado hoy a las <strong>${horaC}</strong> (SLA: <strong>${tktData.minutos_transcurridos || 0} min</strong>).
                        </div>
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
            }
            if (inputBuscar) {
                inputBuscar.value = '';
                inputBuscar.focus();
            }
            return;
        }

        // Proceder al cierre express vía AJAX
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('ingreso_id', tktData.id);
        formData.append('observacion_salida', 'Cierre express vía pistola lectora de código de barras');

        const resCierre = await fetch('{{ route('salida.cerrar_express') }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const dataCierre = await resCierre.json();

        if (dataCierre.status === 'ok') {
            playBeepSalida('success');
            if (banner) {
                banner.innerHTML = `
                    <div class="alert alert-success py-2 mb-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-success border-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check fs-4 text-success"></i>
                            <div>
                                <strong class="text-success fs-6">¡SALIDA REGISTRADA CON ÉXITO!</strong>
                                <div class="small text-dark">
                                    Tiquete: <span class="badge bg-dark fs-6">${dataCierre.ticket}</span> | 
                                    Paciente: <strong>${dataCierre.paciente}</strong> | 
                                    Tiempo Total SLA: <span class="badge bg-primary fs-6">${dataCierre.sla_min} min</span>
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1">
                            <i class="fa-solid fa-bolt me-1"></i> Cierre Express
                        </span>
                    </div>
                `;
            }

            if (inputBuscar) {
                inputBuscar.value = '';
                inputBuscar.focus();
            }

            // Recargar suavemente tras 1.2 segundos para reflejar los nuevos contadores y listados
            setTimeout(() => {
                const sId = {!! json_encode($sede_filtro ?? '') !!};
                window.location.href = '{{ route('salida.index') }}' + (sId ? '?sede_id=' + encodeURIComponent(sId) : '');
            }, 1300);

        } else {
            playBeepSalida('error');
            if (banner) {
                banner.innerHTML = `
                    <div class="alert alert-danger py-2 mb-0 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> ${dataCierre.message || 'Error al procesar el cierre.'}
                    </div>
                `;
            }
            if (inputBuscar) {
                inputBuscar.value = '';
                inputBuscar.focus();
            }
        }

    } catch (err) {
        console.error('Error en cierre express:', err);
        playBeepSalida('error');
        if (banner) {
            banner.innerHTML = `
                <div class="alert alert-danger py-2 mb-0 shadow-sm">
                    <i class="fa-solid fa-circle-xmark me-2"></i> Ocurrió un error de red o de comunicación con el servidor.
                </div>
            `;
        }
        if (inputBuscar) {
            inputBuscar.value = '';
            inputBuscar.focus();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('inputBuscarSalida');
    const switchPistola = document.getElementById('switchModoPistola');
    const formBusqueda = document.getElementById('formBusquedaSalida');

    // Restaurar preferencia de Modo Pistola
    const savedPistola = localStorage.getItem('sispam_modo_pistola_salida');
    if (switchPistola) {
        if (savedPistola === '1') {
            switchPistola.checked = true;
            toggleModoPistola(switchPistola);
        } else if (savedPistola === '0') {
            switchPistola.checked = false;
            toggleModoPistola(switchPistola);
        }
    }

    if (inputBuscar) {
        inputBuscar.focus();
        inputBuscar.select();
    }

    // Interceptar envío del formulario para Modo Pistola
    if (formBusqueda) {
        formBusqueda.addEventListener('submit', (e) => {
            if (switchPistola && switchPistola.checked) {
                e.preventDefault();
                const valor = inputBuscar ? inputBuscar.value.trim() : '';
                if (valor) {
                    ejecutarCierreConPistola(valor);
                }
            }
        });
    }

    // Detector global para pistola lectora (captura escaneo si el foco no estaba en el input)
    let scannerBuffer = '';
    let lastKeyTime = Date.now();

    window.addEventListener('keydown', (e) => {
        // Ignorar si estamos escribiendo dentro de modales o textareas
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isModalOpen = document.body.classList.contains('modal-open');
        if (isModalOpen || activeTag === 'textarea') return;

        const currentTime = Date.now();
        const diff = currentTime - lastKeyTime;
        lastKeyTime = currentTime;

        // La pistola suele escribir rápido (< 40ms entre caracteres)
        if (e.key === 'Enter') {
            if (scannerBuffer.length >= 3 && diff < 100) {
                e.preventDefault();
                if (inputBuscar) inputBuscar.value = scannerBuffer;
                if (switchPistola && switchPistola.checked) {
                    ejecutarCierreConPistola(scannerBuffer);
                } else if (formBusqueda) {
                    formBusqueda.submit();
                }
                scannerBuffer = '';
            } else {
                scannerBuffer = '';
            }
        } else if (e.key.length === 1) {
            if (diff > 250) {
                scannerBuffer = e.key;
            } else {
                scannerBuffer += e.key;
            }
        }
    });

    // Inicializar Paginación en ambas pestañas (50 registros por página por defecto)
    setupTablaPaginacion({
        tableId: 'tablaPendientesSalida',
        rowSelector: '.fila-pendiente',
        searchInputId: 'buscarTablaPendientes',
        selectLimitId: 'selectLimitPendientes',
        badgeId: 'badgeContadorPendientes',
        infoId: 'infoPaginacionPendientes',
        ulPaginationId: 'ulPaginacionPendientes',
        unitLabel: 'tiquetes en espera'
    });

    setupTablaPaginacion({
        tableId: 'tablaCerradosSalida',
        rowSelector: '.fila-cerrada',
        searchInputId: 'buscarTablaCerrados',
        selectLimitId: 'selectLimitCerrados',
        badgeId: 'badgeContadorCerrados',
        infoId: 'infoPaginacionCerrados',
        ulPaginationId: 'ulPaginacionCerrados',
        unitLabel: 'salidas concluidas'
    });

    // Si el controlador backend encontró un ingreso ACTIVO al buscar por URL, abrir el modal automáticamente
    
@if ($ingresoEncontrado && empty($ingresoEncontrado['ya_cerrado']))

        abrirModalCierreDirecto({!! json_encode($ingresoEncontrado) !!});
    
@endif

});

// Función Genérica de Paginación y Filtrado Rápido en Cliente
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

        // Contador
        const badge = document.getElementById(config.badgeId);
        if (badge) {
            badge.innerText = `Mostrando ${totalItems === 0 ? 0 : (startIndex + 1)} - ${endIndex} de ${totalItems} ${config.unitLabel}`;
        }
        const infoEl = document.getElementById(config.infoId);
        if (infoEl) {
            infoEl.innerText = `Página ${currentPage} de ${totalPages} (${totalItems} registros encontrados)`;
        }

        // Render paginador
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

    // Boton Anterior
    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<a class="page-link" href="javascript:void(0)"><i class="fa-solid fa-chevron-left"></i></a>`;
    if (currentPage > 1) {
        liPrev.addEventListener('click', () => onPageChange(currentPage - 1));
    }
    ul.appendChild(liPrev);

    // Páginas numéricas
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

    // Boton Siguiente
    const liNext = document.createElement('li');
    liNext.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    liNext.innerHTML = `<a class="page-link" href="javascript:void(0)"><i class="fa-solid fa-chevron-right"></i></a>`;
    if (currentPage < totalPages) {
        liNext.addEventListener('click', () => onPageChange(currentPage + 1));
    }
    ul.appendChild(liNext);
}

function abrirModalCierreDirecto(data) {
    if (!data) return;

    document.getElementById('modal_ingreso_id').value = data.id || '';
    document.getElementById('modal_ticket_numero').innerText = data.ticket_numero || '---';

    // Prioridad
    const priorBadge = document.getElementById('modal_prioridad_badge');
    const prior = data.prioridad || 'NORMAL';
    if (prior === 'TERCERA_EDAD') {
        priorBadge.className = 'badge bg-warning text-dark fs-6 px-3 py-2 fw-bold';
        priorBadge.innerHTML = '<i class="fa-solid fa-person-cane me-1"></i> Tercera Edad';
    } else if (prior === 'EMBARAZADA') {
        priorBadge.className = 'badge bg-danger text-white fs-6 px-3 py-2 fw-bold';
        priorBadge.innerHTML = '<i class="fa-solid fa-person-pregnant me-1"></i> Embarazada';
    } else if (prior === 'DISCAPACIDAD') {
        priorBadge.className = 'badge bg-info text-dark fs-6 px-3 py-2 fw-bold';
        priorBadge.innerHTML = '<i class="fa-solid fa-wheelchair me-1"></i> Discapacidad';
    } else {
        priorBadge.className = 'badge bg-secondary fs-6 px-3 py-2';
        priorBadge.innerText = 'Atención Normal';
    }

    // Datos del Paciente
    const nombres = (data.nombres || '') + ' ' + (data.apellidos || '');
    document.getElementById('modal_paciente_nombre').innerText = nombres.trim() || 'No Registrado';
    document.getElementById('modal_paciente_documento').innerText = (data.tipo_documento || '') + ' ' + (data.numero_documento || '');
    document.getElementById('modal_paciente_eps').innerText = data.eps_nombre || 'Particular / No Definida';
    document.getElementById('modal_paciente_telefono').innerText = data.telefono || data.numero_celular || 'No registrado';

    // Formateo de Hora de Ingreso
    if (data.fecha_ingreso) {
        try {
            const d = new Date(data.fecha_ingreso.replace(' ', 'T'));
            let horas = d.getHours();
            const ampm = horas >= 12 ? 'PM' : 'AM';
            horas = horas % 12 || 12;
            const minutos = String(d.getMinutes()).padStart(2, '0');
            document.getElementById('modal_hora_ingreso').innerText = `${horas}:${minutos} ${ampm}`;
        } catch (e) {
            document.getElementById('modal_hora_ingreso').innerText = data.fecha_ingreso;
        }
    }

    // Apertura oficial e inicio SLA
    if (data.hora_apertura_oficial) {
        try {
            const parts = data.hora_apertura_oficial.split(':');
            let hA = parseInt(parts[0], 10);
            const mA = parts[1] || '00';
            const ampmA = hA >= 12 ? 'PM' : 'AM';
            hA = hA % 12 || 12;
            document.getElementById('modal_hora_apertura_sla').innerText = `${hA}:${mA} ${ampmA}`;
        } catch(e) {
            document.getElementById('modal_hora_apertura_sla').innerText = data.hora_apertura_oficial;
        }
    }

    const labelApertura = data.label_horario_apertura || 'Horario Oficial de Sede';
    const labelEl = document.getElementById('modal_label_apertura');
    if (labelEl) labelEl.innerText = labelApertura;

    const filaCont = document.getElementById('modal_fila_externa_container');
    const filaEl = document.getElementById('modal_fila_externa');
    if (data.ingresado_antes_apertura && data.tiempo_fila_externa_min > 0) {
        if (filaCont) filaCont.style.display = 'block';
        if (filaEl) filaEl.innerText = `${data.tiempo_fila_externa_min} min`;
    } else {
        if (filaCont) filaCont.style.display = 'none';
    }

    // Minutos Transcurridos y Badge SLA (Farmacia Real)
    const min = parseFloat(data.minutos_transcurridos || 0);
    const tiempoEl = document.getElementById('modal_tiempo_transcurrido');
    const slaBadge = document.getElementById('modal_sla_status_badge');

    tiempoEl.innerText = `${min.toFixed(1)} min`;

    if (min <= 20) {
        tiempoEl.className = 'display-6 fw-bold text-success my-1';
        slaBadge.className = 'badge bg-success px-3 py-1 fw-bold';
        slaBadge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> SLA en Meta (&le; 20 min)';
    } else if (min <= 45) {
        tiempoEl.className = 'display-6 fw-bold text-warning my-1';
        slaBadge.className = 'badge bg-warning text-dark px-3 py-1 fw-bold';
        slaBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> SLA Aceptable (20 - 45 min)';
    } else {
        tiempoEl.className = 'display-6 fw-bold text-danger my-1';
        slaBadge.className = 'badge bg-danger px-3 py-1 fw-bold';
        slaBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> SLA Excedido (&gt; 45 min)';
    }

    document.getElementById('modal_observacion_salida').value = '';

    // Mostrar Modal Bootstrap
    const modalEl = document.getElementById('modalCierreTicket');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

// Control Visual Inmediato de Spinner & Anti-Doble Clic
function activarLoaderSalida(titulo, subtitulo) {
    const loader = document.getElementById('sispamGlobalLoaderSalida');
    if (loader) {
        if (titulo) document.getElementById('sispamSalidaLoaderTitle').innerText = titulo;
        if (subtitulo) document.getElementById('sispamSalidaLoaderSubtitle').innerText = subtitulo;
        loader.style.display = 'flex';
    }
}

function mostrarSpinnerSalida(btn) {
    if (!btn) return;
    activarLoaderSalida('Registrando Salida...', 'Calculando tiempos SLA y cerrando tiquete...');
    btn.style.pointerEvents = 'none';
    btn.classList.add('disabled', 'opacity-75');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2 text-light" role="status" aria-hidden="true"></span> Registrando Salida...';
}

function handleFormSalidaSubmit(form, e) {
    if (form.dataset.submitting === 'true') {
        if (e) e.preventDefault();
        return false;
    }
    form.dataset.submitting = 'true';
    activarLoaderSalida('Registrando Salida...', 'Calculando tiempos SLA y cerrando tiquete...');
    const btnSubmitSalida = document.getElementById('btnSubmitSalida');
    if (btnSubmitSalida) {
        btnSubmitSalida.style.pointerEvents = 'none';
        btnSubmitSalida.classList.add('disabled', 'opacity-75');
        btnSubmitSalida.innerHTML = '<span class="spinner-border spinner-border-sm me-2 text-light" role="status" aria-hidden="true"></span> Registrando Salida...';
    }
    return true;
}
</script>

<!-- OVERLAY DE CARGA GLOBAL EN SALIDA -->
<div id="sispamGlobalLoaderSalida" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(6px); z-index: 999999; flex-direction: column; justify-content: center; align-items: center; color: white;">
    <div class="spinner-border text-success mb-3" style="width: 4.5rem; height: 4.5rem; border-width: 0.35rem;" role="status">
        <span class="visually-hidden">Procesando...</span>
    </div>
    <h3 class="fw-bold text-white mb-2" id="sispamSalidaLoaderTitle">Registrando Salida...</h3>
    <p class="text-white-50 small mb-0 px-3 text-center" id="sispamSalidaLoaderSubtitle">Calculando tiempos de ciclo SLA y cerrando tiquete de atención.</p>
</div>
@endsection
