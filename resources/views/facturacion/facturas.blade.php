@extends('layouts.app')

@section('titulo', 'Facturas - '.config('app.name'))

@section('content')
<div class="container-fluid py-4">
    <!-- Encabezado de Sección -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Facturación Electrónica & RIPS
            </h3>
            <p class="text-muted small mb-0">Gestión de facturas individuales, consolidadas multiusuario EPS y soporte Res. 2275 FEV-RIPS</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('facturacion.consolidada') }}" class="btn btn-primary fw-bold shadow-sm">
                <i class="fa-solid fa-layer-group me-1"></i> Facturación Consolidada EPS
            </a>
            <a href="{{ route('facturacion.configuracion') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="fa-solid fa-sliders me-1"></i> Proveedor DIAN
            </a>
            <a href="{{ route('facturacion.contratos') }}" class="btn btn-outline-info fw-semibold">
                <i class="fa-solid fa-handshake me-1"></i> Contratos EPS
            </a>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card card-glass mb-4 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="facturas">

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Tipo de Factura:</label>
                    <select name="tipo_factura" class="form-select form-select-sm">
                        <option value="">-- Todos los Tipos --</option>
                        <option value="INDIVIDUAL" {{ $filtros['tipo_factura'] === 'INDIVIDUAL' ? 'selected' : '' }}>Factura Individual (Evento)</option>
                        <option value="MULTIUSUARIO_CAPITADA" {{ $filtros['tipo_factura'] === 'MULTIUSUARIO_CAPITADA' ? 'selected' : '' }}>Factura Multiusuario (Capitación)</option>
                        <option value="ALTO_COSTO_MIPRES" {{ $filtros['tipo_factura'] === 'ALTO_COSTO_MIPRES' ? 'selected' : '' }}>Alto Costo / MIPRES</option>
                        <option value="PARTICULAR" {{ $filtros['tipo_factura'] === 'PARTICULAR' ? 'selected' : '' }}>Particular / Privado</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">EPS / Entidad:</label>
                    <input type="text" name="eps_nombre" class="form-control form-control-sm" placeholder="Ej: Savia Salud..." value="{{ $filtros['eps_nombre'] }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Desde:</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $filtros['fecha_desde'] }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Hasta:</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $filtros['fecha_hasta'] }}">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                        <i class="fa-solid fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('facturacion.facturas') }}" class="btn btn-sm btn-light border" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Grilla de Facturas -->
    <div class="card card-glass shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check me-2 text-info"></i>Listado de Documentos Electrónicos Emitidos
            </h6>
            <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold fs-7">
                {{ count($facturas) }} Facturas registradas
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">No. Factura</th>
                            <th>Tipo</th>
                            <th>EPS / Adquiriente</th>
                            <th>Paciente / Periodo</th>
                            <th>Fecha Emisión</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Copago / Cuota</th>
                            <th class="text-end">Total Neto</th>
                            <th class="text-center">Estado DIAN</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@if (empty($facturas))

                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6>No se encontraron facturas con los criterios seleccionados.</h6>
                                </td>
                            </tr>
                        
@else

                            @foreach ($facturas as $f)

                                <tr>
                                    <td class="ps-3 fw-bold text-dark font-mono">
                                        {{ $f['prefijo'] . $f['numero_factura'] }}
                                    </td>
                                    <td>
                                        
@if ($f['tipo_factura'] === 'INDIVIDUAL')

                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-semibold">Individual Evento</span>
                                        
@php
elseif ($f['tipo_factura'] === 'CONSOLIDADA_EVENTO'):
@endphp

                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold">Consolidada Evento</span>
                                        
@php
elseif ($f['tipo_factura'] === 'MULTIUSUARIO_CAPITADA'):
@endphp

                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">Multiusuario Capitación</span>
                                        
@php
elseif ($f['tipo_factura'] === 'CONSOLIDADA_PGP'):
@endphp

                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border fw-semibold">Consolidada PGP</span>
                                        
@php
elseif ($f['tipo_factura'] === 'ALTO_COSTO_MIPRES'):
@endphp

                                            <span class="badge bg-danger fw-semibold">Alto Costo MIPRES</span>
                                        
@else

                                            <span class="badge bg-secondary fw-semibold">{{ $f['tipo_factura'] }}</span>
                                        
@endif

                                    </td>
                                    <td class="fw-semibold text-dark">
                                        {{ $f['eps_nombre'] ?: 'Particular' }}
                                    </td>
                                    <td>
                                        
@if (!empty($f['paciente_nombre_completo']))

                                            <span class="fw-bold text-dark d-block">{{ $f['paciente_nombre_completo'] }}</span>
                                            <small class="text-muted">CC: {{ $f['paciente_documento'] }}</small>
                                        
@else

                                            <span class="badge bg-light text-dark border">
                                                Corte: {{ $f['periodo_corte_desde'] }} al {{ $f['periodo_corte_hasta'] }}
                                            </span>
                                        
@endif

                                    </td>
                                    <td>
                                        {{ date('Y-m-d H:i', strtotime($f['fecha_emision'])) }}
                                    </td>
                                    <td class="text-end font-mono">
                                        ${{ number_format($f['subtotal'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-mono text-danger">
                                        ${{ number_format($f['total_copago_cuota'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-mono fw-bold text-success fs-6">
                                        ${{ number_format($f['total_neto'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        
@if ($f['estado_factura'] === 'VALIDADA_DIAN')

                                            <span class="badge bg-success bg-opacity-75 text-white px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Validada DIAN</span>
                                        
@php
elseif ($f['estado_factura'] === 'ENVIADA_DIAN'):
@endphp

                                            <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-paper-plane me-1"></i> Enviada</span>
                                        
@php
elseif ($f['estado_factura'] === 'RECHAZADA_DIAN'):
@endphp

                                            <span class="badge bg-danger text-white px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i> Rechazada</span>
                                        
@else

                                            <span class="badge bg-secondary text-white px-2 py-1">Borrador</span>
                                        
@endif

                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="verDetalleFactura({{ $f['id'] }})" title="Ver Detalle & CUFE">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <a href="{{ route('facturacion.imprimir_factura') }}?id={{ $f['id'] }}" target="_blank" class="btn btn-outline-dark" title="Imprimir PDF Factura">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            <a href="api/facturacion.php?action=descargar_rips_json&id={{ $f['id'] }}" target="_blank" class="btn btn-outline-success" title="Descargar RIPS JSON (Res. 2275)">
                                                <i class="fa-solid fa-code"></i>
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
</div>

<!-- Modal Detalle de Factura Electrónica -->
<div class="modal fade" id="modalDetalleFactura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalFacturaTitulo">
                    <i class="fa-solid fa-receipt text-info me-2"></i>Factura Electrónica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="modalFacturaBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnImprimirModalFactura" target="_blank" class="btn btn-primary btn-sm fw-bold">
                    <i class="fa-solid fa-print me-1"></i> Imprimir Factura PDF
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalleFactura(facturaId) {
    const modalEl = document.getElementById('modalDetalleFactura');
    const modalBody = document.getElementById('modalFacturaBody');
    const btnImp = document.getElementById('btnImprimirModalFactura');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    btnImp.href = `{{ route('facturacion.imprimir_factura') }}?id=${facturaId}`;

    fetch(`api/facturacion.php?action=detalle_factura&id=${facturaId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.factura) {
                modalBody.innerHTML = `<div class="alert alert-danger">No se pudo cargar el detalle de la factura.</div>`;
                return;
            }

            const f = data.factura;
            document.getElementById('modalFacturaTitulo').innerHTML = `
                <i class="fa-solid fa-receipt text-info me-2"></i>Factura ${f.prefijo}${f.numero_factura}
                <span class="badge bg-success bg-opacity-75 ms-2 fs-7">${f.estado_factura}</span>
            `;

            let itemsHtml = '';
            (f.items || []).forEach((it, idx) => {
                itemsHtml += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>
                            <strong class="text-dark">${it.nombre_medicamento}</strong>
                            <div class="small text-muted">CUMS: ${it.codigo_cums || 'N/A'} | Lote: ${it.lote_numero || 'N/A'}</div>
                        </td>
                        <td class="text-center">${it.cantidad}</td>
                        <td class="text-end">$${Number(it.valor_unitario).toLocaleString()}</td>
                        <td class="text-end fw-bold">$${Number(it.valor_total).toLocaleString()}</td>
                    </tr>
                `;
            });

            modalBody.innerHTML = `
                <div class="row g-3 mb-3">
                    <div class="col-md-6 border-end">
                        <span class="text-muted small text-uppercase fw-bold">Adquiriente / Paciente:</span>
                        <div class="fw-bold text-dark fs-6">${f.paciente_nombre_completo || f.eps_nombre}</div>
                        <div class="small text-muted">Documento: ${f.paciente_documento || f.codigo_eapb || 'N/A'}</div>
                        <div class="small text-muted">EPS: ${f.eps_nombre || 'Particular'}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small text-uppercase fw-bold">Datos de Emisión DIAN:</span>
                        <div class="small"><strong>Fecha:</strong> ${f.fecha_emision}</div>
                        <div class="small"><strong>CUFE:</strong> <span class="font-mono text-break" style="font-size: 0.72rem;">${f.cufe || 'N/A'}</span></div>
                        <div class="small"><strong>TrackID:</strong> <span class="font-mono text-muted">${f.track_id_proveedor || 'N/A'}</span></div>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>#</th>
                                <th>Medicamento / Tecnología</th>
                                <th class="text-center">Cant</th>
                                <th class="text-end">V. Unit</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.82rem;">
                            ${itemsHtml}
                        </tbody>
                        <tfoot class="table-light fw-bold" style="font-size: 0.85rem;">
                            <tr>
                                <td colspan="4" class="text-end">Subtotal:</td>
                                <td class="text-end font-mono">$${Number(f.subtotal).toLocaleString()}</td>
                            </tr>
                            ${f.total_copago_cuota > 0 ? `
                            <tr>
                                <td colspan="4" class="text-end text-danger">Cuota Moderadora / Copago Descontado:</td>
                                <td class="text-end font-mono text-danger">-$${Number(f.total_copago_cuota).toLocaleString()}</td>
                            </tr>` : ''}
                            <tr class="fs-6 table-primary">
                                <td colspan="4" class="text-end text-primary">TOTAL NETO:</td>
                                <td class="text-end font-mono text-primary">$${Number(f.total_neto).toLocaleString()}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-light border small text-muted mb-0">
                    <i class="fa-solid fa-shield-check text-success me-1"></i>
                    <strong>Validación DIAN:</strong> ${f.mensaje_respuesta_dian || 'Documento aceptado y firmado electrónicamente.'}
                </div>
            `;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Error de comunicación: ${err.message}</div>`;
        });
}
</script>
@endsection
