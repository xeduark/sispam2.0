/*
 * Escáner profesional de documentos y lector de cédula del formulario de Ingreso.
 * Portado del sistema nativo (views/ingreso/index.php). Requiere OpenCV.js, jsPDF y ZXing
 * cargados por la vista, y los modales de resources/views/ingreso/partials/escaner_modales.blade.php.
 */
let scannerPro = null;

function evaluarVisualizacionSoportes() {
    const select = document.getElementById('select_persona_reclama');
    const container = document.getElementById('seccion_soportes_container');
    const infoBox = document.getElementById('info_requisitos_reclamacion');
    const lblSub = document.getElementById('lbl_requisito_soportes_sub');
    const contenedorDocs = document.getElementById('contenedor-documentos');

    if (!select || !container || !contenedorDocs) return;

    const val = select.value;

    if (!val) {
        container.classList.add('d-none');
        infoBox.classList.add('d-none');
        contenedorDocs.innerHTML = '';
        return;
    }

    container.classList.remove('d-none');
    infoBox.classList.remove('d-none');

    if (val === 'PACIENTE_DIRECTO') {
        infoBox.className = 'alert alert-info p-3 mb-0 small fw-semibold shadow-sm border-info';
        infoBox.innerHTML = `<i class="fa-solid fa-circle-info me-1 fs-5 align-middle"></i> <strong>Reclamación Directa por el Paciente:</strong><br>Se exige adjuntar por separado <strong>2 Soportes Obligatorios</strong>: Cédula de Identidad y Fórmula / Orden Médica.`;
        lblSub.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> Requisito Obligatorio (2 Soportes): Se exige Cédula y Fórmula Médica por separado.`;
        
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA']);
    } else if (val === 'TERCERO_ACUDIENTE') {
        infoBox.className = 'alert alert-warning p-3 mb-0 small fw-semibold shadow-sm border-warning';
        infoBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 fs-5 align-middle"></i> <strong>Entrega a Nombre de Otra Persona (Tercero/Acudiente):</strong><br>Se exige adjuntar por separado <strong>3 Soportes Obligatorios</strong>: Cédula del paciente, Fórmula Médica y Autorización / Doc. del Tercero.`;
        lblSub.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> Requisito Obligatorio (3 Soportes): Se exige Cédula, Fórmula Médica y Autorización/Doc. del Tercero por separado.`;
        
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA', 'AUTORIZACION']);
    }
}

function generarFilasSoportes(tiposRequeridos) {
    const contenedorDocs = document.getElementById('contenedor-documentos');
    if (!contenedorDocs) return;

    contenedorDocs.innerHTML = '';

    tiposRequeridos.forEach((tipoTag, index) => {
        let labelText = '';
        let iconClass = '';
        let defaultOpt = tipoTag;

        if (tipoTag === 'CEDULA') {
            labelText = `${index + 1}. Cédula / Doc. Identidad del Paciente`;
            iconClass = 'fa-id-card';
        } else if (tipoTag === 'ORDEN_MEDICA') {
            labelText = `${index + 1}. Fórmula / Orden Médica`;
            iconClass = 'fa-file-medical';
        } else if (tipoTag === 'AUTORIZACION') {
            labelText = `${index + 1}. Autorización de Acudiente / Tercero & Doc. Identidad`;
            iconClass = 'fa-file-signature';
        }

        const divRow = document.createElement('div');
        divRow.className = 'card border-primary border-1 mb-3 item-documento bg-white p-3 shadow-sm';
        divRow.innerHTML = `
            <div class="row align-items-center g-2">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary mb-1">
                        <i class="fa-solid ${iconClass} me-1"></i> ${labelText} <span class="text-danger">*</span>
                    </label>
                    <!-- Señal de auditoría: 1 si el orientador tuvo que corregir el recorte a mano.
                         La escribe finalizarYAdjuntarPDF() al adjuntar el PDF. -->
                    <input type="hidden" name="doc_recorte_manual[]" class="input-recorte-manual" value="0">
                    <select name="doc_tipo_categoria[]" class="form-select form-select-sm fw-bold border-primary select-doc-cat">
                        <option value="CEDULA" ${defaultOpt === 'CEDULA' ? 'selected' : ''}>Cédula / Doc. Identidad *</option>
                        <option value="ORDEN_MEDICA" ${defaultOpt === 'ORDEN_MEDICA' ? 'selected' : ''}>Fórmula / Orden Médica *</option>
                        <option value="AUTORIZACION" ${defaultOpt === 'AUTORIZACION' ? 'selected' : ''}>Autorización / Doc. Tercero *</option>
                        <option value="HISTORIA_CLINICA">Historia Clínica / Anexo</option>
                        <option value="MIPRES">MIPRES</option>
                        <option value="OTRO">Otro Documento</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small mb-1">Adjuntar Archivo desde Dispositivo (PDF/JPG):</label>
                    <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="status-doc-adjunto mt-1"></div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-sm btn-success fw-bold w-100 mb-1" onclick="abrirEscanerDesdeFila(this)">
                        <i class="fa-solid fa-camera me-1"></i> Escanear (Pro)
                    </button>
                </div>
            </div>
        `;
        contenedorDocs.appendChild(divRow);
    });
}

// MOSTRAR NOTIFICACIONES FLOTANTES CON BOOTSTRAP MODAL

function agregarFilaDoc() {
    const container = document.getElementById('contenedor-documentos');
    const div = document.createElement('div');
    div.className = 'card border-secondary border-1 mb-3 item-documento bg-white p-3 shadow-sm';
    div.innerHTML = `
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <label class="form-label fw-bold text-dark mb-1">Categoría del Soporte:</label>
                <input type="hidden" name="doc_recorte_manual[]" class="input-recorte-manual" value="0">
                <select name="doc_tipo_categoria[]" class="form-select form-select-sm select-doc-cat">
                    <option value="CEDULA">Cédula / Doc. Identidad</option>
                    <option value="ORDEN_MEDICA">Fórmula / Orden Médica</option>
                    <option value="AUTORIZACION" selected>Autorización de Servicios</option>
                    <option value="HISTORIA_CLINICA">Historia Clínica / Anexo</option>
                    <option value="MIPRES">MIPRES</option>
                    <option value="OTRO">Otro Documento</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold small mb-1">Adjuntar Archivo (PDF/JPG):</label>
                <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
                <div class="status-doc-adjunto mt-1"></div>
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-success fw-bold w-100 mb-1" onclick="abrirEscanerDesdeFila(this)">
                    <i class="fa-solid fa-camera me-1"></i> Escanear este Soporte
                </button>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="eliminarFilaDoc(this)"><i class="fa-solid fa-trash me-1"></i> Eliminar</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function eliminarFilaDoc(btn) {
    const item = btn.closest('.item-documento');
    if (document.querySelectorAll('.item-documento').length > 1) {
        item.remove();
    } else {
        mostrarNotificacionModal('Atención', 'Debe conservar al menos un soporte de documento en la lista.', 'warning');
    }
}

// ============================================================================
// HISTORIAL DE ATENCIONES DEL PACIENTE
// ============================================================================

// Evita reabrir el modal si el campo pierde el foco varias veces con el mismo documento
// (el buscador se dispara también en 'blur').
let _ultimoHistorialMostrado = null;

async function mostrarHistorialPaciente(tipoDoc, numDoc) {
    const clave = `${tipoDoc}|${numDoc}`;
    if (_ultimoHistorialMostrado === clave) return;

    let res;
    try {
        const r = await fetch(`/api/pacientes/historial?tipo_doc=${encodeURIComponent(tipoDoc)}&num_doc=${encodeURIComponent(numDoc)}`);
        res = await r.json();
    } catch (e) {
        console.warn('[Historial] No se pudo consultar el historial:', e);
        return;
    }

    // Paciente sin atenciones previas: no hay nada que mostrar, no se interrumpe.
    if (res.status !== 'success' || !res.atenciones || res.atenciones.length === 0) return;

    _ultimoHistorialMostrado = clave;

    document.getElementById('historialPacienteNombre').textContent =
        `${res.paciente.nombre} · Doc. ${res.paciente.documento}`;

    // Posible dispensación duplicada: los crónicos son mensuales, volver antes es raro.
    const alerta = document.getElementById('historialAlertaDuplicado');
    if (res.alerta_duplicado) {
        alerta.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-2"></i>
            POSIBLE DISPENSACIÓN DUPLICADA — ${res.alerta_duplicado.mensaje}
            <span class="fw-normal">(turno ${res.alerta_duplicado.turno}, ${res.alerta_duplicado.fecha})</span>`;
        alerta.classList.remove('d-none');
    } else {
        alerta.classList.add('d-none');
    }

    document.getElementById('historialResumen').innerHTML = res.incompletas > 0
        ? `<span class="text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i>
           ${res.incompletas} de ${res.total} atención(es) quedaron incompletas.</span>
           Aproveche que el paciente está presente para subsanarlas.`
        : `${res.total} atención(es) previas, todas completas.`;

    const filas = res.atenciones.map((a, i) => {
        const detalle = a.faltantes.length
            ? `<div class="small text-danger">Falta: ${a.faltantes.join(', ')}</div>` : '';
        return `
            <tr class="hist-${a.clase}">
                <td class="text-muted">${i + 1}</td>
                <td class="fw-semibold">${a.fecha}</td>
                <td><span class="badge bg-secondary">${a.turno}</span></td>
                <td>
                    <span class="hist-estado hist-${a.clase}">${a.etiqueta}</span>
                    ${detalle}
                </td>
            </tr>`;
    }).join('');

    document.getElementById('historialTabla').innerHTML = `
        <table class="table table-sm align-middle mb-0">
            <thead><tr class="table-light">
                <th style="width:2.5rem">#</th><th>Fecha</th><th>Turno</th><th>Estado</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;

    try {
        const el = document.getElementById('modalHistorialPaciente');
        (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
    } catch (e) {
        console.warn('[Historial] No se pudo abrir el modal:', e);
    }
}

// LÓGICA DEL MODAL DE ESCÁNER PROFESIONAL

// Fila de "7. Documentos" desde la que se abrió el escáner: el PDF resultante se adjunta
// exactamente ahí, en vez de buscar a ciegas la primera fila de la misma categoría.
let _filaEscanerOrigen = null;

/**
 * Comportamiento del escáner por tipo de documento.
 *
 * Está aquí, como configuración, y no repartido en condicionales por el código: el orientador
 * no debe tomar ninguna decisión técnica (ni modo de color, ni formato de recorte), así que
 * todas esas decisiones se declaran una sola vez por tipo. Si mañana cambia una regla de
 * negocio —por ejemplo, que baste una sola cara de la cédula— se cambia el número aquí y el
 * paso guiado se desactiva solo, sin tocar lógica ni arriesgar regresiones.
 *
 *  - paginasEsperadas: número de capturas del flujo guiado. null = multipágina libre
 *                      (el orientador decide cuántas con "Otra página").
 *  - realce:           'ninguno'      → solo perspectiva y recorte, colores tal cual salen
 *                                        de la cámara (documentos plastificados y a color).
 *                      'documento-bn' → normalización de fondo + binarización, para hoja
 *                                        blanca con texto negro.
 *  - composicion:      'una-por-pagina' | 'ambas-caras-una-pagina'.
 *  - rotulos:          subtítulos del flujo guiado, uno por captura esperada.
 */
const CONFIG_DOCUMENTOS = {
    // SIN realce. El pipeline de realce (división de fondo + CLAHE + unsharp) está pensado
    // para papel blanco con texto negro; sobre un documento plastificado y a color aplana
    // la foto del titular, se come el holograma y puede volver ilegible el número — que es
    // motivo de glosa al radicar ante la EPS.
    CEDULA: {
        titulo: 'Cédula / Documento de Identidad',
        paginasEsperadas: 2,
        realce: 'ninguno',
        composicion: 'ambas-caras-una-pagina',
        rotulos: ['Frente', 'Reverso']
    },
    // Fórmula médica: sin realce agresivo para preservar sellos, firmas a tinta y notas
    ORDEN_MEDICA: {
        titulo: 'Fórmula / Orden Médica',
        paginasEsperadas: null,
        realce: 'ninguno',
        composicion: 'una-por-pagina'
    },
    // Hoja de poder con la que el paciente autoriza a un tercero a reclamar
    AUTORIZACION: {
        titulo: 'Autorización / Poder del Tercero',
        paginasEsperadas: null,
        realce: 'ninguno',
        composicion: 'una-por-pagina'
    },
    // Los tipos sin regla propia van sin realce: es el valor que nunca destruye información
    HISTORIA_CLINICA: {
        titulo: 'Historia Clínica / Anexo',
        paginasEsperadas: null,
        realce: 'ninguno',
        composicion: 'una-por-pagina'
    },
    MIPRES: {
        titulo: 'MIPRES / Direccionamiento',
        paginasEsperadas: null,
        realce: 'ninguno',
        composicion: 'una-por-pagina'
    },
    OTRO: {
        titulo: 'Otro Documento',
        paginasEsperadas: null,
        realce: 'ninguno',
        composicion: 'una-por-pagina'
    }
};

// Traduce el realce declarado al modo interno del motor. `null` = no aplicar nada.
function filtroDeRealce(realce) {
    return (realce === 'documento-bn' || realce === 'binary') ? 'binary' : null;
}

function configDoc(categoria) {
    return CONFIG_DOCUMENTOS[categoria] || CONFIG_DOCUMENTOS.OTRO;
}

// Tipo de documento del escaneo en curso.
let _categoriaEscaner = 'ORDEN_MEDICA';

/**
 * Abre el escáner desde el botón de una fila, tomando la categoría del propio selector de esa fila.
 */
function abrirEscanerDesdeFila(btn) {
    const fila = btn ? btn.closest('.item-documento') : null;
    const sel = fila ? fila.querySelector('.select-doc-cat') : null;
    abrirEscanerProModal(sel ? sel.value : 'ORDEN_MEDICA', fila);
}

async function abrirEscanerProModal(categoriaTarget = 'ORDEN_MEDICA', filaOrigen = null) {
    _filaEscanerOrigen = filaOrigen;
    _capturaEnCurso = false;

    // Limpieza preventiva total de backdrops residuales
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('pointer-events');

    const modalEl = document.getElementById('modalEscanerDocPro');
    if (!modalEl) {
        mostrarNotificacionModal('Error', 'No se encontró la ventana modal del escáner.', 'danger');
        return;
    }

    // El tipo llega de la fila que abrió el escáner
    _categoriaEscaner = categoriaTarget || 'ORDEN_MEDICA';
    _capturasDelFlujo = 0;
    aplicarTituloEscaner();

    const btnSnap = document.getElementById('btn-snap-cam');
    if (btnSnap) {
        btnSnap.disabled = false;
        btnSnap.classList.remove('d-none');
    }
    if (scannerPro) scannerPro.setDocType(_categoriaEscaner);

    // 1. Mostrar Modal usando Bootstrap o Fallback Directo DOM
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
            modal.show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
        }
    } catch (e) {
        console.warn("Bootstrap JS no respondió, usando apertura directa:", e);
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
    }

    // 2. Cargar librerías del escáner de forma perezosa (solo la primera vez en esta página)
    if (typeof DocumentScannerPro === 'undefined') {
        const loadingEl = document.getElementById('scanner-libs-loading');
        if (loadingEl) loadingEl.classList.remove('d-none');

        try {
            await ensureScannerLibsLoaded();
        } catch (err) {
            console.error("[Scanner] Error cargando librerías del escáner:", err);
        } finally {
            if (loadingEl) loadingEl.classList.add('d-none');
        }
    }

    // 3. Reiniciar escáner anterior para nuevo lote de páginas
    if (scannerPro) {
        scannerPro.resetDoc();
        actualizarTiraMiniaturas();
    }

    // 4. Iniciar componente de escaneo
    reiniciarCamaraEscaner();
}

/**
 * Cierre del escáner. Si hay páginas capturadas sin adjuntar, se confirma antes: el lote
 * vive solo en memoria del navegador y cerrar sin finalizar lo descarta por completo.
 * @param {boolean} forzar  true al finalizar y adjuntar (ahí no hay nada que perder)
 */
async function cerrarEscanerPro(forzar = false) {
    if (!forzar && scannerPro && scannerPro.scannedPages && scannerPro.scannedPages.length > 0) {
        const n = scannerPro.scannedPages.length;
        const ok = await confirmarEscaner(
            'Descartar páginas escaneadas',
            `Hay <strong>${n} página(s)</strong> escaneada(s) que todavía no se han adjuntado a ningún soporte.<br><br>Si cierra ahora se <strong>pierden</strong>. Para conservarlas use <strong>"Finalizar &amp; Adjuntar a este Soporte"</strong>.`,
            'Cerrar y descartar'
        );
        if (!ok) return;
    }

    if (scannerPro) {
        try { scannerPro.stopCamera(); } catch (e) {}
    }

    const modalEl = document.getElementById('modalEscanerDocPro');
    if (modalEl) {
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } catch (e) {}

        modalEl.style.display = 'none';
        modalEl.classList.remove('show');
    }

    // Limpieza radical de backdrop para garantizar que ningún botón quede bloqueado
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('pointer-events');

    // Desbloquear cualquier elemento de la interfaz
    document.querySelectorAll('button, input, select').forEach(el => {
        el.style.removeProperty('pointer-events');
    });
}

// Capturas hechas dentro del flujo guiado del documento actual (p. ej. frente y reverso
// de la cédula). Se reinicia al abrir el escáner y al adjuntar.
let _capturasDelFlujo = 0;

/** Título del modal = nombre del documento; subtítulo = paso del flujo guiado, si aplica. */
function aplicarTituloEscaner() {
    const cfg = configDoc(_categoriaEscaner);
    const elTitulo = document.getElementById('scanner-doc-titulo');
    const elPaso = document.getElementById('scanner-doc-paso');
    const badgePaso = document.getElementById('badge-paso-escaner');
    const textoPaso = document.getElementById('texto-paso-escaner');
    const iconoPaso = document.getElementById('icono-paso-escaner');

    if (elTitulo) elTitulo.textContent = cfg.titulo;

    const rotulo = (cfg.rotulos && cfg.paginasEsperadas && _capturasDelFlujo < cfg.paginasEsperadas)
        ? cfg.rotulos[_capturasDelFlujo]
        : null;

    if (rotulo) {
        const textoMsg = `Paso ${_capturasDelFlujo + 1} de ${cfg.paginasEsperadas}: ${rotulo.toUpperCase()}`;
        if (elPaso) {
            elPaso.textContent = textoMsg;
            elPaso.classList.remove('d-none');
        }
        if (badgePaso && textoPaso) {
            badgePaso.classList.remove('d-none');
            textoPaso.textContent = textoMsg;
            if (iconoPaso) {
                iconoPaso.className = (_capturasDelFlujo === 0)
                    ? 'fa-solid fa-id-card text-info me-1'
                    : 'fa-solid fa-rotate text-warning me-1';
            }
        }
    } else {
        if (elPaso) {
            elPaso.textContent = '';
            elPaso.classList.add('d-none');
        }
        if (badgePaso) badgePaso.classList.add('d-none');
    }
}

/** Muestra la cámara en vivo y oculta todo lo de revisión. */
function mostrarPantallaCaptura() {
    document.getElementById('scanner-canvas-overlay').classList.add('d-none');
    document.getElementById('scanner-live-overlay').classList.remove('d-none');
    document.getElementById('webcam-video').classList.remove('d-none');
    document.getElementById('controles-captura').classList.remove('d-none');
    document.getElementById('controles-revision').classList.add('d-none');
    document.getElementById('scanner-footer-revision').classList.add('d-none');
    const btnSnap = document.getElementById('btn-snap-cam');
    if (btnSnap) btnSnap.classList.remove('d-none');
    aplicarTituloEscaner();
}

/** Muestra el recorte sobre la foto ya realzada, con las esquinas arrastrables. */
function mostrarPantallaRevision() {
    document.getElementById('webcam-video').classList.add('d-none');
    document.getElementById('scanner-live-overlay').classList.add('d-none');
    
    const canvasOverlay = document.getElementById('scanner-canvas-overlay');
    if (canvasOverlay) canvasOverlay.classList.remove('d-none');
    
    document.getElementById('controles-captura').classList.add('d-none');
    document.getElementById('controles-revision').classList.remove('d-none');
    document.getElementById('scanner-footer-revision').classList.remove('d-none');
    
    // Redibujar el recorte y la imagen en el canvas ya visible
    if (scannerPro) {
        requestAnimationFrame(() => {
            scannerPro.drawOverlay();
        });
    }

    actualizarBotonesRevision();
}

/**
 * "Adjuntar" solo existe cuando hay algo que adjuntar: contando el lote guardado más la
 * página que esté en revisión sin guardar. Un botón cuya única acción posible es fallar
 * con "tome al menos una foto" no debe estar visible.
 */
function actualizarBotonesRevision() {
    if (!scannerPro) return;
    const hayAlgo = scannerPro.scannedPages.length > 0 || !!scannerPro.rawImage;
    const btnFinalizar = document.getElementById('btn-finalizar-pdf');
    if (btnFinalizar) {
        btnFinalizar.classList.toggle('d-none', !hayAlgo);
    }

    const btnOtra = document.getElementById('btn-agregar-otra-pag');
    if (btnOtra) {
        if (_categoriaEscaner === 'CEDULA' && scannerPro.scannedPages.length === 0) {
            btnOtra.innerHTML = '<i class="fa-solid fa-rotate me-1"></i> Capturar Reverso';
            btnOtra.className = 'btn btn-warning text-dark fw-bold btn-sm flex-fill shadow-sm';
        } else {
            btnOtra.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Otra página';
            btnOtra.className = 'btn btn-outline-info btn-sm fw-bold flex-fill';
        }
    }

    aplicarTituloEscaner();
}

function reiniciarCamaraEscaner() {
    if (scannerPro) {
        scannerPro.rawImage = null;
        scannerPro.previewImage = null;
    }
    mostrarPantallaCaptura();
    iniciarCamaraEscaner();
}

/** "Tomar de nuevo": descarta la captura en revisión y vuelve a la cámara, mismo paso. */
function repetirFotoEscaner() {
    if (scannerPro) {
        scannerPro.rawImage = null;
        scannerPro.previewImage = null;
    }
    mostrarPantallaCaptura();
    iniciarCamaraEscaner();
}

async function iniciarCamaraEscaner() {
    if (typeof DocumentScannerPro === 'undefined') return;
    if (!scannerPro) scannerPro = new DocumentScannerPro();

    // Auto-captura (Fase 3): el detector avisa cuando el documento estuvo quieto lo
    // suficiente; se reutiliza exactamente el mismo flujo del botón manual.
    scannerPro.onAutoCaptureRequested = () => { capturarFotoEscaner(); };

    // El tipo de documento restringe la proporción que la detección acepta como válida.
    scannerPro.setDocType(_categoriaEscaner);
    aplicarModoDepuracionEscaner();

    // Progreso de la auto-captura, también sobre el obturador: el anillo sobre el documento
    // puede quedar fuera de la mirada cuando el pulgar ya está sobre el botón.
    scannerPro.onCountdownProgress = (p) => {
        const b = document.getElementById('btn-snap-cam');
        if (b) {
            b.style.setProperty('--progreso', (p * 360).toFixed(1) + 'deg');
            b.classList.toggle('cargando', p > 0);
        }
    };

    const success = await scannerPro.startCamera();

    const btnSnap = document.getElementById('btn-snap-cam');
    const btnTorch = document.getElementById('btn-torch');

    if (btnSnap) {
        btnSnap.disabled = false;
        btnSnap.classList.remove('d-none');
    }
    if (btnTorch) btnTorch.classList.toggle('d-none', !(scannerPro && scannerPro.torchSupported));
}

let _capturaEnCurso = false;

function flashEfectoCamara() {
    const w = document.querySelector('.scanner-canvas-wrapper');
    if (!w) return;
    w.classList.add('scanner-flash');
    setTimeout(() => w.classList.remove('scanner-flash'), 180);
}

function mostrarAvisoFlotante(msg, ms = 4500) {
    let t = document.getElementById('aviso-flotante-pantalla');
    if (!t) {
        t = document.createElement('div');
        t.id = 'aviso-flotante-pantalla';
        t.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:99999999;padding:12px 24px;border-radius:30px;background:#059669;color:#ffffff;font-size:15px;font-weight:bold;box-shadow:0 12px 30px rgba(0,0,0,0.6);text-align:center;pointer-events:none;transition:all 0.3s ease;max-width:92vw;';
        document.body.appendChild(t);
    }
    t.innerHTML = `<i class="fa-solid fa-circle-check me-2 fs-5"></i><span>${msg}</span>`;
    t.style.display = 'block';
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    setTimeout(() => {
        if (t) {
            t.style.opacity = '0';
            t.style.transform = 'translateX(-50%) translateY(-15px)';
            setTimeout(() => { if (t) t.style.display = 'none'; }, 350);
        }
    }, ms);
}

function mostrarToastEscaner(msg, ms = 3500) {
    mostrarAvisoFlotante(msg, ms);
}

async function capturarFotoEscaner() {
    if (!scannerPro) scannerPro = new DocumentScannerPro();

    // Si la cámara no pudo iniciar stream (sin permisos o sin cámara web), permitir subir desde galería/archivo
    if (!scannerPro.stream) {
        const inputNativo = document.getElementById('input-foto-nativa');
        if (inputNativo) {
            inputNativo.click();
            return;
        }
    }

    // Evita capturar dos veces si la auto-captura y el botón manual coinciden.
    if (_capturaEnCurso) return;
    _capturaEnCurso = true;

    flashEfectoCamara();

    const btnSnap = document.getElementById('btn-snap-cam');
    if (btnSnap) btnSnap.disabled = true;

    try {
        await scannerPro.takeSnapshot();
        await trasCapturar();
    } catch (err) {
        console.error("[Scanner] Error en capturarFotoEscaner:", err);
    } finally {
        if (btnSnap) btnSnap.disabled = false;
        _capturaEnCurso = false;
    }
}

/**
 * Qué pasa justo después de capturar.
 * Pasa siempre a la pantalla de revisión donde el usuario ve la foto y tiene las opciones:
 * "Tomar de nuevo", "Capturar Reverso" / "Otra página" y "Adjuntar".
 */
async function trasCapturar() {
    if (!scannerPro || !scannerPro.rawImage) {
        console.warn("[Scanner] No hay rawImage tras captura.");
        return;
    }

    const cfg = configDoc(_categoriaEscaner);

    try {
        await scannerPro.construirPreviewRealzado(filtroDeRealce(cfg.realce));
    } catch (e) {
        console.warn("[Scanner] Preview error:", e);
    }

    _capturasDelFlujo++;

    // Apagar cámara y pasar SIEMPRE a la pantalla de revisión con los 3 botones
    try { scannerPro.stopCamera(); } catch (e) {}
    mostrarPantallaRevision();
    actualizarTiraMiniaturas();
}

function cargarFotoNativaEscaner(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = async function() {
            if (!scannerPro) scannerPro = new DocumentScannerPro();
            scannerPro.stopCamera();
            scannerPro._lastCaptureMethod = 'archivo nativo/galería';
            scannerPro.loadCapturedImage(img);
            await trasCapturar();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
    event.target.value = '';
}

// El modo depuración se activa abriendo la página con ?scannerdebug=1.
function escanerDebugActivo() {
    try {
        return new URLSearchParams(window.location.search).get('scannerdebug') === '1';
    } catch (e) {
        return false;
    }
}

function aplicarModoDepuracionEscaner() {
    const activo = escanerDebugActivo();
    const panel = document.getElementById('scanner-debug-panel');
    if (panel) panel.classList.toggle('d-none', !activo);
    if (scannerPro) {
        scannerPro.setDebugMode(activo, document.getElementById('scanner-debug-canvas'));
    }
}

/**
 * Guarda la página en revisión y vuelve a la cámara para la siguiente.
 * El recorte y el realce se aplican aquí, con el modo que fija el tipo de documento.
 */
async function guardarPaginaYOtra() {
    if (!scannerPro) return;

    const esCedula = (_categoriaEscaner === 'CEDULA');

    if (scannerPro.rawImage) {
        const cfg = configDoc(_categoriaEscaner);
        scannerPro.processScan(filtroDeRealce(cfg.realce), _categoriaEscaner);
        scannerPro.saveCurrentPageToDoc();
        scannerPro.rawImage = null;   // Prevenir duplicación de página
        scannerPro.previewImage = null;
        actualizarTiraMiniaturas();
    }

    mostrarPantallaCaptura();
    await iniciarCamaraEscaner();

    if (esCedula) {
        mostrarAvisoFlotante('🔄 Gire la CÉDULA al Reverso en el atril. El escaneo iniciará en 2 segundos.', 4500);
        if (scannerPro && scannerPro.liveDetector) {
            scannerPro.liveDetector.autoCaptureEnabled = false;
            setTimeout(() => {
                if (scannerPro && scannerPro.liveDetector) {
                    scannerPro.liveDetector.autoCaptureEnabled = true;
                    scannerPro.liveDetector.resetAutoCapture();
                }
            }, 2500);
        }
    }
}

function actualizarTiraMiniaturas() {
    if (!scannerPro) return;
    const strip = document.getElementById('strip-miniaturas');
    const bloque = document.getElementById('bloque-miniaturas');
    const pages = scannerPro.scannedPages;
    const hayRaw = !!scannerPro.rawImage;

    const totalItems = pages.length + (hayRaw ? 1 : 0);

    if (bloque) bloque.classList.toggle('d-none', totalItems === 0);
    if (totalItems === 0) {
        if (strip) strip.innerHTML = '';
        return;
    }

    const cfg = configDoc(_categoriaEscaner);

    let html = '';
    // 1. Páginas ya guardadas en el lote (ej: Frente de Cédula o Pág 1 de Orden)
    pages.forEach((p, idx) => {
        const flechaIzq = idx > 0
            ? `<button type="button" class="page-thumb-move izq" title="Mover antes" onclick="event.stopPropagation(); moverPaginaEscaner(${idx}, -1)"><i class="fa-solid fa-chevron-left"></i></button>`
            : '';
        const flechaDer = idx < pages.length - 1
            ? `<button type="button" class="page-thumb-move der" title="Mover después" onclick="event.stopPropagation(); moverPaginaEscaner(${idx}, 1)"><i class="fa-solid fa-chevron-right"></i></button>`
            : '';

        const rotulo = (cfg.rotulos && cfg.rotulos[idx]) ? cfg.rotulos[idx] : `Pág ${idx + 1}`;

        html += `
            <div class="page-thumb-item" title="Clic para ampliar y verificar ${rotulo}" onclick="verPaginaEscaner(${idx})">
                <img src="${p.dataUrl}" alt="${rotulo}">
                <span class="page-thumb-badge" style="background:#0284c7;font-weight:bold;">${rotulo}</span>
                <button type="button" class="page-thumb-delete" title="Eliminar página" onclick="event.stopPropagation(); borrarPaginaEscaner(${idx});"><i class="fa-solid fa-xmark"></i></button>
                ${flechaIzq}${flechaDer}
            </div>
        `;
    });

    // 2. Si hay una captura activa en la pantalla de revisión (ej: Reverso o primera toma recién hecha)
    if (hayRaw) {
        const curIdx = pages.length;
        const rotuloCur = (cfg.rotulos && cfg.rotulos[curIdx]) ? `${cfg.rotulos[curIdx]} (Actual)` : `Pág ${curIdx + 1} (Actual)`;
        let curDataUrl = '';
        if (typeof scannerPro.getRawDataUrl === 'function') {
            curDataUrl = scannerPro.getRawDataUrl(0.7);
        } else if (scannerPro.rawImage.toDataURL) {
            curDataUrl = scannerPro.rawImage.toDataURL('image/jpeg', 0.7);
        } else if (scannerPro.rawImage.src) {
            curDataUrl = scannerPro.rawImage.src;
        }

        if (curDataUrl) {
            html += `
                <div class="page-thumb-item active" style="border: 2px solid #22c55e;" title="Clic para ampliar captura actual" onclick="verPreviewPaginaActual()">
                    <img src="${curDataUrl}" alt="${rotuloCur}">
                    <span class="page-thumb-badge bg-success" style="font-weight:bold;"><i class="fa-solid fa-eye me-1"></i>${rotuloCur}</span>
                </div>
            `;
        }
    }

    if (strip) strip.innerHTML = html;
}

async function borrarPaginaEscaner(index) {
    if (!scannerPro) return;
    // Borrar una página capturada es irreversible (no hay historial de deshacer), así que
    // se confirma en vez de descartarla al primer toque.
    const ok = await confirmarEscaner(
        'Eliminar página',
        `¿Eliminar la <strong>página ${index + 1}</strong> de este lote? No se puede deshacer.`,
        'Eliminar'
    );
    if (!ok) return;
    scannerPro.deletePage(index);
    actualizarTiraMiniaturas();
}

function moverPaginaEscaner(index, delta) {
    if (!scannerPro) return;
    scannerPro.movePage(index, delta);
    actualizarTiraMiniaturas();
}

/**
 * Documento de identidad: las dos caras apiladas verticalmente en UNA sola página, que es
 * el formato de la fotocopia de toda la vida y lo que el radicador de la EPS espera.
 *
 * Orden fijo: frente arriba, reverso abajo — lo garantiza el flujo guiado, que captura en
 * ese orden. Si solo hay una cara (el orientador salió antes, o falló el reverso) se
 * renderiza esa sola, centrada verticalmente, en vez de dejar media hoja en blanco.
 */
function componerAmbasCaras(jsPDF, paginas, anchoPag, altoPag) {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const caras = paginas.slice(0, 2);

    // ~68% del ancho de página: prioriza que el número de cédula se lea sin ampliar por
    // encima de reproducir el tamaño físico real de la tarjeta.
    const anchoCara = anchoPag * 0.68;
    const SEPARACION = 12;

    const alturas = caras.map(p => anchoCara * (p.height / p.width));
    const altoTotal = alturas.reduce((a, b) => a + b, 0) + SEPARACION * (caras.length - 1);

    // Si por proporciones no cupieran, se reduce todo en bloque para no recortar nada.
    const disponible = altoPag - 24;
    const ajuste = altoTotal > disponible ? (disponible / altoTotal) : 1;

    const x = (anchoPag - anchoCara * ajuste) / 2;
    let y = (altoPag - altoTotal * ajuste) / 2;

    caras.forEach((p, i) => {
        const w = anchoCara * ajuste;
        const h = alturas[i] * ajuste;
        doc.addImage(p.dataUrl, 'JPEG', x, y, w, h);
        y += h + SEPARACION * ajuste;
    });

    // Cualquier página extra (el orientador añadió más con "Otra página") sigue el formato
    // normal de una imagen por hoja, para no perderla.
    paginas.slice(2).forEach(page => {
        const orientacion = (page.width > page.height) ? 'landscape' : 'portrait';
        doc.addPage('a4', orientacion);
        const aP = (orientacion === 'landscape') ? altoPag : anchoPag;
        const hP = (orientacion === 'landscape') ? anchoPag : altoPag;
        const esc = Math.min((aP - 16) / page.width, (hP - 16) / page.height);
        const w = page.width * esc, h = page.height * esc;
        doc.addImage(page.dataUrl, 'JPEG', (aP - w) / 2, (hP - h) / 2, w, h);
    });

    return doc;
}

/**
 * Aviso efímero que se desvanece solo. Reemplaza al modal de "¡Escaneo finalizado!" con
 * botón Aceptar: ese clic obligatorio se pagaba 2-3 veces por paciente y no aportaba nada,
 * porque la confirmación que el orientador consulta de verdad es el estado de la fila.
 */
function mostrarToastEscaner(mensajeHtml, ms = 2600) {
    let cont = document.getElementById('toast-escaner-cont');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'toast-escaner-cont';
        document.body.appendChild(cont);
    }

    const t = document.createElement('div');
    t.className = 'toast-escaner';
    t.innerHTML = `<i class="fa-solid fa-circle-check me-2"></i><div>${mensajeHtml}</div>`;
    cont.appendChild(t);

    requestAnimationFrame(() => t.classList.add('visible'));
    setTimeout(() => {
        t.classList.remove('visible');
        setTimeout(() => t.remove(), 300);
    }, ms);
}

/** Marca la fila del soporte como cargada: es la confirmación persistente y consultable. */
function marcarFilaAdjunta(fila, nPaginas, pesoMb) {
    if (!fila) return;
    fila.classList.add('doc-adjunto-ok');

    const btn = fila.querySelector('button[onclick^="abrirEscanerDesdeFila"]');
    if (btn) {
        btn.classList.remove('btn-success', 'btn-outline-success');
        btn.classList.add('btn-outline-secondary');
        btn.innerHTML = '<i class="fa-solid fa-rotate-right me-1"></i> Volver a escanear';
    }

    const estado = fila.querySelector('.status-doc-adjunto');
    if (estado) {
        estado.innerHTML = `
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                <div class="alert alert-success p-1 px-2 mb-0 small fw-bold d-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <span>Cargado · ${nPaginas} pág. · ${pesoMb} MB</span>
                </div>
                <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="previsualizarDocFila(this)" title="Ver documento antes de generar tiquete">
                    <i class="fa-solid fa-eye me-1"></i> Ver Documento
                </button>
            </div>`;
    }
}

/** Previsualiza el archivo PDF o imagen adjunto en una fila del formulario antes de enviar */
function previsualizarDocFila(btn) {
    const fila = btn.closest('.item-documento') || btn.closest('tr') || btn.parentElement;
    if (!fila) return;
    const input = fila.querySelector('.input-doc-file');
    if (!input || !input.files || input.files.length === 0) {
        alert('No hay ningún archivo cargado en esta fila.');
        return;
    }

    const file = input.files[0];
    const select = fila.querySelector('.select-doc-cat');
    const catNombre = select ? select.options[select.selectedIndex].text : 'Documento';

    const img = document.getElementById('previewPaginaImg');
    const iframe = document.getElementById('previewPaginaIframe');
    const titulo = document.getElementById('previewPaginaTitulo');
    const info = document.getElementById('previewPaginaInfo');

    if (titulo) titulo.innerHTML = `<i class="fa-solid fa-file-pdf text-danger me-2"></i> ${catNombre} — <span class="text-white-50 small">${file.name}</span>`;
    if (info) info.textContent = `Tamaño: ${(file.size / (1024 * 1024)).toFixed(2)} MB · Tipo: ${file.type || 'Documento'}`;

    const url = URL.createObjectURL(file);

    if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
        if (img) img.classList.add('d-none');
        if (iframe) {
            iframe.classList.remove('d-none');
            iframe.src = url;
        }
    } else {
        if (iframe) iframe.classList.add('d-none');
        if (img) {
            img.classList.remove('d-none');
            img.src = url;
        }
    }

    const modalEl = document.getElementById('modalPreviewPagina');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();

    modalEl.addEventListener('hidden.bs.modal', function onHide() {
        if (iframe) iframe.src = 'about:blank';
        if (img) img.src = '';
        URL.revokeObjectURL(url);
        modalEl.removeEventListener('hidden.bs.modal', onHide);
    }, { once: true });
}

/** Previsualiza la foto actual capturada dentro del escáner en pantalla completa */
function verPreviewPaginaActual() {
    if (!scannerPro) return;

    let dataUrl = null;
    let dimensiones = '';
    let tituloTexto = `Vista Previa (${_categoriaEscaner})`;

    if (scannerPro.rawImage) {
        if (typeof scannerPro.getRawDataUrl === 'function') {
            dataUrl = scannerPro.getRawDataUrl(0.95);
        } else if (scannerPro.rawImage.toDataURL) {
            dataUrl = scannerPro.rawImage.toDataURL('image/jpeg', 0.95);
        } else if (scannerPro.rawImage.src) {
            dataUrl = scannerPro.rawImage.src;
        }
        dimensiones = `${scannerPro.rawWidth || 1920} × ${scannerPro.rawHeight || 1080} px`;

        if (_categoriaEscaner === 'CEDULA') {
            const numPag = scannerPro.scannedPages.length + 1;
            tituloTexto = numPag === 1 ? 'Cédula — Frente (Paso 1)' : 'Cédula — Reverso (Paso 2)';
        } else {
            tituloTexto = `${configDoc(_categoriaEscaner).titulo} — Página ${scannerPro.scannedPages.length + 1}`;
        }
    } else if (scannerPro.scannedPages.length > 0) {
        const lastIdx = scannerPro.scannedPages.length - 1;
        const lastP = scannerPro.scannedPages[lastIdx];
        dataUrl = lastP.dataUrl;
        dimensiones = `${lastP.width} × ${lastP.height} px`;
        const cfg = configDoc(_categoriaEscaner);
        const rotulo = (cfg.rotulos && cfg.rotulos[lastIdx]) ? cfg.rotulos[lastIdx] : `Página ${lastIdx + 1}`;
        tituloTexto = `${cfg.titulo} — ${rotulo}`;
    }

    if (!dataUrl) {
        mostrarNotificacionModal('Atención', 'Por favor tome una foto o active la cámara primero para previsualizar.', 'warning');
        return;
    }

    const img = document.getElementById('previewPaginaImg');
    const iframe = document.getElementById('previewPaginaIframe');
    const titulo = document.getElementById('previewPaginaTitulo');
    const info = document.getElementById('previewPaginaInfo');

    if (iframe) iframe.classList.add('d-none');
    if (img) {
        img.classList.remove('d-none');
        img.src = dataUrl;
    }
    if (titulo) titulo.innerHTML = `<i class="fa-solid fa-camera text-info me-2"></i> ${tituloTexto}`;
    if (info) info.textContent = `${dimensiones} · Compruebe la nitidez y legibilidad`;

    const el = document.getElementById('modalPreviewPagina');
    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
}

function verPaginaEscaner(index) {
    if (!scannerPro) return;
    const page = scannerPro.getPage(index);
    if (!page) return;

    const img = document.getElementById('previewPaginaImg');
    const iframe = document.getElementById('previewPaginaIframe');
    const titulo = document.getElementById('previewPaginaTitulo');
    const info = document.getElementById('previewPaginaInfo');
    if (!img) return;

    const cfg = configDoc(_categoriaEscaner);
    const rotulo = (cfg.rotulos && cfg.rotulos[index]) ? cfg.rotulos[index] : `Página ${index + 1}`;

    if (iframe) iframe.classList.add('d-none');
    img.classList.remove('d-none');
    img.src = page.dataUrl;
    if (titulo) titulo.innerHTML = `<i class="fa-solid fa-image text-info me-2"></i> ${cfg.titulo} — ${rotulo}`;
    if (info) info.textContent = `${page.width} × ${page.height} px · Página guardada`;

    try {
        const el = document.getElementById('modalPreviewPagina');
        (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
    } catch (e) {
        console.warn('[Scanner] No se pudo abrir la vista previa:', e);
    }
}

// Actualizar estado y botón de vista previa cuando se adjunta un archivo manual desde PC o galería
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('input-doc-file')) {
        const fila = e.target.closest('.item-documento');
        if (fila && e.target.files && e.target.files.length > 0) {
            const f = e.target.files[0];
            const mb = (f.size / (1024 * 1024)).toFixed(2);
            marcarFilaAdjunta(fila, 1, mb);
        }
    }
});

/**
 * Confirmación sí/no basada en modal Bootstrap, coherente con el resto del flujo de
 * ingreso (el escáner usaba confirm() nativo, que además bloquea el bucle de detección).
 * @returns {Promise<boolean>}
 */
function confirmarEscaner(titulo, mensajeHtml, textoSi = 'Continuar') {
    return new Promise((resolve) => {
        const modalEl = document.getElementById('modalConfirmEscaner');
        const btnSi = document.getElementById('btnConfirmEscanerSi');
        const btnNo = document.getElementById('btnConfirmEscanerNo');

        if (!modalEl || !btnSi || !btnNo || typeof bootstrap === 'undefined') {
            resolve(window.confirm(mensajeHtml.replace(/<[^>]*>?/gm, '')));
            return;
        }

        document.getElementById('modalConfirmTitle').innerHTML =
            `<i class="fa-solid fa-triangle-exclamation me-2"></i> ${titulo}`;
        document.getElementById('modalConfirmMessage').innerHTML = mensajeHtml;
        btnSi.textContent = textoSi;

        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        let resuelto = false;

        // Los listeners son { once: true } y se limpian entre sí: sin esto, cada llamada
        // acumularía un handler más sobre los mismos botones.
        const terminar = (valor) => {
            if (resuelto) return;
            resuelto = true;
            btnSi.removeEventListener('click', onSi);
            btnNo.removeEventListener('click', onNo);
            modalEl.removeEventListener('hidden.bs.modal', onCerrar);
            resolve(valor);
        };
        const onSi = () => { modal.hide(); terminar(true); };
        const onNo = () => { modal.hide(); terminar(false); };
        const onCerrar = () => terminar(false); // cerrar con la X o ESC = cancelar

        btnSi.addEventListener('click', onSi);
        btnNo.addEventListener('click', onNo);
        modalEl.addEventListener('hidden.bs.modal', onCerrar);

        modal.show();
    });
}

// Límites reales de subida, definidos en .htaccess (php_value). El POST es compartido por
// TODOS los documentos del formulario, no solo por el PDF que se acaba de escanear: si se
// excede post_max_size, PHP entrega $_POST y $_FILES vacíos y el ingreso se pierde sin
// ningún mensaje de error. Por eso se avisa aquí, en el navegador.
const LIMITE_ARCHIVO_MB = 25;
const LIMITE_POST_MB = 30;

function pesoOtrosAdjuntosMb() {
    let bytes = 0;
    document.querySelectorAll('.input-doc-file').forEach(inp => {
        if (inp.files) for (const f of inp.files) bytes += f.size;
    });
    return bytes / (1024 * 1024);
}

async function confirmarPesoPaginas(paginas) {
    if (typeof DocumentScannerPro === 'undefined'
        || typeof DocumentScannerPro.pesoPaginas !== 'function') return true;

    const pdfMb = DocumentScannerPro.pesoPaginas(paginas) / (1024 * 1024);
    const otrosMb = pesoOtrosAdjuntosMb();
    const totalMb = pdfMb + otrosMb;

    const excedeArchivo = pdfMb > LIMITE_ARCHIVO_MB;
    const excedePost = totalMb > LIMITE_POST_MB;
    if (!excedeArchivo && !excedePost) return true;

    const motivo = excedeArchivo
        ? `El PDF pesa alrededor de <strong>${pdfMb.toFixed(1)} MB</strong>, por encima del máximo de ${LIMITE_ARCHIVO_MB} MB por archivo.`
        : `Entre este PDF (${pdfMb.toFixed(1)} MB) y los demás soportes ya adjuntos (${otrosMb.toFixed(1)} MB) el envío sumaría <strong>${totalMb.toFixed(1)} MB</strong>, por encima del máximo de ${LIMITE_POST_MB} MB por envío.`;

    return await confirmarEscaner(
        'El archivo puede ser demasiado pesado',
        `${motivo}<br><br>Si continúa, es probable que <strong>el servidor rechace el ingreso completo</strong>.
         <br><br>Recomendación: use el modo <strong>Blanco y Negro</strong> (mucho más liviano) o divida el lote
         en menos páginas por soporte.`,
        'Continuar de todos modos'
    );
}

async function finalizarYAdjuntarPDF() {
    if (!scannerPro) return;

    // Guardar la página pendiente (capturada y procesada pero sin pulsar "Guardar y
    // Escanear Otra"). Antes esto solo ocurría si el lote estaba vacío, así que en un
    // escaneo de varias páginas la ÚLTIMA se perdía en silencio.
    const targetCat = _categoriaEscaner;
    const catText = configDoc(targetCat).titulo;

    if (scannerPro.rawImage) {
        scannerPro.processScan(filtroDeRealce(configDoc(targetCat).realce), targetCat);
        scannerPro.saveCurrentPageToDoc();
        scannerPro.rawImage = null;
        scannerPro.previewImage = null;
        actualizarTiraMiniaturas();
    }

    // El botón "Adjuntar" solo está visible cuando hay algo que adjuntar, así que este
    // caso ya no debería alcanzarse; se conserva como red de seguridad.
    if (scannerPro.scannedPages.length === 0) return;

    const btnFinalizar = document.getElementById('btn-finalizar-pdf');
    const textoOriginalBtn = btnFinalizar ? btnFinalizar.innerHTML : '';
    if (btnFinalizar) {
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando PDF...';
    }

    let file, fileName, pesoMb;
    try {
        // Comprimir cada página antes de armar el PDF (lado largo ≤2000px, JPEG 0.8).
        const paginas = await scannerPro.getPagesForPdf(2000, 0.8);

        // Guardia de peso sobre el resultado REAL de la compresión (no sobre una
        // predicción): si supera los límites de subida, avisar antes de armar el PDF.
        if (!(await confirmarPesoPaginas(paginas))) return;

        const { jsPDF } = window.jspdf;
        const A4_ANCHO = 210, A4_ALTO = 297, MARGEN = 8;
        let doc = null;

        // Composición de documento de identidad: ambas caras apiladas en UNA página
        // vertical, como la fotocopia tradicional que el radicador espera recibir.
        if (configDoc(targetCat).composicion === 'ambas-caras-una-pagina') {
            doc = componerAmbasCaras(jsPDF, paginas, A4_ANCHO, A4_ALTO);
        } else {

        paginas.forEach((page, i) => {
            // Orientación por página: una cédula (apaisada) en una hoja vertical quedaría
            // diminuta; así cada página usa la orientación que le corresponde.
            const orientacion = (page.width > page.height) ? 'landscape' : 'portrait';

            if (i === 0) {
                doc = new jsPDF({ orientation: orientacion, unit: 'mm', format: 'a4' });
            } else {
                doc.addPage('a4', orientacion);
            }

            const anchoPag = (orientacion === 'landscape') ? A4_ALTO : A4_ANCHO;
            const altoPag  = (orientacion === 'landscape') ? A4_ANCHO : A4_ALTO;

            // Ajustar preservando la proporción y centrar. Antes se estiraba la imagen a
            // la hoja completa (0,0,210,297), deformando todo documento que no fuera A4.
            const dispW = anchoPag - MARGEN * 2;
            const dispH = altoPag - MARGEN * 2;
            const escala = Math.min(dispW / page.width, dispH / page.height);
            const w = page.width * escala;
            const h = page.height * escala;

            doc.addImage(page.dataUrl, 'JPEG', (anchoPag - w) / 2, (altoPag - h) / 2, w, h);
        });

        } // fin de la composición "una imagen por página"

        const pdfBlob = doc.output('blob');
        fileName = `${targetCat.toLowerCase()}_escaneada_${Date.now()}.pdf`;
        file = new File([pdfBlob], fileName, { type: 'application/pdf' });
        pesoMb = (pdfBlob.size / (1024 * 1024)).toFixed(2);
        console.log(`[Scanner] PDF generado: ${paginas.length} pág(s), ${pesoMb} MB`);
    } catch (err) {
        console.error("[Scanner] Error generando el PDF:", err);
        mostrarNotificacionModal('Error', 'No se pudo generar el PDF del documento escaneado. Intente nuevamente.', 'danger');
        return;
    } finally {
        if (btnFinalizar) {
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = textoOriginalBtn;
        }
    }

    // Destino: preferir la fila desde la que se abrió el escáner. Buscar solo por
    // categoría fallaba cuando había dos filas con el mismo tipo (o si el usuario cambió
    // el selector entre abrir el escáner y finalizar): el PDF terminaba en otra fila.
    let targetInput = null;
    let targetStatusDiv = null;

    if (_filaEscanerOrigen && document.body.contains(_filaEscanerOrigen)) {
        targetInput = _filaEscanerOrigen.querySelector('.input-doc-file');
        targetStatusDiv = _filaEscanerOrigen.querySelector('.status-doc-adjunto');
        const selOrigen = _filaEscanerOrigen.querySelector('.select-doc-cat');
        if (selOrigen) selOrigen.value = targetCat;
    }

    // Respaldo: primera fila libre de esa categoría (y si no, cualquiera de esa categoría).
    if (!targetInput) {
        const rows = document.querySelectorAll('.item-documento');
        let ocupada = null;
        rows.forEach(r => {
            const select = r.querySelector('.select-doc-cat');
            if (!select || select.value !== targetCat) return;
            const input = r.querySelector('.input-doc-file');
            if (!input) return;
            if (!targetInput && (!input.files || input.files.length === 0)) {
                targetInput = input;
                targetStatusDiv = r.querySelector('.status-doc-adjunto');
            } else if (!ocupada) {
                ocupada = r;
            }
        });
        if (!targetInput && ocupada) {
            targetInput = ocupada.querySelector('.input-doc-file');
            targetStatusDiv = ocupada.querySelector('.status-doc-adjunto');
        }
    }

    // Si no existía una fila para esa categoría, crearla
    if (!targetInput) {
        agregarFilaDoc();
        const lastRow = document.querySelector('.item-documento:last-child');
        if (lastRow) {
            const select = lastRow.querySelector('.select-doc-cat');
            if (select) select.value = targetCat;
            targetInput = lastRow.querySelector('.input-doc-file');
            targetStatusDiv = lastRow.querySelector('.status-doc-adjunto');
        }
    }

    const cantPaginas = scannerPro.scannedPages.length;

    // Asignar el archivo PDF generado al input correspondiente
    if (targetInput) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        targetInput.files = dataTransfer.files;

        // Señal de auditoría: si en este lote hubo que arrastrar esquinas o lados, queda
        // marcado en la fila y viaja al servidor junto con el archivo.
        const fila = targetInput.closest('.item-documento');
        const flagManual = fila ? fila.querySelector('.input-recorte-manual') : null;
        if (flagManual) flagManual.value = scannerPro.huboAjusteManual ? '1' : '0';

        // Confirmación PERSISTENTE: es la que el orientador consulta de un vistazo para
        // saber qué soportes lleva del paciente actual.
        marcarFilaAdjunta(fila, cantPaginas, pesoMb);
    }

    // forzar=true: el lote ya quedó adjunto como PDF, no hay nada que confirmar.
    cerrarEscanerPro(true);

    // Confirmación EFÍMERA, sin botón que aceptar: se desvanece sola en ~2,5s.
    mostrarToastEscaner(
        `<strong>${catText}</strong><br><span class="small opacity-75">${cantPaginas} página(s) · ${pesoMb} MB</span>`
    );
}

async function toggleLinternaEscaner() {
    if (!scannerPro) return;
    const active = await scannerPro.toggleTorch();
    const btn = document.getElementById('btn-torch');
    if (btn) btn.classList.toggle('activo', active);
}

// ============================================================================
// ESCÁNER DE CÉDULA COLOMBIANA (PDF417 / CÉDULA DIGITAL / MRZ)
// ============================================================================
let _codeReader = null;
let _multiReader = null;
let _videoInputDevices = [];
let _currentDeviceIndex = 0;
let _selectedDeviceId = null;
let _isScanningCedula = false;
let _cedulaTorchActive = false;
let _cedulaMediaStream = null;

function playBeepSuccess() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1760, ctx.currentTime + 0.08);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.22);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.22);
    } catch (e) {
        console.log('Audio error:', e);
    }
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
    }
}

function cleanCedulaText(str) {
    if (!str) return '';
    return str.replace(/[^a-zA-Z0-9\u00C0-\u00FF\s\+\-]/g, '').trim().replace(/\s+/g, ' ');
}

function inferirTipoDocumentoPorEdad(fechaNacStr) {
    if (!fechaNacStr) return 'CC';
    const parts = fechaNacStr.split('-');
    if (parts.length !== 3) return 'CC';
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    if (isNaN(y) || isNaN(m) || isNaN(d)) return 'CC';
    const birth = new Date(y, m, d);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const diffM = now.getMonth() - birth.getMonth();
    if (diffM < 0 || (diffM === 0 && now.getDate() < birth.getDate())) {
        age--;
    }
    if (age >= 18) return 'CC';
    if (age >= 7) return 'TI';
    return 'RC';
}

function parsearCedulaColombiana(raw) {
    if (!raw) return null;
    
    // Si viene como Uint8Array o Array de bytes, convertir a string Latin-1
    if (typeof raw !== 'string') {
        let str = '';
        for (let i = 0; i < raw.length; i++) {
            str += String.fromCharCode(raw[i]);
        }
        raw = str;
    }

    let res = {
        numero_documento: '',
        tipo_documento: 'CC',
        primer_apellido: '',
        segundo_apellido: '',
        primer_nombre: '',
        segundo_nombre: '',
        sexo: '',
        fecha_nacimiento: '',
        grupo_sanguineo: ''
    };

    // =========================================================================
    // CASO 1: Cédula Digital MRZ (ICAO 930 / I<COL... o líneas de formato viaje)
    // =========================================================================
    if (raw.includes('I<COL') || raw.includes('P<COL') || raw.includes('IDCOL') || raw.includes('<<')) {
        const lines = raw.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const docMatch = line.match(/(?:COL|IDCOL|I<COL|<)([0-9A-Z]{6,12})/);
            if (docMatch && !res.numero_documento) {
                res.numero_documento = docMatch[1].replace(/^[0<]+/, '');
            }
            const bdayMatch = line.match(/(\d{6})\d([MF])/);
            if (bdayMatch && !res.fecha_nacimiento) {
                const yy = parseInt(bdayMatch[1].substring(0, 2), 10);
                const mm = bdayMatch[1].substring(2, 4);
                const dd = bdayMatch[1].substring(4, 6);
                const currentYY = new Date().getFullYear() % 100;
                const year = yy > (currentYY + 5) ? (1900 + yy) : (2000 + yy);
                res.fecha_nacimiento = year + '-' + mm + '-' + dd;
                res.sexo = bdayMatch[2];
            }
            if (line.includes('<<') && !line.startsWith('I<') && !line.startsWith('P<')) {
                const parts = line.split('<<');
                if (parts[0]) {
                    const ape = parts[0].split('<').filter(Boolean);
                    if (ape.length > 0) res.primer_apellido = cleanCedulaText(ape[0]);
                    if (ape.length > 1) res.segundo_apellido = cleanCedulaText(ape[1]);
                }
                if (parts[1]) {
                    const nom = parts[1].split('<').filter(Boolean);
                    if (nom.length > 0) res.primer_nombre = cleanCedulaText(nom[0]);
                    if (nom.length > 1) res.segundo_nombre = cleanCedulaText(nom.slice(1).join(' '));
                }
            }
        }
        if (res.fecha_nacimiento) {
            res.tipo_documento = inferirTipoDocumentoPorEdad(res.fecha_nacimiento);
        }
        if (res.numero_documento) return res;
    }

    // =========================================================================    // =========================================================================
    // CASO 2: Cédula Tradicional PDF417 (Registraduría Nacional del Estado Civil)
    // Usando anclaje exacto de frontera de 10 dígitos y 4 bloques de 23 caracteres
    // =========================================================================
    let cleanStr = raw.replace(/[\x00-\x1F\x7F-\x9F\uFFFD]/g, ' ');
    const dateMatch = cleanStr.match(/(19\d\d|20[0-2]\d)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])/);

    if (dateMatch) {
        const F = dateMatch.index;

        // Detectar Sexo antes de la fecha
        const preDate = cleanStr.substring(Math.max(0, F - 6), F);
        let sexChar = '';
        for (let i = preDate.length - 1; i >= 0; i--) {
            if (/[MF]/i.test(preDate[i])) {
                sexChar = preDate[i].toUpperCase();
                break;
            }
        }

        // Buscar el inicio exacto de la Cédula (10 dígitos seguidos inmediatamente de una letra A-Z del primer apellido)
        let bestDocStart = -1;
        let bestDoc = '';

        for (let delta = 94; delta <= 112; delta++) {
            const start = F - delta;
            if (start < 0) continue;
            const cand = cleanStr.substring(start, start + 10);
            const nextChar = cleanStr.charAt(start + 10);
            if (/^\d{10}$/.test(cand) && /^[A-Z\u00C0-\u00DC]/i.test(nextChar)) {
                bestDocStart = start;
                bestDoc = cand;
                break;
            }
        }

        // Fallback: si no coincidió letra, buscar la secuencia de 10 dígitos en ese rango
        if (bestDocStart === -1) {
            for (let delta = 94; delta <= 112; delta++) {
                const start = F - delta;
                if (start < 0) continue;
                const cand = cleanStr.substring(start, start + 10);
                if (/^\d{10}$/.test(cand)) {
                    bestDocStart = start;
                    bestDoc = cand;
                    break;
                }
            }
        }

        if (bestDocStart !== -1) {
            // Estándar Registraduría: 4 campos de 23 caracteres exactos
            const namesStart = bestDocStart + 10;
            const ape1Raw = cleanStr.substring(namesStart, namesStart + 23);
            const ape2Raw = cleanStr.substring(namesStart + 23, namesStart + 46);
            const nom1Raw = cleanStr.substring(namesStart + 46, namesStart + 69);
            const nom2Raw = cleanStr.substring(namesStart + 69, namesStart + 92);

            // Factor RH después de la fecha de nacimiento
            const postDate = cleanStr.substring(F + 8, Math.min(cleanStr.length, F + 35));
            const rhM = postDate.match(/(O\+|O-|A\+|A-|B\+|B-|AB\+|AB-|\+|\-|0\+|0-)/i);
            let rh = rhM ? rhM[1].toUpperCase().replace('0', 'O') : '';
            if (rh === '+' || rh === '-') rh = 'O' + rh;

            res.numero_documento = cleanCedulaText(bestDoc).replace(/\D/g, '').replace(/^0+/, '');
            res.primer_apellido = cleanCedulaText(ape1Raw);
            res.segundo_apellido = cleanCedulaText(ape2Raw);
            res.primer_nombre = cleanCedulaText(nom1Raw);
            res.segundo_nombre = cleanCedulaText(nom2Raw);
            res.sexo = sexChar;
            res.fecha_nacimiento = dateMatch[1] + '-' + dateMatch[2] + '-' + dateMatch[3];
            res.grupo_sanguineo = rh;
            res.tipo_documento = inferirTipoDocumentoPorEdad(res.fecha_nacimiento);

            if (res.numero_documento) return res;
        }
    }

    return null;
}

function inicializarLectorZXing() {
    if (typeof ZXing === 'undefined') return false;
    
    if (!_codeReader) {
        const hints = new Map();
        hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        _codeReader = new ZXing.BrowserPDF417Reader(hints, 200);
    }
    if (!_multiReader) {
        const hints = new Map();
        hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
            ZXing.BarcodeFormat.PDF_417,
            ZXing.BarcodeFormat.QR_CODE,
            ZXing.BarcodeFormat.CODE_128,
            ZXing.BarcodeFormat.DATA_MATRIX
        ]);
        _multiReader = new ZXing.BrowserMultiFormatReader(hints, 250);
    }
    return true;
}

function aplicarContrasteYGrises(data) {
    const factor = 1.7;
    for (let i = 0; i < data.length; i += 4) {
        const gray = 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
        let c = Math.floor(factor * (gray - 128) + 128);
        if (c < 0) c = 0;
        if (c > 255) c = 255;
        data[i] = c;
        data[i+1] = c;
        data[i+2] = c;
    }
}

async function abrirEscanerCedula() {
    const modalEl = document.getElementById('modalEscanerCedula');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();

    setTimeout(() => {
        iniciarCamaraCedula();
    }, 200);
}

async function iniciarCamaraCedula() {
    detenerEscanerCedula();

    const statusEl = document.getElementById('scannerCedulaStatus');
    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1 text-primary"></i> Conectando con la cámara del dispositivo...';

    if (!inicializarLectorZXing()) {
        if (statusEl) statusEl.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error: Librería de escáner no disponible.</span>';
        return;
    }

    try {
        _videoInputDevices = await _codeReader.listVideoInputDevices();
        
        let backCamIndex = _videoInputDevices.findIndex(d => {
            const l = (d.label || '').toLowerCase();
            return l.includes('back') || l.includes('trasera') || l.includes('posterior') || l.includes('environment');
        });
        if (backCamIndex === -1 && _videoInputDevices.length > 0) backCamIndex = _videoInputDevices.length - 1;
        if (!_selectedDeviceId && _videoInputDevices.length > 0 && backCamIndex >= 0) {
            _selectedDeviceId = _videoInputDevices[backCamIndex].deviceId;
        }

        const constraints = {
            audio: false,
            video: {
                deviceId: _selectedDeviceId ? { exact: _selectedDeviceId } : undefined,
                facingMode: _selectedDeviceId ? undefined : { ideal: 'environment' },
                width: { min: 1280, ideal: 1920, max: 3840 },
                height: { min: 720, ideal: 1080, max: 2160 }
            }
        };

        if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-barcode me-1 text-success"></i> Escaneando en vivo. Acerca la cédula a 10-15 cm...';
        _isScanningCedula = true;

        // Iniciar decodificación continua con BrowserPDF417Reader
        _codeReader.decodeFromConstraints(constraints, 'videoEscanerCedula', (result, err) => {
            if (result && _isScanningCedula) {
                _isScanningCedula = false;
                procesarResultadoEscaneo(result);
            }
        });

        // Intentar activar enfoque continuo en el stream
        setTimeout(() => {
            const video = document.getElementById('videoEscanerCedula');
            if (video && video.srcObject) {
                _cedulaMediaStream = video.srcObject;
                const track = _cedulaMediaStream.getVideoTracks()[0];
                if (track && track.applyConstraints) {
                    try {
                        track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
                    } catch(e) {}
                }
            }
        }, 600);

    } catch (err) {
        console.error("Error al iniciar cámara cédula:", err);
        if (statusEl) {
            statusEl.innerHTML = `<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> No se pudo iniciar la cámara: ${err.message || err}. Puedes usar el botón de galería.</span>`;
        }
    }
}

async function capturarFotoYLeerCedula() {
    const video = document.getElementById('videoEscanerCedula');
    const statusEl = document.getElementById('scannerCedulaStatus');
    if (!video || !video.videoWidth) {
        if (statusEl) statusEl.innerHTML = '<span class="text-warning"><i class="fa-solid fa-spinner fa-spin me-1"></i> Esperando video de la cámara...</span>';
        return;
    }

    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1 text-primary"></i> Procesando foto de la cédula...';

    inicializarLectorZXing();

    const canvas = document.getElementById('canvasProcesadorCedula') || document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    const dataUrl = canvas.toDataURL('image/png');
    const img = new Image();
    img.src = dataUrl;
    img.onload = async () => {
        // Intento 1: Lector PDF417 sobre imagen normal
        try {
            const result = await _codeReader.decodeFromImageElement(img);
            if (result) {
                detenerEscanerCedula();
                procesarResultadoEscaneo(result);
                return;
            }
        } catch(e) {}

        // Intento 2: Lector MultiFormat sobre imagen normal
        try {
            const resultMulti = await _multiReader.decodeFromImageElement(img);
            if (resultMulti) {
                detenerEscanerCedula();
                procesarResultadoEscaneo(resultMulti);
                return;
            }
        } catch(e) {}

        // Intento 3: Aplicar contraste y procesar
        try {
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            aplicarContrasteYGrises(imgData.data);
            ctx.putImageData(imgData, 0, 0);

            const contrastImg = new Image();
            contrastImg.src = canvas.toDataURL('image/png');
            contrastImg.onload = async () => {
                try {
                    const resContrast = await _codeReader.decodeFromImageElement(contrastImg);
                    if (resContrast) {
                        detenerEscanerCedula();
                        procesarResultadoEscaneo(resContrast);
                        return;
                    }
                } catch(e) {}

                try {
                    const resContrastMulti = await _multiReader.decodeFromImageElement(contrastImg);
                    if (resContrastMulti) {
                        detenerEscanerCedula();
                        procesarResultadoEscaneo(resContrastMulti);
                        return;
                    }
                } catch(e) {}

                if (statusEl) {
                    statusEl.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> No se detectó el código en la captura. Acerca la cédula a unos 10-15 cm con buena luz y vuelve a tocar el botón.</span>';
                }
            };
        } catch(errContrast) {
            if (statusEl) {
                statusEl.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> No se detectó código. Intenta con mejor iluminación.</span>';
            }
        }
    };
}

function detenerEscanerCedula() {
    _isScanningCedula = false;
    if (_codeReader) {
        try {
            _codeReader.reset();
        } catch(e) {}
    }
    if (_multiReader) {
        try {
            _multiReader.reset();
        } catch(e) {}
    }
    if (_cedulaMediaStream) {
        try {
            _cedulaMediaStream.getTracks().forEach(t => t.stop());
        } catch(e) {}
        _cedulaMediaStream = null;
    }
    _cedulaTorchActive = false;
    const btn = document.getElementById('btnTorchCedula');
    if (btn) btn.classList.remove('btn-warning');
}

async function cambiarCamaraCedula() {
    if (!_videoInputDevices || _videoInputDevices.length <= 1) {
        mostrarNotificacionModal('Cámara', 'Solo se detectó una cámara en este dispositivo.', 'info');
        return;
    }
    detenerEscanerCedula();
    _currentDeviceIndex = (_currentDeviceIndex + 1) % _videoInputDevices.length;
    _selectedDeviceId = _videoInputDevices[_currentDeviceIndex].deviceId;
    iniciarCamaraCedula();
}

async function toggleLinternaCedula() {
    if (!_cedulaMediaStream) return;
    const track = _cedulaMediaStream.getVideoTracks()[0];
    if (!track) return;
    const capabilities = track.getCapabilities ? track.getCapabilities() : {};
    if (!capabilities.torch) {
        mostrarNotificacionModal('Linterna', 'La linterna / flash no es compatible con la cámara seleccionada.', 'info');
        return;
    }
    try {
        _cedulaTorchActive = !_cedulaTorchActive;
        await track.applyConstraints({
            advanced: [{ torch: _cedulaTorchActive }]
        });
        const btn = document.getElementById('btnTorchCedula');
        if (btn) btn.classList.toggle('btn-warning', _cedulaTorchActive);
    } catch (e) {
        console.warn("No se pudo alternar linterna:", e);
    }
}

function escanearDesdeArchivo(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    const statusEl = document.getElementById('scannerCedulaStatus');
    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1 text-primary"></i> Analizando foto de la galería...';

    inicializarLectorZXing();

    const imgUrl = URL.createObjectURL(file);
    const img = new Image();
    img.src = imgUrl;
    img.onload = async () => {
        URL.revokeObjectURL(imgUrl);
        try {
            const res = await _codeReader.decodeFromImageElement(img);
            if (res) {
                detenerEscanerCedula();
                procesarResultadoEscaneo(res);
                return;
            }
        } catch(e) {}

        try {
            const resMulti = await _multiReader.decodeFromImageElement(img);
            if (resMulti) {
                detenerEscanerCedula();
                procesarResultadoEscaneo(resMulti);
                return;
            }
        } catch(e) {}

        if (statusEl) {
            statusEl.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> No se detectó código en la imagen seleccionada. Verifique que esté nítida y bien enfocada.</span>';
        }
    };
}

function procesarResultadoEscaneo(result) {
    playBeepSuccess();
    
    let datos = null;
    let rawText = '';
    
    // Probar primero con result.getText() formateado por ZXing
    if (result && result.getText) {
        rawText = result.getText();
        datos = parsearCedulaColombiana(rawText);
    } else if (typeof result === 'string') {
        rawText = result;
        datos = parsearCedulaColombiana(rawText);
    } else if (result && result.text) {
        rawText = result.text;
        datos = parsearCedulaColombiana(rawText);
    }

    // Si no parseó, intentar con los bytes crudos (Latin-1)
    if ((!datos || !datos.numero_documento) && result && result.getRawBytes) {
        const rawBytes = result.getRawBytes();
        if (rawBytes && rawBytes.length > 0) {
            let byteStr = '';
            for (let i = 0; i < rawBytes.length; i++) {
                byteStr += String.fromCharCode(rawBytes[i]);
            }
            if (byteStr) {
                const byteDatos = parsearCedulaColombiana(byteStr);
                if (byteDatos && byteDatos.numero_documento) {
                    datos = byteDatos;
                    rawText = byteStr;
                } else if (!rawText) {
                    rawText = byteStr;
                }
            }
        }
    }

    detenerEscanerCedula();

    const modalEl = document.getElementById('modalEscanerCedula');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    if (!datos || !datos.numero_documento) {
        mostrarNotificacionModal('Lectura Parcial', `Se detectó un código pero no se pudo estructurar el formato de Cédula Colombiana.<br><small class="text-muted">Texto leído: ${(rawText || '').substring(0, 100)}...</small>`, 'warning');
        return;
    }

    // Auto-completar campos en el formulario
    const docTipo = datos.tipo_documento || 'CC';
    setSelectValue('tipo_documento', docTipo);
    
    const docInput = document.getElementById('numero_documento');
    if (docInput) docInput.value = datos.numero_documento;

    if (datos.primer_apellido && document.getElementById('primer_apellido')) {
        document.getElementById('primer_apellido').value = datos.primer_apellido;
    }
    if (datos.segundo_apellido && document.getElementById('segundo_apellido')) {
        document.getElementById('segundo_apellido').value = datos.segundo_apellido;
    }
    if (datos.primer_nombre && document.getElementById('primer_nombre')) {
        document.getElementById('primer_nombre').value = datos.primer_nombre;
    }
    if (datos.segundo_nombre && document.getElementById('segundo_nombre')) {
        document.getElementById('segundo_nombre').value = datos.segundo_nombre;
    }
    if (datos.sexo) {
        setSelectValue('sexo', datos.sexo);
    }
    if (datos.fecha_nacimiento && document.getElementById('fecha_nacimiento')) {
        document.getElementById('fecha_nacimiento').value = datos.fecha_nacimiento;
    }
    if (datos.grupo_sanguineo) {
        setSelectValue('grupo_sanguineo', datos.grupo_sanguineo);
    }

    const nombreCompleto = `${datos.primer_nombre} ${datos.segundo_nombre} ${datos.primer_apellido} ${datos.segundo_apellido}`.replace(/\s+/g, ' ').trim();
    const docTitulo = docTipo === 'TI' ? 'Tarjeta de Identidad' : (docTipo === 'RC' ? 'Registro Civil' : 'Cédula');
    mostrarNotificacionModal(
        `¡${docTitulo} Escaneada con Éxito!`,
        `<strong>${nombreCompleto}</strong><br>${docTipo}: <strong>${datos.numero_documento}</strong><br>Fecha Nac: ${datos.fecha_nacimiento || 'N/A'} · Sexo: ${datos.sexo || 'N/A'} · RH: ${datos.grupo_sanguineo || 'N/A'}<br><br><span class="badge bg-success-subtle text-success">Datos cargados en el formulario</span>`,
        'success'
    );

    // Disparar búsqueda automática de historial / pacientes crónicos / registros previos
    const btnBuscar = document.getElementById('btnBuscarPaciente');
    if (btnBuscar) {
        setTimeout(() => {
            btnBuscar.click();
        }, 300);
    }
}
