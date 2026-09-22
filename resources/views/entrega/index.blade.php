@extends('layouts.app')

@section('titulo', 'Entrega & Facturación - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-2">

    <!-- CABECERA PRINCIPAL Y SELECTOR DE VENTANILLA -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
                    <i class="fa-solid fa-bullhorn fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4 d-flex align-items-center gap-2 flex-wrap">
                        <span>Puesto de Entrega & Llamador a TV</span>
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 fs-6 fw-bold px-3 py-1 rounded-pill">
                            <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ sesion('active_sede_nombre') ?? 'Sede Principal' }}
                        </span>
                    </h3>
                    <p class="text-muted small mb-0 mt-1">
                        Digita la cédula o escanea el tiquete para llamar al paciente por voz y pantalla al Turnero TV 2.
                    </p>
                </div>
            </div>
            <!-- Input oculto para mantener compatibilidad en JS -->
            <input type="hidden" id="select-mi-modulo" value="VENTANILLA">
        </div>
    </div>

    <!-- TARJETAS DE INDICADORES RÁPIDOS (KPIS) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Llamados Hoy</div>
                        <h3 class="fw-bold text-dark mb-0 mt-1" id="kpi-total-llamados">{{ $statsIniciales['total_llamados'] }}</h3>
                        <small class="text-muted">Pacientes llamados a ventanillas</small>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle fs-4">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-success small fw-bold text-uppercase">En Ventanilla Ahora (TV 2)</div>
                        <h3 class="fw-bold text-success mb-0 mt-1 d-flex align-items-center gap-2">
                            <span id="kpi-en-ventanilla">{{ $statsIniciales['en_ventanilla'] }}</span>
                            
@if ($statsIniciales['en_ventanilla'] > 0)

                                <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            
@endif

                        </h3>
                        <small class="text-muted">Activos en pantalla recibiendo medicamentos</small>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle fs-4">
                        <i class="fa-solid fa-tv"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Salidas Registradas</div>
                        <h3 class="fw-bold text-dark mb-0 mt-1" id="kpi-completados">{{ $statsIniciales['completados'] }}</h3>
                        <small class="text-muted">Cerrados en puerta con tiempo SLA</small>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle fs-4">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CAJA PRINCIPAL DE BÚSQUEDA / ESCANEO DE CÓDIGO DE BARRAS -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-body p-4">
            <form id="formBuscarEntrega" method="GET" action="{{ route('entrega.index') }}" onsubmit="ejecutarBusquedaEntrega(event)">
                <input type="hidden" name="page" value="entrega">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-9 col-md-8">
                        <label for="inputBuscarEntrega" class="form-label fw-bold text-dark fs-6 mb-2 d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-barcode text-primary me-2"></i> Escanear Tiquete o Digitar Cédula del Paciente:</span>
                            <span class="badge bg-light text-muted border small d-none d-sm-inline"><i class="fa-solid fa-keyboard me-1"></i> Presiona Enter para buscar</span>
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border border-primary">
                            <span class="input-group-text bg-primary text-white border-0 px-3">
                                <i class="fa-solid fa-magnifying-glass fs-5"></i>
                            </span>
                            <input type="text" 
                                   name="query"
                                   id="inputBuscarEntrega" 
                                   class="form-control border-0 fw-bold fs-5 shadow-none px-3" 
                                   value="{{ $busquedaTermino }}"
                                   placeholder="Ej: 1036780004  o  TK-260902-0012" 
                                   autocomplete="off" 
                                   autofocus>
                            <button type="button" class="btn btn-light border-0 text-muted px-3" id="btnLimpiarBusqueda" onclick="limpiarBusqueda()" title="Limpiar búsqueda">
                                <i class="fa-solid fa-xmark fs-5"></i>
                            </button>
                            <button type="submit" class="btn btn-primary fw-bold px-4 fs-6" id="btnSubmitBuscar">
                                <i class="fa-solid fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 text-center text-md-start">
                        <div class="p-3 bg-light rounded-4 border text-muted small mt-md-4 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-bolt text-warning fs-3 flex-shrink-0"></i>
                            <div>
                                <strong>Operación Rápida:</strong><br>
                                Con el paquete en mano, digita la CC y pulsa <strong>Llamar a TV</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- CONTENEDOR DE RESULTADO DEL PACIENTE ENCONTRADO (DINÁMICO) -->
    <div id="contenedorResultadoEntrega" class="mb-4 {{ empty($pacientesBuscados) ? 'd-none' : '' }}">
        
@if (!empty($pacientesBuscados))

            @foreach ($pacientesBuscados as $row)
@php
$esPreferencial = (!empty($row['prioridad']) && $row['prioridad'] !== 'NORMAL');
$estaEntregado  = ($row['estado_tramite'] === 'ENTREGADO' || !empty($row['fecha_salida']));
$estaLlamado    = ($row['estado_tramite'] === 'EN_ENTREGA');
$bgHeader       = $estaEntregado ? 'bg-secondary bg-gradient' : ($estaLlamado ? 'bg-success bg-gradient' : 'bg-primary bg-gradient');
@endphp

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-3 bg-white" id="card-orden-{{ $row['id'] }}">
                <!-- Header de la Tarjeta con Gradiente -->
                <div class="p-3 px-4 d-flex flex-wrap justify-content-between align-items-center {{ $bgHeader }} text-white" id="card-header-{{ $row['id'] }}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="bg-white text-dark fw-bold font-monospace px-3 py-1 rounded-pill shadow-sm fs-5 d-inline-flex align-items-center">
                            <i class="fa-solid fa-receipt text-primary me-2"></i> {{ $row['ticket_numero'] }}
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold shadow-sm">
                            <i class="fa-solid fa-building text-warning me-1"></i> {{ $row['nombre_sede'] ?? 'Sede Principal' }}
                        </span>
                        
@if ($esPreferencial)

                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                <i class="fa-solid fa-star me-1"></i> Atención Preferencial
                            </span>
                        
@endif

                        <span class="badge bg-black bg-opacity-25 rounded-pill px-3 py-2 small">
                            <i class="fa-solid fa-clock me-1"></i> Ingreso: {{ date('h:i A', strtotime($row['fecha_ingreso'] ?? $row['created_at'])) }}
                        </span>
                    </div>

                    <div id="badge-estado-{{ $row['id'] }}">
                        
@if ($estaEntregado)

                            <span class="badge bg-dark text-white px-3 py-2 rounded-pill shadow-sm fw-bold">
                                <i class="fa-solid fa-circle-check text-success me-1"></i> Salida Confirmada en Puerta
                            </span>
                        
@php
elseif ($estaLlamado):
@endphp

                            <span class="badge bg-white text-success px-3 py-2 rounded-pill shadow-sm fw-bold fs-6">
                                <i class="fa-solid fa-bullhorn me-1"></i> EN PANTALLA TV - LLAMADO EN {{ $row['modulo_entrega_asignado'] ?? 'VENTANILLA' }}
                            </span>
                        
@else

                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm fw-bold">
                                <i class="fa-solid fa-box-open me-1"></i> Listo para Llamar a Pantalla
                            </span>
                        
@endif

                    </div>
                </div>

                <!-- Cuerpo de Información del Paciente -->
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-none d-sm-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-user-check fs-3"></i>
                                </div>
                                <div>
                                    <div class="text-uppercase text-muted fw-bold small mb-1">Paciente a Recibir Medicamentos:</div>
                                    <h3 class="fw-bold text-dark mb-2 fs-4">
                                        {{ $row['nombres'] . ' ' . $row['apellidos'] }}
                                    </h3>
                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                        <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-bold">
                                             <i class="fa-solid fa-id-card text-primary me-1"></i> {{ ($row['tipo_documento'] ?? 'CC') . ' ' . $row['numero_documento'] }}
                                        </span>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 fs-6 fw-bold">
                                            <i class="fa-solid fa-hospital me-1"></i> {{ $row['eps_nombre'] ?? 'Savia Salud' }}
                                        </span>
                                        
@if (!empty($row['telefono']))

                                            <span class="badge bg-light text-muted border px-2 py-2 small">
                                                <i class="fa-solid fa-phone text-success me-1"></i> {{ $row['telefono'] }}
                                            </span>
                                        
@endif

                                    </div>
                                    
@if (!empty($row['documentos']))

                                        <div class="d-flex flex-wrap gap-1 mt-2 align-items-center">
                                            <span class="text-muted small fw-bold me-1"><i class="fa-solid fa-paperclip"></i> Soportes:</span>
                                            
@foreach ($row['documentos'] as $doc)

                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small shadow-sm" onclick="abrirVisorDocumento('{{ $doc['ruta_archivo'] }}', '{{ $doc['tipo_documento'] }}')">
                                                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> {{ $doc['tipo_documento'] }}
                                                </button>
                                            
@endforeach

                                        </div>
                                    
@endif

                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="col-lg-5 text-center text-lg-end d-flex flex-column gap-2" id="acciones-orden-{{ $row['id'] }}">
                            
@if ($estaEntregado)

                                <a href="{{ route('entrega.acta', $row['id']) }}" target="_blank" class="btn btn-info btn-lg fw-bold text-white px-4 py-3 shadow rounded-4 w-100 fs-5 hover-scale">
                                    <i class="fa-solid fa-file-pdf me-2"></i> 📄 Ver / Imprimir Acta Firmada
                                </a>
                            
@php
elseif ($estaLlamado):
@endphp

                                <button type="button" 
                                        class="btn btn-success btn-lg fw-bold text-white px-4 py-3 shadow rounded-4 w-100 fs-5 hover-scale"
                                        id="btn-entregar-card-{{ $row['id'] }}"
                                        onclick="abrirModalEntregaFirma({{ $row['id'] }}, '{{ $row['ticket_numero'] }}', '{{ addslashes($row['nombres'] . ' ' . $row['apellidos']) }}', '{{ ($row['tipo_documento'] ?? 'CC') . ' ' . $row['numero_documento'] }}', '{{ addslashes($row['eps_nombre'] ?? '') }}')">
                                    <i class="fa-solid fa-signature me-2"></i> ✍️ REALIZAR ENTREGA & FIRMAR ACTA
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-warning text-dark fw-bold btn-md px-4 py-2 shadow-sm rounded-4 w-100"
                                        id="btn-rellamar-card-{{ $row['id'] }}"
                                        onclick="rellamarPacienteTV({{ $row['id'] }}, '{{ $row['ticket_numero'] }}', '{{ addslashes($row['nombres'] . ' ' . $row['apellidos']) }}')">
                                    <i class="fa-solid fa-bell me-1 text-danger"></i> 🔔 RE-LLAMAR A PANTALLA TV
                                </button>
                            
@else

                                <button type="button" 
                                        class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2 shadow-sm rounded-4 w-100 fs-5 hover-scale"
                                        id="btn-llamar-{{ $row['id'] }}"
                                        onclick="abrirModalConfirmarTV({{ $row['id'] }}, '{{ $row['ticket_numero'] }}', '{{ addslashes($row['nombres'] . ' ' . $row['apellidos']) }}', '{{ ($row['tipo_documento'] ?? 'CC') . ' ' . $row['numero_documento'] }}')">
                                    <i class="fa-solid fa-bullhorn me-1 text-danger"></i> 📢 LLAMAR A PANTALLA TV
                                </button>
                                <button type="button" 
                                        class="btn btn-success btn-lg fw-bold text-white px-4 py-2 shadow-sm rounded-4 w-100 fs-5 hover-scale"
                                        id="btn-entregar-card-{{ $row['id'] }}"
                                        onclick="abrirModalEntregaFirma({{ $row['id'] }}, '{{ $row['ticket_numero'] }}', '{{ addslashes($row['nombres'] . ' ' . $row['apellidos']) }}', '{{ ($row['tipo_documento'] ?? 'CC') . ' ' . $row['numero_documento'] }}', '{{ addslashes($row['eps_nombre'] ?? '') }}')">
                                    <i class="fa-solid fa-signature me-1"></i> ✍️ ENTREGA DIRECTA & ACTA
                                </button>
                            
@endif

                        </div>
                    </div>
                </div>
            </div>
            
@endforeach

        @endif

    </div>

    <!-- SECCIÓN: LISTA BÁSICA DE PACIENTES EN PANTALLA TV (EN ESPERA CON BOTÓN RELLAMAR) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4 border-top border-4 border-success">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-tv fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0 fs-5">
                        Pacientes en Pantalla TV <span class="text-muted fs-6 fw-normal">(En Espera en Sala)</span>
                    </h5>
                    <small class="text-muted">Pacientes activos proyectados en el TV de entrega esperando ser atendidos</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-pill px-3 py-2 fw-bold fs-6" id="badge-total-tv">
                    <i class="fa-solid fa-users me-1"></i> <span id="contador-total-tv">{{ count($pacientesEnTV) }}</span> en TV
                </span>
                <button type="button" class="btn btn-sm btn-outline-success rounded-circle" onclick="refrescarHistorialLlamados(true)" title="Actualizar lista de pantalla TV">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaPacientesTV">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4" style="width: 170px;">Tiquete</th>
                        <th>Paciente / Documento</th>
                        <th>EPS</th>
                        <th>Hora de Llamado</th>
                        <th class="text-center" style="width: 290px;">Acciones en Ventanilla</th>
                    </tr>
                </thead>
                <tbody id="tbodyPacientesTV">
                    
@if (empty($pacientesEnTV))

                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-tv fs-2 mb-2 d-block text-secondary opacity-40"></i>
                                <span class="fs-6">No hay pacientes activos en la pantalla TV en este momento.</span>
                            </td>
                        </tr>
                    
@else

                        @foreach ($pacientesEnTV as $ptv)
@php
$esPref = (!empty($ptv['prioridad']) && $ptv['prioridad'] !== 'NORMAL');
$esAltoCosto = (!empty($ptv['es_alto_costo']) && $ptv['es_alto_costo'] == 1);
$horaTxt = !empty($ptv['fecha_llamado_entrega']) ? date('h:i A', strtotime($ptv['fecha_llamado_entrega'])) : (!empty($ptv['updated_at']) ? date('h:i A', strtotime($ptv['updated_at'])) : '--:--');
@endphp

                        <tr class="item-fila-tv" id="fila-tv-{{ $ptv['id'] }}">
                            <td class="ps-4">
                                <span class="badge bg-black border border-success text-success font-monospace fs-6 px-3 py-2 fw-bold shadow-sm">
                                    <i class="fa-solid fa-receipt me-1 text-accent"></i> {{ $ptv['ticket_numero'] }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2 flex-wrap">
                                    <span>{{ $ptv['nombres'] . ' ' . $ptv['apellidos'] }}</span>
                                    
@if ($esPref)

                                        <span class="badge bg-warning text-dark px-2 py-1 small fw-bold"><i class="fa-solid fa-star me-1"></i> Preferencial</span>
                                    
@endif

                                    @if ($esAltoCosto)

                                        <span class="badge bg-danger text-white px-2 py-1 small fw-bold"><i class="fa-solid fa-fire me-1"></i> Alto Costo</span>
                                    
@endif

                                </div>
                                <small class="text-muted">
                                    <i class="fa-solid fa-id-card text-secondary me-1"></i> {{ ($ptv['tipo_documento'] ?? 'CC') . ' ' . $ptv['numero_documento'] }}
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 fw-semibold">
                                    {{ $ptv['eps_nombre'] ?? 'Savia Salud' }}
                                </span>
                            </td>
                            <td>
                                <div class="text-dark small fw-semibold">
                                    <i class="fa-solid fa-clock text-primary me-1"></i> {{ $horaTxt }}
                                </div>
                                
@if (!empty($ptv['contador_rellamados']) && $ptv['contador_rellamados'] > 0)

                                    <small class="text-warning fw-bold d-block mt-1">
                                        <i class="fa-solid fa-bullhorn me-1"></i> Rellamado {{ intval($ptv['contador_rellamados']) }} {{ intval($ptv['contador_rellamados']) === 1 ? 'vez' : 'veces' }}
                                    </small>
                                
@endif

                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <button type="button" 
                                            class="btn btn-warning text-dark fw-bold btn-sm px-2 px-md-3 py-2 rounded-pill shadow-sm hover-scale" 
                                            id="btn-rellamar-{{ $ptv['id'] }}" 
                                            onclick="rellamarPacienteTV({{ $ptv['id'] }}, '{{ $ptv['ticket_numero'] }}', '{{ addslashes($ptv['nombres'] . ' ' . $ptv['apellidos']) }}')"
                                            title="Rellamar paciente por voz y TV">
                                        <i class="fa-solid fa-bell me-1 text-danger"></i> 🔔 Rellamar
                                    </button>
                                    <button type="button" 
                                            class="btn btn-success text-white fw-bold btn-sm px-2 px-md-3 py-2 rounded-pill shadow-sm hover-scale" 
                                            id="btn-entregar-tv-{{ $ptv['id'] }}" 
                                            onclick="abrirModalEntregaFirma({{ $ptv['id'] }}, '{{ $ptv['ticket_numero'] }}', '{{ addslashes($ptv['nombres'] . ' ' . $ptv['apellidos']) }}', '{{ ($ptv['tipo_documento'] ?? 'CC') . ' ' . $ptv['numero_documento'] }}', '{{ addslashes($ptv['eps_nombre'] ?? '') }}')"
                                            title="Realizar entrega física con firma digital y generar acta oficial">
                                        <i class="fa-solid fa-signature me-1"></i> ✍️ Entregar & Firmar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN: GRILLA MODERNA DE PACIENTES LLAMADOS HOY (OCULTA PARA VISTA LIMPIA) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden d-none">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold text-dark mb-0 fs-5">
                    <i class="fa-solid fa-list-check text-primary me-2"></i> Pacientes Llamados a Ventanillas Hoy
                </h5>
                <span class="badge bg-primary rounded-pill px-3 py-1" id="badge-total-tabla">{{ count($historialHoy) }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="inputFiltroTabla" class="form-control form-control-sm border-secondary-subtle" placeholder="Filtrar en esta tabla..." oninput="filtrarTablaHistorial()" style="max-width: 200px;">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refrescarHistorialLlamados(true)" title="Actualizar datos">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaHistorialLlamados">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4" style="width: 140px;">Tiquete</th>
                        <th>Paciente / Documento</th>
                        <th>EPS</th>
                        <th>Prioridad</th>
                        <th>Ventanilla</th>
                        <th>Hora Llamado</th>
                        <th>Estado</th>
                        <th class="text-end pe-4" style="width: 160px;">Acción Rápida</th>
                    </tr>
                </thead>
                <tbody id="tbodyHistorialLlamados">
                    
@if (empty($historialHoy))

                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fs-2 mb-2 d-block text-secondary opacity-50"></i>
                                Aún no se han llamado pacientes a ventanillas el día de hoy.
                            </td>
                        </tr>
                    
@else

                        @foreach ($historialHoy as $h)
@php
$esPref = (!empty($h['prioridad']) && $h['prioridad'] !== 'NORMAL');
$enVent = ($h['estado_tramite'] === 'EN_ENTREGA');
$cerrado = (!empty($h['fecha_salida']) || $h['estado_tramite'] === 'ENTREGADO');
@endphp

                        <tr class="item-fila-llamado">
                            <td class="ps-4">
                                <span class="fw-bold font-monospace text-primary fs-6">
                                    <i class="fa-solid fa-receipt me-1"></i> {{ $h['ticket_numero'] }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $h['nombres'] . ' ' . $h['apellidos'] }}</div>
                                <small class="text-muted">{{ ($h['tipo_documento'] ?? 'CC') . ' ' . $h['numero_documento'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                                    {{ $h['eps_nombre'] ?? 'Savia Salud' }}
                                </span>
                            </td>
                            <td>
                                
@if ($esPref)

                                    <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-star me-1"></i> Preferencial</span>
                                
@else

                                    <span class="badge bg-light text-muted border">Normal</span>
                                
@endif

                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                    <i class="fa-solid fa-desktop text-primary me-1"></i> {{ $h['modulo_entrega_asignado'] ?? 'Ventanilla' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <i class="fa-solid fa-clock me-1"></i> {{ date('h:i A', strtotime($h['updated_at'] ?? $h['created_at'])) }}
                                </span>
                            </td>
                            <td>
                                
@if ($cerrado)

                                    <span class="badge bg-secondary bg-opacity-25 text-secondary border px-2 py-1">
                                        <i class="fa-solid fa-door-open text-info me-1"></i> Salida Registrada
                                    </span>
                                
@php
elseif ($enVent):
@endphp

                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold">
                                        <i class="fa-solid fa-circle-dot text-success me-1"></i> En Ventanilla (TV 2)
                                    </span>
                                
@else

                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                        Listo Entrega
                                    </span>
                                
@endif

                            </td>
                            <td class="text-end pe-4">
                                
@if (!$cerrado)

                                    <button type="button" class="btn btn-sm btn-outline-warning fw-bold shadow-sm" onclick="llamarTurnoATurnero({{ $h['id'] }}, '{{ $h['ticket_numero'] }}')">
                                        <i class="fa-solid fa-volume-high me-1"></i> Re-llamar
                                    </button>
                                
@else

                                    <span class="text-muted small"><i class="fa-solid fa-check text-success me-1"></i> Completado</span>
                                
@endif

                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL VISOR RÁPIDO DE DOCUMENTOS ESCANEADOS -->
<div class="modal fade" id="modalVisorDocEntrega" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content h-100 shadow-lg border-0 bg-dark text-white rounded-4 overflow-hidden">
            <div class="modal-header bg-dark py-2 border-secondary d-flex justify-content-between">
                <h6 class="modal-title fw-bold text-white mb-0" id="modalVisorDocTitulo">
                    <i class="fa-solid fa-file-pdf text-danger me-2"></i> Soporte de Fórmula Médica
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 flex-grow-1 bg-black position-relative">
                <iframe id="iframeVisorDocEntrega" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- TOAST FLOTANTE DE NOTIFICACIONES -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 999999;">
    <div id="toastSispamEntrega" class="toast align-items-center text-white bg-success border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fs-6 fw-bold d-flex align-items-center gap-2" id="toastSispamMsg">
                <i class="fa-solid fa-bullhorn fs-5"></i> <span>¡Paciente llamado a TV 2 con éxito!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ESTILOS Y SDK OFICIAL TOPAZ SIGLITE T-S460 (SIGWEB) -->
<style>
@keyframes topazPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.02); }
}
.animate-pulse {
    animation: topazPulse 1.8s infinite ease-in-out;
}
</style>
<script src="assets/js/SigWebTablet.js?v={{ time() }}"></script>

<script>
// Manejo del selector de Mi Ventanilla / Módulo con memoria en LocalStorage
const STORAGE_KEY_MODULO = 'sispam_mi_modulo_entrega';

function initMiModulo() {
    const select = document.getElementById('select-mi-modulo');
    if (!select) return;
    const guardado = localStorage.getItem(STORAGE_KEY_MODULO);
    if (guardado) {
        let found = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === guardado) {
                select.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) {
            select.value = guardado;
        }
    }
    actualizarLabelsModulo();
}

function guardarModuloLocal(valor) {
    localStorage.setItem(STORAGE_KEY_MODULO, valor);
    actualizarLabelsModulo();
}

function getMiModuloActual() {
    const select = document.getElementById('select-mi-modulo');
    return select ? select.value : 'VENTANILLA 1';
}

function actualizarLabelsModulo() {
    const mod = getMiModuloActual();
    document.querySelectorAll('.lbl-mi-modulo').forEach(el => {
        el.textContent = mod;
    });
}

function mostrarToast(msg, bgClass = 'bg-success') {
    const toastEl = document.getElementById('toastSispamEntrega');
    const msgEl = document.getElementById('toastSispamMsg');
    if (toastEl && msgEl) {
        toastEl.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg rounded-3`;
        msgEl.innerHTML = `<i class="fa-solid fa-circle-check fs-5 me-1"></i> ${msg}`;
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    }
}

// BÚSQUEDA AJAX INSTANTÁNEA
function ejecutarBusquedaEntrega(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('inputBuscarEntrega');
    const term = input ? input.value.trim() : '';
    if (!term) return;

    const btnSubmit = document.getElementById('btnSubmitBuscar');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Buscando...`;
    }

    fetch(`{{ route('entrega.index') }}?ajax_buscar=1&query=${encodeURIComponent(term)}`)
        .then(res => res.json())
        .then(res => {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<i class="fa-solid fa-search me-1"></i> Buscar`;
            }
            renderizarResultadoBusqueda(res.data, term);
        })
        .catch(err => {
            console.error("Error en búsqueda:", err);
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<i class="fa-solid fa-search me-1"></i> Buscar`;
            }
        });
}

function limpiarBusqueda() {
    const input = document.getElementById('inputBuscarEntrega');
    if (input) {
        input.value = '';
        input.focus();
    }
    const contenedor = document.getElementById('contenedorResultadoEntrega');
    if (contenedor) {
        contenedor.innerHTML = '';
        contenedor.classList.add('d-none');
    }
}

function renderizarResultadoBusqueda(lista, termino) {
    const contenedor = document.getElementById('contenedorResultadoEntrega');
    if (!contenedor) return;

    if (!lista || lista.length === 0) {
        contenedor.innerHTML = `
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <i class="fa-solid fa-circle-exclamation text-warning fs-1 mb-2"></i>
                <h5 class="fw-bold text-dark">No se encontró ningún ingreso para "${escapeHtml(termino)}"</h5>
                <p class="text-muted small mb-0">Verifique que el número de cédula o tiquete esté correcto y que haya ingresado hoy.</p>
            </div>
        `;
        contenedor.classList.remove('d-none');
        return;
    }

    let html = '';
    lista.forEach(row => {
        const esPref = (row.prioridad && row.prioridad !== 'NORMAL');
        const estaEntregado = (row.estado_tramite === 'ENTREGADO' || !!row.fecha_salida);
        const estaLlamado = (row.estado_tramite === 'EN_ENTREGA');
        const bgHeader = estaEntregado ? 'bg-secondary bg-gradient' : (estaLlamado ? 'bg-success bg-gradient' : 'bg-primary bg-gradient');

        let docsHtml = '';
        if (row.documentos && row.documentos.length > 0) {
            docsHtml = `<div class="d-flex flex-wrap gap-1 mt-2 align-items-center"><span class="text-muted small fw-bold me-1"><i class="fa-solid fa-paperclip"></i> Soportes:</span>`;
            row.documentos.forEach(d => {
                docsHtml += `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small shadow-sm" onclick="abrirVisorDocumento('${escapeHtml(d.ruta_archivo)}', '${escapeHtml(d.tipo_documento)}')"><i class="fa-solid fa-file-pdf text-danger me-1"></i> ${escapeHtml(d.tipo_documento)}</button>`;
            });
            docsHtml += `</div>`;
        }

        let badgeEstadoHtml = '';
        if (estaEntregado) {
            badgeEstadoHtml = `<span class="badge bg-dark text-white px-3 py-2 rounded-pill shadow-sm fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i> Salida Confirmada en Puerta</span>`;
        } else if (estaLlamado) {
            badgeEstadoHtml = `<span class="badge bg-white text-success px-3 py-2 rounded-pill shadow-sm fw-bold fs-6"><i class="fa-solid fa-bullhorn me-1"></i> EN PANTALLA TV - LLAMADO EN ${escapeHtml(row.modulo_entrega_asignado || 'VENTANILLA')}</span>`;
        } else {
            badgeEstadoHtml = `<span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm fw-bold"><i class="fa-solid fa-box-open me-1"></i> Listo para Llamar a Pantalla</span>`;
        }

        let botonesAccionHtml = '';
        if (estaEntregado) {
            botonesAccionHtml = `
                <a href="{{ url('entrega') }}/${row.id}/acta" target="_blank" class="btn btn-info btn-lg fw-bold text-white px-4 py-3 shadow rounded-4 w-100 fs-5 hover-scale">
                    <i class="fa-solid fa-file-pdf me-2"></i> 📄 Ver / Imprimir Acta Firmada
                </a>
            `;
        } else if (estaLlamado) {
            botonesAccionHtml = `
                <button type="button" 
                        class="btn btn-success btn-lg fw-bold text-white px-4 py-3 shadow rounded-4 w-100 fs-5 hover-scale" 
                        id="btn-entregar-card-${row.id}" 
                        onclick="abrirModalEntregaFirma(${row.id}, '${escapeHtml(row.ticket_numero)}', '${escapeHtml(row.nombres + ' ' + row.apellidos)}', '${escapeHtml((row.tipo_documento || 'CC') + ' ' + row.numero_documento)}', '${escapeHtml(row.eps_nombre || '')}')">
                    <i class="fa-solid fa-signature me-2"></i> ✍️ REALIZAR ENTREGA & FIRMAR ACTA
                </button>
                <button type="button" 
                        class="btn btn-outline-warning text-dark fw-bold btn-md px-4 py-2 shadow-sm rounded-4 w-100" 
                        id="btn-rellamar-card-${row.id}" 
                        onclick="rellamarPacienteTV(${row.id}, '${escapeHtml(row.ticket_numero)}', '${escapeHtml(row.nombres + ' ' + row.apellidos)}')">
                    <i class="fa-solid fa-bell me-1 text-danger"></i> 🔔 RE-LLAMAR A PANTALLA TV
                </button>
            `;
        } else {
            botonesAccionHtml = `
                <button type="button" 
                        class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2 shadow-sm rounded-4 w-100 fs-5 hover-scale" 
                        id="btn-llamar-${row.id}" 
                        onclick="abrirModalConfirmarTV(${row.id}, '${escapeHtml(row.ticket_numero)}', '${escapeHtml(row.nombres + ' ' + row.apellidos)}', '${escapeHtml((row.tipo_documento || 'CC') + ' ' + row.numero_documento)}')">
                    <i class="fa-solid fa-bullhorn me-1 text-danger"></i> 📢 LLAMAR A PANTALLA TV
                </button>
                <button type="button" 
                        class="btn btn-success btn-lg fw-bold text-white px-4 py-2 shadow-sm rounded-4 w-100 fs-5 hover-scale" 
                        id="btn-entregar-card-${row.id}" 
                        onclick="abrirModalEntregaFirma(${row.id}, '${escapeHtml(row.ticket_numero)}', '${escapeHtml(row.nombres + ' ' + row.apellidos)}', '${escapeHtml((row.tipo_documento || 'CC') + ' ' + row.numero_documento)}', '${escapeHtml(row.eps_nombre || '')}')">
                    <i class="fa-solid fa-signature me-1"></i> ✍️ ENTREGA DIRECTA & ACTA
                </button>
            `;
        }

        html += `
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-3 bg-white" id="card-orden-${row.id}">
                <div class="p-3 px-4 d-flex flex-wrap justify-content-between align-items-center ${bgHeader} text-white" id="card-header-${row.id}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="bg-white text-dark fw-bold font-monospace px-3 py-1 rounded-pill shadow-sm fs-5 d-inline-flex align-items-center">
                            <i class="fa-solid fa-receipt text-primary me-2"></i> ${escapeHtml(row.ticket_numero)}
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold shadow-sm">
                            <i class="fa-solid fa-building text-warning me-1"></i> ${escapeHtml(row.nombre_sede || 'Sede Principal')}
                        </span>
                        ${esPref ? '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm"><i class="fa-solid fa-star me-1"></i> Atención Preferencial</span>' : ''}
                        <span class="badge bg-black bg-opacity-25 rounded-pill px-3 py-2 small">
                            <i class="fa-solid fa-clock me-1"></i> Ingreso: ${row.fecha_ingreso ? row.fecha_ingreso.split(' ')[1].substring(0,5) : ''}
                        </span>
                    </div>
                    <div id="badge-estado-${row.id}">
                        ${badgeEstadoHtml}
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-none d-sm-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-user-check fs-3"></i>
                                </div>
                                <div>
                                    <div class="text-uppercase text-muted fw-bold small mb-1">Paciente a Recibir Medicamentos:</div>
                                    <h3 class="fw-bold text-dark mb-2 fs-4">${escapeHtml(row.nombres + ' ' + row.apellidos)}</h3>
                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                        <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-bold">
                                            <i class="fa-solid fa-id-card text-primary me-1"></i> ${escapeHtml((row.tipo_documento || 'CC') + ' ' + row.numero_documento)}
                                        </span>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 fs-6 fw-bold">
                                            <i class="fa-solid fa-hospital me-1"></i> ${escapeHtml(row.eps_nombre || 'Savia Salud')}
                                        </span>
                                        ${row.telefono ? `<span class="badge bg-light text-muted border px-2 py-2 small"><i class="fa-solid fa-phone text-success me-1"></i> ${escapeHtml(row.telefono)}</span>` : ''}
                                    </div>
                                    ${docsHtml}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 text-center text-lg-end d-flex flex-column gap-2" id="acciones-orden-${row.id}">
                            ${botonesAccionHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    contenedor.innerHTML = html;
    contenedor.classList.remove('d-none');
}


let turnoPendienteId = null;
let turnoPendienteTicket = '';

function abrirModalConfirmarTV(id, ticket, nombreCompleto, doc) {
    turnoPendienteId = id;
    turnoPendienteTicket = ticket;
    
    const nomEl = document.getElementById('modalConfirmarNombrePaciente');
    const docEl = document.getElementById('modalConfirmarDocPaciente');
    if (nomEl) nomEl.textContent = nombreCompleto || 'Paciente';
    if (docEl) docEl.innerHTML = `Documento: <strong>${escapeHtml(doc || '')}</strong> | Tiquete: <strong class="text-primary font-monospace">${escapeHtml(ticket || '')}</strong>`;
    
    const modalEl = document.getElementById('modalConfirmarPasoTV');
    if (modalEl) {
        const modalObj = new bootstrap.Modal(modalEl);
        modalObj.show();
    }
}

function ejecutarPasoATV() {
    if (!turnoPendienteId) return;
    const btn = document.getElementById('btnModalAceptarPasarTV');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Enviando...`;
    }
    
    // Cerrar modal de confirmación
    const modalEl = document.getElementById('modalConfirmarPasoTV');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
    
    llamarTurnoATurnero(turnoPendienteId, turnoPendienteTicket, () => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Aceptar y Pasar al TV`;
        }
    });
}

// LLAMADO DIRECTO A TURNERO 2
function llamarTurnoATurnero(id, ticket, callback = null) {
    const miModulo = getMiModuloActual();
    const btnLlamar = document.getElementById(`btn-llamar-${id}`);
    if (btnLlamar) {
        btnLlamar.disabled = true;
        btnLlamar.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Llamando...`;
    }

    const formData = new FormData();
    formData.append('action', 'llamar_turno_entrega');
    formData.append('ingreso_id', id);
    formData.append('modulo_nombre', miModulo);

    fetch('{{ route('entrega.index') }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            mostrarToast(`¡Tiquete <strong>${escapeHtml(ticket)}</strong> remitido a la lista del Turnero 2 TV!`);
            
            // Desplegar Modal Informativo de confirmación
            const ticketEl = document.getElementById('modalConfirmacionTicket');
            const msgEl = document.getElementById('modalConfirmacionMensaje');
            if (ticketEl) ticketEl.innerHTML = `Tiquete: <span class="text-primary font-monospace">${escapeHtml(ticket)}</span>`;
            if (msgEl) msgEl.innerHTML = `El paciente ha sido remitido exitosamente a la lista del <strong>Turnero 2 TV</strong>.`;
            const modalEl = document.getElementById('modalConfirmacionTurnero2');
            if (modalEl) {
                const modalObj = new bootstrap.Modal(modalEl);
                modalObj.show();
            }
            if (typeof callback === 'function') callback();
            
            // Limpiar caja de búsqueda y mostrar tarjeta limpia de confirmación
            const input = document.getElementById('inputBuscarEntrega');
            if (input) input.value = '';

            const contenedor = document.getElementById('contenedorResultadoEntrega');
            if (contenedor) {
                contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white border-start border-4 border-success">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-circle-check fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-success mb-1">¡Paciente Enviado al Turnero 2 TV!</h4>
                        <p class="text-muted fs-6 mb-0">
                            El tiquete <strong class="text-primary font-monospace">${escapeHtml(ticket)}</strong> ha sido remitido exitosamente a la lista del Turnero 2 TV.
                        </p>
                    </div>
                `;
                contenedor.classList.remove('d-none');
            }

            // Actualizar tabla inferior de inmediato
            refrescarHistorialLlamados();
        } else {
            alert(data.message || 'Error al realizar el llamado.');
            if (btnLlamar) {
                btnLlamar.disabled = false;
                btnLlamar.innerHTML = `<i class="fa-solid fa-bullhorn me-2"></i> 📢 LLAMAR A PANTALLA TV`;
            }
        }
    })
    .catch(err => {
        console.error("Error al llamar:", err);
        alert("Error de conexión al enviar el llamado al Turnero 2.");
        if (btnLlamar) {
            btnLlamar.disabled = false;
            btnLlamar.innerHTML = `<i class="fa-solid fa-bullhorn me-2"></i> 📢 LLAMAR A PANTALLA TV`;
        }
    });
}

// RELLAMAR PACIENTE ACTIVO EN PANTALLA TV (SOLO PITIDO + MODAL INTERMITENTE EN TV)
function rellamarPacienteTV(id, ticket, nombreCompleto) {
    const btn = document.getElementById(`btn-rellamar-${id}`);
    const originalHtml = btn ? btn.innerHTML : '<i class="fa-solid fa-bell me-1 text-danger"></i> 🔔 Rellamar';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-dark"></span> Rellamando...`;
    }

    const formData = new FormData();
    formData.append('action', 'rellamar_paciente_tv');
    formData.append('ingreso_id', id);

    fetch('{{ route('entrega.index') }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            mostrarToast(`¡Alerta sonora y modal de rellamado emitidos en Pantalla TV para <strong>${escapeHtml(nombreCompleto || ticket)}</strong>!`, 'bg-warning text-dark');
            if (btn) {
                btn.className = 'btn btn-success text-white fw-bold btn-sm px-3 py-2 rounded-pill shadow-sm w-100';
                btn.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> ¡Rellamado!`;
                setTimeout(() => {
                    if (btn) {
                        btn.className = 'btn btn-warning text-dark fw-bold btn-sm px-3 py-2 rounded-pill shadow-sm hover-scale w-100';
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                }, 3000);
            }
            refrescarHistorialLlamados();
        } else {
            alert(data.message || 'Error al emitir el rellamado.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    })
    .catch(err => {
        console.error("Error al rellamar:", err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

let cierrePendienteId = null;
let cierrePendienteTicket = '';
let cierrePendienteNombre = '';

// ABRIR MODAL BOOTSTRAP PARA CONFIRMAR CIERRE Y SALIDA
function cerrarEntregaPaciente(id, ticket, nombreCompleto) {
    cierrePendienteId = id;
    cierrePendienteTicket = ticket;
    cierrePendienteNombre = nombreCompleto;

    const nomEl = document.getElementById('modalCierreNombrePaciente');
    const tckEl = document.getElementById('modalCierreTicketPaciente');
    if (nomEl) nomEl.textContent = nombreCompleto || 'Paciente';
    if (tckEl) tckEl.textContent = ticket || '--';

    const modalEl = document.getElementById('modalConfirmarCierreEntrega');
    if (modalEl) {
        const modalObj = new bootstrap.Modal(modalEl);
        modalObj.show();
    }
}

// EJECUTAR CIERRE FÍSICO DESDE EL MODAL BOOTSTRAP
function ejecutarCierreEntregaConfirmado() {
    if (!cierrePendienteId) return;

    const btn = document.getElementById('btnModalAceptarCierre');
    const id = cierrePendienteId;
    const ticket = cierrePendienteTicket;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Procesando Cierre...`;
    }

    const formData = new FormData();
    formData.append('action', 'cerrar_entrega_ajax');
    formData.append('ingreso_id', id);
    formData.append('observacion', 'Entrega física y cierre completado en ventanilla.');

    fetch('{{ route('entrega.index') }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Sí, Entregar y Cerrar`;
        }

        // Cerrar modal
        const modalEl = document.getElementById('modalConfirmarCierreEntrega');
        if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        }

        if (data.status === 'ok') {
            mostrarToast(`¡Tiquete <strong>${escapeHtml(ticket)}</strong> entregado y cerrado! Tiempo SLA: <strong>${data.sla_min} min</strong>.`, 'bg-success text-white');
            refrescarHistorialLlamados();
        } else {
            mostrarToast(data.message || 'No fue posible registrar el cierre de la entrega.', 'bg-danger text-white');
        }
    })
    .catch(err => {
        console.error("Error al cerrar entrega:", err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Sí, Entregar y Cerrar`;
        }
        mostrarToast("Error de conexión al procesar el cierre.", 'bg-danger text-white');
    });
}

// ACTUALIZACIÓN PERIÓDICA DEL HISTORIAL Y KPIS
function refrescarHistorialLlamados(manual = false) {
    fetch('{{ route('entrega.index') }}?ajax_get_llamados_hoy=1')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'ok') {
                if (res.pacientes_tv !== undefined) {
                    renderizarTablaPacientesTV(res.pacientes_tv);
                    const kpiVent = document.getElementById('kpi-en-ventanilla');
                    if (kpiVent) kpiVent.textContent = res.pacientes_tv.length;
                }
                if (res.stats) {
                    const kpiTot = document.getElementById('kpi-total-llamados');
                    const kpiComp = document.getElementById('kpi-completados');
                    if (kpiTot) kpiTot.textContent = res.stats.total_llamados;
                    if (kpiComp) kpiComp.textContent = res.stats.completados;
                }
                renderizarTablaHistorial(res.historial);
                if (manual) mostrarToast('Historial y pantalla TV actualizados.', 'bg-primary');
            }
        })
        .catch(err => console.error("Error al refrescar historial:", err));
}

function renderizarTablaPacientesTV(lista) {
    const tbody = document.getElementById('tbodyPacientesTV');
    const badgeTot = document.getElementById('contador-total-tv');
    if (!tbody) return;

    if (badgeTot) badgeTot.textContent = lista ? lista.length : 0;

    if (!lista || lista.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fa-solid fa-tv fs-2 mb-2 d-block text-secondary opacity-40"></i>
                    <span class="fs-6">No hay pacientes activos en la pantalla TV en este momento.</span>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    lista.forEach(ptv => {
        const esPref = (ptv.prioridad && ptv.prioridad !== 'NORMAL');
        const esAltoCosto = (ptv.es_alto_costo == 1);
        let horaTxt = ptv.fecha_llamado_entrega ? ptv.fecha_llamado_entrega.split(' ')[1].substring(0, 5) : (ptv.updated_at ? ptv.updated_at.split(' ')[1].substring(0, 5) : '--:--');

        html += `
            <tr class="item-fila-tv" id="fila-tv-${ptv.id}">
                <td class="ps-4">
                    <span class="badge bg-black border border-success text-success font-monospace fs-6 px-3 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-receipt me-1 text-accent"></i> ${escapeHtml(ptv.ticket_numero)}
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2 flex-wrap">
                        <span>${escapeHtml(ptv.nombres + ' ' + ptv.apellidos)}</span>
                        ${esPref ? '<span class="badge bg-warning text-dark px-2 py-1 small fw-bold"><i class="fa-solid fa-star me-1"></i> Preferencial</span>' : ''}
                        ${esAltoCosto ? '<span class="badge bg-danger text-white px-2 py-1 small fw-bold"><i class="fa-solid fa-fire me-1"></i> Alto Costo</span>' : ''}
                    </div>
                    <small class="text-muted">
                        <i class="fa-solid fa-id-card text-secondary me-1"></i> ${escapeHtml((ptv.tipo_documento || 'CC') + ' ' + ptv.numero_documento)}
                    </small>
                </td>
                <td>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 fw-semibold">
                        ${escapeHtml(ptv.eps_nombre || 'Savia Salud')}
                    </span>
                </td>
                <td>
                    <div class="text-dark small fw-semibold">
                        <i class="fa-solid fa-clock text-primary me-1"></i> ${horaTxt}
                    </div>
                    ${ptv.contador_rellamados && ptv.contador_rellamados > 0 ? `<small class="text-warning fw-bold d-block mt-1"><i class="fa-solid fa-bullhorn me-1"></i> Rellamado ${ptv.contador_rellamados} ${ptv.contador_rellamados == 1 ? 'vez' : 'veces'}</small>` : ''}
                </td>
                <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <button type="button" 
                                class="btn btn-warning text-dark fw-bold btn-sm px-2 px-md-3 py-2 rounded-pill shadow-sm hover-scale" 
                                id="btn-rellamar-${ptv.id}" 
                                onclick="rellamarPacienteTV(${ptv.id}, '${escapeHtml(ptv.ticket_numero)}', '${escapeHtml(ptv.nombres + ' ' + ptv.apellidos)}')"
                                title="Rellamar paciente por voz y TV">
                            <i class="fa-solid fa-bell me-1 text-danger"></i> 🔔 Rellamar
                        </button>
                        <button type="button" 
                                class="btn btn-success text-white fw-bold btn-sm px-2 px-md-3 py-2 rounded-pill shadow-sm hover-scale" 
                                id="btn-entregar-tv-${ptv.id}" 
                                onclick="abrirModalEntregaFirma(${ptv.id}, '${escapeHtml(ptv.ticket_numero)}', '${escapeHtml(ptv.nombres + ' ' + ptv.apellidos)}', '${escapeHtml((ptv.tipo_documento || 'CC') + ' ' + ptv.numero_documento)}', '${escapeHtml(ptv.eps_nombre || '')}')"
                                title="Realizar entrega física con firma digital y generar acta oficial">
                            <i class="fa-solid fa-signature me-1"></i> ✍️ Entregar & Firmar
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderizarTablaHistorial(lista) {
    const tbody = document.getElementById('tbodyHistorialLlamados');
    const badgeTot = document.getElementById('badge-total-tabla');
    if (!tbody) return;

    if (badgeTot) badgeTot.textContent = lista ? lista.length : 0;

    if (!lista || lista.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-inbox fs-2 mb-2 d-block text-secondary opacity-50"></i>
                    Aún no se han llamado pacientes a ventanillas el día de hoy.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    lista.forEach(h => {
        const esPref = (h.prioridad && h.prioridad !== 'NORMAL');
        const enVent = (h.estado_tramite === 'EN_ENTREGA');
        const cerrado = (h.fecha_salida !== null && h.fecha_salida !== '') || (h.estado_tramite === 'ENTREGADO');

        let badgeEstado = '';
        if (cerrado) {
            badgeEstado = `<span class="badge bg-secondary bg-opacity-25 text-secondary border px-2 py-1"><i class="fa-solid fa-door-open text-info me-1"></i> Salida Registrada</span>`;
        } else if (enVent) {
            badgeEstado = `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold"><i class="fa-solid fa-circle-dot text-success me-1"></i> En Ventanilla (TV 2)</span>`;
        } else {
            badgeEstado = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Listo Entrega</span>`;
        }

        let horaTxt = h.updated_at ? h.updated_at.split(' ')[1].substring(0, 5) : '--:--';

        html += `
            <tr class="item-fila-llamado">
                <td class="ps-4">
                    <span class="fw-bold font-monospace text-primary fs-6">
                        <i class="fa-solid fa-receipt me-1"></i> ${escapeHtml(h.ticket_numero)}
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(h.nombres + ' ' + h.apellidos)}</div>
                    <small class="text-muted">${escapeHtml((h.tipo_documento || 'CC') + ' ' + h.numero_documento)}</small>
                </td>
                <td>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                        ${escapeHtml(h.eps_nombre || 'Savia Salud')}
                    </span>
                </td>
                <td>
                    ${esPref ? '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-star me-1"></i> Preferencial</span>' : '<span class="badge bg-light text-muted border">Normal</span>'}
                </td>
                <td>
                    <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                        <i class="fa-solid fa-desktop text-primary me-1"></i> ${escapeHtml(h.modulo_entrega_asignado || 'Ventanilla')}
                    </span>
                </td>
                <td>
                    <span class="text-muted small">
                        <i class="fa-solid fa-clock me-1"></i> ${horaTxt}
                    </span>
                </td>
                <td>${badgeEstado}</td>
                <td class="text-end pe-4">
                    ${!cerrado 
                        ? `<button type="button" class="btn btn-sm btn-outline-warning fw-bold shadow-sm" onclick="llamarTurnoATurnero(${h.id}, '${escapeHtml(h.ticket_numero)}')"><i class="fa-solid fa-volume-high me-1"></i> Re-llamar</button>` 
                        : `<span class="text-muted small"><i class="fa-solid fa-check text-success me-1"></i> Completado</span>`
                    }
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    filtrarTablaHistorial();
}

function filtrarTablaHistorial() {
    const input = document.getElementById('inputFiltroTabla');
    const filtro = input ? input.value.toLowerCase().trim() : '';
    const filas = document.querySelectorAll('.item-fila-llamado');
    filas.forEach(f => {
        const txt = f.textContent.toLowerCase();
        f.style.display = txt.includes(filtro) ? '' : 'none';
    });
}

function abrirVisorDocumento(url, titulo) {
    const iframe = document.getElementById('iframeVisorDocEntrega');
    const modalTit = document.getElementById('modalVisorDocTitulo');
    if (iframe) iframe.src = url;
    if (modalTit) modalTit.innerHTML = `<i class="fa-solid fa-file-pdf text-danger me-2"></i> ${escapeHtml(titulo || 'Soporte Médico')}`;
    const modal = new bootstrap.Modal(document.getElementById('modalVisorDocEntrega'));
    modal.show();
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// ==========================================
// CONTROL DE ENTREGA FÍSICA Y FIRMA DIGITAL
// ==========================================
let canvasFirma = null;
let ctxFirma = null;
let dibujandoFirma = false;
let firmaRealizada = false;
let entregaItemsDispensar = [];
let entregaItemsFaltantes = [];
let entregaIngresoActivo = null;

// Controladores Tableta Digitalizadora Topaz (T-S460)
let topazActivo = false;
let topazTimer = null;

function checkTopazSigWeb() {
    const badge = document.getElementById('badgeEstadoTopaz');
    const txt = document.getElementById('txtEstadoTopaz');

    const isInstalled = (typeof IsSigWebInstalled === 'function') ? IsSigWebInstalled() : false;
    if (isInstalled) {
        if (badge) {
            badge.className = 'badge bg-success p-2 shadow-sm';
            badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Pad Topaz Conectado';
        }
        if (txt) txt.textContent = 'Topaz T-S460 Listo (47290 OK)';
        return true;
    }

    if (badge) {
        badge.className = 'badge bg-secondary p-2';
        badge.innerHTML = '<i class="fa-solid fa-tablet-screen-button me-1"></i> Modo Pantalla / Táctil';
    }
    if (txt) txt.textContent = 'Firme en pantalla o active SigWeb';
    return false;
}

function activarPadTopaz() {
    const badge = document.getElementById('badgeEstadoTopaz');
    if (!checkTopazSigWeb()) {
        abrirModalAyudaTopaz();
        return;
    }

    try {
        if (!canvasFirma) {
            canvasFirma = document.getElementById('canvasFirmaEntrega');
            if (canvasFirma) ctxFirma = canvasFirma.getContext('2d');
        }
        if (!canvasFirma || !ctxFirma) return;

        // Resetear y limpiar completamente la memoria del pad antes de empezar
        if (typeof Reset === 'function') Reset();
        if (typeof ClearTablet === 'function') ClearTablet();
        limpiarCanvasFirmaEntrega();

        SetDisplayTarget(ctxFirma);
        SetImageXSize(canvasFirma.offsetWidth || 500);
        SetImageYSize(canvasFirma.offsetHeight || 160);
        SetImagePenWidth(3.5);

        // Activar hardware Topaz y refresco de tinta en vivo a 50ms
        topazTimer = SetTabletState(1, ctxFirma, 50);
        topazActivo = true;
        firmaRealizada = true;

        if (badge) {
            badge.className = 'badge bg-warning text-dark p-2 shadow-sm animate-pulse';
            badge.innerHTML = '<i class="fa-solid fa-pen-fancy me-1"></i> Firmando en Pad Topaz...';
        }
    } catch(e) {
        console.error("Error al activar Topaz:", e);
        alert('No se pudo activar el Pad Topaz.\n\nVerifique que la tableta Topaz T-S460 esté conectada por USB y que el servicio SigWeb esté en ejecución.');
    }
}

function limpiarPadTopaz() {
    try {
        if (typeof Reset === 'function') Reset();
        if (typeof ClearTablet === 'function') ClearTablet();
        limpiarCanvasFirmaEntrega();

        if (canvasFirma && ctxFirma) {
            SetDisplayTarget(ctxFirma);
            SetTabletState(1, ctxFirma, 50);
            topazActivo = true;
            firmaRealizada = false;
        }
        const badge = document.getElementById('badgeEstadoTopaz');
        if (badge) {
            badge.className = 'badge bg-warning text-dark p-2 shadow-sm animate-pulse';
            badge.innerHTML = '<i class="fa-solid fa-pen-fancy me-1"></i> Pad Limpio: Firme en la Tableta';
        }
    } catch(e) {
        console.error("Error al limpiar Topaz:", e);
    }
}

function capturarFirmaPadTopaz() {
    const badge = document.getElementById('badgeEstadoTopaz');
    try {
        if (typeof GetSigImageB64 !== 'function') return;
        GetSigImageB64(function(b64String) {
            if (b64String && b64String.trim().length > 30) {
                const fullDataUrl = "data:image/png;base64," + b64String.trim();
                const cvs = document.getElementById('canvasFirmaEntrega');
                if (cvs) {
                    const cContext = cvs.getContext('2d');
                    const img = new Image();
                    img.onload = function() {
                        cContext.clearRect(0, 0, cvs.width, cvs.height);
                        cContext.drawImage(img, 0, 0, cvs.width, cvs.height);
                        firmaRealizada = true;
                    };
                    img.src = fullDataUrl;
                }

                cerrarPadTopaz();

                if (badge) {
                    badge.className = 'badge bg-success p-2 shadow-sm';
                    badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Firma Topaz Capturada';
                }
            } else {
                const cvs = document.getElementById('canvasFirmaEntrega');
                if (cvs && !isCanvasBlank(cvs)) {
                    firmaRealizada = true;
                    cerrarPadTopaz();
                    if (badge) {
                        badge.className = 'badge bg-success p-2 shadow-sm';
                        badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Firma Capturada';
                    }
                    return;
                }
                alert('No se detectó ningún trazo nuevo en la tableta Topaz.\n\nPor favor realice la firma en la tableta con el lápiz antes de pulsar "Pasar Firma".');
            }
        });
    } catch(e) {
        console.error("Error al capturar firma Topaz:", e);
        alert('Error al transferir la firma desde el pad Topaz.');
    }
}

function cerrarPadTopaz() {
    if (topazActivo) {
        try {
            if (typeof SetTabletState === 'function') {
                SetTabletState(0, topazTimer);
            }
        } catch(e) {}
        topazActivo = false;
    }
}

function isCanvasBlank(canvas) {
    if (!canvas) return true;
    try {
        const ctx = canvas.getContext('2d');
        const pixelBuffer = new Uint32Array(
            ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer
        );
        return !pixelBuffer.some(color => color !== 0);
    } catch (e) {
        return false;
    }
}

function abrirModalAyudaTopaz() {
    const modalEl = document.getElementById('modalAyudaTopaz');
    if (modalEl) {
        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
    }
}

function inicializarCanvasFirmaEntrega() {
    canvasFirma = document.getElementById('canvasFirmaEntrega');
    if (!canvasFirma) return;
    ctxFirma = canvasFirma.getContext('2d');
    
    // Ajustar dimensiones internas
    const rect = canvasFirma.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvasFirma.width = (rect.width || 600) * dpr;
    canvasFirma.height = (rect.height || 160) * dpr;
    ctxFirma.scale(dpr, dpr);
    
    ctxFirma.strokeStyle = "#002060";
    ctxFirma.lineWidth = 2.5;
    ctxFirma.lineCap = "round";
    ctxFirma.lineJoin = "round";

    firmaRealizada = false;

    if (canvasFirma._hasSignatureListeners) return;
    canvasFirma._hasSignatureListeners = true;

    function getPos(e) {
        const r = canvasFirma.getBoundingClientRect();
        if (e.touches && e.touches.length > 0) {
            return {
                x: e.touches[0].clientX - r.left,
                y: e.touches[0].clientY - r.top
            };
        }
        return {
            x: e.clientX - r.left,
            y: e.clientY - r.top
        };
    }

    function empezar(e) {
        e.preventDefault();
        dibujandoFirma = true;
        firmaRealizada = true;
        const pos = getPos(e);
        ctxFirma.beginPath();
        ctxFirma.moveTo(pos.x, pos.y);
    }

    function trazar(e) {
        if (!dibujandoFirma) return;
        e.preventDefault();
        const pos = getPos(e);
        ctxFirma.lineTo(pos.x, pos.y);
        ctxFirma.stroke();
    }

    function terminar(e) {
        if (dibujandoFirma) {
            dibujandoFirma = false;
            ctxFirma.closePath();
        }
    }

    canvasFirma.addEventListener('mousedown', empezar);
    canvasFirma.addEventListener('mousemove', trazar);
    window.addEventListener('mouseup', terminar);

    canvasFirma.addEventListener('touchstart', empezar, { passive: false });
    canvasFirma.addEventListener('touchmove', trazar, { passive: false });
    window.addEventListener('touchend', terminar);
}

function limpiarCanvasFirmaEntrega() {
    if (!canvasFirma || !ctxFirma) return;
    ctxFirma.clearRect(0, 0, canvasFirma.width, canvasFirma.height);
    firmaRealizada = false;
}

// ====================================================
// CONTROL DE CÁMARAS Y PRIORIDAD HIKVISION DS-U02
// ====================================================
let streamCamaraEntrega = null;
let _fotoEntregaBase64 = '';
let listaDispositivosVideo = [];
let idCamaraSeleccionada = localStorage.getItem('sispam_camara_entrega_device_id') || '';

async function listarDispositivosVideo() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        listaDispositivosVideo = devices.filter(d => d.kind === 'videoinput');

        const select = document.getElementById('selectCamaraEntrega');
        if (!select) return;

        select.innerHTML = '';

        if (listaDispositivosVideo.length === 0) {
            select.innerHTML = '<option value="">No se detectaron cámaras</option>';
            actualizarBadgeTipoCamara();
            return;
        }

        let idHikvision = '';
        let idGuardadaValida = false;

        listaDispositivosVideo.forEach((dev, idx) => {
            const label = dev.label || `Cámara ${idx + 1}`;
            const opt = document.createElement('option');
            opt.value = dev.deviceId;

            // Priorizar detección de Hikvision DS-U02 o cámara USB externa
            const esHikvision = /hikvision|ds-u02|u02/i.test(label);
            const esUsb = /usb/i.test(label);
            const esFrontalAio = /integrated|front|internal|frontal|incorporada/i.test(label);

            if (esHikvision) {
                opt.textContent = `📷 ${label} (Hikvision DS-U02 ★)`;
                if (!idHikvision) idHikvision = dev.deviceId;
            } else if (esUsb && !esFrontalAio) {
                opt.textContent = `📷 ${label} (Cámara USB Externa)`;
                if (!idHikvision) idHikvision = dev.deviceId;
            } else if (esFrontalAio) {
                opt.textContent = `📷 ${label} (Cámara Frontal AIO)`;
            } else {
                opt.textContent = `📷 ${label}`;
            }

            if (dev.deviceId === idCamaraSeleccionada) {
                idGuardadaValida = true;
            }

            select.appendChild(opt);
        });

        // Prioridad:
        // 1. Si hay una Hikvision DS-U02 detectada y no se ha fijado otra manualmente, usar Hikvision por defecto.
        // 2. Si la guardada en localStorage es válida, respetarla.
        // 3. De lo contrario, usar la primera encontrada.
        if (idHikvision && (!idGuardadaValida || !idCamaraSeleccionada)) {
            idCamaraSeleccionada = idHikvision;
            select.value = idHikvision;
            localStorage.setItem('sispam_camara_entrega_device_id', idHikvision);
        } else if (idGuardadaValida && idCamaraSeleccionada) {
            select.value = idCamaraSeleccionada;
        } else if (idHikvision) {
            select.value = idHikvision;
            idCamaraSeleccionada = idHikvision;
        } else if (listaDispositivosVideo[0]) {
            idCamaraSeleccionada = listaDispositivosVideo[0].deviceId;
            select.value = idCamaraSeleccionada;
        }

        actualizarBadgeTipoCamara();
    } catch (e) {
        console.warn("No se pudieron enumerar dispositivos de video:", e);
    }
}

function cambiarDispositivoCamara(nuevoDeviceId) {
    if (!nuevoDeviceId) return;
    idCamaraSeleccionada = nuevoDeviceId;
    localStorage.setItem('sispam_camara_entrega_device_id', nuevoDeviceId);
    actualizarBadgeTipoCamara();

    // Si la cámara web está actualmente transmitiendo en vivo, reiniciar con la nueva seleccionada
    if (streamCamaraEntrega) {
        iniciarCamaraEntrega();
    }
}

function actualizarBadgeTipoCamara() {
    const badge = document.getElementById('badgeCamaraTipo');
    if (!badge) return;

    const select = document.getElementById('selectCamaraEntrega');
    const selectedText = select && select.selectedOptions && select.selectedOptions[0] ? select.selectedOptions[0].textContent : '';

    if (/hikvision|ds-u02|u02/i.test(selectedText)) {
        badge.className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 flex-shrink-0';
        badge.innerHTML = '<i class="fa-solid fa-camera-retro me-1"></i> Hikvision DS-U02 Activa';
    } else if (/usb/i.test(selectedText)) {
        badge.className = 'badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-50 px-2 py-1 flex-shrink-0';
        badge.innerHTML = '<i class="fa-solid fa-usb me-1"></i> USB Externa';
    } else {
        badge.className = 'badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-50 px-2 py-1 flex-shrink-0';
        badge.innerHTML = '<i class="fa-solid fa-desktop me-1"></i> Frontal AIO';
    }
}

function obtenerNombreCamaraActiva() {
    const select = document.getElementById('selectCamaraEntrega');
    if (select && select.selectedOptions && select.selectedOptions[0]) {
        let txt = select.selectedOptions[0].textContent.replace(/^📷\s*/, '').trim();
        if (/hikvision|ds-u02/i.test(txt)) return 'Hikvision DS-U02';
        return txt;
    }
    return 'Cámara';
}

async function iniciarCamaraEntrega() {
    const video = document.getElementById('videoFotoEntrega');
    const imgPreview = document.getElementById('imgFotoEntregaPreview');
    const boxPlaceholder = document.getElementById('boxFotoPlaceholder');
    const lblEstado = document.getElementById('lblFotoEstado');

    const btnAbrir = document.getElementById('btnAbrirCamaraEntrega');
    const btnCapturar = document.getElementById('btnCapturarFotoEntrega');
    const btnRetomar = document.getElementById('btnRetomarFotoEntrega');
    const btnLimpiar = document.getElementById('btnLimpiarFotoEntrega');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Su navegador no soporta acceso directo a la cámara web. Puede utilizar el botón de subir archivo.');
        return;
    }

    detenerCamaraEntrega();

    // Si aún no tenemos los nombres de los dispositivos de video por falta de permisos iniciales,
    // pedir un stream temporal rápido para que el navegador libere los labels reales
    if (listaDispositivosVideo.length === 0 || !listaDispositivosVideo[0].label) {
        try {
            const tempStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            tempStream.getTracks().forEach(t => t.stop());
            await listarDispositivosVideo();
        } catch(e) {
            console.warn("Permiso previo de cámara:", e);
        }
    }

    // Configurar resolución HD/FHD (1080p nativo de Hikvision DS-U02)
    const videoConstraints = {
        width: { ideal: 1920, min: 1280 },
        height: { ideal: 1080, min: 720 }
    };

    if (idCamaraSeleccionada) {
        videoConstraints.deviceId = { exact: idCamaraSeleccionada };
    }

    navigator.mediaDevices.getUserMedia({ 
        video: videoConstraints, 
        audio: false 
    })
    .then(stream => {
        streamCamaraEntrega = stream;
        if (video) {
            video.srcObject = stream;
            video.style.display = 'block';
        }
        if (imgPreview) imgPreview.style.display = 'none';
        if (boxPlaceholder) boxPlaceholder.style.display = 'none';

        if (btnAbrir) btnAbrir.classList.add('d-none');
        if (btnRetomar) btnRetomar.classList.add('d-none');
        if (btnLimpiar) btnLimpiar.classList.remove('d-none');
        if (btnCapturar) btnCapturar.classList.remove('d-none');

        // Refrescar listado con los nombres ya autorizados
        listarDispositivosVideo();

        const nomCam = obtenerNombreCamaraActiva();
        if (lblEstado) {
            lblEstado.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ${nomCam} activa:</span> <span class="text-muted">Encuadre al paciente y presione <strong>Tomar Foto</strong></span>`;
        }
    })
    .catch(err => {
        console.error('Error al acceder a la cámara seleccionada:', err);
        // Fallback resiliente: si falló con deviceId exacto, intentar genérico
        if (videoConstraints.deviceId) {
            navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false })
            .then(fallbackStream => {
                streamCamaraEntrega = fallbackStream;
                if (video) {
                    video.srcObject = fallbackStream;
                    video.style.display = 'block';
                }
                if (imgPreview) imgPreview.style.display = 'none';
                if (boxPlaceholder) boxPlaceholder.style.display = 'none';
                if (btnAbrir) btnAbrir.classList.add('d-none');
                if (btnRetomar) btnRetomar.classList.add('d-none');
                if (btnLimpiar) btnLimpiar.classList.remove('d-none');
                if (btnCapturar) btnCapturar.classList.remove('d-none');
                listarDispositivosVideo();
            })
            .catch(err2 => {
                alert('No se pudo acceder a la cámara Hikvision DS-U02: ' + (err2.message || 'Verifique que esté conectada por USB y cuente con permisos.'));
            });
        } else {
            alert('No se pudo acceder a la cámara: ' + (err.message || 'Verifique los permisos de cámara en el navegador.'));
        }
    });
}

function capturarFotoEntrega() {
    const video = document.getElementById('videoFotoEntrega');
    const canvas = document.getElementById('canvasFotoEntrega');
    const imgPreview = document.getElementById('imgFotoEntregaPreview');
    const lblEstado = document.getElementById('lblFotoEstado');

    const btnCapturar = document.getElementById('btnCapturarFotoEntrega');
    const btnRetomar = document.getElementById('btnRetomarFotoEntrega');
    const btnLimpiar = document.getElementById('btnLimpiarFotoEntrega');
    const btnAbrir = document.getElementById('btnAbrirCamaraEntrega');

    if (!video || !canvas) return;

    const w = video.videoWidth || 640;
    const h = video.videoHeight || 480;
    canvas.width = w;
    canvas.height = h;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);

    _fotoEntregaBase64 = canvas.toDataURL('image/jpeg', 0.88);

    if (imgPreview) {
        imgPreview.src = _fotoEntregaBase64;
        imgPreview.style.display = 'block';
    }
    if (video) video.style.display = 'none';

    detenerCamaraEntrega();

    if (btnCapturar) btnCapturar.classList.add('d-none');
    if (btnAbrir) btnAbrir.classList.add('d-none');
    if (btnRetomar) btnRetomar.classList.remove('d-none');
    if (btnLimpiar) btnLimpiar.classList.remove('d-none');

    if (lblEstado) {
        lblEstado.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Foto capturada y lista para el acta</span>`;
    }
}

function limpiarFotoEntrega() {
    detenerCamaraEntrega();
    _fotoEntregaBase64 = '';

    const video = document.getElementById('videoFotoEntrega');
    const imgPreview = document.getElementById('imgFotoEntregaPreview');
    const boxPlaceholder = document.getElementById('boxFotoPlaceholder');
    const lblEstado = document.getElementById('lblFotoEstado');
    const inputFile = document.getElementById('inputFotoEntregaArchivo');

    if (inputFile) inputFile.value = '';
    if (video) video.style.display = 'none';
    if (imgPreview) {
        imgPreview.style.display = 'none';
        imgPreview.src = '';
    }
    if (boxPlaceholder) boxPlaceholder.style.display = 'block';

    const btnAbrir = document.getElementById('btnAbrirCamaraEntrega');
    const btnCapturar = document.getElementById('btnCapturarFotoEntrega');
    const btnRetomar = document.getElementById('btnRetomarFotoEntrega');
    const btnLimpiar = document.getElementById('btnLimpiarFotoEntrega');

    if (btnAbrir) btnAbrir.classList.remove('d-none');
    if (btnCapturar) btnCapturar.classList.add('d-none');
    if (btnRetomar) btnRetomar.classList.add('d-none');
    if (btnLimpiar) btnLimpiar.classList.add('d-none');

    if (lblEstado) {
        lblEstado.innerHTML = `<i class="fa-solid fa-shield-halved text-success me-1"></i> Registro fotográfico para soporte y auditoría del acta`;
    }
}

function detenerCamaraEntrega() {
    if (streamCamaraEntrega) {
        try {
            streamCamaraEntrega.getTracks().forEach(track => track.stop());
        } catch (e) {}
        streamCamaraEntrega = null;
    }
}

function cargarFotoDesdeArchivo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            _fotoEntregaBase64 = e.target.result;
            detenerCamaraEntrega();

            const video = document.getElementById('videoFotoEntrega');
            const imgPreview = document.getElementById('imgFotoEntregaPreview');
            const boxPlaceholder = document.getElementById('boxFotoPlaceholder');
            const lblEstado = document.getElementById('lblFotoEstado');

            if (video) video.style.display = 'none';
            if (boxPlaceholder) boxPlaceholder.style.display = 'none';
            if (imgPreview) {
                imgPreview.src = _fotoEntregaBase64;
                imgPreview.style.display = 'block';
            }

            const btnAbrir = document.getElementById('btnAbrirCamaraEntrega');
            const btnCapturar = document.getElementById('btnCapturarFotoEntrega');
            const btnRetomar = document.getElementById('btnRetomarFotoEntrega');
            const btnLimpiar = document.getElementById('btnLimpiarFotoEntrega');

            if (btnAbrir) btnAbrir.classList.add('d-none');
            if (btnCapturar) btnCapturar.classList.add('d-none');
            if (btnRetomar) btnRetomar.classList.remove('d-none');
            if (btnLimpiar) btnLimpiar.classList.remove('d-none');

            if (lblEstado) {
                lblEstado.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Imagen cargada: ${escapeHtml(file.name)}</span>`;
            }
        };
        reader.readAsDataURL(file);
    }
}

let _es100PendienteActivo = false;

function abrirModalEntregaFirma(id, ticket, nombreCompleto, doc, eps) {
    entregaIngresoActivo = id;
    _es100PendienteActivo = false;
    
    const tckEl = document.getElementById('modalEntregaTicket');
    const nomEl = document.getElementById('modalEntregaPaciente');
    const docEl = document.getElementById('modalEntregaDoc');
    const epsEl = document.getElementById('modalEntregaEps');
    
    const initialTicket = (ticket && ticket !== 'undefined' && ticket.trim() !== '') ? ticket : 'Cargando...';
    const initialNombre = (nombreCompleto && nombreCompleto !== 'undefined' && nombreCompleto.trim() !== '') ? nombreCompleto : 'Cargando...';
    const initialDoc    = (doc && doc !== 'undefined' && doc.trim() !== '') ? doc : 'Cargando...';
    const initialEps    = (eps && eps !== 'undefined' && eps.trim() !== '') ? eps : 'SAVIA SALUD EPS';

    if (tckEl) tckEl.textContent = initialTicket;
    if (nomEl) nomEl.textContent = initialNombre;
    if (docEl) docEl.textContent = initialDoc;
    if (epsEl) epsEl.textContent = initialEps;
    
    const tbody = document.getElementById('tbodyMedsEntrega');
    if (tbody) {
        tbody.innerHTML = `
            <tr><td colspan="5" class="text-center py-4"><span class="spinner-border text-primary spinner-border-sm me-2"></span> Cargando lista de medicamentos alistados...</td></tr>
        `;
    }
    const contFalt = document.getElementById('contenedorFaltantesEntrega');
    if (contFalt) contFalt.classList.add('d-none');
    
    const txtObs = document.getElementById('txtObservacionesEntrega');
    if (txtObs) txtObs.value = '';
    
    const modalEl = document.getElementById('modalEntregaConFirma');
    if (modalEl) {
        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalObj.show();
    }
    
    limpiarFotoEntrega();

    setTimeout(() => {
        inicializarCanvasFirmaEntrega();
        limpiarCanvasFirmaEntrega();
        if (checkTopazSigWeb()) {
            activarPadTopaz();
        }
        listarDispositivosVideo();
    }, 350);

    fetch(`{{ route('entrega.index') }}?ajax_get_detalle_entrega=1&ingreso_id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                if (data.ingreso) {
                    const ing = data.ingreso;
                    const finalTicket = ing.ticket_numero || ing.ticket || ticket || '--';
                    const finalNombre = (ing.nombres || ing.apellidos) ? `${ing.nombres || ''} ${ing.apellidos || ''}`.trim() : (nombreCompleto || '--');
                    const finalDoc    = (ing.numero_documento) ? `${ing.tipo_documento || 'CC'} ${ing.numero_documento}`.trim() : (doc || '--');
                    const finalEps    = ing.eps_nombre || ing.eps || eps || 'SAVIA SALUD EPS';

                    if (tckEl) tckEl.textContent = finalTicket;
                    if (nomEl) nomEl.textContent = finalNombre;
                    if (docEl) docEl.textContent = finalDoc;
                    if (epsEl) epsEl.textContent = finalEps;

                    const inpDir = document.getElementById('inputEntregaDirDomicilio');
                    const inpTel = document.getElementById('inputEntregaTelDomicilio');
                    const inpBarrio = document.getElementById('inputEntregaBarrioDomicilio');
                    if (inpDir) inpDir.value = ing.direccion_residencia || ing.direccion || '';
                    if (inpTel) inpTel.value = ing.telefono || ing.numero_celular || '';
                    if (inpBarrio) inpBarrio.value = ing.barrio_nombre || ing.barrio || ing.ciudad_residencia || '';
                }

                entregaItemsDispensar = data.items_dispensar || [];
                entregaItemsFaltantes = data.items_faltantes || [];
                _es100PendienteActivo = !!data.es_100_pendiente || (entregaItemsDispensar.length === 0 && entregaItemsFaltantes.length > 0);

                const banner100 = document.getElementById('bannerAlerta100Pendiente');
                const cardFoto = document.getElementById('cardFotoEntregaCol');
                const cardDom = document.getElementById('cardDomicilioDatosCol');
                const cardMedsDisp = document.getElementById('cardMedsDispensadosWrapper');
                const btnConfirmar = document.getElementById('btnConfirmarEntregaFirma');
                const lblFirma = document.getElementById('lblTituloFirmaEntrega');
                const tituloFalt = document.getElementById('tituloFaltantesCard');

                if (_es100PendienteActivo) {
                    if (banner100) banner100.classList.remove('d-none');
                    if (cardFoto) cardFoto.classList.add('d-none');
                    if (cardDom) cardDom.classList.remove('d-none');
                    if (cardMedsDisp) cardMedsDisp.classList.add('d-none');
                    if (tituloFalt) tituloFalt.innerHTML = `<i class="fa-solid fa-truck text-warning fs-5 me-1"></i> Medicamentos Radicados para Despacho a Domicilio`;
                    if (btnConfirmar) {
                        btnConfirmar.innerHTML = `<i class="fa-solid fa-truck-fast me-2"></i> Radicar Domicilio y Generar Comprobante`;
                        btnConfirmar.className = 'btn btn-warning btn-lg fw-bold rounded-pill px-5 shadow-sm hover-scale text-dark';
                    }
                    if (lblFirma) {
                        lblFirma.innerHTML = `<i class="fa-solid fa-signature text-warning-emphasis me-1"></i> 3. Firma de Notificación del Usuario`;
                    }
                } else {
                    if (banner100) banner100.classList.add('d-none');
                    if (cardFoto) cardFoto.classList.remove('d-none');
                    if (cardDom) cardDom.classList.add('d-none');
                    if (cardMedsDisp) cardMedsDisp.classList.remove('d-none');
                    if (tituloFalt) tituloFalt.innerHTML = `2. Medicamentos Faltantes (Traslado a Módulo Domicilios)`;
                    if (btnConfirmar) {
                        btnConfirmar.innerHTML = `<i class="fa-solid fa-signature me-2"></i> Confirmar Entrega y Generar Acta`;
                        btnConfirmar.className = 'btn btn-success btn-lg fw-bold rounded-pill px-5 shadow-sm hover-scale';
                    }
                    if (lblFirma) {
                        lblFirma.innerHTML = `<i class="fa-solid fa-signature text-primary me-1"></i> 3. Firma Digital del Receptor`;
                    }
                }

                renderizarItemsEntregaModal(entregaItemsDispensar, entregaItemsFaltantes, data.faltantes_texto);
            } else {
                if (tbody) {
                    tbody.innerHTML = `
                        <tr><td colspan="5" class="text-center text-danger py-3">Error al cargar medicamentos: ${escapeHtml(data.message)}</td></tr>
                    `;
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (tbody) {
                tbody.innerHTML = `
                    <tr><td colspan="5" class="text-center text-danger py-3">Error de comunicación al obtener detalles de la orden.</td></tr>
                `;
            }
        });
}

function renderizarItemsEntregaModal(dispensar, faltantes, faltantesTexto) {
    const tbody = document.getElementById('tbodyMedsEntrega');
    if (!tbody) return;
    
    if (!dispensar || dispensar.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-3">
                    <i class="fa-solid fa-circle-exclamation text-warning me-1"></i> Sin medicamentos en bodega física (100% pendientes a domicilio).
                </td>
            </tr>
        `;
    } else {
        let html = '';
        dispensar.forEach((m, idx) => {
            const nom = escapeHtml(m.nombre_medicamento || m.descripcion || 'Medicamento');
            const lote = escapeHtml(m.numero_lote || m.lote_codigo || 'FEFO');
            const vence = escapeHtml(m.fecha_vencimiento || m.vencimiento || 'N/A');
            const cPresc = parseInt(m.cantidad_prescrita || m.cantidad_solicitada || m.cantidad_entregar || 1);
            const cEntr = parseInt(m.cantidad_entregar || m.cantidad_dispensar || cPresc);
            const pos = escapeHtml(m.posologia || m.dosis || '--');

            html += `
                <tr>
                    <td class="text-center fw-bold text-muted">${idx + 1}</td>
                    <td>
                        <div class="fw-bold text-dark">${nom}</div>
                        <small class="text-muted"><i class="fa-solid fa-clock-rotate-left me-1"></i> ${pos}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border font-monospace px-2 py-1"><i class="fa-solid fa-barcode me-1 text-primary"></i> ${lote}</span>
                        ${vence !== 'N/A' ? `<small class="text-muted d-block mt-1">Vence: ${vence}</small>` : ''}
                    </td>
                    <td class="text-center fw-semibold text-secondary fs-6">${cPresc}</td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center fw-bold text-success mx-auto input-cant-entrega" 
                                style="max-width: 85px;" 
                                data-idx="${idx}" 
                                min="0" 
                                max="${cPresc}" 
                                value="${cEntr}" 
                                onchange="actualizarCantidadEntregar(${idx}, this.value)">
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // Faltantes
    const contFalt = document.getElementById('contenedorFaltantesEntrega');
    const tbodyFalt = document.getElementById('tbodyFaltantesEntrega');
    if (faltantes && faltantes.length > 0) {
        let hf = '';
        faltantes.forEach((f, fidx) => {
            hf += `
                <tr>
                    <td class="text-center fw-bold text-danger">${fidx + 1}</td>
                    <td class="fw-bold text-dark">${escapeHtml(f.nombre_medicamento || f.descripcion || 'Medicamento')}</td>
                    <td class="text-center fw-bold text-danger">${f.cantidad_pendiente || f.cantidad_faltante || 1}</td>
                    <td class="text-muted small">${escapeHtml(f.motivo || f.observaciones || 'Sin existencia en bodega / Se radica a Domicilios')}</td>
                </tr>
            `;
        });
        if (tbodyFalt) tbodyFalt.innerHTML = hf;
        if (contFalt) contFalt.classList.remove('d-none');
    } else if (faltantesTexto && faltantesTexto.trim() !== '') {
        if (tbodyFalt) {
            tbodyFalt.innerHTML = `
                <tr>
                    <td colspan="4" class="text-dark small py-2">${escapeHtml(faltantesTexto)}</td>
                </tr>
            `;
        }
        if (contFalt) contFalt.classList.remove('d-none');
    } else {
        if (contFalt) contFalt.classList.add('d-none');
    }
}

function actualizarCantidadEntregar(idx, val) {
    if (entregaItemsDispensar && entregaItemsDispensar[idx]) {
        entregaItemsDispensar[idx].cantidad_entregar = parseInt(val) || 0;
    }
}

function getTrimmedSignatureCanvas(sourceCanvas) {
    if (!sourceCanvas) return null;
    const ctx = sourceCanvas.getContext('2d');
    const w = sourceCanvas.width;
    const h = sourceCanvas.height;
    const imgData = ctx.getImageData(0, 0, w, h);
    const data = imgData.data;

    let minX = w, minY = h, maxX = 0, maxY = 0;
    let found = false;

    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            const idx = (y * w + x) * 4;
            const alpha = data[idx + 3];
            if (alpha > 15) {
                found = true;
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
            }
        }
    }

    if (!found) return sourceCanvas;

    const pad = 20;
    minX = Math.max(0, minX - pad);
    minY = Math.max(0, minY - pad);
    maxX = Math.min(w, maxX + pad);
    maxY = Math.min(h, maxY + pad);

    const cropW = Math.max(1, maxX - minX);
    const cropH = Math.max(1, maxY - minY);

    const trimmedCanvas = document.createElement('canvas');
    trimmedCanvas.width = cropW;
    trimmedCanvas.height = cropH;
    const trimmedCtx = trimmedCanvas.getContext('2d');
    trimmedCtx.drawImage(sourceCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);

    return trimmedCanvas;
}

function ejecutarEntregaFinalConfirmada() {
    if (!entregaIngresoActivo) return;
    
    const obs = document.getElementById('txtObservacionesEntrega') ? document.getElementById('txtObservacionesEntrega').value.trim() : '';
    const btn = document.getElementById('btnConfirmarEntregaFirma');
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> ${_es100PendienteActivo ? 'Radicando a Domicilios y Generando Comprobante...' : 'Descontando Kardex y Generando Acta...'}`;
    }

    function procederConEnvio(firmaBase64Final) {
        cerrarPadTopaz();

        const formData = new FormData();
        formData.append('action', 'confirmar_entrega_con_firma');
        formData.append('ingreso_id', entregaIngresoActivo);
        formData.append('items_dispensar', JSON.stringify(entregaItemsDispensar));
        formData.append('items_faltantes', JSON.stringify(entregaItemsFaltantes));
        formData.append('observaciones', obs);
        formData.append('firma_base64', firmaBase64Final);
        formData.append('foto_base64', _fotoEntregaBase64 || '');

        if (_es100PendienteActivo) {
            formData.append('direccion_domicilio', document.getElementById('inputEntregaDirDomicilio')?.value.trim() || '');
            formData.append('telefono_domicilio', document.getElementById('inputEntregaTelDomicilio')?.value.trim() || '');
            formData.append('barrio_domicilio', document.getElementById('inputEntregaBarrioDomicilio')?.value.trim() || '');
        }

        fetch('{{ route('entrega.index') }}', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF },
            body: formData
        })
    .then(async res => {
        const text = await res.text();
        try {
            // Intentar parsear JSON directamente o extraer el bloque JSON si viniera con algún warning PHP
            const jsonStart = text.indexOf('{');
            const jsonEnd = text.lastIndexOf('}');
            if (jsonStart !== -1 && jsonEnd !== -1) {
                return JSON.parse(text.substring(jsonStart, jsonEnd + 1));
            }
            return JSON.parse(text);
        } catch (e) {
            console.error('Servidor retornó respuesta no JSON:', text);
            throw new Error('Respuesta inválida del servidor: ' + text.substring(0, 200));
        }
    })
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-signature me-1"></i> Confirmar Entrega y Generar Acta`;
        }

        if (data && data.status === 'ok') {
            detenerCamaraEntrega();
            const modalEl = document.getElementById('modalEntregaConFirma');
            if (modalEl) {
                const inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            }

            const ticket = data.ticket || (data.data ? data.data.ticket_numero : '') || '';
            const ingresoId = entregaIngresoActivo;
            
            const exTck = document.getElementById('exitoTicketNumero');
            const exCnt = document.getElementById('exitoMedsCount');
            if (exTck) exTck.textContent = ticket;
            if (exCnt) exCnt.textContent = (entregaItemsDispensar ? entregaItemsDispensar.length : 0);
            
            const faltCont = document.getElementById('exitoFaltantesInfo');
            if (entregaItemsFaltantes && entregaItemsFaltantes.length > 0) {
                const exFaltCnt = document.getElementById('exitoFaltantesCount');
                if (exFaltCnt) exFaltCnt.textContent = entregaItemsFaltantes.length;
                if (faltCont) faltCont.classList.remove('d-none');
            } else {
                if (faltCont) faltCont.classList.add('d-none');
            }

            const btnActa = document.getElementById('btnExitoImprimirActa');
            if (btnActa) {
                btnActa.onclick = () => window.open(`{{ url('entrega') }}/${ingresoId}/acta`, '_blank');
            }
            const btnPick = document.getElementById('btnExitoImprimirPicking');
            if (btnPick) {
                btnPick.onclick = () => window.open(`{{ url('alistamiento') }}/${ingresoId}/orden-unificada?auto_print=1`, '_blank');
            }

            const modalExito = new bootstrap.Modal(document.getElementById('modalExitoEntregaActa'));
            modalExito.show();

            refrescarHistorialLlamados();
            limpiarBusqueda();
        } else {
            alert((data && data.message) ? data.message : 'Error al procesar la entrega.');
        }
    })
    .catch(err => {
        console.error(err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-signature me-1"></i> Confirmar Entrega y Generar Acta`;
        }
        alert('Notificación SISPAM:\n' + (err.message || 'Error de conexión al procesar la entrega y descuento de inventario.'));
    });
    }

    // Resolver la firma (desde hardware Topaz o desde Canvas táctil)
    if (topazActivo && typeof GetSigImageB64 === 'function') {
        try {
            GetSigImageB64(function(b64String) {
                if (b64String && b64String.trim().length > 30) {
                    procederConEnvio("data:image/png;base64," + b64String.trim());
                    return;
                }
                extraerDeCanvasYEnviar();
            });
            return;
        } catch (err) {
            console.warn("Error al extraer firma desde Topaz B64:", err);
        }
    }
    extraerDeCanvasYEnviar();

    function extraerDeCanvasYEnviar() {
        let firmaBase64 = '';
        if (canvasFirma && (firmaRealizada || !isCanvasBlank(canvasFirma))) {
            const trimmed = getTrimmedSignatureCanvas(canvasFirma);
            firmaBase64 = trimmed ? trimmed.toDataURL('image/png') : canvasFirma.toDataURL('image/png');
        } else if (canvasFirma && !isCanvasBlank(canvasFirma)) {
            firmaBase64 = canvasFirma.toDataURL('image/png');
        }
        procederConEnvio(firmaBase64);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initMiModulo();
    listarDispositivosVideo();

    if (navigator.mediaDevices && navigator.mediaDevices.addEventListener) {
        navigator.mediaDevices.addEventListener('devicechange', () => {
            listarDispositivosVideo();
        });
    }

    const modalEntregaEl = document.getElementById('modalEntregaConFirma');
    if (modalEntregaEl) {
        modalEntregaEl.addEventListener('hidden.bs.modal', () => {
            cerrarPadTopaz();
            detenerCamaraEntrega();
        });
    }

    // Auto-refresco en segundo plano cada 4 segundos
    setInterval(() => {
        refrescarHistorialLlamados();
    }, 4000);
});
</script>



<!-- MODAL DE CONFIRMACIÓN: ACEPTAR Y PASAR A TURNERO 2 TV -->
<div class="modal fade" id="modalConfirmarPasoTV" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-bullhorn fs-4"></i> Confirmar Llamado a Turnero 2 TV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 75px; height: 75px;">
                    <i class="fa-solid fa-tv fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1" id="modalConfirmarNombrePaciente">--</h4>
                <div class="text-muted small mb-3" id="modalConfirmarDocPaciente">--</div>
                
                <div class="alert alert-warning border-0 rounded-3 text-start py-2 px-3 mb-0 small">
                    <i class="fa-solid fa-circle-info text-warning me-1"></i>
                    Al presionar <strong>Aceptar</strong>, el paciente será enviado a la pantalla del <strong>Turnero 2 TV</strong> y se registrará la hora de entrega en estadísticas.
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-0 justify-content-end gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm" id="btnModalAceptarPasarTV" onclick="ejecutarPasoATV()">
                    <i class="fa-solid fa-circle-check me-1"></i> Aceptar y Pasar al TV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL INFORMATIVO DE REMISIÓN A TURNERO 2 TV -->
<div class="modal fade" id="modalConfirmacionTurnero2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-success text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check fs-4"></i> Paciente Remitido al Turnero 2 TV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-tv fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2" id="modalConfirmacionTicket">--</h4>
                <p class="text-muted fs-6 mb-0" id="modalConfirmacionMensaje">
                    El paciente ha sido enviado a la lista de espera del <strong>Turnero 2 TV</strong> y se encuentra proyectado en pantalla.
                </p>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-0 justify-content-center">
                <button type="button" class="btn btn-success fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN: ENTREGAR Y CERRAR CICLO (SLA / SALIDA) -->
<div class="modal fade" id="modalConfirmarCierreEntrega" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-success text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-box-open fs-4"></i> Confirmar Entrega y Cierre
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 75px; height: 75px;">
                    <i class="fa-solid fa-check-double fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1" id="modalCierreNombrePaciente">--</h4>
                <div class="text-muted small mb-3">
                    Tiquete: <span class="badge bg-black border border-success text-success font-monospace fs-6 px-3 py-1 fw-bold shadow-sm" id="modalCierreTicketPaciente">--</span>
                </div>
                
                <div class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 text-start py-3 px-3 mb-0 small text-dark">
                    <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-info text-success fs-5"></i>
                        <span>¿Confirmas que realizaste la entrega física de los medicamentos?</span>
                    </div>
                    <ul class="mb-0 ps-3 text-muted">
                        <li>El paciente <strong>saldrá automáticamente de la pantalla Turnero 2 TV</strong>.</li>
                        <li>Se registrará la <strong>fecha y hora de salida</strong> para los reportes de tiempos de atención (SLA).</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-0 justify-content-end gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm" id="btnModalAceptarCierre" onclick="ejecutarCierreEntregaConfirmado()">
                    <i class="fa-solid fa-circle-check me-1"></i> Sí, Entregar y Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL DE ENTREGA FÍSICA, VERIFICACIÓN Y FIRMA DIGITAL   -->
<!-- ======================================================== -->
<div class="modal fade" id="modalEntregaConFirma" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4 border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-signature fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold fs-5 mb-0">
                            Entrega Física de Medicamentos & Acta Digital
                        </h5>
                        <small class="text-white-50">Verifique los medicamentos alistados, registre la firma del paciente y descargue el inventario</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- ALERTA DINÁMICA: ATENCIÓN 100% PENDIENTE A DOMICILIO -->
                <div id="bannerAlerta100Pendiente" class="alert alert-warning border-warning d-none py-3 px-3 mb-3 rounded-3 shadow-sm text-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-warning bg-opacity-25 text-warning-emphasis rounded-circle flex-shrink-0" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-truck-fast fs-4 text-dark"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark fs-6">Radicación 100% Pendiente para Entrega a Domicilio</strong>
                            <span class="small text-dark">Fórmula sin existencias físicas en bodega. La totalidad de los medicamentos quedan radicados para despacho directo al domicilio del paciente (SLA 48 a 72h). <strong>No se requiere fotografía</strong>, confirme la dirección y registre la firma de notificación del usuario.</span>
                        </div>
                    </div>
                </div>

                <!-- Ficha del Paciente -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Tiquete Turno:</span>
                            <span class="badge bg-black border border-success text-success font-monospace fs-6 px-3 py-1 fw-bold" id="modalEntregaTicket">--</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Paciente:</span>
                            <strong class="text-dark fs-6" id="modalEntregaPaciente">--</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Documento:</span>
                            <strong class="text-dark" id="modalEntregaDoc">--</strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted small d-block">Aseguradora (EPS):</span>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 fw-bold" id="modalEntregaEps">--</span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Medicamentos Alistados (Picking Físico) -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3 bg-white" id="cardMedsDispensadosWrapper">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-boxes-packing text-primary"></i> 1. Medicamentos Alistados para Entrega
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">
                            <i class="fa-solid fa-warehouse me-1"></i> Descuento de Kardex en este paso
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 45px;">#</th>
                                    <th>Medicamento / Posología</th>
                                    <th style="width: 200px;">Lote Asignado & Vence</th>
                                    <th class="text-center" style="width: 110px;">Prescrita</th>
                                    <th class="text-center" style="width: 120px;">A Entregar</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyMedsEntrega">
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">Cargando lista...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Contenedor de Faltantes / Domicilios -->
                <div class="card border-warning border-2 shadow-sm rounded-3 overflow-hidden mb-3 bg-white d-none" id="contenedorFaltantesEntrega">
                    <div class="card-header bg-warning bg-opacity-10 py-2 px-3 border-bottom border-warning d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-truck text-warning fs-5"></i> <span id="tituloFaltantesCard">2. Medicamentos Faltantes (Traslado a Módulo Domicilios)</span>
                        </h6>
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                            <i class="fa-solid fa-arrow-right me-1"></i> Pendientes por Despachar
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 45px;">#</th>
                                    <th>Medicamento No Disponible</th>
                                    <th class="text-center" style="width: 110px;">Cant. Faltante</th>
                                    <th>Novedad / Observación</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyFaltantesEntrega">
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-warning bg-opacity-10 py-2 px-3 small text-dark">
                        <i class="fa-solid fa-circle-info text-warning me-1"></i> Estos ítems quedarán registrados en el <strong>Módulo de Domicilios y Pendientes</strong> para su consecución y despacho dentro de 48 a 72h.
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Observaciones de la Entrega / Radicación:</label>
                    <input type="text" class="form-control" id="txtObservacionesEntrega" placeholder="Ej: Entrega conforme / Radicación de faltantes para despacho domiciliario.">
                </div>

                <!-- 3. Firma Digital y 4. Captura de Foto o Confirmación de Dirección -->
                <div class="row g-3">
                    <!-- Columna Izquierda: Firma Digital (Topaz SigLite T-S460 & Táctil/Mouse) -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="lblTituloFirmaEntrega">
                                    <i class="fa-solid fa-signature text-primary"></i> 3. Firma Digital del Receptor
                                </label>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary small me-1" onclick="abrirModalAyudaTopaz()" title="Instrucciones de configuración Topaz SigLite T-S460">
                                        <i class="fa-solid fa-circle-question fs-6"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="limpiarCanvasFirmaEntrega()" title="Limpiar firma">
                                        <i class="fa-solid fa-eraser me-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                            <!-- Barra de Estado y Controles Topaz SigWeb -->
                            <div class="p-2 mb-2 bg-light rounded-3 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 shadow-sm" id="panelTopazEntrega">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary p-2" id="badgeEstadoTopaz">
                                        <i class="fa-solid fa-tablet me-1"></i> Pad Topaz: Comprobando...
                                    </span>
                                    <small class="text-muted small" id="txtEstadoTopaz">Topaz T-S460</small>
                                </div>
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-2" id="btnActivarTopaz" onclick="activarPadTopaz()" title="Activar pad Topaz para firmar con el lápiz">
                                        <i class="fa-solid fa-pen-nib me-1"></i> Firmar en Pad
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-2" id="btnLimpiarTopaz" onclick="limpiarPadTopaz()" title="Borrar pantalla LCD del pad Topaz">
                                        <i class="fa-solid fa-rotate me-1"></i> Limpiar Pad
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success fw-bold rounded-pill px-2" id="btnCapturarTopaz" onclick="capturarFirmaPadTopaz()" title="Transferir trazo del pad al canvas">
                                        <i class="fa-solid fa-check me-1"></i> Pasar Firma
                                    </button>
                                </div>
                            </div>

                            <div class="bg-light border-2 border-dashed rounded-3 p-1 text-center position-relative" style="border: 2px dashed #cbd5e1; background: #fafafa;">
                                <canvas id="canvasFirmaEntrega" style="width: 100%; height: 160px; display: block; cursor: crosshair; background: #ffffff; border-radius: 6px;"></canvas>
                                <div class="text-muted small mt-1 d-flex justify-content-between px-2 align-items-center" style="font-size: 0.72rem;">
                                    <span><i class="fa-solid fa-pen-to-square text-primary me-1"></i> Pad Topaz T-S460</span>
                                    <span><i class="fa-solid fa-hand-pointer text-secondary me-1"></i> Modo táctil / mouse disponible</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha A: Foto del Paciente (Para entregas físicas) -->
                    <div class="col-lg-6" id="cardFotoEntregaCol">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-camera text-success"></i> 4. Foto del Paciente / Receptor
                                </label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-2 shadow-sm" id="btnAbrirCamaraEntrega" onclick="iniciarCamaraEntrega()">
                                        <i class="fa-solid fa-video me-1"></i> Cámara
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-2 shadow-sm d-none" id="btnCapturarFotoEntrega" onclick="capturarFotoEntrega()">
                                        <i class="fa-solid fa-circle-dot me-1"></i> Tomar Foto
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 d-none" id="btnRetomarFotoEntrega" onclick="iniciarCamaraEntrega()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Retomar
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2 d-none" id="btnLimpiarFotoEntrega" onclick="limpiarFotoEntrega()">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-2" title="Cargar archivo" onclick="document.getElementById('inputFotoEntregaArchivo').click()">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>
                                    <input type="file" id="inputFotoEntregaArchivo" accept="image/*" capture="user" class="d-none" onchange="cargarFotoDesdeArchivo(this)">
                                </div>
                            </div>

                            <!-- Selector y Detección de Cámara USB Hikvision DS-U02 -->
                            <div class="p-2 mb-2 bg-light rounded-3 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 shadow-sm" id="panelSelectorCamaraEntrega">
                                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                    <i class="fa-solid fa-video text-success flex-shrink-0"></i>
                                    <select id="selectCamaraEntrega" class="form-select form-select-sm py-1 border-0 bg-transparent fw-semibold text-dark" style="font-size: 0.76rem; cursor: pointer;" onchange="cambiarDispositivoCamara(this.value)">
                                        <option value="">🔍 Detectando cámaras (Hikvision DS-U02)...</option>
                                    </select>
                                </div>
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 flex-shrink-0" id="badgeCamaraTipo" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-usb me-1"></i> Hikvision DS-U02
                                </span>
                            </div>

                            <div class="bg-light border-2 border-dashed rounded-3 p-1 text-center position-relative d-flex flex-column align-items-center justify-content-center" style="border: 2px dashed #cbd5e1; background: #fafafa; min-height: 160px; height: 160px; overflow: hidden;">
                                <video id="videoFotoEntrega" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; display: none; background: #000;"></video>
                                <img id="imgFotoEntregaPreview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; display: none;" alt="Foto Paciente" />
                                <div id="boxFotoPlaceholder" class="p-2 text-center text-muted">
                                    <i class="fa-solid fa-user-check fs-2 text-secondary opacity-50 mb-1 d-block"></i>
                                    <div class="small fw-semibold">Sin foto registrada</div>
                                    <span class="small text-muted" style="font-size: 11px;">Presione <strong>Cámara</strong> para capturar en vivo o <strong><i class="fa-solid fa-upload"></i></strong> para cargar</span>
                                </div>
                                <canvas id="canvasFotoEntrega" style="display: none;"></canvas>
                            </div>
                            <div class="text-muted small mt-1 d-flex justify-content-between px-2 align-items-center" style="font-size: 0.72rem;">
                                <span id="lblFotoEstado"><i class="fa-solid fa-shield-halved text-success me-1"></i> Registro fotográfico para soporte del acta</span>
                                <span class="text-muted"><i class="fa-solid fa-video me-1"></i> FHD 1080p</span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha B: Confirmación de Dirección de Domicilio (Para 100% pendientes sin foto) -->
                    <div class="col-lg-6 d-none" id="cardDomicilioDatosCol">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                            <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-map-location-dot text-warning fs-5"></i> 4. Confirmación de Datos para Despacho Domiciliario
                            </label>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-0 fw-semibold">Dirección de Residencia / Entrega:</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-house text-primary"></i></span>
                                    <input type="text" id="inputEntregaDirDomicilio" class="form-control form-control-sm fw-bold" placeholder="Ej: Calle 45 # 12-34 Apto 301">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-0 fw-semibold">Teléfono / Celular:</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-phone text-success"></i></span>
                                        <input type="text" id="inputEntregaTelDomicilio" class="form-control form-control-sm" placeholder="Ej: 3001234567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-0 fw-semibold">Barrio / Municipio:</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-location-dot text-danger"></i></span>
                                        <input type="text" id="inputEntregaBarrioDomicilio" class="form-control form-control-sm" placeholder="Ej: Prado Centro">
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-circle-info text-primary"></i> El domiciliario utilizará estos datos para realizar la entrega.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white py-3 px-4 border-0 justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success btn-lg fw-bold rounded-pill px-5 shadow-sm hover-scale" id="btnConfirmarEntregaFirma" onclick="ejecutarEntregaFinalConfirmada()">
                    <i class="fa-solid fa-signature me-2"></i> Confirmar Entrega y Generar Acta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL DE ÉXITO TRAS ENTREGA & DESCUENTO DE KARDEX        -->
<!-- ======================================================== -->
<div class="modal fade" id="modalExitoEntregaActa" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-success text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check fs-4"></i> ¡Entrega Realizada con Éxito!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center bg-white">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-file-circle-check fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Tiquete <span class="text-success font-monospace" id="exitoTicketNumero">--</span> Finalizado</h4>
                <p class="text-muted fs-6 mb-3">
                    Se descargaron exitosamente los medicamentos del inventario en Kardex y se registró el acta oficial con la firma digital del paciente.
                </p>

                <div class="card bg-light border-0 rounded-3 p-3 mb-3 text-start small">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-check text-success"></i>
                        <span><strong id="exitoMedsCount">0</strong> Medicamento(s) entregados y descontados de inventario.</span>
                    </div>
                    <div id="exitoFaltantesInfo" class="d-flex align-items-center gap-2 text-warning d-none">
                        <i class="fa-solid fa-truck"></i>
                        <span><strong id="exitoFaltantesCount">0</strong> Medicamento(s) trasladados al Módulo de Domicilios.</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg fw-bold rounded-pill shadow-sm" id="btnExitoImprimirActa">
                        <i class="fa-solid fa-file-signature me-2"></i> Imprimir Acta de Entrega Firmada
                    </button>
                    <button type="button" class="btn btn-outline-primary fw-bold rounded-pill" id="btnExitoImprimirPicking">
                        <i class="fa-solid fa-boxes-packing me-2"></i> Imprimir Picking / Alistamiento
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-0 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fa-solid fa-arrow-right me-1"></i> Continuar con la siguiente entrega
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AYUDA E INSTALACIÓN TOPAZ SIGWEB (T-S460) -->
<div class="modal fade" id="modalAyudaTopaz" tabindex="-1" style="z-index: 10850;" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-5 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tablet fs-4"></i> Configuración de Tableta Digitalizadora Topaz (T-S460)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fa-solid fa-circle-info fs-3 me-3 text-primary flex-shrink-0"></i>
                    <div class="small">
                        <strong>¿Por qué se requiere el servicio SigWeb?</strong><br>
                        Las tabletas Topaz transmiten firmas biométricas de forma cifrada y segura. Para que el navegador web pueda comunicarse con el hardware USB del pad, se requiere tener instalado y activo el software oficial <strong>Topaz SigWeb™</strong> en este computador.
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Pasos para conectar y activar la tableta:</h6>

                <ol class="list-group list-group-numbered mb-4 small">
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold text-dark">1. Descargar e instalar SigWeb para Windows</div>
                            Descargue el instalador oficial de Topaz Systems e instálelo en el computador de la ventanilla.
                        </div>
                        <a href="https://www.topazsystems.com/sigweb.html" target="_blank" class="btn btn-sm btn-primary fw-bold text-nowrap rounded-pill px-3">
                            <i class="fa-solid fa-download me-1"></i> Descargar SigWeb
                        </a>
                    </li>
                    <li class="list-group-item py-3">
                        <div class="ms-2">
                            <div class="fw-bold text-dark">2. Seleccionar el modelo durante la instalación</div>
                            Cuando el asistente le solicite el modelo de pad:
                            <ul class="mt-1 mb-0">
                                <li>Modelo de Tableta: <strong>SigLite LCD 1x5</strong> (Serie <code>T-S460</code>)</li>
                                <li>Tipo de Conexión: <strong>HID USB</strong> (o USB)</li>
                            </ul>
                        </div>
                    </li>
                    <li class="list-group-item py-3">
                        <div class="ms-2">
                            <div class="fw-bold text-dark">3. Actualizar Certificado de Seguridad (Puerto 47290 HTTPS)</div>
                            Topaz requiere un certificado local para conexiones seguras HTTPS. Si el servicio no responde:
                            <ol class="mt-2 mb-2">
                                <li>Presione <kbd>Win + R</kbd>, escriba <code>services.msc</code> y verifique que el servicio <strong>Topaz SigWeb Tablet Service</strong> esté en estado <em>En ejecución</em> (Running).</li>
                                <li class="mt-2">Descargue y ejecute el actualizador de certificados de Topaz:
                                    <div class="my-2">
                                        <a href="https://www.topazsystems.com/software/sigweb_update_Cert.exe" target="_blank" class="btn btn-sm btn-outline-primary fw-bold rounded-pill">
                                            <i class="fa-solid fa-certificate me-1"></i> Descargar SigWeb Certificate Updater (.exe)
                                        </a>
                                    </div>
                                </li>
                                <li>Compruebe la conexión segura al servicio local:
                                    <div class="my-2">
                                        <a href="https://tablet.sigwebtablet.com:47290/SigWeb/GetDaysUntilCertificateExpires" target="_blank" class="badge bg-success p-2 text-decoration-none">
                                            <i class="fa-solid fa-shield-halved me-1"></i> Probar Puerto Seguro 47290 (HTTPS)
                                        </a>
                                    </div>
                                    <small class="text-muted d-block">Si el navegador solicita autorización de <strong>"Acceso a la Red Local" (Local Network Access)</strong>, haga clic en <strong>"Permitir"</strong>.</small>
                                </li>
                            </ol>
                        </div>
                    </li>
                </ol>

                <div class="bg-light p-3 rounded-3 border">
                    <p class="mb-0 small text-muted">
                        <i class="fa-solid fa-lightbulb text-warning me-1"></i> <strong>Modo Alternativo Inmediato:</strong> Si el pad no está conectado en este momento, el paciente puede firmar con el dedo en pantallas táctiles o con el mouse directamente sobre el recuadro blanco de firma sin bloquear la dispensación.
                    </p>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 border-0 justify-content-end">
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
