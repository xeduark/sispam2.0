<div class="modal fade" id="modalHistorialPaciente" tabindex="-1" aria-hidden="true" style="z-index: 1080;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <div class="overflow-hidden">
                    <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de atenciones</h5>
                    <small id="historialPacienteNombre" class="opacity-75"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-2">
                <div id="historialAlertaDuplicado" class="d-none alert alert-danger py-2 px-3 mb-2 fw-bold"></div>
                <div id="historialResumen" class="small text-muted mb-2"></div>
                <div id="historialTabla"></div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-primary fw-bold" data-bs-dismiss="modal">
                    Continuar con el ingreso
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmación genérica (sí/no) para el escáner. Se abre por encima del modal del
     escáner (z-index 1055), por eso 1090. -->
<div class="modal fade" id="modalConfirmEscaner" tabindex="-1" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1095;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fw-bold" id="modalConfirmTitle">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Confirmar
                </h5>
            </div>
            <div class="modal-body" id="modalConfirmMessage"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnConfirmEscanerNo">Cancelar</button>
                <button type="button" class="btn btn-warning fw-bold btn-sm text-dark" id="btnConfirmEscanerSi">Continuar</button>
            </div>
        </div>
    </div>
</div>

<!-- Vista previa de documento o página escaneada a pantalla completa (Imagen / PDF) -->
<div class="modal fade" id="modalPreviewPagina" tabindex="-1" aria-hidden="true" style="z-index: 1098;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="z-index: 1099;">
        <div class="modal-content bg-dark border-secondary text-white shadow-lg">
            <div class="modal-header py-2 border-secondary">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="previewPaginaTitulo">
                    <i class="fa-solid fa-file-lines text-info"></i> Vista Previa del Documento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2" style="background:#0f172a; min-height: 400px; display: flex; align-items: center; justify-content: center;">
                <img id="previewPaginaImg" class="img-fluid rounded shadow" style="max-height:80vh; object-fit: contain;" alt="Vista previa de documento">
                <iframe id="previewPaginaIframe" class="w-100 d-none rounded" style="height:80vh; border:0; background:#fff;"></iframe>
            </div>
            <div class="modal-footer py-2 border-secondary justify-content-between">
                <small class="text-info fw-semibold" id="previewPaginaInfo"></small>
                <button type="button" class="btn btn-outline-light btn-sm px-4 fw-bold" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Cerrar Vista
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ESCÁNER DE CÉDULA CON CÁMARA (PDF417 / CÉDULA COLOMBIANA) -->
<style>
.laser-line-cedula {
    position: absolute;
    top: 10%;
    left: 5%;
    right: 5%;
    height: 2px;
    background: #00ff88;
    box-shadow: 0 0 12px #00ff88, 0 0 24px #00ff88;
    animation: scanLaserCedulaAnim 2s infinite ease-in-out alternate;
}
@keyframes scanLaserCedulaAnim {
    0% { top: 12%; opacity: 0.9; }
    100% { top: 88%; opacity: 0.9; }
}
</style>

<div class="modal fade" id="modalEscanerCedula" tabindex="-1" aria-labelledby="modalEscanerCedulaTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-success bg-opacity-25 rounded-circle text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-id-card fs-5 text-success"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalEscanerCedulaTitle">Escanear Cédula de Ciudadanía</h5>
                        <small class="text-white-50">Apunta la cámara al código de barras del reverso (PDF417)</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="detenerEscanerCedula()"></button>
            </div>
            
            <div class="modal-body p-0 bg-black position-relative text-center">
                <div class="position-relative d-inline-block w-100" style="min-height: 320px; max-height: 480px; overflow: hidden; background: #000;">
                    <video id="videoEscanerCedula" class="w-100 h-100" style="object-fit: cover; max-height: 480px;" playsinline autoplay muted></video>
                    <canvas id="canvasProcesadorCedula" class="d-none"></canvas>
                    
                    <!-- Guía visual con esquinas y mira láser animada -->
                    <div class="cedula-scanner-overlay position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center justify-content-center" style="width: 88%; max-width: 440px; height: 190px; border: 2.5px dashed #00ff88; border-radius: 14px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.6); pointer-events: none;">
                        <div class="laser-line-cedula"></div>
                        <div class="badge bg-dark bg-opacity-75 text-white px-3 py-1 rounded-pill mt-auto mb-2 small fw-semibold border border-secondary">
                            <i class="fa-solid fa-barcode me-1 text-warning"></i> Encuadra el código de barras aquí
                        </div>
                    </div>

                    <!-- Estado / Spinner de detección en tiempo real -->
                    <div id="scannerCedulaStatus" class="position-absolute bottom-0 start-0 end-0 p-2 text-white bg-dark bg-opacity-85 small text-center fw-semibold">
                        <i class="fa-solid fa-spinner fa-spin me-1 text-primary"></i> Iniciando cámara en alta resolución HD...
                    </div>
                </div>

                <!-- Botón de Captura Rápida en pantalla -->
                <div class="bg-dark p-3 text-center border-top border-secondary">
                    <button type="button" class="btn btn-success btn-lg px-4 py-2 fw-bold shadow rounded-pill" id="btnCapturarCedulaManual" onclick="capturarFotoYLeerCedula()">
                        <i class="fa-solid fa-camera me-2"></i> 📸 Tomar Foto y Leer Cédula
                    </button>
                    <div class="text-white-50 small mt-1">Si no lee en vivo automáticamente, toca el botón para capturar foto nítida</div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTorchCedula" onclick="toggleLinternaCedula()" title="Encender Linterna">
                        <i class="fa-solid fa-lightbulb me-1"></i> Linterna
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSwitchCamCedula" onclick="cambiarCamaraCedula()" title="Cambiar Cámara">
                        <i class="fa-solid fa-camera-rotate me-1"></i> Cambiar Cámara
                    </button>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <label class="btn btn-outline-primary btn-sm mb-0 cursor-pointer" title="Cargar foto desde galería">
                        <i class="fa-solid fa-image me-1"></i> Galería / Archivo
                        <input type="file" id="inputFotoCedula" accept="image/*" class="d-none" onchange="escanearDesdeArchivo(this)">
                    </label>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="detenerEscanerCedula()">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNotificacionEscaner" tabindex="-1" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1095;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" id="modalNotifHeader">
                <h5 class="modal-title fw-bold" id="modalNotifTitle">
                    <i class="fa-solid fa-bell me-2"></i> Notificación
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarNotificacionModal()" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="modalNotifIcon" class="display-3 mb-3"></div>
                <div id="modalNotifMessage" class="fs-5 fw-semibold"></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="cerrarNotificacionModal()" data-bs-dismiss="modal">
                    Entendido / Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ESCÁNER DE DOCUMENTOS PROFESIONAL (CAMSCANNER MULTIPÁGINA & REINICIO DE CÁMARA) -->
<!-- backdrop="static" + keyboard="false": sin esto, cerrar con ESC o con un clic fuera
     esquivaba cerrarEscanerPro(), dejando la cámara encendida y perdiendo el lote de
     páginas sin ningún aviso. Ahora el cierre pasa siempre por el botón X o Cancelar. -->
<div class="modal fade" id="modalEscanerDocPro" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1090 !important;">
    <div class="modal-dialog scanner-modal-dialog">
        <div class="modal-content scanner-modal-content">
            
            <!-- El título es el NOMBRE DEL DOCUMENTO que se está escaneando, no el del
                 escáner: el orientador necesita saber qué está subiendo. El subtítulo solo
                 aparece en los flujos guiados de varias caras ("Frente" / "Reverso"). -->
            <div class="scanner-modal-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <i class="fa-solid fa-camera-retro text-info fs-5"></i>
                    <div class="overflow-hidden">
                        <h5 class="modal-title fw-bold mb-0 text-white text-truncate" id="scanner-doc-titulo">Documento</h5>
                        <small class="text-warning fw-bold d-none" id="scanner-doc-paso"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarEscanerPro()"></button>
            </div>

            <div class="scanner-modal-body">
                <!-- Alerta HTTPS para Servidor de Producción -->
                <div id="camera-https-alert" class="alert alert-warning alert-dismissible fade show d-none small mb-2">
                    <i class="fa-solid fa-lock me-1"></i> <strong>Conexión Segura Requerida:</strong> Para usar la cámara en vivo en Hostinger, ingrese por HTTPS.
                    <a href="javascript:void(0)" onclick="window.location.href = window.location.href.replace('http:', 'https:')" class="fw-bold text-dark ms-2">Clic aquí para cambiar a HTTPS</a>
                </div>

                <!-- El tipo de documento ya lo fijó la fila desde la que se abrió el escáner
                     (botón "Escanear"). Tenerlo además en un selector aquí era estado
                     duplicado y una decisión técnica que el orientador no debe tomar. -->

                <!-- VISOR ÚNICO: el mismo recuadro sirve para la cámara en vivo y para la
                     revisión del recorte; solo cambia qué capa está visible y qué controles
                     hay debajo. Así no se mueven nodos del DOM entre pantallas. -->
                <div id="paso-captura-container">
                    <div class="scanner-canvas-wrapper shadow-lg position-relative mb-2">
                        <!-- Estado de carga de librerías (OpenCV.js/jsPDF/scanner_doc.js), perezosas -->
                        <div id="scanner-libs-loading" class="d-none text-center text-white p-3">
                            <div class="spinner-border text-info mb-2" role="status"></div>
                            <div class="small">Cargando componentes del escáner...</div>
                        </div>

                        <!-- Video WebRTC en vivo -->
                        <video id="webcam-video" autoplay playsinline muted class="w-100 h-100"></video>

                        <!-- Overlay de detección de bordes EN VIVO (Fase 2): sigue el documento mientras se encuadra -->
                        <canvas id="scanner-live-overlay" class="position-absolute top-0 start-0"></canvas>

                        <!-- Overlay Canvas para ajuste interactivo de esquinas (después de capturar) -->
                        <canvas id="scanner-canvas-overlay" class="d-none position-absolute top-0 start-0"></canvas>

                        <!-- Indicador visual en vivo del paso actual (Frente / Reverso) -->
                        <div id="badge-paso-escaner" class="position-absolute top-0 start-50 translate-middle-x mt-2 px-3 py-1 rounded-pill bg-dark bg-opacity-75 text-white fw-bold small text-nowrap shadow d-none" style="z-index: 100;">
                            <i class="fa-solid fa-id-card text-info me-1" id="icono-paso-escaner"></i>
                            <span id="texto-paso-escaner">Paso 1 de 2: Frente de la Cédula</span>
                        </div>

                        <!-- Lupa de precisión táctil -->
                        <div id="loupe-container" class="d-none">
                            <canvas id="canvas-loupe"></canvas>
                        </div>
                    </div>

                    <!-- Panel de depuración de la detección de bordes. Oculto salvo que se
                         abra la página con ?scannerdebug=1 — permite afinar los umbrales
                         geométricos con datos reales en vez de a ciegas. -->
                    <div id="scanner-debug-panel" class="d-none mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-warning fw-bold">
                                <i class="fa-solid fa-bug me-1"></i> Depuración de detección
                                <span class="text-muted">(bordes Canny · candidatos naranja · ganador verde)</span>
                            </small>
                        </div>
                        <canvas id="scanner-debug-canvas" class="w-100 rounded border border-warning" style="max-height:40vh;object-fit:contain;background:#000;"></canvas>
                    </div>

                    <!-- CONTROLES DE CAPTURA: obturador y dos accesos discretos. El aviso
                         "mantenga el documento quieto" se eliminó: lo que importa no es
                         "no lo muevas" sino "va a disparar solo", y eso lo comunica el
                         anillo de progreso sobre el documento y sobre el obturador. -->
                    <div id="controles-captura" class="text-center my-2">
                        <button type="button" class="scanner-shutter" id="btn-snap-cam"
                                onclick="capturarFotoEscaner()" title="Capturar Foto / Documento">
                            <span class="scanner-shutter-core"><i class="fa-solid fa-camera"></i></span>
                        </button>

                        <div class="d-flex justify-content-center align-items-center gap-3 mt-2">
                            <button type="button" class="scanner-link" id="btn-torch" onclick="toggleLinternaEscaner()">
                                <i class="fa-solid fa-bolt me-1"></i> Linterna
                            </button>
                            <label class="scanner-link mb-0" title="Usar una foto que ya está en el teléfono o equipo">
                                <i class="fa-solid fa-image me-1"></i> Galería / Archivo
                                <input type="file" id="input-foto-nativa" accept="image/*,application/pdf" capture="environment" class="d-none" onchange="cargarFotoNativaEscaner(event)">
                            </label>
                        </div>
                    </div>

                    <!-- CONTROLES DE REVISIÓN: solo giro, como iconos pequeños. Los presets
                         de formato se eliminaron porque el tipo de documento ya determina el
                         formato esperado, y el selector de modo de color porque el modo lo
                         decide el tipo (ver CONFIG_DOCUMENTOS). -->
                    <div id="controles-revision" class="d-none d-flex justify-content-center align-items-center gap-2 my-2 flex-wrap">
                        <button type="button" class="scanner-icon-btn" onclick="scannerPro.rotateImage('left')" title="Girar 90° a la izquierda">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button type="button" class="scanner-icon-btn" onclick="scannerPro.rotateImage('right')" title="Girar 90° a la derecha">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-info text-white fw-bold px-3 shadow-sm rounded-pill" onclick="verPreviewPaginaActual()" title="Ver la captura en pantalla completa para verificar nitidez">
                            <i class="fa-solid fa-magnifying-glass-plus me-1"></i> Ver Legible / Zoom
                        </button>
                    </div>
                </div>

                <!-- Canvas de salida: fuera de pantalla. Sigue siendo donde processScan()
                     deja el resultado final que se guarda en el lote, pero ya no es un paso
                     que el orientador tenga que mirar ni confirmar. -->
                <canvas id="canvas-processed" class="d-none"></canvas>

                <!-- Miniaturas: ocultas mientras el lote esté vacío, para no gastar alto -->
                <div class="mt-1 d-none" id="bloque-miniaturas">
                    <div class="pages-thumbnail-strip" id="strip-miniaturas"></div>
                </div>
            </div>

            <!-- Pie de REVISIÓN: exactamente tres acciones, sin nada más. Queda oculto
                 durante la captura para no gastar alto en pantalla de teléfono. -->
            <div class="scanner-modal-footer d-none" id="scanner-footer-revision">
                <button type="button" class="btn btn-outline-light btn-sm flex-fill" id="btn-repetir-foto" onclick="repetirFotoEscaner()">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Tomar de nuevo
                </button>

                <button type="button" class="btn btn-outline-info btn-sm fw-bold flex-fill" id="btn-agregar-otra-pag" onclick="guardarPaginaYOtra()">
                    <i class="fa-solid fa-plus me-1"></i> Otra página
                </button>

                <!-- Oculto mientras el lote esté vacío: un botón cuya única acción posible
                     es fallar con "tome al menos una foto" no debe existir en ese estado. -->
                <button type="button" class="btn btn-success btn-sm fw-bold flex-fill shadow d-none" id="btn-finalizar-pdf" onclick="finalizarYAdjuntarPDF()">
                    <i class="fa-solid fa-check me-1"></i> Adjuntar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Librería de decodificación de códigos de barras (PDF417 / Cédula Colombiana) -->
