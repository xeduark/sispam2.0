<?php
require_once __DIR__ . '/../../config/app.php';
check_role('entrega');

require_once __DIR__ . '/../../models/Ingreso.php';
require_once __DIR__ . '/../../models/ModuloEntrega.php';

$ingresoModel = new Ingreso();
$modModel     = new ModuloEntrega();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finalizar_entrega') {
    $ingreso_id           = intval($_POST['ingreso_id'] ?? 0);
    $firma_base64         = $_POST['firma_base64'] ?? '';
    $foto_paciente_base64 = $_POST['foto_paciente_base64'] ?? '';
    $foto_paciente_file   = $_FILES['foto_paciente_file'] ?? null;

    if (!empty($firma_base64)) {
        if ($ingresoModel->finalizarEntrega($ingreso_id, $firma_base64, $foto_paciente_base64, $foto_paciente_file)) {
            $mensaje = "Entrega finalizada con éxito. El acta de entrega ha sido firmada digitalmente. <a href='index.php?page=imprimir_acta&id={$ingreso_id}' target='_blank' class='btn btn-sm btn-success ms-2 fw-bold'><i class='fa-solid fa-print me-1'></i> Imprimir Acta Firmada + PDFs</a>";
        } else {
            $error = 'Ocurrió un error al guardar la entrega.';
        }
    } else {
        $error = 'Es obligatorio capturar la firma digital del paciente.';
    }
}

// Obtener todos los registros listos para entrega en cola abierta (sin filtro de módulos)
$listaEntrega = $ingresoModel->getListaEntrega('TODOS');

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-hand-holding-medical me-2"></i> Módulo de Facturación, Entrega & Firma Digital</h4>
        <p class="text-muted small">Atención en ventanilla en cola abierta, validación de empaque, novedades de alistamiento, foto del paciente y captura de firma digital.</p>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show small"><i class="fa-solid fa-circle-check me-1"></i> <?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card card-glass border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users-rectangle me-2 text-info"></i> Cola General de Entrega & Facturación</h5>
        <span class="badge bg-info text-dark fs-6"><?= count($listaEntrega) ?> Esperando Entrega</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tiquete</th>
                        <th>Ventanilla Surtida</th>
                        <th>Paciente</th>
                        <th>EPS</th>
                        <th>Novedad / Faltantes</th>
                        <th>PDFs Adjuntos</th>
                        <th class="text-end pe-3">Gestionar Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listaEntrega)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay pacientes esperando en la cola general de entrega.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($listaEntrega as $row): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-primary fs-5"><?= htmlspecialchars($row['ticket_numero']) ?></td>
                        <td><span class="badge bg-success fs-6"><i class="fa-solid fa-door-open me-1"></i> <?= htmlspecialchars($row['modulo_entrega_asignado']) ?></span></td>
                        <td>
                            <div class="fw-bold">
                                <?= htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) ?>
                                <?= get_prioridad_badge($row['prioridad'] ?? 'NORMAL') ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($row['tipo_documento'] . ' ' . $row['numero_documento']) ?></small>
                        </td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['eps_nombre']) ?></span></td>
                        <td>
                            <?php if (!empty($row['faltantes_alistamiento'])): ?>
                                <span class="badge bg-warning text-dark p-2 text-wrap" style="max-width: 250px;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Con Diferencia de Cantidad
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success p-2"><i class="fa-solid fa-circle-check me-1"></i> Entrega 100% Completa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if (!empty($row['pdf_transcripcion_url'])): ?>
                                    <a href="<?= $row['pdf_transcripcion_url'] ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF Transcripción">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Transcripción
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($row['pdf_alistamiento'])): ?>
                                    <a href="<?= $row['pdf_alistamiento'] ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Ver PDF Alistamiento">
                                        <i class="fa-solid fa-boxes-packing me-1"></i> Alistamiento
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" 
                                    class="btn btn-primary fw-bold btn-abrir-firma" 
                                    data-id="<?= $row['id'] ?>"
                                    data-ticket="<?= htmlspecialchars($row['ticket_numero'], ENT_QUOTES) ?>"
                                    data-paciente="<?= htmlspecialchars($row['nombres'] . ' ' . $row['apellidos'], ENT_QUOTES) ?>"
                                    data-doc="<?= htmlspecialchars($row['tipo_documento'] . ' ' . $row['numero_documento'], ENT_QUOTES) ?>"
                                    data-faltantes="<?= htmlspecialchars($row['faltantes_alistamiento'] ?? '', ENT_QUOTES) ?>">
                                <i class="fa-solid fa-signature me-1"></i> Entregar & Capturar Firma
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Firma Digital Táctil & Foto Paciente -->
<div class="modal fade" id="modalFirmaDigital" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-glass">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-signature me-2 text-warning"></i> Entrega, Firma Digital & Registro Fotográfico del Paciente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="formEntregaFirma">
                <input type="hidden" name="action" value="finalizar_entrega">
                <input type="hidden" name="ingreso_id" id="entrega_ingreso_id">
                <input type="hidden" name="firma_base64" id="firma_base64">
                <input type="hidden" name="foto_paciente_base64" id="foto_paciente_base64">

                <div class="modal-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="text-muted small">Tiquete de Atención:</span>
                                <div class="fw-bold text-primary fs-5" id="entrega_ticket_txt">-</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small">Paciente Recepcionado:</span>
                                <div class="fw-bold text-dark fs-5" id="entrega_paciente_txt">-</div>
                                <small class="text-muted" id="entrega_doc_txt"></small>
                            </div>
                        </div>
                    </div>

                    <div id="container-novedades-entrega" class="mb-3 d-none">
                        <div class="alert alert-warning p-3 small fw-bold">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Novedades de Alistamiento (Detalle de Cantidades Faltantes):</strong>
                            <div id="entrega_faltantes_txt" class="mt-2 text-dark font-monospace bg-white p-2 rounded border border-warning" style="white-space: pre-wrap;"></div>
                        </div>
                    </div>

                    <!-- Captura Fotográfica del Paciente -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <label class="form-label fw-bold text-dark mb-2">
                            <i class="fa-solid fa-camera text-primary me-1"></i> Captura o Subida de Foto del Paciente (Opcional):
                        </label>
                        <div class="row align-items-center">
                            <div class="col-md-6 text-center">
                                <video id="video-camara-paciente" class="img-fluid rounded border bg-dark mb-2 d-none" style="max-height: 160px; width: 100%; object-fit: cover;" autoplay playsinline></video>
                                <canvas id="canvas-foto-paciente" class="img-fluid rounded border d-none" style="max-height: 160px;"></canvas>
                                <div id="foto-paciente-preview-placeholder" class="p-3 bg-white rounded border text-muted small text-center">
                                    <i class="fa-solid fa-user-shield fs-2 d-block mb-1 text-secondary"></i>
                                    Sin foto capturada
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 fw-bold" id="btnIniciarCamaraPaciente">
                                    <i class="fa-solid fa-video me-1"></i> Activar Cámara Web
                                </button>
                                <button type="button" class="btn btn-warning btn-sm w-100 mb-2 fw-bold text-dark d-none" id="btnTomarFotoPaciente">
                                    <i class="fa-solid fa-camera me-1"></i> 📸 Capturar Foto
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2 d-none" id="btnRepetirFotoPaciente">
                                    <i class="fa-solid fa-rotate-right me-1"></i> Repetir Foto
                                </button>

                                <div class="mt-2 border-top pt-2">
                                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fa-solid fa-upload me-1"></i> O seleccionar foto desde archivo:</label>
                                    <input type="file" name="foto_paciente_file" class="form-control form-control-sm" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-semibold">Firme en el recuadro inferior (Tableta digitalizadora / Pantalla táctil / Mouse):</label>
                    
                    <div class="signature-container text-center mb-2">
                        <canvas id="canvas-firma" class="signature-pad"></canvas>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnLimpiarFirma">
                            <i class="fa-solid fa-eraser me-1"></i> Borrar / Limpiar Firma
                        </button>
                        <span class="small text-muted"><i class="fa-solid fa-tablet-screen-button me-1"></i> Compatible con Wacom, Topaz, iPad y pantallas táctiles</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-circle-check me-1"></i> Confirmar Entrega y Generar Acta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let canvas, ctx, isDrawing = false;
let videoStreamPaciente = null;

document.addEventListener('DOMContentLoaded', () => {
    canvas = document.getElementById('canvas-firma');
    ctx = canvas.getContext('2d');

    // Ajustar resolución del canvas
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    ctx.strokeStyle = "#000000";
    ctx.lineWidth = 3;
    ctx.lineCap = "round";

    // Eventos Mouse
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    // Eventos Touch (Tabletas táctiles / Celulares / iPad)
    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); startDrawing(e.touches[0]); });
    canvas.addEventListener('touchmove', (e) => { e.preventDefault(); draw(e.touches[0]); });
    canvas.addEventListener('touchend', stopDrawing);

    document.getElementById('btnLimpiarFirma').addEventListener('click', limpiarCanvas);

    // Lógica de Cámara Web Paciente
    document.getElementById('btnIniciarCamaraPaciente').addEventListener('click', iniciarCamaraPaciente);
    document.getElementById('btnTomarFotoPaciente').addEventListener('click', tomarFotoPaciente);
    document.getElementById('btnRepetirFotoPaciente').addEventListener('click', repetirFotoPaciente);

    document.getElementById('formEntregaFirma').addEventListener('submit', (e) => {
        if (isCanvasBlank(canvas)) {
            alert('Por favor solicite al paciente realizar la firma en la pantalla antes de finalizar.');
            e.preventDefault();
            return;
        }
        document.getElementById('firma_base64').value = canvas.toDataURL('image/png');
        detenerCamaraPaciente();
    });

    // Delegación de eventos para el botón de entrega
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-abrir-firma');
        if (btn) {
            const id = btn.getAttribute('data-id');
            const ticket = btn.getAttribute('data-ticket');
            const paciente = btn.getAttribute('data-paciente');
            const doc = btn.getAttribute('data-doc');
            const faltantes = btn.getAttribute('data-faltantes');

            abrirModalFirma(id, ticket, paciente, doc, faltantes);
        }
    });
});

async function iniciarCamaraPaciente() {
    try {
        videoStreamPaciente = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
        const video = document.getElementById('video-camara-paciente');
        video.srcObject = videoStreamPaciente;
        video.classList.remove('d-none');
        document.getElementById('foto-paciente-preview-placeholder').classList.add('d-none');
        document.getElementById('canvas-foto-paciente').classList.add('d-none');
        document.getElementById('btnIniciarCamaraPaciente').classList.add('d-none');
        document.getElementById('btnTomarFotoPaciente').classList.remove('d-none');
    } catch (e) {
        alert("No se pudo acceder a la cámara web. Verifique los permisos en el navegador.");
    }
}

function tomarFotoPaciente() {
    const video = document.getElementById('video-camara-paciente');
    const canvasFoto = document.getElementById('canvas-foto-paciente');
    const ctxFoto = canvasFoto.getContext('2d');

    canvasFoto.width = video.videoWidth || 640;
    canvasFoto.height = video.videoHeight || 480;
    ctxFoto.drawImage(video, 0, 0, canvasFoto.width, canvasFoto.height);

    const dataUrl = canvasFoto.toDataURL('image/jpeg', 0.85);
    document.getElementById('foto_paciente_base64').value = dataUrl;

    canvasFoto.classList.remove('d-none');
    video.classList.add('d-none');
    document.getElementById('btnTomarFotoPaciente').classList.add('d-none');
    document.getElementById('btnRepetirFotoPaciente').classList.remove('d-none');

    detenerCamaraPaciente();
}

function repetirFotoPaciente() {
    document.getElementById('foto_paciente_base64').value = '';
    document.getElementById('btnRepetirFotoPaciente').classList.add('d-none');
    iniciarCamaraPaciente();
}

function detenerCamaraPaciente() {
    if (videoStreamPaciente) {
        videoStreamPaciente.getTracks().forEach(track => track.stop());
        videoStreamPaciente = null;
    }
}

function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
}

function startDrawing(e) {
    isDrawing = true;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
}

function draw(e) {
    if (!isDrawing) return;
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
}

function stopDrawing() {
    isDrawing = false;
}

function limpiarCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function isCanvasBlank(c) {
    const blank = document.createElement('canvas');
    blank.width = c.width;
    blank.height = c.height;
    return c.toDataURL() === blank.toDataURL();
}

function abrirModalFirma(id, ticket, paciente, doc, faltantes) {
    document.getElementById('entrega_ingreso_id').value = id;
    document.getElementById('entrega_ticket_txt').innerText = ticket;
    document.getElementById('entrega_paciente_txt').innerText = paciente;
    document.getElementById('entrega_doc_txt').innerText = doc;

    const divFaltantes = document.getElementById('container-novedades-entrega');
    const txtFaltantes = document.getElementById('entrega_faltantes_txt');
    if (faltantes && faltantes.trim()) {
        divFaltantes.classList.remove('d-none');
        txtFaltantes.innerText = faltantes;
    } else {
        divFaltantes.classList.add('d-none');
        txtFaltantes.innerText = '';
    }

    limpiarCanvas();
    const modal = new bootstrap.Modal(document.getElementById('modalFirmaDigital'));
    modal.show();

    // Reajustar tamaño del canvas cuando se renderiza el modal
    setTimeout(() => {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        ctx.strokeStyle = "#000000";
        ctx.lineWidth = 3;
        ctx.lineCap = "round";
    }, 300);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
