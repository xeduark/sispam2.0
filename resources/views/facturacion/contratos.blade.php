@extends('layouts.app')

@section('titulo', 'Contratos EPS - '.config('app.name'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-handshake text-primary me-2"></i>Contratos & Modalidades de Facturación por EPS
            </h3>
            <p class="text-muted small mb-0">Parametrización de convenios con EPS (Capitación vs. Evento) y reglas de liquidación automática</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="abrirModalContrato()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Contrato EPS
            </button>
            <a href="{{ route('facturacion.facturas') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Facturas
            </a>
        </div>
    </div>

    <div class="card card-glass shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">EPS / Entidad</th>
                            <th>Código EAPB</th>
                            <th>No. Contrato</th>
                            <th>Modalidad de Pago</th>
                            <th>Regla de Facturación</th>
                            <th>Copago / Cuota Regulada</th>
                            <th>Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@foreach ($contratos as $c)

                            <tr>
                                <td class="ps-3 fw-bold text-dark fs-6">
                                    {{ $c['eps_nombre'] }}
                                </td>
                                <td class="font-mono text-muted">
                                    {{ $c['codigo_eapb'] ?: 'N/A' }}
                                </td>
                                <td class="font-mono">
                                    {{ $c['numero_contrato'] ?: 'N/A' }}
                                </td>
                                <td>
                                    
@if ($c['modalidad_pago'] === 'CAPITACION')

                                        <span class="badge bg-primary text-white"><i class="fa-solid fa-users me-1"></i> Capitación</span>
                                    
@php
elseif ($c['modalidad_pago'] === 'EVENTO'):
@endphp

                                        <span class="badge bg-info text-dark"><i class="fa-solid fa-receipt me-1"></i> Pago por Evento</span>
                                    
@php
elseif ($c['modalidad_pago'] === 'PGP'):
@endphp

                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-layer-group me-1"></i> PGP Farmacéutico</span>
                                    
@else

                                        <span class="badge bg-secondary text-white">Particular / Privado</span>
                                    
@endif

                                </td>
                                <td>
                                    
@if ($c['facturacion_automatica'] === 'CONSOLIDADA_FIN_MES')

                                        <span class="badge bg-light text-primary border border-primary border-opacity-50">Consolidada a Cierre de Periodo</span>
                                    
@else

                                        <span class="badge bg-light text-success border border-success border-opacity-50">Factura Inmediata por Usuario</span>
                                    
@endif

                                </td>
                                <td>
                                    {{ $c['aplica_copago_regulado'] ? '<span class="text-success small fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Sí aplica</span>' : '<span class="text-muted small">Exento</span>' }}
                                </td>
                                <td>
                                    {{ $c['estado_activo'] ? '<span class="badge bg-success bg-opacity-75">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' }}
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarContrato({{ json_encode($c) }})">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        
@endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Formulario Contrato -->
<div class="modal fade" id="modalFormContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalContratoTitulo">
                    <i class="fa-solid fa-handshake text-info me-2"></i>Contrato EPS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContratoEps" onsubmit="guardarContrato(event)">
                <input type="hidden" name="id" id="contratoId" value="0">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Nombre de la EPS / Entidad:</label>
                            <input type="text" name="eps_nombre" id="contratoEpsNombre" class="form-control text-uppercase" placeholder="Ej: SAVIA SALUD EPS" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Código EAPB (MinSalud):</label>
                            <input type="text" name="codigo_eapb" id="contratoCodigoEapb" class="form-control text-uppercase font-mono" placeholder="Ej: EPS040">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">No. de Contrato / Convenio:</label>
                            <input type="text" name="numero_contrato" id="contratoNumero" class="form-control text-uppercase font-mono" placeholder="Ej: CTO-2026-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Modalidad de Pago:</label>
                            <select name="modalidad_pago" id="contratoModalidad" class="form-select" required>
                                <option value="CAPITACION">Capitación (Población Asignada)</option>
                                <option value="EVENTO">Evento (Pago por Medicamento)</option>
                                <option value="PGP">PGP (Pago Global Prospectivo)</option>
                                <option value="PARTICULAR">Particular / Privado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Regla de Facturación:</label>
                            <select name="facturacion_automatica" id="contratoFacturacion" class="form-select" required>
                                <option value="CONSOLIDADA_FIN_MES">Consolidada (Cierre de Periodo)</option>
                                <option value="SI_INMEDIATA">Inmediata (Factura por Paciente)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="aplica_copago_regulado" value="1" id="contratoAplicaCopago" checked>
                                <label class="form-check-label small fw-bold text-dark" for="contratoAplicaCopago">
                                    Liquidar Cuotas Moderadoras y Copagos de Ley
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="estado_activo" value="1" id="contratoActivo" checked>
                                <label class="form-check-label small fw-bold text-dark" for="contratoActivo">
                                    Contrato Activo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4" id="btnGuardarContrato">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalContrato() {
    document.getElementById('contratoId').value = '0';
    document.getElementById('contratoEpsNombre').value = '';
    document.getElementById('contratoCodigoEapb').value = '';
    document.getElementById('contratoNumero').value = '';
    document.getElementById('contratoModalidad').value = 'CAPITACION';
    document.getElementById('contratoFacturacion').value = 'CONSOLIDADA_FIN_MES';
    document.getElementById('contratoAplicaCopago').checked = true;
    document.getElementById('contratoActivo').checked = true;
    document.getElementById('modalContratoTitulo').innerHTML = '<i class="fa-solid fa-handshake text-info me-2"></i>Nuevo Contrato EPS';

    const modal = new bootstrap.Modal(document.getElementById('modalFormContrato'));
    modal.show();
}

function editarContrato(c) {
    document.getElementById('contratoId').value = c.id;
    document.getElementById('contratoEpsNombre').value = c.eps_nombre;
    document.getElementById('contratoCodigoEapb').value = c.codigo_eapb || '';
    document.getElementById('contratoNumero').value = c.numero_contrato || '';
    document.getElementById('contratoModalidad').value = c.modalidad_pago;
    document.getElementById('contratoFacturacion').value = c.facturacion_automatica;
    document.getElementById('contratoAplicaCopago').checked = (c.aplica_copago_regulado == 1);
    document.getElementById('contratoActivo').checked = (c.estado_activo == 1);
    document.getElementById('modalContratoTitulo').innerHTML = `<i class="fa-solid fa-handshake text-info me-2"></i>Editar Contrato ${c.eps_nombre}`;

    const modal = new bootstrap.Modal(document.getElementById('modalFormContrato'));
    modal.show();
}

function guardarContrato(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarContrato');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';

    const formData = new FormData(document.getElementById('formContratoEps'));

    fetch('api/facturacion.php?action=guardar_contrato_eps', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Contrato';
        if (data.success) {
            window.modalAlert(data.message, () => { location.reload(); });
        } else {
            window.modalAlert(`Error: ${data.message}`);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Contrato';
        window.modalAlert(`Error de comunicación: ${err.message}`);
    });
}
</script>
@endsection
