@extends('layouts.app')

@section('titulo', 'Pendientes y Crónicos - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    
@if (!empty($mensaje))

        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ $mensaje }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if (!empty($error))

        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-truck-fast fs-3 text-primary"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Logística, Domicilios & Entregas Programadas</h3>
                    <p class="text-muted small mb-0 mt-1">Búsqueda 360° por Cédula: Historial de tickets entregados y gestión de entregas programadas por direccionar.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
            <a href="{{ route('inventario.pendientes') }}?tab=cronicos&export_cronicos=1" class="btn btn-success fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar Hoja de Ruta
            </a>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Stock Bodega
            </a>
        </div>
    </div>

    <!-- BUSCADOR INTELIGENTE POR CÉDULA / PACIENTE -->
    <div class="card border-0 shadow rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="inventario_pendientes">
                <input type="hidden" name="tab" value="{{ $tabActiva }}">
                
                <div class="col-lg-8">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-id-card text-primary me-1"></i> Buscar Paciente por Cédula / Documento de Identidad
                    </label>
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-light border-0 px-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-0 px-3 fw-bold" placeholder="Ingresa el número de cédula (Ej: 174243, 1036780004)..." value="{{ $buscar }}" autofocus>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-search me-1"></i> Consultar Paciente
                        </button>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end pt-lg-4">
                    
@if (!empty($buscar))

                        <a href="{{ route('inventario.pendientes') }}?tab={{ $tabActiva }}" class="btn btn-outline-danger rounded-pill fw-bold px-3">
                            <i class="fa-solid fa-xmark me-1"></i> Limpiar Búsqueda
                        </a>
                    
@endif

                </div>
            </form>
        </div>
    </div>

    
@if ($pacienteBuscado)

        <!-- ========================================================================= -->
        <!-- VISTA PACIENTE 360: TICKETS ENTREGADOS ARRIBA + PENDIENTES ABAJO         -->
        <!-- ========================================================================= -->

        <!-- FICHA DE DATOS DEL PACIENTE (ALTA VISIBILIDAD & CONTRASTE) -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 p-4 border-start border-4 border-primary">
            <div class="row align-items-center g-3">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-user-check fs-2"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 fw-bold text-uppercase px-3 py-1 mb-1">
                                <i class="fa-solid fa-circle-check me-1"></i> Paciente Identificado
                            </span>
                            <h4 class="fw-bold mb-1 text-dark">{{ $pacienteBuscado['nombres'] . ' ' . $pacienteBuscado['apellidos'] }}</h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    <i class="fa-solid fa-id-badge text-primary me-1"></i> {{ $pacienteBuscado['tipo_documento'] . ': ' . $pacienteBuscado['numero_documento'] }}
                                </span>
                                <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1 fw-bold">
                                    <i class="fa-solid fa-shield-heart me-1"></i> {{ $pacienteBuscado['eps_nombre'] ?: 'EPS General' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="p-3 bg-light rounded-4 border text-start text-md-end">
                        <div class="small text-dark">
                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> 
                            <strong class="text-secondary">Dirección:</strong> 
                            <span class="fw-bold text-dark">{{ $pacienteBuscado['direccion_residencia'] ?: 'Sin registrar' }}</span>
                        </div>
                        <div class="small text-dark mt-1">
                            <i class="fa-solid fa-phone me-1 text-success"></i> 
                            <strong class="text-secondary">Teléfono:</strong> 
                            <span class="fw-bold text-dark">{{ $pacienteBuscado['telefono'] ?: ($pacienteBuscado['numero_celular'] ?: 'Sin registrar') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. BLOQUE SUPERIOR: TICKETS Y ENTREGAS REALIZADAS -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-success bg-opacity-10 border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success fs-5"></i>
                    <h5 class="fw-bold mb-0 text-success">1. Tickets & Entregas Realizadas al Paciente</h5>
                </div>
                <span class="badge bg-success text-white rounded-pill px-3 py-2 fw-bold">{{ count($ticketsPaciente) }} Tickets Registrados</span>
            </div>

            <div class="card-body p-0">
                
@if (empty($ticketsPaciente))

                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-inbox fs-2 mb-2 d-block text-secondary"></i>
                        No se registran entregas históricas previas para este paciente.
                    </div>
                
@else

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4">Tiquete / Turno</th>
                                    <th>Fecha y Hora</th>
                                    <th>Sede de Atención</th>
                                    <th>Medicamentos Dispensados en este Ticket</th>
                                    <th>Estado de Entrega</th>
                                    <th class="text-end pe-4">Acta / Soporte</th>
                                </tr>
                            </thead>
                            <tbody>
                                
@foreach ($ticketsPaciente as $t)

                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-primary text-white font-monospace fs-6 px-2 py-1">{{ $t['ticket_numero'] }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{ date('d/m/Y', strtotime($t['fecha_ingreso'])) }}</strong>
                                            <small class="text-muted">{{ date('H:i A', strtotime($t['fecha_ingreso'])) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1"><i class="fa-solid fa-building me-1 text-primary"></i> {{ $t['sede_nombre'] }}</span>
                                            
@if (!empty($t['alistador_nombre']))

                                                <small class="text-muted d-block mt-1">Dispensó: {{ $t['alistador_nombre'] }}</small>
                                            
@endif

                                        </td>
                                        <td>
                                            
@if (empty($t['medicamentos_dispensados']))

                                                <span class="text-muted small fst-italic">Entrega de fórmula o trámite administrativo</span>
                                            
@else

                                                <div class="d-flex flex-column gap-1">
                                                    
@foreach ($t['medicamentos_dispensados'] as $md)

                                                        <div class="small text-dark">
                                                            <strong class="text-primary">• {{ $md['nombre_generico'] . ' ' . $md['concentracion'] }}:</strong> 
                                                            <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-0">
                                                                {{ intval($md['cantidad_entregada']) }} Unid.
                                                            </span>
                                                            
@if (!empty($md['numero_lote']))

                                                                <span class="text-muted font-monospace small">(Lote: {{ $md['numero_lote'] }})</span>
                                                            
@endif

                                                        </div>
                                                    
@endforeach

                                                </div>
                                            
@endif

                                        </td>
                                        <td>
                                            
@if ($t['estado_tramite'] === 'ENTREGADO')

                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check-double me-1"></i> ENTREGADO A SATISFACCIÓN</span>
                                            
@else

                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">{{ $t['estado_tramite'] }}</span>
                                            
@endif

                                        </td>
                                        <td class="text-end pe-4">
                                            
@if (!empty($t['acta_entrega_url']))

                                                <a href="{{ $t['acta_entrega_url'] }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3">
                                                    <i class="fa-solid fa-file-signature me-1"></i> Ver Acta
                                                </a>
                                            
@else

                                                <span class="badge bg-light text-muted border">Registrado en SISPAM</span>
                                            
@endif

                                        </td>
                                    </tr>
                                
@endforeach

                            </tbody>
                        </table>
                    </div>
                
@endif

            </div>
        </div>

        <!-- 2. BLOQUE INFERIOR: PENDIENTES & FUTURAS ENTREGAS POR DIRECCIONAR -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden border-top border-4 border-warning">
            <div class="card-header bg-warning bg-opacity-10 border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-boxes-packing text-dark fs-5"></i>
                    <h5 class="fw-bold mb-0 text-dark">2. Pendientes & Entregas Programadas por Direccionar</h5>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold">{{ count($cronicosPaciente) }} Entregas Crónicas Futuras</span>
                    <span class="badge bg-danger text-white rounded-pill px-3 py-2 fw-bold">{{ count($faltantesPaciente) }} Faltantes Stock</span>
                </div>
            </div>

            <div class="card-body p-4">
                
                <!-- SUB-SECCIÓN A: ENTREGAS MULTIMES PROGRAMADAS (CRÓNICOS) -->
                <h6 class="fw-bold text-primary mb-3">
                    <i class="fa-solid fa-calendar-check me-2"></i> Entregas Multimes Programadas (Tratamientos Crónicos Periodo 2, 3... N)
                </h6>

                
@if (empty($cronicosPaciente))

                    <div class="alert alert-light border rounded-3 text-center py-3 text-muted mb-4">
                        <i class="fa-solid fa-circle-info text-info me-2"></i> El paciente no tiene fórmulas multimes con periodos futuros pendientes.
                    </div>
                
@else

                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle mb-0 border rounded-3">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-3">Periodo / Fecha Programada</th>
                                    <th>Medicamento</th>
                                    <th class="text-center">Cant. a Entregar</th>
                                    <th>Modalidad Actual</th>
                                    <th>Estado Logístico</th>
                                    <th class="text-end pe-3">Acciones / Cambio Modalidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                
@foreach ($cronicosPaciente as $ec)
@php
$esEntregado = in_array($ec['estado'], ['ENTREGADO_PRESENCIAL', 'DESPACHADO_DOMICILIO']);
$tsProg = strtotime($ec['fecha_programada']);
$tsHoy = strtotime(date('Y-m-d'));
$diasDiff = round(($tsProg - $tsHoy) / 86400);
$esHoy = ($diasDiff == 0);
$esVencida = ($diasDiff < 0 && !$esEntregado);
@endphp

                                    <tr class="{{ $esEntregado ? 'table-light text-muted' : '' }}">
                                        <td class="ps-3">
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 mb-1">
                                                Periodo {{ $ec['periodo_numero'] }} de {{ $ec['total_periodos'] }}
                                            </span>
                                            <div>
                                                
@if ($esEntregado)

                                                    <span class="badge bg-light text-muted border"><i class="fa-solid fa-check me-1"></i> Entregado ({{ $ec['fecha_programada'] }})</span>
                                                
@php
elseif ($esHoy):
@endphp

                                                    <span class="badge bg-success text-white fw-bold"><i class="fa-solid fa-bell me-1"></i> Programada Hoy ({{ $ec['fecha_programada'] }})</span>
                                                
@php
elseif ($esVencida):
@endphp

                                                    <span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Atrasada por {{ abs($diasDiff) }} día(s) ({{ $ec['fecha_programada'] }})</span>
                                                
@else

                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold"><i class="fa-regular fa-calendar-check me-1"></i> En {{ $diasDiff }} día(s) ({{ $ec['fecha_programada'] }})</span>
                                                
@endif

                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{ $ec['medicamento_nombre'] }}</strong>
                                            <small class="text-muted"><i class="fa-solid fa-prescription-bottle me-1 text-primary"></i>{{ $ec['posologia'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark text-white fs-6 fw-bold px-3 py-2 rounded-pill">
                                                {{ intval($ec['cantidad_periodo']) }} Unid.
                                            </span>
                                        </td>
                                        <td>
                                            
@if ($ec['modalidad'] === 'DOMICILIO')

                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                                    <i class="fa-solid fa-motorcycle me-1"></i> 🛵 Domicilio
                                                </span>
                                            
@else

                                                <span class="badge bg-info text-white px-3 py-2 rounded-pill fw-bold">
                                                    <i class="fa-solid fa-building me-1"></i> 🏢 Presencial en Sede
                                                </span>
                                            
@endif

                                        </td>
                                        <td>
                                            
@if ($ec['estado'] === 'ENTREGADO_PRESENCIAL')

                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> Entregado en Sede</span>
                                            
@php
elseif ($ec['estado'] === 'DESPACHADO_DOMICILIO'):
@endphp

                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-truck-fast me-1"></i> Despachado ({{ $ec['guia_domicilio'] ?: 'En ruta' }})</span>
                                            
@else

                                                <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Pendiente / Programado</span>
                                            
@endif

                                        </td>
                                        <td class="text-end pe-3">
                                            
@if (!$esEntregado)

                                                <div class="d-flex gap-1 justify-content-end align-items-center flex-wrap">
                                                    
                                                    <!-- BOTÓN PROGRAMAR / LLAMAR AL PACIENTE -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 fw-bold" onclick='abrirModalReprogramar({!! json_encode($ec, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})' title="Llamar al paciente / Reprogramar fecha y modalidad">
                                                        <i class="fa-solid fa-phone me-1"></i> Programar / Llamar
                                                    </button>

                                                    <!-- BOTÓN DESPACHAR A DOMICILIO -->
                                                    <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold px-3" onclick='abrirModalCronicoDomicilio({!! json_encode($ec, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                                        <i class="fa-solid fa-truck-fast me-1"></i> Despachar
                                                    </button>

                                                    <!-- BOTÓN ENTREGAR EN SEDE HOY -->
                                                    <form method="POST" action="{{ route('inventario.pendientes') }}?q={{ urlencode($buscar) }}" class="d-inline" onsubmit="return confirm('¿Confirmas que el paciente vino a la sede y se le entregó este periodo presencialmente?');">
@csrf
                                                        <input type="hidden" name="action" value="marcar_cronico_presencial">
                                                        <input type="hidden" name="entrega_id" value="{{ $ec['id'] }}">
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill fw-bold px-3" title="Registrar entrega presencial">
                                                            <i class="fa-solid fa-hand-holding-hand me-1"></i> Entregar en Sede
                                                        </button>
                                                    </form>

                                                </div>
                                            
@else

                                                <span class="text-muted small"><i class="fa-solid fa-check-double text-success me-1"></i> Entrega finalizada</span>
                                            
@endif

                                        </td>
                                    </tr>
                                
@endforeach

                            </tbody>
                        </table>
                    </div>
                
@endif


                <!-- SUB-SECCIÓN B: FALTANTES INMEDIATOS POR STOCK -->
                <h6 class="fw-bold text-danger mb-3">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Faltantes Inmediatos por Stock en Ventanilla
                </h6>

                
@if (empty($faltantesPaciente))

                    <div class="alert alert-light border rounded-3 text-center py-3 text-muted">
                        <i class="fa-solid fa-check-circle text-success me-2"></i> El paciente no tiene medicamentos faltantes por stock de ventanilla.
                    </div>
                
@else

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 border rounded-3">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-3">Tiquete Origen</th>
                                    <th>Medicamento Faltante</th>
                                    <th class="text-center">Cant. Faltante</th>
                                    <th>Estado de Envío</th>
                                    <th class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                
@foreach ($faltantesPaciente as $fp)
@php
$esEntregadoF = ($fp['estado_domicilio'] === 'ENTREGADO_DOMICILIO');
@endphp

                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-black border border-primary text-primary font-monospace fs-6 px-2 py-1">{{ $fp['ticket_numero'] }}</span>
                                            <small class="text-muted d-block mt-1">{{ date('d/m/Y', strtotime($fp['fecha_ingreso'])) }}</small>
                                        </td>
                                        <td>
                                            <strong class="text-danger d-block">{{ $fp['nombre_medicamento'] }}</strong>
                                            <small class="text-muted">{{ $fp['concentracion'] . ' ' . $fp['forma_farmaceutica'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger text-white fs-6 fw-bold px-3 py-2 rounded-pill">
                                                {{ intval($fp['cantidad_pendiente']) }} Faltantes
                                            </span>
                                        </td>
                                        <td>
                                            
@if ($esEntregadoF)

                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Entregado</span>
                                            
@php
elseif ($fp['estado_domicilio'] === 'EN_RUTA_DOMICILIO'):
@endphp

                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-truck-fast me-1"></i> En Ruta ({{ $fp['guia_domicilio'] ?: 'En camino' }})</span>
                                            
@else

                                                <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Pendiente Alistar</span>
                                            
@endif

                                        </td>
                                        <td class="text-end pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick='editarDomicilio({!! json_encode($fp, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Gestionar Despacho
                                            </button>
                                        </td>
                                    </tr>
                                
@endforeach

                            </tbody>
                        </table>
                    </div>
                
@endif


            </div>
        </div>

    
@else


        <!-- ========================================================================= -->
        <!-- BANDEJA GENERAL DE LOGÍSTICA (SI NO HAY BÚSQUEDA PUNTUAL DE PACIENTE)    -->
        <!-- ========================================================================= -->

        <!-- TARJETAS DE MÉTRICAS LOGÍSTICAS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=vencidas" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-danger h-100 {{ $filtroRango === 'vencidas' ? 'bg-danger bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-danger small fw-bold text-uppercase"><i class="fa-solid fa-triangle-exclamation me-1"></i> Vencidas / En Mora</div>
                                <div class="fs-3 fw-bold text-danger">{{ $metricasCronicas['vencidas'] }}</div>
                                <small class="text-muted fw-semibold">Requieren atención urgente</small>
                            </div>
                            <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle"><i class="fa-solid fa-clock-rotate-left fs-4"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=hoy" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-success h-100 {{ $filtroRango === 'hoy' ? 'bg-success bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-success small fw-bold text-uppercase"><i class="fa-solid fa-calendar-day me-1"></i> Entregas para Hoy</div>
                                <div class="fs-3 fw-bold text-success">{{ $metricasCronicas['hoy'] }}</div>
                                <small class="text-muted fw-semibold">Programadas para hoy</small>
                            </div>
                            <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle"><i class="fa-solid fa-calendar-check fs-4"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=semana" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-warning h-100 {{ $filtroRango === 'semana' ? 'bg-warning bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-warning-emphasis small fw-bold text-uppercase"><i class="fa-solid fa-boxes-packing me-1"></i> Próximos 7 Días</div>
                                <div class="fs-3 fw-bold text-dark">{{ $metricasCronicas['semana'] }}</div>
                                <small class="text-muted fw-semibold">Alistamiento semanal</small>
                            </div>
                            <div class="p-3 bg-warning bg-opacity-10 text-warning-emphasis rounded-circle"><i class="fa-solid fa-truck-ramp-box fs-4"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('inventario.pendientes') }}?tab=cronicos&modalidad=DOMICILIO" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-primary h-100 {{ $filtroMod === 'DOMICILIO' ? 'bg-primary bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-primary small fw-bold text-uppercase"><i class="fa-solid fa-motorcycle me-1"></i> Domicilios Activos</div>
                                <div class="fs-3 fw-bold text-primary">{{ $metricasCronicas['domicilios_pendientes'] }}</div>
                                <small class="text-muted fw-semibold">En espera o en ruta</small>
                            </div>
                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle"><i class="fa-solid fa-route fs-4"></i></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- PESTAÑAS PRINCIPALES -->
        <ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4">
            <li class="nav-item">
                <a class="nav-link fw-bold py-2 {{ $tabActiva === 'cronicos' ? 'active shadow-sm' : 'text-dark' }}" href="{{ route('inventario.pendientes') }}?tab=cronicos">
                    <i class="fa-solid fa-calendar-days me-2"></i> 1. Entregas Programadas Pacientes Crónicos (Fórmulas Multimes)
                    <span class="badge bg-light text-dark ms-2">{{ count($entregasCronicas) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold py-2 {{ $tabActiva === 'faltantes' ? 'active shadow-sm' : 'text-dark' }}" href="{{ route('inventario.pendientes') }}?tab=faltantes">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> 2. Faltantes Inmediatos por Stock en Ventanilla
                    <span class="badge bg-light text-dark ms-2">{{ count($pendientes) }}</span>
                </a>
            </li>
        </ul>

        
@if ($tabActiva === 'cronicos')

            <!-- FILTROS CRÓNICOS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3">
                    <!-- ACCESOS RÁPIDOS POR URGENCIA (PILLS) -->
                    <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom align-items-center">
                        <span class="small fw-bold text-muted text-uppercase me-2"><i class="fa-solid fa-bolt text-warning me-1"></i> Acceso Rápido:</span>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos" class="btn btn-sm rounded-pill {{ empty($filtroRango) && empty($filtroMod) && empty($filtroEstado) ? 'btn-dark' : 'btn-outline-secondary' }}">
                            <i class="fa-solid fa-list me-1"></i> Todas ({{ count($entregasCronicas) }})
                        </a>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=vencidas" class="btn btn-sm rounded-pill {{ $filtroRango === 'vencidas' ? 'btn-danger' : 'btn-outline-danger' }}">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> 🔴 Vencidas / En Mora ({{ $metricasCronicas['vencidas'] }})
                        </a>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=hoy" class="btn btn-sm rounded-pill {{ $filtroRango === 'hoy' ? 'btn-success' : 'btn-outline-success' }}">
                            <i class="fa-solid fa-calendar-day me-1"></i> 🟢 Para Hoy ({{ $metricasCronicas['hoy'] }})
                        </a>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=semana" class="btn btn-sm rounded-pill {{ $filtroRango === 'semana' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' }}">
                            <i class="fa-solid fa-boxes-packing me-1"></i> 🟡 Próximos 7 Días ({{ $metricasCronicas['semana'] }})
                        </a>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos&rango=futuras" class="btn btn-sm rounded-pill {{ $filtroRango === 'futuras' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            <i class="fa-solid fa-calendar-plus me-1"></i> ⚪ Futuras (> 7 Días) ({{ $metricasCronicas['futuras'] }})
                        </a>
                        <a href="{{ route('inventario.pendientes') }}?tab=cronicos&export_cronicos=1" class="btn btn-sm btn-outline-success ms-auto rounded-pill" title="Exportar Hoja de Ruta CSV">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar Hoja de Ruta
                        </a>
                    </div>

                    <form method="GET" action="index.php" class="row g-2 align-items-center">
                        <input type="hidden" name="page" value="inventario_pendientes">
                        <input type="hidden" name="tab" value="cronicos">
                        
                        <div class="col-md-4">
                            <input type="text" name="q" class="form-control border-secondary-subtle" placeholder="Buscar por paciente, cédula o medicamento..." value="{{ $buscar }}">
                        </div>
                        <div class="col-md-2">
                            <select name="rango" class="form-select border-secondary-subtle">
                                <option value="">Cualquier Fecha</option>
                                <option value="vencidas" {{ $filtroRango === 'vencidas' ? 'selected' : '' }}>🔴 Vencidas / En Mora</option>
                                <option value="hoy" {{ $filtroRango === 'hoy' ? 'selected' : '' }}>🟢 Programadas Hoy</option>
                                <option value="semana" {{ $filtroRango === 'semana' ? 'selected' : '' }}>🟡 Próximos 7 Días</option>
                                <option value="futuras" {{ $filtroRango === 'futuras' ? 'selected' : '' }}>⚪ Futuras (> 7 Días)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="modalidad" class="form-select border-secondary-subtle">
                                <option value="">Modalidad (Todas)</option>
                                <option value="DOMICILIO" {{ $filtroMod === 'DOMICILIO' ? 'selected' : '' }}>🛵 Domicilio</option>
                                <option value="PRESENCIAL" {{ $filtroMod === 'PRESENCIAL' ? 'selected' : '' }}>🏢 Presencial en Sede</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="estado" class="form-select border-secondary-subtle">
                                <option value="">Estado (Todos)</option>
                                <option value="PROGRAMADO" {{ $filtroEstado === 'PROGRAMADO' ? 'selected' : '' }}>Programado</option>
                                <option value="EN_ALISTAMIENTO" {{ $filtroEstado === 'EN_ALISTAMIENTO' ? 'selected' : '' }}>En Alistamiento</option>
                                <option value="DESPACHADO_DOMICILIO" {{ $filtroEstado === 'DESPACHADO_DOMICILIO' ? 'selected' : '' }}>Despachado en Domicilio</option>
                                <option value="ENTREGADO_PRESENCIAL" {{ $filtroEstado === 'ENTREGADO_PRESENCIAL' ? 'selected' : '' }}>Entregado Presencial</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary fw-bold w-100 rounded-3">
                                <i class="fa-solid fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA CRÓNICOS -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4">Prioridad / Fecha</th>
                                <th>Paciente / Identificación</th>
                                <th>Medicamento & Periodo</th>
                                <th class="text-center">Cant. a Entregar</th>
                                <th>Dirección / Contacto</th>
                                <th>Modalidad / Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            
@if (empty($entregasCronicas))

                                <tr><td colspan="7" class="text-center py-5 text-muted">No hay entregas crónicas programadas con los filtros seleccionados.</td></tr>
                            
@else

                                @foreach ($entregasCronicas as $ec)
@php
$esEntregado = in_array($ec['estado'], ['ENTREGADO_PRESENCIAL', 'DESPACHADO_DOMICILIO', 'ENTREGADO']);
$tsProg = strtotime($ec['fecha_programada']);
$tsHoy = strtotime(date('Y-m-d'));
$diasDiff = round(($tsProg - $tsHoy) / 86400);
$esHoy = ($diasDiff == 0);
$esVencida = ($diasDiff < 0 && !$esEntregado);
$esSemana = ($diasDiff > 0 && $diasDiff <= 7 && !$esEntregado);
$trClass = '';
@endphp
@if ($esEntregado)
@php
$trClass = 'opacity-75';
@endphp
@elseif ($esVencida)
@php
$trClass = 'table-danger border-start border-4 border-danger';
@endphp
@elseif ($esHoy)
@php
$trClass = 'table-success border-start border-4 border-success';
@endphp
@elseif ($esSemana)
@php
$trClass = 'table-warning border-start border-4 border-warning';
@endphp
@endif

                                <tr class="{{ $trClass }}">
                                    <td class="ps-4">
                                        
@if ($esEntregado)

                                            <span class="badge bg-light text-muted border fs-6 px-2 py-1"><i class="fa-solid fa-check me-1"></i> Entregado ({{ $ec['fecha_programada'] }})</span>
                                        
@php
elseif ($esVencida):
@endphp

                                            <span class="badge bg-danger text-white fs-6 px-2 py-1 fw-bold shadow-sm"><i class="fa-solid fa-triangle-exclamation me-1"></i> Vencida por {{ abs($diasDiff) }} d ({{ $ec['fecha_programada'] }})</span>
                                        
@php
elseif ($esHoy):
@endphp

                                            <span class="badge bg-success text-white fs-6 px-2 py-1 fw-bold shadow-sm"><i class="fa-solid fa-bell me-1"></i> ¡Para Hoy! ({{ $ec['fecha_programada'] }})</span>
                                        
@php
elseif ($esSemana):
@endphp

                                            <span class="badge bg-warning text-dark border border-warning fs-6 px-2 py-1 fw-bold"><i class="fa-solid fa-clock me-1"></i> En {{ $diasDiff }} día(s) ({{ $ec['fecha_programada'] }})</span>
                                        
@else

                                            <span class="badge bg-light text-dark border fs-6 px-2 py-1"><i class="fa-regular fa-calendar-check me-1"></i> En {{ $diasDiff }} día(s) ({{ $ec['fecha_programada'] }})</span>
                                        
@endif

                                        <small class="text-muted d-block mt-1">Fórmula: {{ $ec['formula_numero'] }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $ec['nombres'] . ' ' . $ec['apellidos'] }}</div>
                                        <small class="text-muted d-block">{{ $ec['tipo_documento'] }}: {{ $ec['numero_documento'] }}</small>
                                        <span class="badge bg-light text-muted border px-2 py-0 small">{{ $ec['eps_nombre'] ?: 'EPS General' }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">{{ $ec['medicamento_nombre'] }}</div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1">
                                            Periodo {{ $ec['periodo_numero'] }} de {{ $ec['total_periodos'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark text-white fs-6 fw-bold px-3 py-2 rounded-pill">
                                            {{ intval($ec['cantidad_periodo']) }} Unidades
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><i class="fa-solid fa-location-dot text-danger me-1"></i> {{ $ec['direccion_entrega'] ?: $ec['direccion_residencia'] ?: 'Sin dirección' }}</div>
                                        <small class="text-muted d-block"><i class="fa-solid fa-phone text-success me-1"></i> {{ $ec['telefono_contacto'] ?: $ec['telefono'] ?: $ec['celular'] ?: 'Sin teléfono' }}</small>
                                    </td>
                                    <td>
                                        
@if ($ec['estado'] === 'ENTREGADO_PRESENCIAL')

                                            <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-building me-1"></i> Entregado en Sede</span>
                                        
@php
elseif ($ec['estado'] === 'DESPACHADO_DOMICILIO'):
@endphp

                                            <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-motorcycle me-1"></i> Despachado Domicilio ({{ $ec['guia_domicilio'] ?: 'En camino' }})</span>
                                        
@php
elseif ($ec['modalidad'] === 'DOMICILIO'):
@endphp

                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-motorcycle me-1"></i> Programado a Domicilio</span>
                                        
@else

                                            <span class="badge bg-info text-white px-3 py-2 rounded-pill"><i class="fa-solid fa-person-walking me-1"></i> Presencial Programado</span>
                                        
@endif

                                    </td>
                                    <td class="text-end pe-4">
                                        
@if (!$esEntregado)

                                            <div class="d-flex gap-1 justify-content-end align-items-center flex-wrap">
                                                <!-- BOTÓN PROGRAMAR / LLAMAR AL PACIENTE -->
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 fw-bold" onclick='abrirModalReprogramar({!! json_encode($ec, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})' title="Llamar al paciente / Reprogramar fecha y modalidad">
                                                    <i class="fa-solid fa-phone me-1"></i> Programar / Llamar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold px-2" onclick='abrirModalCronicoDomicilio({!! json_encode($ec, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                                    <i class="fa-solid fa-truck-fast me-1"></i> Despachar
                                                </button>
                                                <form method="POST" action="{{ route('inventario.pendientes') }}?tab=cronicos" class="d-inline" onsubmit="return confirm('¿Confirmas que el paciente retiró este periodo presencialmente en la sede?');">
@csrf
                                                    <input type="hidden" name="action" value="marcar_cronico_presencial">
                                                    <input type="hidden" name="entrega_id" value="{{ $ec['id'] }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-2" title="Marcar como entregado en sede">
                                                        <i class="fa-solid fa-hand-holding-hand"></i> En Sede
                                                    </button>
                                                </form>
                                            </div>
                                        
@else

                                            <span class="text-muted small"><i class="fa-solid fa-check-double text-success me-1"></i> Completado</span>
                                        
@endif

                                    </td>
                                </tr>
                                
@endforeach

                            @endif

                        </tbody>
                    </table>
                </div>
            </div>

        
@else

            <!-- PESTAÑA FALTANTES INMEDIATOS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3">
                    <form method="GET" action="index.php" class="row g-2">
                        <input type="hidden" name="page" value="inventario_pendientes">
                        <input type="hidden" name="tab" value="faltantes">
                        <div class="col-md-7">
                            <input type="text" name="q" class="form-control border-secondary-subtle" placeholder="Buscar por paciente, cédula, tiquete o medicamento..." value="{{ $buscar }}">
                        </div>
                        <div class="col-md-3">
                            <select name="estado" class="form-select border-secondary-subtle">
                                <option value="">Todos los Estados</option>
                                <option value="PENDIENTE_ALISTAR" {{ $filtroEstado === 'PENDIENTE_ALISTAR' ? 'selected' : '' }}>Pendiente por Alistar</option>
                                <option value="EN_RUTA_DOMICILIO" {{ $filtroEstado === 'EN_RUTA_DOMICILIO' ? 'selected' : '' }}>En Ruta de Entrega</option>
                                <option value="ENTREGADO_DOMICILIO" {{ $filtroEstado === 'ENTREGADO_DOMICILIO' ? 'selected' : '' }}>Entregado en Domicilio</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary fw-bold w-100 rounded-3">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4">Tiquete / Sede</th>
                                <th>Paciente / Contacto</th>
                                <th>Dirección de Envío</th>
                                <th>Medicamento Faltante</th>
                                <th class="text-center">Cant. Pendiente</th>
                                <th>Estado Logístico</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            
@if (empty($pendientes))

                                <tr><td colspan="7" class="text-center py-5 text-muted">No hay medicamentos pendientes registrados por stock.</td></tr>
                            
@else

                                @foreach ($pendientes as $p)
@php
$esEntregado = ($p['estado_domicilio'] === 'ENTREGADO_DOMICILIO');
$enRuta = ($p['estado_domicilio'] === 'EN_RUTA_DOMICILIO');
@endphp

                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-black border border-primary text-primary font-monospace fs-6 px-2 py-1">{{ $p['ticket_numero'] }}</span>
                                        <small class="text-muted d-block mt-1">{{ $p['nombre_sede'] }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $p['nombres'] . ' ' . $p['apellidos'] }}</div>
                                        <small class="text-muted d-block">CC: {{ $p['numero_documento'] }}</small>
                                        <span class="badge bg-light text-dark border px-2 py-0 small"><i class="fa-solid fa-phone text-success me-1"></i> {{ $p['telefono'] ?: 'Sin Tel.' }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><i class="fa-solid fa-location-dot text-danger me-1"></i> {{ $p['direccion_residencia'] ?: 'Sin dirección' }}</div>
                                        <small class="text-muted">{{ $p['ciudad_residencia'] ?: 'Medellín' }} • {{ $p['eps_nombre'] ?: 'Savia Salud' }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-danger">{{ $p['nombre_medicamento'] }}</div>
                                        <small class="text-muted">{{ $p['concentracion'] . ' ' . $p['forma_farmaceutica'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger text-white fs-6 fw-bold px-3 py-2 rounded-pill">
                                            {{ intval($p['cantidad_pendiente']) }} Faltantes
                                        </span>
                                    </td>
                                    <td>
                                        
@if ($esEntregado)

                                            <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Entregado</span>
                                        
@php
elseif ($enRuta):
@endphp

                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-truck-fast me-1"></i> En Ruta ({{ $p['guia_domicilio'] ?: 'Sin Guía' }})</span>
                                        
@else

                                            <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Pendiente Alistar</span>
                                        
@endif

                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick='editarDomicilio({!! json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Gestionar
                                        </button>
                                    </td>
                                </tr>
                                
@endforeach

                            @endif

                        </tbody>
                    </table>
                </div>
            </div>
        
@endif


    @endif


</div>

<!-- MODAL GESTIONAR DESPACHO CRÓNICO A DOMICILIO -->
<div class="modal fade" id="modalCronicoDomicilio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form method="POST" action="{{ route('inventario.pendientes') }}?q={{ urlencode($buscar) }}&tab=cronicos">
@csrf
                <input type="hidden" name="action" value="actualizar_cronico_domicilio">
                <input type="hidden" name="entrega_id" id="modal_cronico_id">
                
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-motorcycle me-2"></i> Despacho Domiciliario - Tratamiento Crónico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark fs-6" id="modal_cronico_paciente"></div>
                        <div class="small text-muted" id="modal_cronico_med"></div>
                        <div class="small text-primary fw-bold" id="modal_cronico_dir"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado del Despacho</label>
                        <select name="estado" id="modal_cronico_estado" class="form-select" required>
                            <option value="EN_ALISTAMIENTO">📦 En Alistamiento de Paquete</option>
                            <option value="DESPACHADO_DOMICILIO" selected>🛵 Despachado / En Camino con Mensajero</option>
                            <option value="ENTREGADO_PRESENCIAL">🏢 Entregado en Farmacia (Presencial)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Empresa Mensajería / Mensajero</label>
                            <input type="text" name="empresa_mensajeria" id="modal_cronico_mensajeria" class="form-control" placeholder="Ej: Mensajero Propio / Coordinadora">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Número de Guía / Control</label>
                            <input type="text" name="guia_domicilio" id="modal_cronico_guia" class="form-control" placeholder="Ej: GUIA-98124">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Observaciones / Novedad de Entrega</label>
                        <textarea name="observaciones" id="modal_cronico_obs" class="form-control" rows="2" placeholder="Ej: Entregar en portería, timbre 204..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Guardar Despacho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL GESTIONAR FALTANTES INMEDIATOS -->
<div class="modal fade" id="modalDomicilio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form method="POST" action="{{ route('inventario.pendientes') }}?q={{ urlencode($buscar) }}&tab=faltantes">
@csrf
                <input type="hidden" name="action" value="actualizar_estado">
                <input type="hidden" name="id" id="modal_p_id">
                
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck-ramp-box me-2"></i> Gestión de Medicamento Pendiente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark fs-6" id="modal_paciente_nombre"></div>
                        <div class="small text-muted" id="modal_paciente_dir"></div>
                        <div class="small text-danger fw-bold" id="modal_med_nombre"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado del Despacho / Entrega</label>
                        <select name="estado_domicilio" id="modal_estado_domicilio" class="form-select" required>
                            <option value="PENDIENTE_ALISTAR">⏳ Pendiente por Alistar</option>
                            <option value="EN_RUTA_DOMICILIO">🛵 En Ruta de Entrega (Asignado a Domiciliario)</option>
                            <option value="ENTREGADO_DOMICILIO">✅ Entregado Satisfactoriamente al Paciente</option>
                            <option value="CANCELADO">❌ Cancelado / No Requiere</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Empresa Mensajería / Mensajero</label>
                            <input type="text" name="empresa_mensajeria" id="modal_mensajeria" class="form-control" placeholder="Ej: Mensajero Propio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Número de Guía / Control</label>
                            <input type="text" name="guia_domicilio" id="modal_guia" class="form-control" placeholder="Ej: GUIA-10293">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Observaciones del Despacho</label>
                        <textarea name="observaciones" id="modal_observaciones" class="form-control" rows="2" placeholder="Novedades de la entrega..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Guardar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LLAMADA AL PACIENTE Y REPROGRAMACIÓN DE ENTREGA CRÓNICA -->
<div class="modal fade" id="modalReprogramarEntregaCronica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form method="POST" action="{{ route('inventario.pendientes') }}?q={{ urlencode($buscar) }}&tab=cronicos">
@csrf
                <input type="hidden" name="action" value="reprogramar_entrega_cronico">
                <input type="hidden" name="entrega_id" id="reprog_cronico_id">
                
                <div class="modal-header bg-primary bg-gradient text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-headset me-2 text-warning"></i> Coordinación de Entrega & Llamada al Paciente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark fs-6" id="reprog_paciente_nombre"></div>
                        <div class="text-primary fw-semibold small" id="reprog_med_nombre"></div>
                        <div class="small text-muted" id="reprog_posologia_txt"></div>
                        <div class="badge bg-dark text-white mt-2 px-3 py-1 fs-6" id="reprog_cant_badge"></div>
                    </div>

                    <!-- FECHA PROGRAMADA DE ENTREGA -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">
                            <i class="fa-regular fa-calendar-days text-primary me-1"></i> Fecha Programada de Entrega / Retiro
                        </label>
                        <input type="date" name="nueva_fecha_programada" id="reprog_fecha_input" class="form-control fw-bold fs-6" required>
                        <small class="text-muted">Fecha acordada con el paciente para el despacho a domicilio o visita presencial.</small>
                    </div>

                    <!-- MODALIDAD DE ENTREGA -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">
                            <i class="fa-solid fa-route text-primary me-1"></i> Modalidad de Entrega Acordada
                        </label>
                        <select name="modalidad" id="reprog_modalidad_sel" class="form-select fw-bold" onchange="toggleDireccionDomicilio(this.value)">
                            <option value="DOMICILIO">🛵 Domicilio (Se enviará por mensajería a su dirección)</option>
                            <option value="PRESENCIAL">🏢 Presencial en Sede (El paciente vendrá a recogerlo en farmacia)</option>
                        </select>
                    </div>

                    <!-- DATOS DE DOMICILIO -->
                    <div id="bloque_datos_domicilio">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Dirección de Entrega</label>
                            <input type="text" name="direccion_entrega" id="reprog_dir_input" class="form-control" placeholder="Ej: Carrera 45 # 50-20 Apto 302">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Teléfono / Celular de Contacto</label>
                            <input type="text" name="telefono_contacto" id="reprog_tel_input" class="form-control" placeholder="Ej: 3001234567">
                        </div>
                    </div>

                    <!-- BITÁCORA DE LLAMADA -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Notas de la Llamada / Observaciones</label>
                        <textarea name="observaciones_llamada" id="reprog_obs_input" class="form-control" rows="2" placeholder="Ej: Paciente confirma recepción en casa en la fecha programada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Programación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalReprogramar(ec) {
    document.getElementById('reprog_cronico_id').value = ec.id || '';
    var nom = (ec.nombres ? (ec.nombres + ' ' + (ec.apellidos || '')) : (ec.paciente_nombre || 'Paciente')).trim();
    var doc = ec.numero_documento ? `(CC: ${ec.numero_documento})` : '';
    document.getElementById('reprog_paciente_nombre').innerText = `${nom} ${doc}`.trim();
    document.getElementById('reprog_med_nombre').innerText = `Periodo ${ec.periodo_numero || 1} de ${ec.total_periodos || 1}: ${ec.medicamento_nombre || ''}`;
    document.getElementById('reprog_posologia_txt').innerText = ec.posologia || 'Posología según fórmula médica';
    document.getElementById('reprog_cant_badge').innerText = `Cantidad Pendiente: ${ec.cantidad_periodo || 0} Unidades`;
    document.getElementById('reprog_fecha_input').value = ec.fecha_programada || '';
    document.getElementById('reprog_modalidad_sel').value = ec.modalidad || 'DOMICILIO';
    document.getElementById('reprog_dir_input').value = ec.direccion_entrega || ec.direccion_residencia || '';
    document.getElementById('reprog_tel_input').value = ec.telefono_contacto || ec.telefono || ec.numero_celular || ec.celular || '';
    document.getElementById('reprog_obs_input').value = ec.observaciones || '';

    toggleDireccionDomicilio(ec.modalidad || 'DOMICILIO');
    new bootstrap.Modal(document.getElementById('modalReprogramarEntregaCronica')).show();
}

function toggleDireccionDomicilio(modalidad) {
    var blk = document.getElementById('bloque_datos_domicilio');
    if (blk) {
        if (modalidad === 'PRESENCIAL') {
            blk.style.opacity = '0.5';
        } else {
            blk.style.opacity = '1';
        }
    }
}

function abrirModalCronicoDomicilio(ec) {
    document.getElementById('modal_cronico_id').value = ec.id || '';
    var nom = (ec.nombres ? (ec.nombres + ' ' + (ec.apellidos || '')) : (ec.paciente_nombre || 'Paciente')).trim();
    var doc = ec.numero_documento ? `(CC: ${ec.numero_documento})` : '';
    document.getElementById('modal_cronico_paciente').innerText = `${nom} ${doc}`.trim();
    document.getElementById('modal_cronico_med').innerText = `Periodo ${ec.periodo_numero || 1}/${ec.total_periodos || 1}: ${ec.medicamento_nombre || ''} (${ec.cantidad_periodo || 0} un.)`;
    document.getElementById('modal_cronico_dir').innerText = `Dirección: ${ec.direccion_entrega || ec.direccion_residencia || 'Sin dirección'} | Tel: ${ec.telefono_contacto || ec.telefono || ec.numero_celular || ec.celular || 'Sin Tel.'}`;
    document.getElementById('modal_cronico_mensajeria').value = ec.empresa_mensajeria || '';
    document.getElementById('modal_cronico_guia').value = ec.guia_domicilio || '';
    document.getElementById('modal_cronico_obs').value = ec.observaciones || '';
    
    new bootstrap.Modal(document.getElementById('modalCronicoDomicilio')).show();
}

function editarDomicilio(p) {
    document.getElementById('modal_p_id').value = p.id || '';
    var nom = (p.nombres ? (p.nombres + ' ' + (p.apellidos || '')) : (p.paciente_nombre || 'Paciente')).trim();
    var doc = p.numero_documento ? `(CC: ${p.numero_documento})` : '';
    document.getElementById('modal_paciente_nombre').innerText = `${nom} ${doc}`.trim();
    document.getElementById('modal_paciente_dir').innerText = `Dir: ${p.direccion_residencia || p.direccion_entrega || 'Sin dirección'} | Tel: ${p.telefono || p.numero_celular || p.celular || 'Sin Tel.'}`;
    document.getElementById('modal_med_nombre').innerText = `Faltante: ${p.cantidad_pendiente || 0} un. de ${p.nombre_medicamento || p.nombre_generico || 'Medicamento'} ${p.concentracion || ''}`;
    document.getElementById('modal_estado_domicilio').value = p.estado_domicilio || 'PENDIENTE_ALISTAR';
    document.getElementById('modal_mensajeria').value = p.empresa_mensajeria || '';
    document.getElementById('modal_guia').value = p.guia_domicilio || '';
    document.getElementById('modal_observaciones').value = p.observaciones || '';

    new bootstrap.Modal(document.getElementById('modalDomicilio')).show();
}
</script>
@endsection
