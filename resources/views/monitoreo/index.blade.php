@extends('layouts.app')

@section('titulo', 'Módulo de Monitoreo & Verificación - ' . config('app.name'))

@push('estilos')
<style>
@keyframes blinkMipresAnim {
    0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 14px rgba(239, 68, 68, 0.95); }
    50% { opacity: 0.25; transform: scale(0.96); box-shadow: none; }
}
.blinking-mipres {
    animation: blinkMipresAnim 1s infinite ease-in-out;
    font-weight: 800;
    letter-spacing: 0.5px;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="fa-solid fa-eye me-2"></i> Módulo de Monitoreo &amp; Verificación
            </h3>
            <p class="text-muted mb-0">Control de Calidad: Compare la orden médica escaneada vs la fórmula transcrita antes del alistamiento.</p>
        </div>
        <div>
            <span class="badge bg-purple text-white fs-6 px-3 py-2" style="background-color: #6f42c1;">
                <i class="fa-solid fa-clipboard-check me-1"></i> {{ count($ingresosMonitoreo) }} Órdenes por Verificar
            </span>
        </div>
    </div>

    @include('partials.alertas')

    <div class="card card-glass border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tiquete</th>
                            <th>Sede</th>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>EPS</th>
                            <th>Prioridad</th>
                            <th>Transcriptor</th>
                            <th>Estado Bloqueo</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($ingresosMonitoreo))
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-circle-check fa-3x mb-3 text-success"></i>
                                    <h5>No hay órdenes pendientes por monitorear</h5>
                                    <p class="mb-0">Todas las transcripciones han sido verificadas.</p>
                                </td>
                            </tr>
                        @else
                            @php $userId = auth()->id(); $esAdmin = auth()->user()?->esAdministrador(); @endphp
                            @foreach ($ingresosMonitoreo as $ing)
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">
                                        {{ $ing['ticket_numero'] }}
                                        @if (!empty($ing['contiene_mipres']) && $ing['contiene_mipres'] === 'SI')
                                            <span class="badge bg-danger text-white ms-1 fw-bold" style="font-size: 0.72rem;"><i class="fa-solid fa-file-waveform me-1"></i> MIPRES</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $ing['nombre_sede'] ?? 'Sede Principal' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $ing['nombres'] . ' ' . $ing['apellidos'] }}</div>
                                        <small class="text-muted">Ingreso: {{ date('d/m/Y h:i A', strtotime($ing['fecha_ingreso'])) }}</small>
                                    </td>
                                    <td>{{ $ing['tipo_documento'] . ' ' . $ing['numero_documento'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $ing['eps_nombre'] }}</span></td>
                                    <td>{!! get_prioridad_badge($ing['prioridad'] ?? 'NORMAL') !!}</td>
                                    <td>
                                        <small><i class="fa-solid fa-user-pen me-1"></i> Transcrito</small>
                                    </td>
                                    <td>
                                        @if (!empty($ing['locked_by_user']) && $ing['locked_by_user'] != $userId)
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-solid fa-lock me-1"></i> En uso por {{ $ing['locked_by_nombre'] ?? 'Otro Usuario' }}
                                            </span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                <i class="fa-solid fa-lock-open me-1"></i> Disponible
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if (!empty($ing['locked_by_user']) && $ing['locked_by_user'] != $userId && !$esAdmin)
                                            <button type="button" class="btn btn-sm btn-secondary disabled" disabled>
                                                <i class="fa-solid fa-lock me-1"></i> Bloqueado
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-purple text-white fw-bold shadow-sm" style="background-color: #6f42c1;" onclick="abrirMonitoreoComparativo({{ $ing['id'] }})">
                                                <i class="fa-solid fa-code-compare me-1"></i> Comparar &amp; Verificar
                                            </button>
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
</div>

<!-- Modal Visor Comparativo Lado a Lado (Side-by-Side) para Monitor -->
<div class="modal fade" id="modalMonitoreoComparativo" tabindex="-1" data-bs-backdrop="static" style="z-index: 1080;">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content style-dark" style="background-color: #0f172a; color: #fff;">
            <div class="modal-header py-2 bg-dark text-white border-bottom border-secondary d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title fw-bold mb-0 text-info">
                        <i class="fa-solid fa-eye me-2"></i> Verificación de Calidad de Transcripción: <span id="lblTicketMonitoreo" class="text-white"></span>
                    </h5>
                    <span id="lblPacienteMonitoreo" class="badge bg-secondary fs-6"></span>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarMonitoreoComparativo()"></button>
            </div>
            
            <div class="modal-body p-2 d-flex flex-column" style="height: calc(100vh - 120px); overflow: hidden;">
                <div class="row g-2 flex-grow-1 h-100">
                    <!-- Panel Izquierdo: Orden Médica Escaneada Inicial -->
                    <div class="col-md-6 d-flex flex-column h-100">
                        <div class="card bg-dark border-secondary h-100 d-flex flex-column">
                            <div class="card-header py-2 bg-secondary bg-opacity-20 text-white fw-bold d-flex justify-content-between align-items-center border-bottom border-secondary">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-file-medical text-danger fs-5"></i>
                                    <span class="text-white">1. Orden Médica Escaneada (Fórmula)</span>
                                    <span id="badgeMipresAlertaMonitoreo" class="badge bg-danger text-white fs-6 ms-2 blinking-mipres d-none">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> CONTIENE MIPRES
                                    </span>
                                </div>
                                <div id="selectorDocsMonitoreoCont" class="d-none">
                                    <select id="selectDocMonitoreo" class="form-select form-select-sm bg-dark text-info border-info fw-bold py-0" style="font-size: 0.82rem; max-width: 260px;" onchange="cambiarDocMonitoreo(this.value)">
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0 flex-grow-1 position-relative overflow-hidden" id="containerEscaneadoMonitoreo">
                            </div>
                        </div>
                    </div>

                    <!-- Panel Derecho: PDF / Datos Transcritos por Transcriptor -->
                    <div class="col-md-6 d-flex flex-column h-100">
                        <div class="card bg-dark border-secondary h-100 d-flex flex-column">
                            <div class="card-header py-2 bg-secondary bg-opacity-20 text-white fw-bold d-flex justify-content-between align-items-center border-bottom border-secondary">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-file-pdf text-info fs-5"></i>
                                    <span class="text-white fw-bold">2. Fórmula Transcrita por Transcriptor</span>
                                </div>
                                <div id="selectorTranscripcionesMonitoreoCont" class="d-none">
                                    <select id="selectTranscripcionMonitoreo" class="form-select form-select-sm bg-dark text-warning border-warning fw-bold py-0" style="font-size: 0.82rem; max-width: 280px;" onchange="cambiarTranscripcionMonitoreo(this.value)">
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0 flex-grow-1 position-relative overflow-hidden" id="containerTranscritoMonitoreo">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer: Botones de Decisión, Auditoría MIPRES y Formulario de Corrección -->
            <div class="modal-footer bg-dark border-top border-secondary py-2">
                <form id="formVerificacionMonitor" method="POST" action="{{ route('monitoreo.verificar') }}" enctype="multipart/form-data" class="w-100 d-flex flex-column gap-2">
                    @csrf
                    <input type="hidden" name="action" value="guardar_verificacion">
                    <input type="hidden" name="ingreso_id" id="monitoreoIngresoId">
                    <input type="hidden" name="estado_verificacion" id="monitoreoEstadoVerificacion" value="VERIFICADA">
                    <input type="hidden" name="monitoreo_contiene_mipres" id="input_monitoreo_contiene_mipres_hidden" value="NO">

                    <!-- Barra Superior del Footer: Auditoría de MIPRES por el Monitor -->
                    <div id="card_auditoria_mipres_monitor" class="w-100 p-2 px-3 bg-black bg-opacity-40 rounded-3 border border-secondary border-opacity-50 d-none flex-column gap-1">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-purple text-white p-2 rounded-2" style="background-color: #6f42c1;">
                                        <i class="fa-solid fa-file-waveform fs-6"></i>
                                    </span>
                                    <span class="fw-bold text-white small">¿Orden con Prescripción MIPRES?</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="monitoreo_contiene_mipres_opt" id="mon_mipres_si" value="SI" onchange="toggleMonitorMipresUpload(true)">
                                        <label class="form-check-label text-white fw-bold small" for="mon_mipres_si" style="cursor: pointer;">
                                            <i class="fa-solid fa-circle-check text-success me-1"></i> SÍ Contiene MIPRES
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="monitoreo_contiene_mipres_opt" id="mon_mipres_no" value="NO" checked onchange="toggleMonitorMipresUpload(false)">
                                        <label class="form-check-label text-white-50 small" for="mon_mipres_no" style="cursor: pointer;">
                                            <i class="fa-solid fa-circle-xmark text-danger me-1"></i> NO Contiene
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra Inferior del Footer: Controles de Corrección y Aprobación -->
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap w-100">
                        <div class="d-flex align-items-center gap-2 flex-grow-1" id="sectionErroresForm" style="display: none !important;">
                            <input type="text" name="observaciones" id="monitoreoObservaciones" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Describa el error de transcripción detectado..." style="max-width: 320px;">
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <span class="input-group-text bg-secondary text-white border-secondary"><i class="fa-solid fa-file-upload"></i></span>
                                <input type="file" name="nuevo_pdf_file" class="form-control bg-dark text-white border-secondary" accept=".pdf,.png,.jpg,.jpeg">
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-outline-warning text-white fw-bold" id="btnMarcarErrorMonitoreo" onclick="activarModoErrorMonitoreo()">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Con Errores de Transcripción (Editar/Subir Nuevo)
                            </button>
                            <button type="submit" class="btn btn-success fw-bold px-4" id="btnAprobarMonitoreo" onclick="document.getElementById('monitoreoEstadoVerificacion').value='VERIFICADA'">
                                <i class="fa-solid fa-circle-check me-1"></i> VERIFICADA OK (Pasar a Alistamiento)
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cerrarMonitoreoComparativo()">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentMonitoreoIngresoId = null;
let currentMipresUrl = null;

function toggleMonitorMipresUpload(isSi) {
    const fileCont = document.getElementById('monitoreo_mipres_file_container');
    const badgeMipres = document.getElementById('badgeMipresAlertaMonitoreo');
    const feedbackEl = document.getElementById('monitoreo_mipres_feedback');
    const cardBar = document.getElementById('card_auditoria_mipres_monitor');

    if (feedbackEl) feedbackEl.classList.add('d-none');
    if (cardBar) cardBar.classList.remove('border-danger', 'bg-danger', 'bg-opacity-25');

    if (isSi) {
        if (fileCont) fileCont.classList.remove('d-none');
        if (badgeMipres) badgeMipres.classList.remove('d-none');
    } else {
        if (fileCont) fileCont.classList.add('d-none');
        if (badgeMipres) badgeMipres.classList.add('d-none');
        const fileInp = document.getElementById('monitoreo_pdf_mipres_file');
        if (fileInp) fileInp.value = '';
    }
}

function cambiarDocMonitoreo(url) {
    renderizarDocEscaneado(url);
}

function cambiarTranscripcionMonitoreo(url) {
    renderizarDocTranscrito(url);
}

function renderizarDocEscaneado(url) {
    const containerEscaneado = document.getElementById('containerEscaneadoMonitoreo');
    if (!containerEscaneado) return;

    if (!url) {
        containerEscaneado.innerHTML = `
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted p-4 text-center">
                <i class="fa-solid fa-file-circle-xmark fa-3x mb-3 text-warning"></i>
                <h6 class="text-white">No hay documento escaneado adjunto</h6>
                <small>El ingreso no cuenta con archivo escaneado de orden médica.</small>
            </div>`;
        return;
    }

    const ext = url.split('.').pop().split('?')[0].toLowerCase();
    const cleanUrl = url.startsWith('http') || url.startsWith('/') ? url : `/${url}`;
    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
        containerEscaneado.innerHTML = `
            <div class="h-100 p-2 overflow-auto text-center bg-dark d-flex align-items-center justify-content-center">
                <img src="${cleanUrl}" class="img-fluid rounded shadow" style="max-height: 80vh; max-width: 100%; object-fit: contain;">
            </div>`;
    } else {
        containerEscaneado.innerHTML = `
            <object data="${cleanUrl}#view=Fit&toolbar=1" type="application/pdf" width="100%" height="100%">
                <embed src="${cleanUrl}#view=Fit&toolbar=1" type="application/pdf" width="100%" height="100%" />
            </object>`;
    }
}

function renderizarDocTranscrito(url) {
    const containerTranscrito = document.getElementById('containerTranscritoMonitoreo');
    if (!containerTranscrito) return;

    if (!url) {
        containerTranscrito.innerHTML = `
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted p-4 text-center">
                <i class="fa-solid fa-file-circle-xmark fa-3x mb-3 text-warning"></i>
                <h6 class="text-white">No hay archivo transcrito adjunto</h6>
                <small>El transcriptor no ha adjuntado el archivo de orden transcrita.</small>
            </div>`;
        return;
    }

    const ext = url.split('.').pop().split('?')[0].toLowerCase();
    const cleanUrl = url.startsWith('http') || url.startsWith('/') ? url : `/${url}`;
    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
        containerTranscrito.innerHTML = `
            <div class="h-100 p-2 overflow-auto text-center bg-dark d-flex align-items-center justify-content-center">
                <img src="${cleanUrl}" class="img-fluid rounded shadow" style="max-height: 80vh; max-width: 100%; object-fit: contain;">
            </div>`;
    } else {
        containerTranscrito.innerHTML = `
            <object data="${cleanUrl}#view=Fit&toolbar=1" type="application/pdf" width="100%" height="100%">
                <embed src="${cleanUrl}#view=Fit&toolbar=1" type="application/pdf" width="100%" height="100%" />
            </object>`;
    }
}

function abrirMonitoreoComparativo(id) {
    currentMonitoreoIngresoId = id;
    document.getElementById('monitoreoIngresoId').value = id;
    
    // Bloquear registro
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(`/api/ingresos/${id}/lock`, { 
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    });

    // Cargar detalles
    fetch(`{{ route('monitoreo.index') }}?ajax_get_detail=1&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data || !data.id) {
                document.getElementById('lblTicketMonitoreo').innerText = `ID #${id}`;
            } else {
                document.getElementById('lblTicketMonitoreo').innerText = data.ticket_numero || `ID #${id}`;
                document.getElementById('lblPacienteMonitoreo').innerText = `${data.nombres || ''} ${data.apellidos || ''} (${data.tipo_documento || ''} ${data.numero_documento || ''})`;
            }
            
            document.getElementById('sectionErroresForm').style.setProperty('display', 'none', 'important');
            document.getElementById('monitoreoObservaciones').value = '';
            document.getElementById('monitoreoEstadoVerificacion').value = 'VERIFICADA';

            const currentDocs = data.documentos || [];
            const selectorCont = document.getElementById('selectorDocsMonitoreoCont');
            const selectDoc = document.getElementById('selectDocMonitoreo');
            if (selectDoc) selectDoc.innerHTML = '';

            let docOrdenMedicaUrl = '';

            if (currentDocs.length > 0) {
                let docOrden = currentDocs.find(d => {
                    const tipo = (d.tipo_documento || '').toUpperCase();
                    const ruta = (d.ruta_archivo || '').toUpperCase();
                    const nombre = (d.nombre_original || '').toUpperCase();
                    return tipo.includes('ORDEN') || tipo.includes('FORMULA') || tipo.includes('MEDICA') || tipo.includes('RECETA') ||
                           ruta.includes('ORDEN') || ruta.includes('FORMULA') || nombre.includes('ORDEN') || nombre.includes('FORMULA');
                });

                if (!docOrden) {
                    docOrden = currentDocs.find(d => {
                        const tipo = (d.tipo_documento || '').toUpperCase();
                        return !tipo.includes('CEDULA') && !tipo.includes('IDENTIDAD');
                    });
                }

                if (!docOrden) {
                    docOrden = currentDocs[currentDocs.length - 1];
                }

                docOrdenMedicaUrl = docOrden ? docOrden.ruta_archivo : '';

                if (selectDoc && currentDocs.length > 1) {
                    currentDocs.forEach((d, idx) => {
                        const opt = document.createElement('option');
                        opt.value = d.ruta_archivo;
                        const esEstaOrden = (d.ruta_archivo === docOrdenMedicaUrl);
                        const tipoTxt = d.tipo_documento || ('Doc ' + (idx + 1));
                        opt.textContent = `${tipoTxt}${esEstaOrden ? ' (⭐ Orden Médica)' : ''}`;
                        if (esEstaOrden) opt.selected = true;
                        selectDoc.appendChild(opt);
                    });
                    if (selectorCont) selectorCont.classList.remove('d-none');
                } else if (selectorCont) {
                    selectorCont.classList.add('d-none');
                }
            } else if (data.foto_paciente_url) {
                docOrdenMedicaUrl = data.foto_paciente_url;
                if (selectorCont) selectorCont.classList.add('d-none');
            } else if (selectorCont) {
                selectorCont.classList.add('d-none');
            }

            renderizarDocEscaneado(docOrdenMedicaUrl);

            const transcripcionesList = data.transcripciones_archivos || [];
            const selectorTransCont = document.getElementById('selectorTranscripcionesMonitoreoCont');
            const selectTrans = document.getElementById('selectTranscripcionMonitoreo');
            if (selectTrans) selectTrans.innerHTML = '';

            let primerPdfUrl = '';

            if (transcripcionesList.length > 0) {
                primerPdfUrl = transcripcionesList[0].url;

                if (selectTrans && transcripcionesList.length > 1) {
                    transcripcionesList.forEach((t, idx) => {
                        const opt = document.createElement('option');
                        opt.value = t.url;
                        const nom = t.nombre ? (t.nombre.length > 25 ? t.nombre.substring(0, 22) + '...' : t.nombre) : ('Doc ' + (idx + 1));
                        opt.textContent = `PDF #${idx + 1}: ${nom}`;
                        if (idx === 0) opt.selected = true;
                        selectTrans.appendChild(opt);
                    });
                    if (selectorTransCont) selectorTransCont.classList.remove('d-none');
                } else if (selectorTransCont) {
                    selectorTransCont.classList.add('d-none');
                }
            } else if (data.pdf_transcripcion_url) {
                primerPdfUrl = data.pdf_transcripcion_url;
                if (selectorTransCont) selectorTransCont.classList.add('d-none');
            } else if (selectorTransCont) {
                selectorTransCont.classList.add('d-none');
            }

            renderizarDocTranscrito(primerPdfUrl);

            const modalEl = document.getElementById('modalMonitoreoComparativo');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });
}

function activarModoErrorMonitoreo() {
    document.getElementById('monitoreoEstadoVerificacion').value = 'CON_ERRORES';
    document.getElementById('sectionErroresForm').style.setProperty('display', 'flex', 'important');
    const btnAprobar = document.getElementById('btnAprobarMonitoreo');
    btnAprobar.className = "btn btn-warning fw-bold px-4 text-dark";
    btnAprobar.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Corrección y Enviar a Alistamiento';
    document.getElementById('monitoreoObservaciones').focus();
}

function cerrarMonitoreoComparativo() {
    if (currentMonitoreoIngresoId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(`/api/ingresos/${currentMonitoreoIngresoId}/lock`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        currentMonitoreoIngresoId = null;
    }

    const modalEl = document.getElementById('modalMonitoreoComparativo');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

document.addEventListener('DOMContentLoaded', () => {
    const formVerif = document.getElementById('formVerificacionMonitor');
    if (formVerif) {
        formVerif.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('{{ route("monitoreo.verificar") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error al procesar la verificación.');
                }
            })
            .catch(err => {
                console.error("Error al enviar verificación vía AJAX:", err);
                this.submit();
            });
        });
    }
});
</script>
@endpush

