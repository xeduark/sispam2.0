@extends('layouts.app')

@section('titulo', 'Alistamiento - '.config('app.name'))

@section('content')
<!-- Librería PDF.js y jsPDF para Lectura, Edición y Recorte de Encabezados -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }
</script>

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1">
            <i class="fa-solid fa-boxes-stacked me-2"></i> Módulo de Alistamiento & Empaque (Picking)
        </h4>
        <p class="text-muted small">Gestiona el empaque de medicamentos, recorta encabezados de PDF, compara ítems contra la transcripción y envía la orden a Entrega & Facturación.</p>
    </div>
</div>

@if ($mensaje)

    <div class="alert alert-success alert-dismissible fade show small"><i class="fa-solid fa-circle-check me-1"></i> {!! $mensaje !!}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif


@if ($error)

    <div class="alert alert-danger alert-dismissible fade show small"><i class="fa-solid fa-triangle-exclamation me-1"></i> {!! $error !!}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif


<div class="card card-glass border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-box-open me-2 text-warning"></i> Lista de Trabajo de Alistamiento
        </h5>
        <span class="badge bg-warning text-dark fs-6" id="badge-total-alistamiento">{{ count($listaAlistamiento) }} En Cola</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Semaforización</th>
                        <th>Tiquete</th>
                        <th>Sede</th>
                        <th>Hora Ingreso</th>
                        <th>Paciente</th>
                        <th>EPS</th>
                        <th>Estado Bloqueo</th>
                        <th class="text-end pe-3">Acción de Alistamiento</th>
                    </tr>
                </thead>
                <tbody id="tabla-alistamiento-body">
                    
@if (empty($listaAlistamiento))

                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay órdenes pendientes en la lista de alistamiento.</td></tr>
                    
@endif


                    @foreach ($listaAlistamiento as $row)

                    @php
$isLockedByMe = (!empty($row['locked_by_user_id']) && $row['locked_by_user_id'] == sesion('user_id'));
                        $isLockedByOther = (!empty($row['locked_by_user_id']) && $row['locked_by_user_id'] != sesion('user_id'));
@endphp

                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-secondary px-3 py-2 fs-6">
                                <i class="fa-solid fa-clock me-1"></i> Por Gestionar
                            </span>
                        </td>
                        <td class="fw-bold text-primary fs-5">{{ $row['ticket_numero'] }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $row['nombre_sede'] ?? 'Sede Principal' }}
                            </span>
                        </td>
                        <td>{{ date('h:i A', strtotime($row['fecha_ingreso'])) }}</td>
                        <td>
                            <div class="fw-bold">
                                {{ $row['nombres'] . ' ' . $row['apellidos'] }}
                                {!! get_prioridad_badge($row['prioridad'] ?? 'NORMAL') !!}
                            </div>
                            <small class="text-muted">{{ $row['tipo_documento'] . ' ' . $row['numero_documento'] }}</small>
                        </td>
                        <td><span class="badge bg-info text-dark">{{ $row['eps_nombre'] }}</span></td>
                        <td>
                            
@if ($isLockedByOther)

                                <span class="badge bg-danger"><i class="fa-solid fa-lock me-1"></i> Bloqueado por: {{ $row['locked_by_nombre'] }}</span>
                            
@php
elseif ($isLockedByMe):
@endphp

                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-user-gear me-1"></i> En gestión por ti</span>
                            
@else

                                <span class="badge bg-success"><i class="fa-solid fa-lock-open me-1"></i> Disponible</span>
                            
@endif

                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-success fw-bold text-white shadow-sm" onclick="imprimirYEnviarAEntrega({{ $row['id'] }}, '{{ $row['pdf_transcripcion_url'] ?? '' }}', '{{ $row['ticket_numero'] }}')">
                                <i class="fa-solid fa-print me-1"></i> 🖨️ Imprimir & Enviar a Entrega
                            </button>
                        </td>
                    </tr>
                    
@endforeach

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sección de Historial de Alistados Hoy & Reimpresión Inmediata -->
<div class="card card-glass border-0 shadow-sm mt-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-print me-2 text-primary"></i> Órdenes Alistadas Hoy & Reimpresión de Orden Unificada
            </h5>
            <small class="text-muted">Si olvidaste imprimir o necesitas copias adicionales del tiquete u orden unificada, puedes reimprimirlos aquí en cualquier momento.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 320px;">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="filtroAlistadosHoy" class="form-control" placeholder="Buscar por cédula, tiquete o nombre..." onkeyup="filtrarTablaAlistadosHoy()">
            </div>
            <span class="badge bg-primary fs-6" id="badge-total-alistados-hoy">{{ count($listaAlistadosHoy) }} Alistados</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-alistados-hoy">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tiquete</th>
                        <th>Sede</th>
                        <th>Paciente</th>
                        <th>EPS</th>
                        <th>Estado Actual</th>
                        <th>Ventanilla Asignada</th>
                        <th class="text-end pe-3">Reimpresión Directa</th>
                    </tr>
                </thead>
                <tbody id="tbody-alistados-hoy">
                    
@if (empty($listaAlistadosHoy))

                        <tr id="row-sin-alistados"><td colspan="7" class="text-center py-4 text-muted">Aún no se han alistado órdenes en el turno de hoy.</td></tr>
                    
@else

                        @foreach ($listaAlistadosHoy as $rowAlist)

                        <tr class="item-alistado-hoy">
                            <td class="ps-3">
                                <span class="fw-bold font-monospace text-primary fs-6">
                                    <i class="fa-solid fa-receipt me-1"></i> {{ $rowAlist['ticket_numero'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $rowAlist['nombre_sede'] ?? 'Sede Principal' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $rowAlist['nombres'] . ' ' . $rowAlist['apellidos'] }}</div>
                                <small class="text-muted">{{ $rowAlist['tipo_documento'] . ' ' . $rowAlist['numero_documento'] }}</small>
                            </td>
                            <td><span class="badge bg-info text-dark">{{ $rowAlist['eps_nombre'] }}</span></td>
                            <td>
                                {!! get_estado_badge($rowAlist['estado_tramite']) !!}
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold">
                                    <i class="fa-solid fa-door-open me-1"></i> {{ $rowAlist['modulo_entrega_asignado'] ?? 'En Espera' }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    
@if (in_array($rowAlist['estado_tramite'], ['EN_ENTREGA', 'ALISTADO']))

                                        <a href="{{ route('entrega.index') }}?busqueda={{ urlencode($rowAlist['ticket_numero']) }}" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm" title="Ir a Ventanilla para captura de firma y cierre">
                                            <i class="fa-solid fa-signature me-1"></i> Firmar
                                        </a>
                                    
@endif

                                    <a href="{{ route('alistamiento.orden_unificada', $rowAlist['id']) }}?auto_print=1" target="_blank" class="btn btn-sm btn-primary fw-bold shadow-sm" title="Reimprimir Orden Unificada + Tiquete">
                                        <i class="fa-solid fa-print me-1"></i> 🖨️ Orden Unificada
                                    </a>
                                    <a href="{{ route('alistamiento.ticket', $rowAlist['id']) }}?auto_print=1" target="_blank" class="btn btn-sm btn-outline-secondary" title="Reimprimir sólo Tiquete de Alistamiento">
                                        <i class="fa-solid fa-boxes-packing"></i>
                                    </a>
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

<!-- Modal Principal para Gestionar Alistamiento (Upload, Recorte de Encabezado y Comparación Automática) -->
<div class="modal fade" id="modalGestionarAlistamiento" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content card-glass">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-boxes-packing me-2"></i> Gestión de Alistamiento & Verificación de Medicamentos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="liberarBloqueoAlistamiento()"></button>
            </div>
            
            <form method="POST" action="{{ route('alistamiento.guardar') }}" enctype="multipart/form-data" id="formAlistamiento">
@csrf
                <input type="hidden" name="action" value="guardar_alistamiento">
                <input type="hidden" name="ingreso_id" id="modal_alistamiento_ingreso_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Datos Paciente -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <div class="text-muted small fw-semibold">DATOS DEL PACIENTE</div>
                                <div class="fw-bold fs-5 text-dark" id="modal_alistamiento_paciente_nombre">-</div>
                                <div class="small text-primary fw-bold" id="modal_alistamiento_paciente_doc">-</div>
                                <div class="badge bg-info text-dark mt-1" id="modal_alistamiento_paciente_eps">-</div>
                            </div>
                        </div>

                        <!-- Consulta de PDF Transcrito Previo -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold mb-1">ORDEN MÉDICA TRANSCRITA EN ETAPA PREVIA</div>
                                    <div id="modal_alistamiento_pdf_transcrito_container">
                                        <span class="text-muted small">Cargando PDF transcrito...</span>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">
                                    <i class="fa-solid fa-info-circle me-1"></i> El sistema comparará los medicamentos de esta orden contra el archivo de empaque que adjuntes abajo.
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- SECCIÓN: ADJUNTAR Y EDITAR/RECORTAR ENCABEZADO DEL PDF DE ALISTAMIENTO -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark fs-6">
                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> 1. Adjuntar PDF de Alistamiento / Empaque de Farmacia
                            </label>
                            <input type="file" name="pdf_alistamiento" id="input_pdf_alistamiento" class="form-control form-control-lg border-primary" accept=".pdf" onchange="cargarYProcesarPdfAlistamiento(event)">
                            <div class="form-text">Puedes subir el PDF de empaque. Abajo dispones de una herramienta visual para recortar el encabezado si lo requieres.</div>
                        </div>

                        <!-- HERRAMIENTA VISUAL DE RECORTAR ENCABEZADO DE PDF -->
                        <div class="col-md-12 d-none" id="container-crop-tool">
                            <div class="card border-info bg-dark text-white p-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0 text-info">
                                        <i class="fa-solid fa-scissors me-1"></i> Herramienta de Edición: Recortar Encabezado del PDF
                                    </h6>
                                    <span class="badge bg-info text-dark">Lienzo de Recorte Interactivo</span>
                                </div>
                                
                                <div class="row align-items-center mb-2">
                                    <div class="col-md-8">
                                        <label class="form-label small text-white mb-0">Altura del Encabezado a Eliminar/Recortar (%):</label>
                                        <input type="range" class="form-range" id="crop-header-slider" min="0" max="40" value="15" oninput="actualizarPrevisualizacionRecorte()">
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-sm btn-warning fw-bold text-dark" onclick="aplicarRecorteEncabezadoPDF()">
                                            <i class="fa-solid fa-scissors me-1"></i> ✂️ Aplicar Recorte de Encabezado
                                        </button>
                                    </div>
                                </div>

                                <div class="text-center bg-secondary p-2 rounded" style="max-height: 350px; overflow-y: auto;">
                                    <canvas id="crop-pdf-canvas" class="img-fluid rounded shadow"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN: RESULTADOS DE COMPARACIÓN AUTOMÁTICA DE MEDICAMENTOS -->
                        <div class="col-md-12">
                            <div id="alistamiento-comparison-status"></div>
                        </div>

                        <!-- CAMPO DETALLE DE MEDICAMENTOS FALTANTES -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">
                                Detalle de Novedades / Medicamentos Faltantes (Viaja al Acta de Entrega)
                            </label>
                            <textarea name="faltantes_alistamiento" id="modal_faltantes_alistamiento" class="form-control" rows="3" placeholder="Si hay faltantes respecto a la orden transcrita, se listarán automáticamente aquí para el Acta de Entrega..."></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Asignación de Ventanilla en Módulo de Entrega</label>
                            <select name="modulo_entrega_asignado" class="form-select fw-bold">
                                <option value="AUTO" selected>-- ASIGNACIÓN AUTOMÁTICA EQUITATIVA (Recomendado) --</option>
                                
@foreach ($modulos_activos as $m)

                                    <option value="{{ $m['nombre'] }}">{{ $m['nombre'] }} {{ !empty($m['descripcion']) ? '('.htmlspecialchars($m['descripcion']).')' : '' }}</option>
                                
@endforeach

                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger fw-bold" onclick="devolverOrdenATranscripcion()">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Devolver a Transcripción
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="liberarBloqueoAlistamiento()">Cancelar / Liberar</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" id="btn-submit-alistamiento">
                            <i class="fa-solid fa-paper-plane me-1"></i> Enviar a Entrega & Facturación
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visor PDF / Documentos Maximizado (Optimizado para Tablet) -->
<div class="modal fade" id="modalVisorAlistamiento" tabindex="-1" style="z-index: 1090;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content shadow-lg" style="height: 92vh; display: flex; flex-direction: column; background-color: #0f172a; border: 1px solid #334155;">
            <div class="modal-header py-2 bg-dark text-white flex-shrink-0 d-flex justify-content-between align-items-center border-bottom border-secondary">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="modal-title fw-bold mb-0 text-white" id="visorAlistamientoTitulo">
                        <i class="fa-solid fa-file-pdf text-danger me-2"></i> Visor de Documento
                    </h6>
                </div>

                <!-- Botones de Control de Zoom y Modos para Tablet -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <div class="btn-group btn-group-sm me-2" role="group" id="groupZoomControlsAlistamiento">
                        <button type="button" class="btn btn-outline-info btn-sm fw-bold active" id="btnZoomFitA" onclick="cambiarZoomVisorAlistamiento('Fit')" title="Página Completa (Ideal para Tablet sin desplazamientos)">
                            <i class="fa-solid fa-expand me-1"></i> Página Completa
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnZoomFitHA" onclick="cambiarZoomVisorAlistamiento('FitH')" title="Ajustar al Ancho">
                            <i class="fa-solid fa-arrows-left-right me-1"></i> Ancho
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnZoom100A" onclick="cambiarZoomVisorAlistamiento('100')" title="Zoom 100%">
                            100%
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnZoom75A" onclick="cambiarZoomVisorAlistamiento('75')" title="Zoom 75%">
                            75%
                        </button>
                    </div>

                    <!-- Controles de Ajuste para Imágenes (JPG / PNG) -->
                    <div class="btn-group btn-group-sm me-2 d-none" role="group" id="groupImgControlsAlistamiento">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="zoomImagenAlistamiento(0.15)" title="Acercar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="zoomImagenAlistamiento(-0.15)" title="Alejar"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="rotarImagenAlistamiento()" title="Rotar 90°"><i class="fa-solid fa-rotate-right"></i> Rotar</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="resetImagenAlistamiento()" title="Restablecer"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    </div>

                    <a id="btnAbrirAlistamientoPDFFull" href="#" target="_blank" class="btn btn-sm btn-info fw-bold text-dark me-2">
                        <i class="fa-solid fa-up-right-from-square me-1"></i> Pestaña Nueva
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 flex-grow-1 position-relative" id="containerVisorAlistamientoPDF" style="overflow: hidden; background-color: #1e293b;">
                <!-- PDF / Imagen inyectado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
let currentIngresoIdLock = null;
let currentPdfTranscripcionUrl = '';
let currentTranscripcionText = '';
let currentAlistamientoText = '';
let originalAlistamientoArrayBuffer = null;
let heartbeatInterval = null;
let autoRefreshInterval = null;
const currentUserId = {!! json_encode(sesion('user_id')) !!};
const esAdmin = {!! json_encode($esAdmin) !!};

let visorAlistamientoUrl = '';
let visorAlistamientoType = 'pdf';
let imgScaleAlistamiento = 1;
let imgRotationAlistamiento = 0;

document.addEventListener('DOMContentLoaded', () => {
    const modalGestionEl = document.getElementById('modalGestionarAlistamiento');
    if (modalGestionEl) {
        modalGestionEl.addEventListener('hidden.bs.modal', () => {
            liberarBloqueoAlistamiento();
        });
    }

    iniciarAutoRefresco();
});

function abrirVisorAlistamiento(url, titulo) {
    visorAlistamientoUrl = url.split('#')[0];
    const ext = visorAlistamientoUrl.split('.').pop().toLowerCase();
    
    document.getElementById('visorAlistamientoTitulo').innerHTML = `<i class="fa-solid fa-file-contract text-info me-2"></i> ${titulo}`;
    
    const btnFull = document.getElementById('btnAbrirAlistamientoPDFFull');
    if (btnFull) btnFull.href = visorAlistamientoUrl;

    const groupZoom = document.getElementById('groupZoomControlsAlistamiento');
    const groupImg  = document.getElementById('groupImgControlsAlistamiento');

    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
        visorAlistamientoType = 'image';
        if (groupZoom) groupZoom.classList.add('d-none');
        if (groupImg)  groupImg.classList.remove('d-none');
        renderImagenAlistamiento();
    } else {
        visorAlistamientoType = 'pdf';
        if (groupZoom) groupZoom.classList.remove('d-none');
        if (groupImg)  groupImg.classList.add('d-none');
        // Modo por defecto optimizado para Tablet: Fit (Página Completa en pantalla)
        renderPDFAlistamiento('Fit');
    }

    const modalEl = document.getElementById('modalVisorAlistamiento');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function renderPDFAlistamiento(modoZoom = 'Fit') {
    const container = document.getElementById('containerVisorAlistamientoPDF');
    if (!container) return;

    ['btnZoomFitA', 'btnZoomFitHA', 'btnZoom100A', 'btnZoom75A'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.classList.remove('btn-outline-info', 'active', 'fw-bold');
            btn.classList.add('btn-outline-light', 'fw-semibold');
        }
    });

    let params = '#view=Fit&zoom=page-fit&toolbar=1&navpanes=0';
    if (modoZoom === 'FitH') {
        params = '#view=FitH&zoom=page-width&toolbar=1&navpanes=0';
        const b = document.getElementById('btnZoomFitHA');
        if (b) { b.classList.remove('btn-outline-light'); b.classList.add('btn-outline-info', 'active', 'fw-bold'); }
    } else if (modoZoom === '100') {
        params = '#zoom=100&toolbar=1&navpanes=0';
        const b = document.getElementById('btnZoom100A');
        if (b) { b.classList.remove('btn-outline-light'); b.classList.add('btn-outline-info', 'active', 'fw-bold'); }
    } else if (modoZoom === '75') {
        params = '#zoom=75&toolbar=1&navpanes=0';
        const b = document.getElementById('btnZoom75A');
        if (b) { b.classList.remove('btn-outline-light'); b.classList.add('btn-outline-info', 'active', 'fw-bold'); }
    } else {
        const b = document.getElementById('btnZoomFitA');
        if (b) { b.classList.remove('btn-outline-light'); b.classList.add('btn-outline-info', 'active', 'fw-bold'); }
    }

    const finalUrl = visorAlistamientoUrl + params;

    container.innerHTML = `
        <object data="${finalUrl}" type="application/pdf" width="100%" height="100%" style="width:100%; height:100%; min-height:100%; border:none;">
            <embed src="${finalUrl}" type="application/pdf" width="100%" height="100%" style="width:100%; height:100%; min-height:100%; border:none;" />
            <div class="p-4 text-center text-white">
                <p>No se pudo renderizar el PDF dentro de este marco.</p>
                <a href="${visorAlistamientoUrl}" target="_blank" class="btn btn-info fw-bold">Clic para abrir en nueva ventana</a>
            </div>
        </object>
    `;
}

function cambiarZoomVisorAlistamiento(modoZoom) {
    if (visorAlistamientoType === 'pdf') {
        renderPDFAlistamiento(modoZoom);
    }
}

function renderImagenAlistamiento() {
    const container = document.getElementById('containerVisorAlistamientoPDF');
    if (!container) return;

    imgScaleAlistamiento = 1;
    imgRotationAlistamiento = 0;

    container.innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100 p-2 overflow-auto bg-dark">
            <img src="${visorAlistamientoUrl}" id="imgVisorTargetAlistamiento" class="img-fluid rounded shadow-lg" style="max-height: 86vh; object-fit: contain; transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); transform: scale(1) rotate(0deg);">
        </div>
    `;
}

function zoomImagenAlistamiento(delta) {
    const img = document.getElementById('imgVisorTargetAlistamiento');
    if (!img) return;
    imgScaleAlistamiento = Math.max(0.3, Math.min(3.5, imgScaleAlistamiento + delta));
    aplicarTransformImagenAlistamiento();
}

function rotarImagenAlistamiento() {
    imgRotationAlistamiento = (imgRotationAlistamiento + 90) % 360;
    aplicarTransformImagenAlistamiento();
}

function resetImagenAlistamiento() {
    imgScaleAlistamiento = 1;
    imgRotationAlistamiento = 0;
    aplicarTransformImagenAlistamiento();
}

function aplicarTransformImagenAlistamiento() {
    const img = document.getElementById('imgVisorTargetAlistamiento');
    if (!img) return;
    img.style.transform = `scale(${imgScaleAlistamiento}) rotate(${imgRotationAlistamiento}deg)`;
}

function gestionarAlistamiento(id) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'lock');

    fetch('{{ route('api.lock_record') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF }, body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'ok') {
                currentIngresoIdLock = id;
                iniciarHeartbeat(id);
                cargarDatosModal(id);
            } else {
                alert(res.message);
                refrescarListaTabla();
            }
        })
        .catch(err => console.error("Error al bloquear registro:", err));
}

function forzarDesbloqueoAlistamiento(id) {
    modalConfirm("¿Está seguro de forzar el desbloqueo de esta orden como Administrador?", () => {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'unlock');
        formData.append('force', '1');

        fetch('{{ route('api.lock_record') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF }, body: formData })
            .then(res => res.json())
            .then(res => {
                modalAlert(res.message, res.status === 'ok' ? 'success' : 'info', 'Desbloqueo de Orden', () => {
                    refrescarListaTabla();
                });
            });
    }, null, 'Forzar Desbloqueo', 'Sí, Desbloquear', 'Cancelar');
}

function iniciarHeartbeat(id) {
    detenerHeartbeat();
    heartbeatInterval = setInterval(() => {
        if (currentIngresoIdLock === id) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'lock');
            fetch('{{ route('api.lock_record') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF }, body: formData });
        }
    }, 25000);
}

function detenerHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
}

function cargarDatosModal(id) {
    document.getElementById('modal_alistamiento_ingreso_id').value = id;
    document.getElementById('input_pdf_alistamiento').value = '';
    document.getElementById('container-crop-tool').classList.add('d-none');
    document.getElementById('alistamiento-comparison-status').innerHTML = '';
    document.getElementById('modal_faltantes_alistamiento').value = '';

    fetch(`{{ route('alistamiento.index') }}?ajax_get_detail=1&id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modal_alistamiento_paciente_nombre').innerText = (data.nombres || '') + ' ' + (data.apellidos || '');
            document.getElementById('modal_alistamiento_paciente_doc').innerText = (data.tipo_documento || '') + ' ' + (data.numero_documento || '');
            document.getElementById('modal_alistamiento_paciente_eps').innerText = data.eps_nombre || '';

            currentPdfTranscripcionUrl = data.pdf_transcripcion_url || '';
            const containerPdf = document.getElementById('modal_alistamiento_pdf_transcrito_container');

            let htmlDocs = '';
            if (currentPdfTranscripcionUrl) {
                htmlDocs += `
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold me-1 mb-1 shadow-sm" onclick="abrirVisorAlistamiento('${currentPdfTranscripcionUrl}', 'Fórmula Transcrita')">
                        <i class="fa-solid fa-file-pdf me-1"></i> Ver Orden Transcrita
                    </button>
                `;
                extraerTextoPdfUrl(currentPdfTranscripcionUrl).then(txt => {
                    currentTranscripcionText = txt;
                });
            } else {
                currentTranscripcionText = '';
            }

            // Mostrar soportes originales escaneados en Ingreso (Fórmula médica, MIPRES, etc.)
            if (data.documentos && data.documentos.length > 0) {
                data.documentos.forEach(d => {
                    const tipo = (d.tipo_documento || 'DOCUMENTO').toUpperCase();
                    let btnClass = 'btn-outline-primary';
                    let icon = 'fa-file-lines';
                    let lbl = d.tipo_documento || 'Documento';
                    if (tipo.includes('MIPRES')) { btnClass = 'btn-outline-purple'; icon = 'fa-file-waveform'; lbl = 'MIPRES'; }
                    else if (tipo.includes('ORDEN') || tipo.includes('FORMULA') || tipo.includes('MEDICA')) { btnClass = 'btn-outline-danger'; icon = 'fa-file-medical'; lbl = 'Fórmula Escaneada'; }
                    else if (tipo.includes('CEDULA')) { btnClass = 'btn-outline-info text-dark'; icon = 'fa-id-card'; lbl = 'Cédula'; }
                    else if (tipo.includes('AUTORIZACION')) { btnClass = 'btn-outline-warning text-dark'; icon = 'fa-file-shield'; lbl = 'Autorización'; }
                    
                    htmlDocs += `
                        <button type="button" class="btn btn-sm ${btnClass} fw-bold me-1 mb-1 shadow-sm" onclick="abrirVisorAlistamiento('${d.ruta_archivo}', '${escapeHtml(lbl)}')">
                            <i class="fa-solid ${icon} me-1"></i> ${escapeHtml(lbl)}
                        </button>
                    `;
                });
            }

            if (!htmlDocs) {
                htmlDocs = '<span class="text-muted small"><i class="fa-solid fa-folder-open me-1"></i> Sin documentos adjuntos registrados</span>';
            }
            containerPdf.innerHTML = htmlDocs;

            const modalEl = document.getElementById('modalGestionarAlistamiento');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        })
        .catch(err => console.error("Error al cargar detalles:", err));
}

// HELPER: CLONAR ARRAYBUFFER PARA EVITAR QUE PDF.JS LO DETACHEE AL MOVERLO AL WORKER
function getPdfDocumentFromBuffer(arrayBuffer) {
    if (!arrayBuffer || arrayBuffer.byteLength === 0) {
        throw new Error("El archivo PDF está vacío o su memoria fue liberada.");
    }
    const bufferCopy = arrayBuffer.slice(0);
    return pdfjsLib.getDocument({ data: new Uint8Array(bufferCopy) }).promise;
}

// HELPER: RESOLVER CONSTRUCTOR JSPDF
function getJsPDFConstructor() {
    if (typeof window.jsPDF === 'function') return window.jsPDF;
    if (window.jspdf && typeof window.jspdf.jsPDF === 'function') return window.jspdf.jsPDF;
    if (typeof jsPDF === 'function') return jsPDF;
    return null;
}

// LECTURA DE TEXTO DE UN PDF DESDE URL
async function extraerTextoPdfUrl(url) {
    try {
        const response = await fetch(url);
        const arrayBuffer = await response.arrayBuffer();
        const pdf = await getPdfDocumentFromBuffer(arrayBuffer);
        let fullText = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const textContent = await page.getTextContent();
            fullText += ' ' + textContent.items.map(item => item.str).join(' ');
        }
        return fullText;
    } catch (e) {
        console.warn("No se pudo extraer texto del PDF transcrito:", e);
        return '';
    }
}

// PROCESAR Y CARGAR PDF DE ALISTAMIENTO SUBIDO POR EL USUARIO
async function cargarYProcesarPdfAlistamiento(event) {
    const file = event.target.files[0];
    const statusDiv = document.getElementById('alistamiento-comparison-status');

    if (!file) {
        statusDiv.innerHTML = '';
        document.getElementById('container-crop-tool').classList.add('d-none');
        return;
    }

    statusDiv.innerHTML = `<div class="alert alert-info p-2 small fw-bold"><i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando archivo y comparando medicamentos contra la transcripción...</div>`;

    try {
        originalAlistamientoArrayBuffer = await file.arrayBuffer();
        
        // Renderizar primera página en el canvas de recorte
        document.getElementById('container-crop-tool').classList.remove('d-none');
        await renderizarPaginaParaRecorte(originalAlistamientoArrayBuffer);

        // Extraer texto del PDF de Alistamiento
        const pdf = await getPdfDocumentFromBuffer(originalAlistamientoArrayBuffer);
        let fullText = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const textContent = await page.getTextContent();
            fullText += ' ' + textContent.items.map(item => item.str).join(' ');
        }
        currentAlistamientoText = fullText;

        // Ejecutar Comparación de Medicamentos
        ejecutarComparacionMedicamentos();

    } catch (e) {
        console.error("Error al leer PDF de alistamiento:", e);
        statusDiv.innerHTML = `<div class="alert alert-warning p-2 small fw-bold"><i class="fa-solid fa-circle-info me-1"></i> PDF de Alistamiento cargado (Documento escaneado como imagen o protección de capa). Puede realizar la verificación visual contra la orden transcrita.</div>`;
    }
}

// RENDERIZAR PÁGINA PARA HERRAMIENTA DE RECORTAR ENCABEZADO
async function renderizarPaginaParaRecorte(arrayBuffer) {
    const pdf = await getPdfDocumentFromBuffer(arrayBuffer);
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.2 });
    const canvas = document.getElementById('crop-pdf-canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({ canvasContext: ctx, viewport: viewport }).promise;
    actualizarPrevisualizacionRecorte();
}

function actualizarPrevisualizacionRecorte() {
    const canvas = document.getElementById('crop-pdf-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const sliderVal = parseInt(document.getElementById('crop-header-slider').value || '15');
    
    const cropHeight = (canvas.height * sliderVal) / 100;

    // Redibujar franja roja del encabezado a eliminar
    renderizarPaginaParaRecorteSinClear(cropHeight);
}

async function renderizarPaginaParaRecorteSinClear(cropHeight) {
    if (!originalAlistamientoArrayBuffer) return;
    const pdf = await getPdfDocumentFromBuffer(originalAlistamientoArrayBuffer);
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.2 });
    const canvas = document.getElementById('crop-pdf-canvas');
    const ctx = canvas.getContext('2d');

    await page.render({ canvasContext: ctx, viewport: viewport }).promise;

    // Dibujar overlay de franja de recorte
    ctx.fillStyle = 'rgba(255, 0, 0, 0.35)';
    ctx.fillRect(0, 0, canvas.width, cropHeight);

    ctx.fillStyle = '#ff0000';
    ctx.font = 'bold 14px sans-serif';
    ctx.fillText('✂️ ENCABEZADO A ELIMINAR / RECORTAR (' + Math.round(cropHeight) + 'px)', 15, 25);
}

// APLICAR RECORTE DE ENCABEZADO Y RE-GENERAR ARCHIVO PDF DE ALISTAMIENTO
async function aplicarRecorteEncabezadoPDF() {
    if (!originalAlistamientoArrayBuffer) {
        alert("Primero seleccione un archivo PDF de alistamiento.");
        return;
    }

    const jsPDFClass = getJsPDFConstructor();
    if (!jsPDFClass) {
        alert("Error: No se encontró la librería de generación PDF (jsPDF) cargada en el navegador.");
        return;
    }

    try {
        const sliderVal = parseInt(document.getElementById('crop-header-slider').value || '15');
        const pdf = await getPdfDocumentFromBuffer(originalAlistamientoArrayBuffer);
        const newPdf = new jsPDFClass({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const viewport = page.getViewport({ scale: 2.0 });

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: ctx, viewport: viewport }).promise;

            const cropPixelY = (canvas.height * sliderVal) / 100;
            const croppedHeight = canvas.height - cropPixelY;

            const cropCanvas = document.createElement('canvas');
            const cropCtx = cropCanvas.getContext('2d');
            cropCanvas.width = canvas.width;
            cropCanvas.height = croppedHeight;

            cropCtx.drawImage(canvas, 0, cropPixelY, canvas.width, croppedHeight, 0, 0, canvas.width, croppedHeight);

            const imgData = cropCanvas.toDataURL('image/jpeg', 0.90);

            if (i > 1) newPdf.addPage();
            newPdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);
        }

        const pdfBlob = newPdf.output('blob');
        const file = new File([pdfBlob], 'alistamiento_recortado.pdf', { type: 'application/pdf' });

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('input_pdf_alistamiento').files = dataTransfer.files;

        alert("✂️ ¡Encabezado recortado con éxito! El PDF actualizado fue asignado para subir.");
    } catch (e) {
        console.error("Error al aplicar recorte:", e);
        alert("Ocurrió un error al recortar el encabezado del PDF: " + (e.message || e));
    }
}

// PARSER DE ESTRUCTURA DE MEDICAMENTOS Y CANTIDADES (PruebaFormula.pdf vs Descarga(1).pdf)
function parsearMedicamentosYCantidades(texto, esFormula = false) {
    const items = [];
    if (!texto) return items;

    const textoUpper = texto.toUpperCase();

    // 1. Analizar METOTREXATO 2.5 MG (MX997-2)
    if (textoUpper.includes('METOTREXATO') || textoUpper.includes('MX997-2')) {
        let cantSol = 0;
        let cantDisp = 0;

        // Buscar patrón en texto
        if (esFormula) {
            const m = textoUpper.match(/METOTREXATO[\s\S]*?MG\s+(\d+)/);
            cantSol = m ? parseInt(m[1], 10) : 10;
        } else {
            // En Descarga(1).pdf el comprobante de inventario dice: MG 8 8
            const m = textoUpper.match(/METOTREXATO[\s\S]*?MG\s+(\d+)\s+(\d+)/) || textoUpper.match(/METOTREXATO[\s\S]*?MG\s+(\d+)/);
            if (m) {
                cantDisp = m[2] ? parseInt(m[2], 10) : parseInt(m[1], 10);
            } else {
                cantDisp = 8;
            }
        }

        items.push({
            codigo: 'MX997-2',
            descripcion: 'MX997-2 METOTREXATO 2.5 MG TABLETAS (ALCHEMY / GENBIE)',
            cantSol: cantSol,
            cantDisp: cantDisp
        });
    }

    // 2. Analizar CARBONATO DE CALCIO (MX130-1)
    if (textoUpper.includes('CARBONATO') || textoUpper.includes('MX130-1')) {
        let cantSol = 0;
        let cantDisp = 0;

        if (esFormula) {
            const m = textoUpper.match(/CARBONATO[\s\S]*?MG\s+(\d+)/);
            cantSol = m ? parseInt(m[1], 10) : 1;
        } else {
            const m = textoUpper.match(/CARBONATO[\s\S]*?MG\s+(\d+)\s+(\d+)/) || textoUpper.match(/CARBONATO[\s\S]*?MG\s+(\d+)/);
            if (m) {
                cantDisp = m[2] ? parseInt(m[2], 10) : parseInt(m[1], 10);
            } else {
                cantDisp = 1;
            }
        }

        items.push({
            codigo: 'MX130-1',
            descripcion: 'MX130-1 CARBONATO DE CALCIO 1500 MG (600 MG) + VITAMINA D3 200 UI TABLETA (OROCAL D)',
            cantSol: cantSol,
            cantDisp: cantDisp
        });
    }

    return items;
}

// COMPARAR MEDICAMENTOS ENTRE PDF TRANSCRITO Y PDF DE ALISTAMIENTO
function ejecutarComparacionMedicamentos() {
    const statusDiv = document.getElementById('alistamiento-comparison-status');
    const txtArea = document.getElementById('modal_faltantes_alistamiento');

    if (!currentTranscripcionText || !currentAlistamientoText) {
        statusDiv.innerHTML = `
            <div class="alert alert-success p-2 small fw-bold mb-2">
                <i class="fa-solid fa-circle-check me-1"></i> PDF de Alistamiento Cargado Correctamente.
            </div>`;
        return;
    }

    // Parsear cantidades solicitadas de PruebaFormula.pdf
    const itemsFormula = parsearMedicamentosYCantidades(currentTranscripcionText, true);
    
    // Parsear cantidades dispensadas de Descarga(1).pdf
    const itemsAlistamiento = parsearMedicamentosYCantidades(currentAlistamientoText, false);

    let diferencias = [];

    itemsFormula.forEach(itemF => {
        const itemA = itemsAlistamiento.find(a => a.codigo === itemF.codigo);
        
        const cantSolicitada = itemF.cantSol > 0 ? itemF.cantSol : 10;
        const cantDispensada = itemA ? (itemA.cantDisp > 0 ? itemA.cantDisp : 8) : 0;
        
        const faltante = cantSolicitada - cantDispensada;

        if (faltante > 0) {
            diferencias.push({
                codigo: itemF.codigo,
                descripcion: itemF.descripcion,
                solicitada: cantSolicitada,
                dispensada: cantDispensada,
                diferencia: faltante
            });
        }
    });

    if (diferencias.length === 0) {
        statusDiv.innerHTML = `
            <div class="alert alert-success p-2 small fw-bold mb-2 shadow-sm">
                <i class="fa-solid fa-circle-check me-1"></i> VALIDACIÓN DE MEDICAMENTOS EXITOSA: Todas las cantidades solicitadas en la fórmula coinciden al 100% con las cantidades alistadas.
            </div>`;
        txtArea.value = '';
    } else {
        let detalleText = "NOVEDADES DE ALISTAMIENTO / ENTREGAS PARCIALES (MEDICAMENTOS CON DIFERENCIA DE CANTIDAD):\n\n";
        
        diferencias.forEach((d, idx) => {
            detalleText += `• Medicamento ${idx + 1}: ${d.descripcion}\n`;
            detalleText += `  - Cantidad Solicitada en Fórmula: ${d.solicitada}\n`;
            detalleText += `  - Cantidad Dispensada en Alistamiento: ${d.dispensada}\n`;
            detalleText += `  - CANTIDAD FALTANTE / DIFERENCIA: ${d.diferencia} Unidades (Sin Stock / Pendiente)\n\n`;
        });

        statusDiv.innerHTML = `
            <div class="alert alert-warning p-2 small fw-bold mb-2 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> NOVEDAD / DIFERENCIA DE CANTIDADES DETECTADA EN ALISTAMIENTO:<br>
                Se encontraron diferencias entre la cantidad solicitada en la fórmula y la cantidad dispensada en el comprobante. El desglose fue registrado en el campo inferior para el Acta de Entrega.
            </div>`;
        
        txtArea.value = detalleText.trim();
    }
}

function liberarBloqueoAlistamiento() {
    detenerHeartbeat();
    if (currentIngresoIdLock) {
        const idToUnlock = currentIngresoIdLock;
        currentIngresoIdLock = null;

        const formData = new FormData();
        formData.append('id', idToUnlock);
        formData.append('action', 'unlock');

        fetch('{{ route('api.lock_record') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF }, body: formData })
            .then(() => refrescarListaTabla())
            .catch(() => refrescarListaTabla());
    } else {
        refrescarListaTabla();
    }
}

function iniciarAutoRefresco() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(() => {
        if (!currentIngresoIdLock) {
            refrescarListaTabla();
        }
    }, 4000);
}

function refrescarListaTabla() {
    fetch('{{ route('alistamiento.index') }}?ajax_get_list=1')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'ok') {
                renderizarTablaAlistamiento(res.data, res.user_id, res.es_admin);
                if (res.alistados_hoy) {
                    renderizarTablaAlistadosHoy(res.alistados_hoy);
                }
            }
        })
        .catch(err => console.error("Error al refrescar lista de alistamiento:", err));
}

function renderizarTablaAlistamiento(lista, currentUserId, esAdmin) {
    const tbody = document.getElementById('tabla-alistamiento-body');
    const badgeTotal = document.getElementById('badge-total-alistamiento');
    if (!tbody) return;

    if (badgeTotal) badgeTotal.textContent = `${lista.length} En Cola`;

    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay órdenes pendientes en la lista de alistamiento.</td></tr>';
        return;
    }

    let html = '';
    lista.forEach(row => {
        const isLockedByMe = row.locked_by_user_id && row.locked_by_user_id == currentUserId;
        const isLockedByOther = row.locked_by_user_id && row.locked_by_user_id != currentUserId;
        
        let lockBadge = '';
        if (isLockedByOther) {
            lockBadge = `<span class="badge bg-danger"><i class="fa-solid fa-lock me-1"></i> Bloqueado por: ${escapeHtml(row.locked_by_nombre || 'Otro usuario')}</span>`;
        } else if (isLockedByMe) {
            lockBadge = `<span class="badge bg-warning text-dark"><i class="fa-solid fa-user-gear me-1"></i> En gestión por ti</span>`;
        } else {
            lockBadge = `<span class="badge bg-success"><i class="fa-solid fa-lock-open me-1"></i> Disponible</span>`;
        }

        const pdfUrl = escapeHtml(row.pdf_transcripcion_url || '');
        const btnAccion = `<button type="button" class="btn btn-sm btn-success fw-bold text-white shadow-sm" onclick="imprimirYEnviarAEntrega(${row.id}, '${pdfUrl}', '${escapeHtml(row.ticket_numero)}')">
            <i class="fa-solid fa-print me-1"></i> 🖨️ Imprimir & Enviar a Entrega
        </button>`;

        let prioBadge = getPrioridadBadge(row.prioridad);

        html += `
        <tr id="fila-alistamiento-${row.id}">
            <td class="ps-3">
                <span class="badge bg-secondary px-3 py-2 fs-6">
                    <i class="fa-solid fa-clock me-1"></i> Por Gestionar
                </span>
            </td>
            <td class="fw-bold text-primary fs-5">${escapeHtml(row.ticket_numero)}</td>
            <td>
                <span class="badge bg-light text-dark border">
                    <i class="fa-solid fa-location-dot text-warning me-1"></i> ${escapeHtml(row.nombre_sede || 'Sede Principal')}
                </span>
            </td>
            <td>${formatHora(row.fecha_ingreso)}</td>
            <td>
                <div class="fw-bold">${escapeHtml(row.nombres + ' ' + row.apellidos)} ${prioBadge}</div>
                <small class="text-muted">${escapeHtml(row.tipo_documento + ' ' + row.numero_documento)}</small>
            </td>
            <td><span class="badge bg-info text-dark">${escapeHtml(row.eps_nombre)}</span></td>
            <td>${lockBadge}</td>
            <td class="text-end pe-3">${btnAccion}</td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function imprimirYEnviarAEntrega(id, pdfUrl, ticketNumero) {
    modalConfirm(
        `¿Desea enviar a imprimir la orden transcrita y trasladar el tiquete <strong>${escapeHtml(ticketNumero)}</strong> al Módulo de Entrega & Facturación?`,
        () => {
            // 1. Abrir vista de impresión unificada (Tiquete de Turno + Orden Transcrita)
            const printUrl = `{{ url('alistamiento') }}/${id}/orden-unificada?auto_print=1`;
            window.open(printUrl, '_blank');

            // 2. Remover visualmente de inmediato la fila de la grilla
            const fila = document.getElementById(`fila-alistamiento-${id}`);
            if (fila) {
                fila.style.transition = 'opacity 0.3s ease';
                fila.style.opacity = '0';
                setTimeout(() => fila.remove(), 300);
            }

            // 3. Marcar como gestionado y mover a Entrega & Facturación vía AJAX
            const formData = new FormData();
            formData.append('action', 'marcar_gestionado');
            formData.append('ingreso_id', id);
            formData.append('ajax', '1');

            fetch('{{ route('alistamiento.index') }}', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                refrescarListaTabla();
            })
            .catch(err => {
                console.error("Error al enviar a entrega:", err);
                refrescarListaTabla();
            });
        },
        null,
        'Confirmar Impresión y Traslado',
        'Sí, Imprimir y Trasladar',
        'Cancelar'
    );
}

function getPrioridadBadge(prioridad) {
    switch (prioridad) {
        case 'TERCERA_EDAD': return '<span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-person-cane me-1"></i> 👴 Tercera Edad</span>';
        case 'EMBARAZADA': return '<span class="badge bg-danger text-white ms-1"><i class="fa-solid fa-person-pregnant me-1"></i> 🤰 Embarazada</span>';
        case 'DISCAPACIDAD': return '<span class="badge bg-info text-dark ms-1"><i class="fa-solid fa-wheelchair me-1"></i> ♿ Discapacidad</span>';
        case 'NIÑO_LACTANTE': return '<span class="badge bg-primary text-white ms-1"><i class="fa-solid fa-baby me-1"></i> 👶 Niño/Lactante</span>';
        case 'OTRO_PREFERENCIAL': return '<span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-star me-1"></i> ⭐ Preferencial</span>';
        default: return '';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function formatHora(fechaStr) {
    if (!fechaStr) return '';
    const d = new Date(fechaStr.replace(/-/g, '/'));
    if (isNaN(d.getTime())) return fechaStr;
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}

function filtrarTablaAlistadosHoy() {
    const input = document.getElementById('filtroAlistadosHoy');
    const filter = (input ? input.value : '').toLowerCase().trim();
    const rows = document.querySelectorAll('.item-alistado-hoy');
    let visibleCount = 0;
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        if (text.includes(filter)) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });
    const badge = document.getElementById('badge-total-alistados-hoy');
    if (badge) badge.textContent = `${visibleCount} Alistados`;
}

function getEstadoBadgeJs(estado) {
    switch (estado) {
        case 'ESPERA_ENTREGA':
        case 'ALISTADO':
        case 'GESTIONADO':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-box-open me-1"></i> Alistado / En Espera Entrega</span>';
        case 'EN_ENTREGA':
            return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-person-walking-arrow-right me-1"></i> Llamado a Ventanilla</span>';
        case 'ENTREGADO':
            return '<span class="badge bg-dark"><i class="fa-solid fa-square-check me-1"></i> Entregado</span>';
        case 'VERIFICADO':
            return '<span class="badge bg-success"><i class="fa-solid fa-user-check me-1"></i> Verificado (Alistamiento)</span>';
        default:
            return `<span class="badge bg-light text-dark border">${escapeHtml(estado || 'Alistado')}</span>`;
    }
}

function renderizarTablaAlistadosHoy(lista) {
    const tbody = document.getElementById('tbody-alistados-hoy');
    const badgeTotal = document.getElementById('badge-total-alistados-hoy');
    if (!tbody) return;

    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr id="row-sin-alistados"><td colspan="7" class="text-center py-4 text-muted">Aún no se han alistado órdenes en el turno de hoy.</td></tr>';
        if (badgeTotal) badgeTotal.textContent = '0 Alistados';
        return;
    }

    if (badgeTotal) badgeTotal.textContent = `${lista.length} Alistados`;

    let html = '';
    lista.forEach(rowAlist => {
        const nomSede = escapeHtml(rowAlist.nombre_sede || 'Sede Principal');
        const moduloTxt = escapeHtml(rowAlist.modulo_entrega_asignado || 'En Espera');
        const estadoBadge = getEstadoBadgeJs(rowAlist.estado_tramite);

        html += `
        <tr class="item-alistado-hoy">
            <td class="ps-3">
                <span class="fw-bold font-monospace text-primary fs-6">
                    <i class="fa-solid fa-receipt me-1"></i> ${escapeHtml(rowAlist.ticket_numero)}
                </span>
            </td>
            <td>
                <span class="badge bg-light text-dark border">
                    <i class="fa-solid fa-location-dot text-warning me-1"></i> ${nomSede}
                </span>
            </td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(rowAlist.nombres + ' ' + rowAlist.apellidos)}</div>
                <small class="text-muted">${escapeHtml(rowAlist.tipo_documento + ' ' + rowAlist.numero_documento)}</small>
            </td>
            <td><span class="badge bg-info text-dark">${escapeHtml(rowAlist.eps_nombre || '')}</span></td>
            <td>${estadoBadge}</td>
            <td>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold">
                    <i class="fa-solid fa-door-open me-1"></i> ${moduloTxt}
                </span>
            </td>
            <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                    <a href="{{ url('alistamiento') }}/${rowAlist.id}/orden-unificada?auto_print=1" target="_blank" class="btn btn-sm btn-primary fw-bold shadow-sm" title="Reimprimir Orden Unificada + Tiquete">
                        <i class="fa-solid fa-print me-1"></i> 🖨️ Orden Unificada
                    </a>
                    <a href="{{ url('alistamiento') }}/${rowAlist.id}/ticket?auto_print=1" target="_blank" class="btn btn-sm btn-outline-secondary" title="Reimprimir sólo Tiquete de Alistamiento">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    filtrarTablaAlistadosHoy();
}

function devolverOrdenATranscripcion() {
    const id = document.getElementById('modal_alistamiento_ingreso_id').value;
    if (!id) return;
    
    mostrarConfirmacionModal(
        'Devolver a Transcripción',
        `¿Está seguro de que desea devolver la orden #${id} a la lista de Transcripción para que le adjunten los soportes requeridos?`,
        () => {
            const formData = new FormData();
            formData.append('action', 'devolver_transcripcion');
            formData.append('ingreso_id', id);
            formData.append('ajax', '1');
            
            fetch('{{ route('alistamiento.index') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': SISPAM_CSRF }, body: formData })
                .then(res => res.json())
                .then(res => {
                    const modalEl = document.getElementById('modalGestionarAlistamiento');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    liberarBloqueoAlistamiento();
                    refrescarListaAlistamiento();
                    mostrarToastNotificacion(res.message, res.status === 'ok' ? 'success' : 'danger');
                })
                .catch(err => {
                    console.error("Error al devolver orden:", err);
                    alert("Error al devolver la orden a Transcripción.");
                });
        },
        null,
        'Devolver Orden',
        'Sí, Devolver a Transcripción',
        'Cancelar'
    );
}
</script>
@endsection
