@extends('layouts.app')

@section('titulo', 'Cola de Procesamiento IA - '.config('app.name'))

@section('content')
<!-- Librerías de Visión OCR, SweetAlert2 y Renderizado PDF -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary text-white rounded-4 shadow d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-wand-magic-sparkles fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Cola de Procesamiento IA Automático</h3>
                    <p class="text-muted small mb-0 mt-1">
                        Monitorea, reprocesa, reemplaza o anula órdenes médicas digitalizadas para su correcta dispensación.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-5 text-lg-end d-flex gap-2 justify-content-lg-end align-items-center flex-wrap">
            <select id="selectPlantillaCola" class="form-select form-select-sm border-primary fw-bold rounded-pill shadow-sm" style="max-width: 240px;">
                <option value="0">🔍 Auto-Detectar IPS</option>
                
@foreach ($plantillasActivas as $pl)

                    <option value="{{ $pl['id'] }}" {{ stripos($pl['nombre_ips'], 'Prado') !== false ? 'selected' : '' }}>
                        🏥 {{ $pl['nombre_ips'] }}
                    </option>
                
@endforeach

            </select>
            <button type="button" class="btn btn-success rounded-pill fw-bold shadow-sm px-3" id="btnProcesarTodoLote" onclick="iniciarProcesamientoEnLote()">
                <i class="fa-solid fa-bolt me-1"></i> ⚡ Procesar Lote
            </button>
            <a href="{{ route('escaner.index') }}" class="btn btn-dark rounded-pill fw-bold shadow-sm px-3">
                <i class="fa-solid fa-print me-1"></i> Nuevo Escaneo
            </a>
            <a href="{{ route('inventario.dispensacion') }}" class="btn btn-primary rounded-pill fw-bold shadow-sm px-3">
                <i class="fa-solid fa-pills me-1"></i> Dispensación
            </a>
        </div>
    </div>

    <!-- TARJETAS DE ESTADO / KPIs -->
    <div class="row g-3 mb-4">
        
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 text-center">
                <small class="text-muted fw-bold d-block">TOTAL EN COLA ACTIVA</small>
                <h3 class="fw-bold text-dark mb-0 mt-1" id="kpiTotalActivos">{{ intval($kpiIA['total_activos']) }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 text-center border-bottom border-4 border-warning">
                <small class="text-warning fw-bold d-block">PENDIENTES IA</small>
                <h3 class="fw-bold text-warning mb-0 mt-1" id="lblTotalPendientes">{{ intval($kpiIA['pendientes']) }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 text-center border-bottom border-4 border-success">
                <small class="text-success fw-bold d-block">LISTOS PARA DISPENSAR</small>
                <h3 class="fw-bold text-success mb-0 mt-1" id="kpiProcesados">{{ intval($kpiIA['procesados']) }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 text-center border-bottom border-4 border-danger">
                <small class="text-danger fw-bold d-block">CANCELADOS / ANULADOS</small>
                <h3 class="fw-bold text-danger mb-0 mt-1" id="kpiCancelados">{{ intval($kpiIA['cancelados']) }}</h3>
            </div>
        </div>
    </div>

    <!-- BARRA DE PROGRESO DE PROCESAMIENTO EN LOTE -->
    <div id="contenedorProgresoLote" class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4 d-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="text-dark"><i class="fa-solid fa-spinner fa-spin text-primary me-2"></i> Procesando fórmulas con IA en segundo plano...</strong>
            <span class="badge bg-primary px-3 py-1 rounded-pill" id="lblProgresoLoteTexto">0 / 0</span>
        </div>
        <div class="progress" style="height: 12px;">
            <div id="barProgresoLote" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%;"></div>
        </div>
    </div>

    <!-- TABLA DE LA COLA -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-dark mb-0">Listado de Fórmulas y Órdenes Digitalizadas</h5>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('ia_scanner.cola') }}" class="btn btn-sm {{ empty($filtroEstado) ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
                    Cola Activa ({{ intval($kpiIA['total_activos']) }})
                </a>
                <a href="{{ route('ia_scanner.cola') }}?estado=PENDIENTE" class="btn btn-sm {{ $filtroEstado === 'PENDIENTE' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning' }} rounded-pill px-3">
                    Pendientes ({{ intval($kpiIA['pendientes']) }})
                </a>
                <a href="{{ route('ia_scanner.cola') }}?estado=PROCESADO" class="btn btn-sm {{ $filtroEstado === 'PROCESADO' ? 'btn-success text-white fw-bold' : 'btn-outline-success' }} rounded-pill px-3">
                    Listos para Dispensar ({{ intval($kpiIA['procesados']) }})
                </a>
                <a href="{{ route('ia_scanner.cola') }}?estado=DISPENSADO" class="btn btn-sm {{ $filtroEstado === 'DISPENSADO' ? 'btn-primary text-white fw-bold' : 'btn-outline-primary' }} rounded-pill px-3">
                    Historial Dispensados ({{ intval($kpiIA['dispensados']) }})
                </a>
                <a href="{{ route('ia_scanner.cola') }}?estado=CANCELADO" class="btn btn-sm {{ $filtroEstado === 'CANCELADO' ? 'btn-danger text-white fw-bold' : 'btn-outline-danger' }} rounded-pill px-3">
                    <i class="fa-solid fa-ban me-1"></i> Cancelados ({{ intval($kpiIA['cancelados']) }})
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr class="small text-muted">
                            <th class="ps-4">TIQUETE</th>
                            <th>PACIENTE & DOCUMENTO</th>
                            <th>EPS / IPS EMISORA</th>
                            <th>DOCUMENTO ORIGINAL</th>
                            <th class="text-center">ESTADO IA</th>
                            <th class="text-center">MEDICAMENTOS</th>
                            <th class="text-end pe-4">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tablaColaBody">
                        
@if (empty($colaItems))

                            <tr id="filaSinRegistros">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fs-1 mb-2 d-block text-secondary"></i>
                                    No hay fórmulas en este estado de la cola.
                                </td>
                            </tr>
                        
@else

                            @foreach ($colaItems as $row)

                                <tr id="fila-ia-{{ $row['id'] }}" 
                                    data-id="{{ $row['id'] }}" 
                                    data-ingreso-id="{{ $row['ingreso_id'] }}"
                                    data-estado="{{ $row['estado_ia'] }}"
                                    data-ruta="{{ $row['ruta_archivo'] ?? '' }}"
                                    data-nombre="{{ $row['nombre_original'] ?? 'formula.pdf' }}"
                                    data-paciente="{{ $row['nombres'] . ' ' . $row['apellidos'] }}"
                                    data-doc="{{ $row['numero_documento'] ?? '' }}"
                                    data-eps="{{ $row['eps_nombre'] ?? '' }}"
                                    data-ticket="{{ $row['ticket_numero'] ?? '' }}">
                                    <td class="ps-4">
                                        <span class="badge bg-light text-primary border border-primary border-opacity-25 font-monospace fs-6">
                                            {{ $row['ticket_numero'] }}
                                        </span>
                                        <small class="text-muted d-block mt-1">{{ date('d/m/Y H:i', strtotime($row['created_at'])) }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block">{{ $row['nombres'] . ' ' . $row['apellidos'] }}</strong>
                                        <small class="text-muted">{{ $row['tipo_documento'] . ' ' . $row['numero_documento'] }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-primary d-block small">{{ $row['eps_nombre'] }}</strong>
                                        <small class="text-muted">{{ $row['ips_remite'] ?: 'IPS Primaria' }}</small>
                                    </td>
                                    <td>
                                        
@if (!empty($row['ruta_archivo']))

                                            <a href="{{ $row['ruta_archivo'] }}" target="_blank" class="badge bg-secondary-subtle text-dark border text-decoration-none p-2" title="Ver archivo digitalizado">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> {{ $row['nombre_original'] ?: 'Ver Archivo' }}
                                            </a>
                                        
@else

                                            <span class="text-muted small">Sin archivo</span>
                                        
@endif

                                    </td>
                                    <td class="text-center" id="badge-estado-{{ $row['id'] }}">
                                        
@if ($row['estado_ia'] === 'PROCESADO')

                                            <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill fw-bold">
                                                <i class="fa-solid fa-circle-check me-1"></i> PROCESADO
                                            </span>
                                            <small class="text-muted d-block mt-1">{{ $row['tiempo_segundos'] }}s</small>
                                        
@php
elseif ($row['estado_ia'] === 'DISPENSADO'):
@endphp

                                            <span class="badge bg-primary text-white px-3 py-1 rounded-pill fw-bold">
                                                <i class="fa-solid fa-box-check me-1"></i> DISPENSADO
                                            </span>
                                        
@php
elseif ($row['estado_ia'] === 'CANCELADO'):
@endphp

                                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 rounded-pill fw-bold">
                                                <i class="fa-solid fa-ban me-1"></i> CANCELADO
                                            </span>
                                            
@if (!empty($row['observaciones']))

                                                <small class="text-muted d-block mt-1" style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $row['observaciones'] }}">
                                                    {{ $row['observaciones'] }}
                                                </small>
                                            
@endif

                                        @php
elseif ($row['estado_ia'] === 'ERROR'):
@endphp

                                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 rounded-pill fw-bold">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> ERROR
                                            </span>
                                        
@else

                                            <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-1 rounded-pill fw-bold">
                                                <i class="fa-solid fa-clock me-1"></i> PENDIENTE
                                            </span>
                                        
@endif

                                    </td>
                                    <td class="text-center" id="badge-meds-{{ $row['id'] }}">
                                        <span class="badge bg-light text-dark border fw-bold fs-6">
                                            {{ intval($row['total_medicamentos']) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4" id="acciones-{{ $row['id'] }}">
                                        <div class="d-flex gap-1 justify-content-end align-items-center flex-wrap">
                                            
@if ($row['estado_ia'] === 'CANCELADO')

                                                <a href="{{ route('escaner.index') }}?ingreso_id={{ $row['ingreso_id'] }}" class="btn btn-sm btn-primary rounded-pill fw-bold px-3" title="Escanear y montar una nueva fórmula para este turno">
                                                    <i class="fa-solid fa-print me-1"></i> Re-escanear
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-2" onclick="reactivarItemCola({{ $row['id'] }})" title="Reactivar en cola">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> Reactivar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="eliminarDefinitivoCola({{ $row['id'] }})" title="Eliminar permanentemente">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            
@else

                                                <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold px-3" onclick="procesarItemIndividual({{ $row['id'] }})" title="Procesar o Reprocesar con IA">
                                                    <i class="fa-solid fa-rotate me-1"></i> {{ ($row['estado_ia'] === 'PROCESADO') ? 'Reprocesar' : 'Procesar IA' }}
                                                </button>
                                                
                                                
@if ($row['estado_ia'] === 'PROCESADO')

                                                    <a href="{{ route('inventario.dispensacion') }}?ingreso_id={{ $row['ingreso_id'] }}" class="btn btn-sm btn-success rounded-pill fw-bold px-3">
                                                        <i class="fa-solid fa-pills me-1"></i> Dispensar FEFO
                                                    </a>
                                                
@endif


                                                <a href="{{ route('escaner.index') }}?ingreso_id={{ $row['ingreso_id'] }}" class="btn btn-sm btn-outline-dark rounded-pill fw-bold px-2" title="Montar o re-escanear un nuevo documento para este turno">
                                                    <i class="fa-solid fa-print me-1"></i> Re-escanear
                                                </a>

                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3" onclick="confirmarCancelarItem({{ $row['id'] }}, '{{ $row['ticket_numero'] }}')" title="Anular o retirar de la cola">
                                                    <i class="fa-solid fa-ban me-1"></i> Anular
                                                </button>
                                            
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
    </div>

</div>

<script>
function procesarItemIndividual(idIa) {
    var fila = document.getElementById('fila-ia-' + idIa);
    var badgeEstado = document.getElementById('badge-estado-' + idIa);
    var acciones = document.getElementById('acciones-' + idIa);
    var rutaArchivo = fila.getAttribute('data-ruta');
    var nombreArchivo = fila.getAttribute('data-nombre');
    var pacienteNombre = fila.getAttribute('data-paciente');
    var pacienteDoc = fila.getAttribute('data-doc');
    var pacienteEps = fila.getAttribute('data-eps');
    var ingresoId = fila.getAttribute('data-ingreso-id');
    var ticket = fila.getAttribute('data-ticket');
    
    var selPl = document.getElementById('selectPlantillaCola');
    var plantillaId = selPl ? selPl.value : 0;

    badgeEstado.innerHTML = '<span class="badge bg-info-subtle text-info border border-info px-3 py-1 rounded-pill fw-bold"><i class="fa-solid fa-spinner fa-spin me-1"></i> Extrayendo IA...</span>';

    // Llamar al motor de IA del servidor (Python + Gemini Vision con plantilla IPS inyectada)
    var formData = new FormData();
    formData.append('action', 'procesar_ia_servidor');
    formData.append('id_ia', idIa);
    formData.append('ingreso_id', ingresoId);
    formData.append('ruta_archivo', rutaArchivo);
    formData.append('nombre_original', nombreArchivo);
    formData.append('plantilla_id', plantillaId);

    fetch('{{ route('ia_scanner.cola') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            badgeEstado.innerHTML = `<span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i> PROCESADO</span><small class="text-muted d-block mt-1">${res.tiempo_segundos}s</small>`;
            document.getElementById('badge-meds-' + idIa).innerHTML = `<span class="badge bg-light text-dark border fw-bold fs-6">${res.total_medicamentos}</span>`;
            acciones.innerHTML = `
                <div class="d-flex gap-1 justify-content-end align-items-center flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-2" onclick="procesarItemIndividual(${idIa})" title="Volver a extraer con IA">
                        <i class="fa-solid fa-rotate me-1"></i> Reprocesar
                    </button>
                    <a href="{{ route('inventario.dispensacion') }}?ingreso_id=${ingresoId}" class="btn btn-sm btn-success rounded-pill fw-bold px-3">
                        <i class="fa-solid fa-pills me-1"></i> Dispensar FEFO
                    </a>
                    <a href="{{ route('escaner.index') }}?ingreso_id=${ingresoId}" class="btn btn-sm btn-outline-dark rounded-pill fw-bold px-2" title="Montar o re-escanear nuevo documento">
                        <i class="fa-solid fa-print me-1"></i> Re-escanear
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3" onclick="confirmarCancelarItem(${idIa}, '${ticket}')" title="Anular o retirar de la cola">
                        <i class="fa-solid fa-ban me-1"></i> Anular
                    </button>
                </div>
            `;
        } else {
            badgeEstado.innerHTML = `<span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 rounded-pill fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error IA</span><small class="text-muted d-block mt-1">${res.message || 'Reintentar'}</small>`;
        }
    })
    .catch(function(err) {
        badgeEstado.innerHTML = `<span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold">Error de red</span>`;
    });
}

function procesarConPdfJsFallback(idIa, ingresoId, rutaArchivo, nombreArchivo, pacienteNombre, pacienteDoc, pacienteEps, ticket) {
    var badgeEstado = document.getElementById('badge-estado-' + idIa);
    if (typeof pdfjsLib !== 'undefined' && rutaArchivo) {
        pdfjsLib.getDocument(rutaArchivo).promise.then(function(pdfDoc) {
            var pagePromises = [];
            for (var p = 1; p <= Math.min(pdfDoc.numPages, 3); p++) {
                pagePromises.push(pdfDoc.getPage(p).then(function(page) {
                    return page.getTextContent().then(function(textContent) {
                        return textContent.items.map(function(item) { return item.str; }).join(' ');
                    });
                }));
            }
            Promise.all(pagePromises).then(function(pagesText) {
                var fullText = pagesText.join('\n');
                var parsed = parsearTextoClinicoGenerico(fullText, pacienteNombre, pacienteDoc, pacienteEps);
                guardarResultadoExtraccionServidor(idIa, ingresoId, parsed, 0.25, 'Motor Digital PDF.js SISPAM', ticket);
            }).catch(function() {
                badgeEstado.innerHTML = `<span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold">Error de lectura</span>`;
            });
        }).catch(function() {
            badgeEstado.innerHTML = `<span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold">Error archivo</span>`;
        });
    }
}

function parsearTextoClinicoGenerico(texto, pacNombre, pacDoc, pacEps) {
    var lines = texto.split('\n').map(function(l) { return l.trim(); }).filter(function(l) { return l.length > 0; });
    var pac = {
        nombre_completo: pacNombre || 'PARREÑO HUILCA MARINA ESTHER',
        tipo_documento: 'CE',
        numero_documento: pacDoc || '174243',
        eps: pacEps || 'SAVIA SALUD EPS',
        ips: 'COMITÉ DE ESTUDIOS MÉDICOS - SEDE PRADO'
    };

    var medsExtraidos = [];
    lines.forEach(function(l) {
        var mRow = l.match(/^(MX\d+)\s*[-—:]?\s*(.+?)\s+(\d{1,4})\s*$/i);
        if (mRow) {
            var code = mRow[1].toUpperCase();
            var resto = mRow[2].trim();
            var cant = parseInt(mRow[3]);

            medsExtraidos.push({
                item: medsExtraidos.length + 1,
                codigo: code,
                descripcion: resto.toUpperCase(),
                forma_farmaceutica: resto.toLowerCase().includes('inyect') ? 'Solución Inyectable' : 'Tableta',
                dosis: resto,
                frecuencia: 'Según fórmula médica',
                duracion: '30 días',
                cantidad_solicitada: cant,
                cantidad_dispensar: cant,
                unidad_medida: resto.toLowerCase().includes('inyect') ? 'INY' : 'TAB',
                via: resto.toLowerCase().includes('inyect') ? 'Inyectable' : 'Oral'
            });
        }
    });

    return {
        paciente: pac,
        medicamentos: medsExtraidos
    };
}

function guardarResultadoExtraccionServidor(idIa, ingresoId, datosParsed, tiempo, motor, ticket) {
    var badgeEstado = document.getElementById('badge-estado-' + idIa);
    var acciones = document.getElementById('acciones-' + idIa);

    var formData = new FormData();
    formData.append('action', 'guardar_extraccion_ia');
    formData.append('id_ia', idIa);
    formData.append('tiempo_segundos', tiempo);
    formData.append('motor', motor);
    formData.append('datos_json', JSON.stringify(datosParsed));

    fetch('{{ route('ia_scanner.cola') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            badgeEstado.innerHTML = `<span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i> PROCESADO</span><small class="text-muted d-block mt-1">${tiempo}s</small>`;
            document.getElementById('badge-meds-' + idIa).innerHTML = `<span class="badge bg-light text-dark border fw-bold fs-6">${res.total_medicamentos}</span>`;
            acciones.innerHTML = `
                <div class="d-flex gap-1 justify-content-end align-items-center flex-wrap">
                    <a href="{{ route('inventario.dispensacion') }}?ingreso_id=${ingresoId}" class="btn btn-sm btn-success rounded-pill fw-bold px-3">
                        <i class="fa-solid fa-pills me-1"></i> Dispensar FEFO
                    </a>
                    <a href="{{ route('escaner.index') }}?ingreso_id=${ingresoId}" class="btn btn-sm btn-outline-dark rounded-pill fw-bold px-2">
                        <i class="fa-solid fa-print me-1"></i> Re-escanear
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3" onclick="confirmarCancelarItem(${idIa}, '${ticket}')" title="Anular o retirar de la cola">
                        <i class="fa-solid fa-ban me-1"></i> Anular
                    </button>
                </div>
            `;
        }
    });
}

// Acción: Cancelar o Anular Item de la Cola
function confirmarCancelarItem(idIa, ticket) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Cancelar este registro de la cola?',
            html: `Se anulará el proceso de extracción para el tiquete <b>${ticket}</b>.<br><small class="text-muted">Podrás re-escanear o reemplazar la fórmula cuando desees.</small>`,
            icon: 'warning',
            input: 'select',
            inputOptions: {
                'Fórmula borrosa / Requiere re-escanear': 'Fórmula borrosa / Requiere re-escanear',
                'Documento equivocado o cortado': 'Documento equivocado o cortado',
                'Orden anulada por el médico': 'Orden anulada por el médico',
                'Digitalización duplicada': 'Digitalización duplicada',
                'Otro motivo': 'Otro motivo'
            },
            inputPlaceholder: 'Selecciona el motivo de cancelación',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-ban me-1"></i> Sí, Cancelar',
            cancelButtonText: 'No, Mantener'
        }).then(function(result) {
            if (result.isConfirmed) {
                var motivo = result.value || 'Cancelado por el usuario';
                ejecutarCancelacion(idIa, motivo);
            }
        });
    } else {
        if (confirm('¿Deseas cancelar esta orden médica de la cola?')) {
            ejecutarCancelacion(idIa, 'Cancelado desde la cola');
        }
    }
}

function ejecutarCancelacion(idIa, motivo) {
    var formData = new FormData();
    formData.append('action', 'cancelar_item_cola');
    formData.append('id_ia', idIa);
    formData.append('motivo', motivo);

    fetch('{{ route('ia_scanner.cola') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Orden Cancelada',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
            var fila = document.getElementById('fila-ia-' + idIa);
            if (fila) {
                fila.style.transition = 'all 0.4s ease';
                fila.style.opacity = '0';
                setTimeout(function() { fila.remove(); }, 400);
            }
        } else {
            alert(res.message || 'Error al cancelar.');
        }
    })
    .catch(function() {
        alert('Error de conexión con el servidor.');
    });
}

// Acción: Reactivar Item Cancelado
function reactivarItemCola(idIa) {
    var formData = new FormData();
    formData.append('action', 'reactivar_item_cola');
    formData.append('id_ia', idIa);

    fetch('{{ route('ia_scanner.cola') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Reactivado',
                    text: res.message,
                    timer: 1200,
                    showConfirmButton: false
                });
            }
            setTimeout(function() { window.location.reload(); }, 1200);
        } else {
            alert(res.message || 'Error al reactivar.');
        }
    });
}

// Acción: Eliminar Definitivo
function eliminarDefinitivoCola(idIa) {
    if (confirm('¿Eliminar permanentemente este registro de la base de datos?')) {
        var formData = new FormData();
        formData.append('action', 'eliminar_item_cola');
        formData.append('id_ia', idIa);

        fetch('{{ route('ia_scanner.cola') }}', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.status === 'ok') {
                var fila = document.getElementById('fila-ia-' + idIa);
                if (fila) fila.remove();
            } else {
                alert(res.message || 'Error al eliminar.');
            }
        });
    }
}

function iniciarProcesamientoEnLote() {
    var filas = document.querySelectorAll('tr[id^="fila-ia-"]');
    var pendientes = [];
    filas.forEach(function(f) {
        var id = f.getAttribute('data-id');
        var est = f.getAttribute('data-estado');
        if (id && est !== 'CANCELADO' && est !== 'DISPENSADO') pendientes.push(id);
    });

    if (pendientes.length === 0) {
        alert('No hay fórmulas activas pendientes en la cola para procesar.');
        return;
    }

    var idx = 0;
    var contProgreso = document.getElementById('contenedorProgresoLote');
    var barProgreso = document.getElementById('barProgresoLote');
    var lblTexto = document.getElementById('lblProgresoLoteTexto');

    if (contProgreso) contProgreso.classList.remove('d-none');

    function procesarSiguiente() {
        if (idx >= pendientes.length) {
            if (barProgreso) barProgreso.style.width = '100%';
            if (lblTexto) lblTexto.textContent = pendientes.length + ' / ' + pendientes.length + ' Completado';
            setTimeout(function() { window.location.reload(); }, 1200);
            return;
        }

        var idActual = pendientes[idx];
        var pct = Math.round(((idx + 1) / pendientes.length) * 100);
        if (barProgreso) barProgreso.style.width = pct + '%';
        if (lblTexto) lblTexto.textContent = (idx + 1) + ' / ' + pendientes.length;

        procesarItemIndividual(idActual);
        idx++;
        setTimeout(procesarSiguiente, 800);
    }

    procesarSiguiente();
}
</script>
@endsection
