@extends('layouts.app')

@section('titulo', 'Facturación Consolidada - '.config('app.name'))

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-layer-group text-primary me-2"></i>Facturación Consolidada Multiusuario EPS
            </h3>
            <p class="text-muted small mb-0">Cierre periódico de cuentas médicas y emisión de factura global para contratos por Capitación y PGP</p>
        </div>
        <div>
            <a href="{{ route('facturacion.facturas') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Facturas
            </a>
        </div>
    </div>

    <!-- Filtro de Periodo & EPS -->
    <div class="card card-glass mb-4 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="facturacion_consolidada">

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">EPS / Administradora Capitada:</label>
                    <select name="eps_nombre" class="form-select form-select-sm">
                        <option value="">-- Todas las EPS con Entregas --</option>
                        
@foreach ($contratos as $c)

                            <option value="{{ $c['eps_nombre'] }}" {{ $epsFiltro === $c['eps_nombre'] ? 'selected' : '' }}>
                                {{ $c['eps_nombre'] }} ({{ $c['modalidad_pago'] }})
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Desde:</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Hasta:</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados Pendientes de Consolidar -->
    <div class="card card-glass shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-clipboard-list me-2 text-success"></i>Bolsa de Actas de Dispensación Listas para Cierre
            </h6>
            <span class="badge bg-success rounded-pill px-3 py-2 fw-bold">
                {{ count($pendientes) }} EPS con entregas pendientes
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">EPS / Entidad</th>
                            <th class="text-center">Total Pacientes</th>
                            <th class="text-center">Órdenes / Fórmulas</th>
                            <th class="text-center">Medicamentos Dispensados</th>
                            <th>Rango de Entrega</th>
                            <th class="text-end">Subtotal Liquidado</th>
                            <th class="text-center pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($pendientes))

                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-check-double fa-3x mb-3 text-success opacity-50"></i>
                                    <h6>Todas las actas del periodo ya han sido consolidadas o no hay registros en este rango de fechas.</h6>
                                </td>
                            </tr>
                        
@else

                            @foreach ($pendientes as $row)

                                <tr>
                                    <td class="ps-3">
                                        <strong class="text-dark fs-6 d-block">
                                            {{ $row['eps_nombre'] }}
                                            @if (!empty($row['codigo_eapb']) && $row['codigo_eapb'] !== $row['eps_nombre'])

                                                <span class="text-muted fw-normal small">({{ $row['codigo_eapb'] }})</span>
                                            
@endif

                                        </strong>
                                        
@php
$mod = strtoupper($row['modalidad_pago'] ?? 'CAPITACION');
                                            $badgeClass = ($mod === 'EVENTO') ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : (($mod === 'PGP') ? 'bg-secondary-subtle text-secondary-emphasis border' : 'bg-info-subtle text-info-emphasis border border-info-subtle');
@endphp

                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge {{ $badgeClass }}" style="font-size: 0.72rem;">
                                                Modalidad {{ $mod }}
                                            </span>
                                            
@if (!empty($row['numero_contrato']) && $row['numero_contrato'] !== 'SIN-CONTRATO')

                                                <span class="text-muted font-mono" style="font-size: 0.70rem;">• {{ $row['numero_contrato'] }}</span>
                                            
@endif

                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-6 fw-bold">
                                            <i class="fa-solid fa-users text-primary me-1"></i> {{ number_format($row['total_pacientes']) }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold font-mono">
                                        {{ number_format($row['total_ordenes']) }}
                                    </td>
                                    <td class="text-center fw-semibold font-mono text-secondary">
                                        <i class="fa-solid fa-pills me-1"></i> {{ number_format($row['total_medicamentos']) }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $row['fecha_primera_entrega'] }} al {{ $row['fecha_ultima_entrega'] }}
                                    </td>
                                    <td class="text-end font-mono fw-bold fs-6 text-success">
                                        ${{ number_format($row['subtotal_estimado'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center pe-3">
                                        
@if ($mod === 'EVENTO')

                                            <button type="button" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm"
                                                    onclick="confirmarConsolidacion('{{ $row['eps_nombre'] }}', '{{ $fechaDesde }}', '{{ $fechaHasta }}', {{ $row['total_pacientes'] }}, {{ $row['subtotal_estimado'] }}, 'EVENTO')">
                                                <i class="fa-solid fa-file-invoice me-1"></i> Facturar por Evento
                                            </button>
                                        
@php
elseif ($mod === 'PGP'):
@endphp

                                            <button type="button" class="btn btn-sm btn-dark fw-bold px-3 shadow-sm"
                                                    onclick="confirmarConsolidacion('{{ $row['eps_nombre'] }}', '{{ $fechaDesde }}', '{{ $fechaHasta }}', {{ $row['total_pacientes'] }}, {{ $row['subtotal_estimado'] }}, 'PGP')">
                                                <i class="fa-solid fa-file-shield me-1"></i> Facturar PGP
                                            </button>
                                        
@else

                                            <button type="button" class="btn btn-sm btn-success fw-bold px-3 shadow-sm"
                                                    onclick="confirmarConsolidacion('{{ $row['eps_nombre'] }}', '{{ $fechaDesde }}', '{{ $fechaHasta }}', {{ $row['total_pacientes'] }}, {{ $row['subtotal_estimado'] }}, 'CAPITACION')">
                                                <i class="fa-solid fa-file-circle-check me-1"></i> Emitir Factura Multiusuario
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

<!-- Modal de Confirmación de Consolidación -->
<div class="modal fade" id="modalConsolidar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalTituloConsolidar">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>Emitir Factura
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3" id="modalMensajeTipo">Está a punto de generar la <strong>Factura Electrónica</strong> ante la DIAN para la siguiente entidad:</p>
                <div class="alert alert-light border p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-primary fs-6" id="modalEpsNombre">--</div>
                        <span class="badge bg-secondary" id="modalBadgeModalidad">--</span>
                    </div>
                    <div class="small text-muted mt-1" id="modalPeriodo">--</div>
                    <div class="small text-muted" id="modalResumenPacientes">--</div>
                    <div class="fw-bold text-success fs-5 mt-2" id="modalTotalLiquidado">$0</div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="chkGenerarRips" checked disabled>
                    <label class="form-check-label small fw-bold text-dark" for="chkGenerarRips">
                        Generar paquete de soporte RIPS JSON (Resolución 2275 de 2024 - Módulo AM)
                    </label>
                </div>
                <div class="small text-muted">
                    <i class="fa-solid fa-circle-info text-info me-1"></i>
                    Todas las actas de dispensación correspondientes quedarán marcadas como facturadas.
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-4" id="btnEjecutarConsolidacion" onclick="ejecutarConsolidacion()">
                    <i class="fa-solid fa-bolt me-1"></i> Confirmar y Emitir DIAN
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let epsSeleccionada = '';
let fDesdeSel = '';
let fHastaSel = '';

function confirmarConsolidacion(eps, desde, hasta, pacientes, total, modalidad) {
    epsSeleccionada = eps;
    fDesdeSel = desde;
    fHastaSel = hasta;
    modalidad = modalidad || 'CAPITACION';

    const titEl = document.getElementById('modalTituloConsolidar');
    const msgEl = document.getElementById('modalMensajeTipo');
    const badgeEl = document.getElementById('modalBadgeModalidad');

    if (modalidad === 'EVENTO') {
        titEl.innerHTML = '<i class="fa-solid fa-file-invoice me-2"></i>Emitir Factura por Evento (Corte Periódico)';
        msgEl.innerHTML = 'Está a punto de generar la <strong>Factura Electrónica por Modalidad de Evento (Consolidado de Actas)</strong> ante la DIAN:';
        badgeEl.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        badgeEl.textContent = 'Modalidad EVENTO';
    } else if (modalidad === 'PGP') {
        titEl.innerHTML = '<i class="fa-solid fa-file-shield me-2"></i>Emitir Factura PGP (Pago Global Prospectivo)';
        msgEl.innerHTML = 'Está a punto de generar la <strong>Factura Electrónica Consolidada PGP</strong> ante la DIAN:';
        badgeEl.className = 'badge bg-secondary-subtle text-secondary-emphasis border';
        badgeEl.textContent = 'Modalidad PGP';
    } else {
        titEl.innerHTML = '<i class="fa-solid fa-file-circle-check me-2"></i>Emitir Factura Consolidada Multiusuario';
        msgEl.innerHTML = 'Está a punto de generar la <strong>Factura Electrónica Multiusuario (Capitación)</strong> ante la DIAN:';
        badgeEl.className = 'badge bg-info-subtle text-info-emphasis border border-info-subtle';
        badgeEl.textContent = 'Modalidad CAPITACIÓN';
    }

    document.getElementById('modalEpsNombre').textContent = eps;
    document.getElementById('modalPeriodo').textContent = `Periodo de Corte: ${desde} al ${hasta}`;
    document.getElementById('modalResumenPacientes').textContent = `Total Pacientes Atendidos: ${pacientes}`;
    document.getElementById('modalTotalLiquidado').textContent = `Total a Facturar: $${Number(total).toLocaleString()}`;

    const modal = new bootstrap.Modal(document.getElementById('modalConsolidar'));
    modal.show();
}

function ejecutarConsolidacion() {
    const btn = document.getElementById('btnEjecutarConsolidacion');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Emitiendo Factura DIAN...';

    const formData = new FormData();
    formData.append('eps_nombre', epsSeleccionada);
    formData.append('fecha_desde', fDesdeSel);
    formData.append('fecha_hasta', fHastaSel);

    fetch('api/facturacion.php?action=emitir_consolidada', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Confirmar y Emitir DIAN';
        const modalCons = bootstrap.Modal.getInstance(document.getElementById('modalConsolidar'));
        if (modalCons) modalCons.hide();

        if (data.success) {
            document.getElementById('exitoFacturaNum').textContent = data.numero_factura || 'FE-EMITIDA';
            document.getElementById('exitoTotalUsuarios').textContent = data.total_usuarios || '1';
            document.getElementById('exitoTotalNeto').textContent = '$' + Number(data.total_neto || 0).toLocaleString();
            document.getElementById('exitoCufe').textContent = data.cufe || '--';

            const btnVerFact = document.getElementById('btnVerFacturaEmitida');
            btnVerFact.href = `{{ route('facturacion.imprimir_factura') }}?id=${data.factura_id}`;

            const btnDescRips = document.getElementById('btnDescargarRipsEmitido');
            btnDescRips.href = `api/facturacion.php?action=descargar_rips_json&id=${data.factura_id}`;

            const modalExito = new bootstrap.Modal(document.getElementById('modalExitoFactura'));
            modalExito.show();
        } else {
            if (typeof window.modalAlert === 'function') {
                window.modalAlert(`Error al facturar: ${data.message}`);
            } else {
                alert(`Error: ${data.message}`);
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Confirmar y Emitir DIAN';
        if (typeof window.modalAlert === 'function') {
            window.modalAlert(`Error de comunicación: ${err.message}`);
        } else {
            alert(`Error de comunicación: ${err.message}`);
        }
    });
}
</script>

<!-- Modal de Éxito de Emisión de Factura & RIPS -->
<div class="modal fade" id="modalExitoFactura" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <span class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success fs-1">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-1">¡Factura Electrónica Emitida!</h4>
                <p class="text-muted small mb-3">Documento procesado exitosamente por el Motor de Facturación DIAN (Ambiente de Pruebas FEV-RIPS).</p>

                <div class="alert alert-light border rounded-3 text-start p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Número de Factura:</span>
                        <span class="badge bg-primary fs-6 font-mono" id="exitoFacturaNum">SETP0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Pacientes Consolidados:</span>
                        <span class="fw-bold text-dark" id="exitoTotalUsuarios">1</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Valor Total Liquidado:</span>
                        <span class="fw-bold text-success fs-6" id="exitoTotalNeto">$0</span>
                    </div>
                    <div class="border-top pt-2 mt-2">
                        <span class="text-muted small d-block">CUFE DIAN:</span>
                        <div class="font-mono text-break text-secondary" style="font-size: 0.68rem;" id="exitoCufe">--</div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-2">
                    <a href="#" target="_blank" class="btn btn-primary fw-bold py-2 rounded-pill shadow-sm" id="btnVerFacturaEmitida">
                        <i class="fa-solid fa-file-invoice me-1"></i> Ver Factura Electrónica (PDF)
                    </a>
                    <a href="#" target="_blank" class="btn btn-success fw-bold py-2 rounded-pill shadow-sm" id="btnDescargarRipsEmitido">
                        <i class="fa-solid fa-file-code me-1"></i> Descargar RIPS JSON (Res. 2275)
                    </a>
                    <a href="{{ route('facturacion.facturas') }}" class="btn btn-outline-secondary fw-semibold py-2 rounded-pill">
                        <i class="fa-solid fa-list-check me-1"></i> Ir al Listado General de Facturas
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none" onclick="window.location.reload()">
                    Cerrar y refrescar tabla
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
