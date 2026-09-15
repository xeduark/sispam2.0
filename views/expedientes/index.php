<?php
require_once __DIR__ . '/../../config/app.php';
check_auth();

require_once __DIR__ . '/../../models/Ingreso.php';

$ingresoModel = new Ingreso();
$num_doc = trim($_GET['num_doc'] ?? '');
$resultados = [];

if (!empty($num_doc)) {
    $resultados = $ingresoModel->buscarPorDocumento($num_doc);
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-folder-tree me-2"></i> Módulo de Consulta de Expedientes</h4>
        <p class="text-muted small">Busca el historial de ingresos, tiquetes, órdenes médicas adjuntas y reimprime las actas de entrega firmadas.</p>
    </div>
</div>

<div class="card card-glass p-4 shadow-sm border-0 mb-4">
    <form method="GET" action="" class="row g-3 align-items-center">
        <input type="hidden" name="page" value="expedientes">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Número de Documento del Paciente</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa-solid fa-id-card text-muted"></i></span>
                <input type="text" name="num_doc" class="form-control" placeholder="Ej: 88197902" value="<?= htmlspecialchars($num_doc) ?>" required autofocus>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar Histórico
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($num_doc)): ?>
    <?php if (!empty($resultados)): ?>
        <div class="card card-glass border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fa-solid fa-user me-2"></i> Paciente: <?= htmlspecialchars($resultados[0]['nombres'] . ' ' . $resultados[0]['apellidos']) ?> 
                    <span class="badge bg-secondary ms-2"><?= htmlspecialchars($resultados[0]['tipo_documento'] . ' ' . $resultados[0]['numero_documento']) ?></span>
                    <span class="badge bg-info text-dark ms-1"><?= htmlspecialchars($resultados[0]['eps_nombre']) ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tiquete</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                                <th>Orientador</th>
                                <th>Documentos & Firma</th>
                                <th class="text-end pe-3">Reimpresión & Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados as $ing): ?>
                            <?php $detalles = $ingresoModel->getById($ing['id']); ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary"><?= htmlspecialchars($ing['ticket_numero']) ?></td>
                                <td><?= date('d/m/Y h:i A', strtotime($ing['fecha_ingreso'])) ?></td>
                                <td>
                                    <?= get_estado_badge($ing['estado_tramite']) ?>
                                    <?= get_prioridad_badge($ing['prioridad'] ?? 'NORMAL') ?>
                                </td>
                                <td><?= htmlspecialchars($ing['orientador_nombre']) ?></td>
                                <td>
                                    <?php foreach ($detalles['documentos'] as $doc): ?>
                                        <a href="<?= $doc['ruta_archivo'] ?>" target="_blank" class="btn btn-sm btn-outline-dark me-1 mb-1" title="<?= htmlspecialchars($doc['tipo_documento']) ?>">
                                            <i class="fa-solid fa-file-pdf text-danger me-1"></i> <?= htmlspecialchars($doc['tipo_documento']) ?>
                                        </a>
                                    <?php endforeach; ?>

                                    <?php if (!empty($detalles['pdf_transcripcion_url'])): ?>
                                        <a href="<?= $detalles['pdf_transcripcion_url'] ?>" target="_blank" class="btn btn-sm btn-outline-success me-1 mb-1" title="PDF Transcrito">
                                            <i class="fa-solid fa-file-circle-check me-1"></i> Transcripción
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($detalles['firma_paciente_url'])): ?>
                                        <a href="<?= $detalles['firma_paciente_url'] ?>" target="_blank" class="btn btn-sm btn-outline-info me-1 mb-1" title="Firma Digital Paciente">
                                            <i class="fa-solid fa-signature me-1"></i> Ver Firma
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        <a href="index.php?page=imprimir_ticket&id=<?= $ing['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" title="Reimprimir Tiquete Térmico de Turno con datos del paciente y módulo">
                                            <i class="fa-solid fa-print me-1"></i> Reimprimir Ticket
                                        </a>
                                        
                                        <?php if ($ing['estado_tramite'] === 'ENTREGADO' || !empty($detalles['firma_paciente_url'])): ?>
                                            <a href="index.php?page=imprimir_acta&id=<?= $ing['id'] ?>" target="_blank" class="btn btn-sm btn-success fw-bold shadow-sm" title="Reimprimir Acta de Entrega Firmada">
                                                <i class="fa-solid fa-file-signature me-1"></i> Reimprimir Acta Firmada
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border align-self-center">Sin Entrega Final</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center p-4">
            <i class="fa-solid fa-triangle-exclamation fs-3 me-2"></i> No se encontraron registros de órdenes o ingresos para el documento <strong><?= htmlspecialchars($num_doc) ?></strong>.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
