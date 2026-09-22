@extends('layouts.app')

@section('titulo', 'Traslados - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    
@if (!empty($mensaje))

        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ $mensaje }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if (!empty($error))

        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-truck-ramp-box fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Traslados entre Bodegas & Sedes</h3>
                    <p class="text-muted small mb-0 mt-1">Transferencias controladas de lotes desde Bodega Central hacia Bodegas de Sedes y Ventanillas.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end">
            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm" onclick="abrirModalNuevoTraslado()">
                <i class="fa-solid fa-plus me-1"></i> Solicitar Traslado
            </button>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Stock
            </a>
        </div>
    </div>

    <!-- LISTA DE TRASLADOS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-5"><i class="fa-solid fa-arrows-split-up-and-left text-primary me-2"></i> Registro de Transferencias</h5>
            <span class="badge bg-primary rounded-pill px-3 py-1">{{ count($traslados) }} Traslados</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">No. Traslado</th>
                        <th>Bodega Origen</th>
                        <th>Bodega Destino</th>
                        <th>Fecha Despacho</th>
                        <th>Estado</th>
                        <th>Responsables</th>
                        <th class="text-end pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($traslados))

                        <tr><td colspan="7" class="text-center py-5 text-muted">No se han registrado traslados entre bodegas.</td></tr>
                    
@else

                        @foreach ($traslados as $t)
@php
$enTransito = ($t['estado'] === 'EN_TRANSITO');
$recibido = ($t['estado'] === 'RECIBIDO');
@endphp

                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-dark text-white font-monospace fs-6 px-3 py-1">{{ $t['numero_traslado'] }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $t['bodega_origen_nombre'] }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ $t['bodega_destino_nombre'] }}</div>
                            </td>
                            <td>{{ $t['fecha_despacho'] ? date('d/m/Y h:i A', strtotime($t['fecha_despacho'])) : '--' }}</td>
                            <td>
                                
@if ($enTransito)

                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-truck-fast me-1"></i> En Tránsito</span>
                                
@php
elseif ($recibido):
@endphp

                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-check-double me-1"></i> Recibido Conforme</span>
                                
@else

                                    <span class="badge bg-secondary px-3 py-2 rounded-pill">{{ $t['estado'] }}</span>
                                
@endif

                            </td>
                            <td>
                                <small class="text-muted d-block">Despachado por: <strong>{{ $t['solicitado_por_nombre'] ?: 'Admin' }}</strong></small>
                                
@if ($recibido && !empty($t['recibido_por_nombre']))

                                    <small class="text-success d-block">Recibido por: <strong>{{ $t['recibido_por_nombre'] }}</strong></small>
                                
@endif

                            </td>
                            <td class="text-end pe-4">
                                
@if ($enTransito)

                                    <form method="POST" action="{{ route('inventario.traslados') }}" style="display:inline;" onsubmit="return confirm('¿Confirmas la recepción física y conforme de este traslado?')">
@csrf
                                        <input type="hidden" name="action" value="recibir_traslado">
                                        <input type="hidden" name="traslado_id" value="{{ $t['id'] }}">
                                        <button type="submit" class="btn btn-sm btn-success fw-bold rounded-pill px-3 shadow-sm">
                                            <i class="fa-solid fa-box-open me-1"></i> Recibir & Acreditar
                                        </button>
                                    </form>
                                
@else

                                    <span class="text-muted small"><i class="fa-solid fa-circle-check text-success me-1"></i> Finalizado</span>
                                
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

<!-- MODAL NUEVO TRASLADO -->
<div class="modal fade" id="modalTraslado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form method="POST" action="{{ route('inventario.traslados') }}" id="formNuevoTraslado" onsubmit="prepararEnvioTraslado(event)">
@csrf
                <input type="hidden" name="action" value="crear_traslado">
                <input type="hidden" name="items_json" id="traslado_items_json">

                <div class="modal-header bg-primary text-white py-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck-ramp-box me-2"></i> Solicitar Traslado de Medicamentos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bodega Origen (Desde donde sale) *</label>
                            <select name="bodega_origen_id" id="bod_origen_select" class="form-select" required onchange="cargarLotesOrigen()">
                                <option value="">Seleccione Origen...</option>
                                
@foreach ($bodegas as $b)

                                    <option value="{{ $b['id'] }}">{{ $b['nombre_bodega'] }} ({{ $b['nombre_sede'] }})</option>
                                
@endforeach

                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bodega Destino (Hacia donde llega) *</label>
                            <select name="bodega_destino_id" class="form-select" required>
                                <option value="">Seleccione Destino...</option>
                                
@foreach ($bodegas as $b)

                                    <option value="{{ $b['id'] }}">{{ $b['nombre_bodega'] }} ({{ $b['nombre_sede'] }})</option>
                                
@endforeach

                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Ítems a Transferir</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill" onclick="agregarFilaTraslado()">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Lote
                        </button>
                    </div>

                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-bordered align-middle mb-0" id="tablaTraslado">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th style="width: 70%;">Lote / Medicamento Disponible en Origen</th>
                                    <th style="width: 20%;">Cantidad a Trasladar</th>
                                    <th style="width: 10%;" class="text-center"><i class="fa-solid fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyTraslado">
                                <!-- Filas -->
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Observaciones / Motivo del Traslado</label>
                        <input type="text" name="observaciones" class="form-control" placeholder="Ej: Reabastecimiento semanal de medicamentos de alta rotación.">
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 px-4 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Despachar Traslado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lotesOrigenDisponibles = [];

function abrirModalNuevoTraslado() {
    document.getElementById('tbodyTraslado').innerHTML = '';
    document.getElementById('bod_origen_select').selectedIndex = 0;
    new bootstrap.Modal(document.getElementById('modalTraslado')).show();
}

function cargarLotesOrigen() {
    const bodId = document.getElementById('bod_origen_select').value;
    const tbody = document.getElementById('tbodyTraslado');
    tbody.innerHTML = '';
    if (!bodId) return;

    fetch(`{{ route('api.bodegas.lotes') }}?bodega_id=${bodId}`)
        .then(r => r.json())
        .then(data => {
            lotesOrigenDisponibles = data.data || [];
            agregarFilaTraslado();
        })
        .catch(() => {
            agregarFilaTraslado();
        });
}

function agregarFilaTraslado() {
    const tbody = document.getElementById('tbodyTraslado');
    const tr = document.createElement('tr');

    let options = '<option value="">Seleccione Lote con Existencia...</option>';
    if (lotesOrigenDisponibles.length === 0) {
        options = '<option value="" disabled>(No hay lotes con existencias en esta bodega)</option>';
    } else {
        lotesOrigenDisponibles.forEach(l => {
            options += `<option value="${l.lote_id}" data-max="${l.cantidad_actual}">${escapeHtml(l.nombre_generico)} (${escapeHtml(l.concentracion)}) - Lote: ${escapeHtml(l.numero_lote)} [Stock: ${l.cantidad_actual} | Vence: ${l.fecha_vencimiento}]</option>`;
        });
    }

    tr.innerHTML = `
        <td>
            <select class="form-select form-select-sm sel-lote-traslado" required onchange="validarMaxStock(this)">
                ${options}
            </select>
        </td>
        <td>
            <input type="number" min="1" class="form-control form-control-sm inp-cant-traslado text-center fw-bold" required value="1">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
}

function validarMaxStock(select) {
    const opt = select.options[select.selectedIndex];
    const max = parseInt(opt.getAttribute('data-max') || '99999');
    const tr = select.closest('tr');
    const inp = tr.querySelector('.inp-cant-traslado');
    if (inp) {
        inp.max = max;
        if (parseInt(inp.value) > max) inp.value = max;
    }
}

function prepararEnvioTraslado(e) {
    const items = [];
    const filas = document.querySelectorAll('#tbodyTraslado tr');
    filas.forEach(f => {
        const loteId = f.querySelector('.sel-lote-traslado').value;
        const cant = f.querySelector('.inp-cant-traslado').value;
        if (loteId && cant) {
            items.push({ lote_id: loteId, cantidad: cant });
        }
    });
    if (items.length === 0) {
        e.preventDefault();
        alert('Por favor seleccione al menos un lote y cantidad a trasladar.');
        return false;
    }
    document.getElementById('traslado_items_json').value = JSON.stringify(items);
    return true;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>
@endsection
