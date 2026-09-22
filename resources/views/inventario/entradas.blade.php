@extends('layouts.app')

@section('titulo', 'Entradas - '.config('app.name'))

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
                    <i class="fa-solid fa-file-invoice fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Recepción Técnica & Entradas de Inventario</h3>
                    <p class="text-muted small mb-0 mt-1">Ingreso de facturas de proveedores con validación de Lotes, Vencimiento e Inspección Física (Res. 1403/2007).</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end">
            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm" onclick="abrirModalNuevaEntrada()">
                <i class="fa-solid fa-plus me-1"></i> Nueva Recepción Técnica
            </button>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Stock
            </a>
        </div>
    </div>

    <!-- HISTORIAL DE RECEPCIONES -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-5"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Historial de Recepciones Técnicas</h5>
            <span class="badge bg-primary rounded-pill px-3 py-1">{{ count($historialCompras) }} Registros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">No. Factura</th>
                        <th>Proveedor</th>
                        <th>Bodega Destino</th>
                        <th>Fecha Factura</th>
                        <th>Inspección Técnica</th>
                        <th class="text-center">Total Ítems</th>
                        <th class="text-end pe-4">Total Factura</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($historialCompras))

                        <tr><td colspan="7" class="text-center py-5 text-muted">No se han registrado entradas de compras aún.</td></tr>
                    
@else

                        @foreach ($historialCompras as $c)

                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-dark text-white font-monospace fs-6 px-3 py-1">{{ $c['numero_factura'] }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $c['proveedor_nombre'] }}</div>
                                <small class="text-muted">NIT: {{ $c['proveedor_nit'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-bold px-2 py-1">{{ $c['bodega_destino_nombre'] }}</span>
                            </td>
                            <td>{{ date('d/m/Y', strtotime($c['fecha_factura'])) }}</td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">
                                    <i class="fa-solid fa-circle-check me-1"></i> Conforme ({{ $c['temperatura_llegada'] ?: '20°C' }})
                                </span>
                            </td>
                            <td class="text-center fw-bold">{{ intval($c['total_items']) }}</td>
                            <td class="text-end pe-4 fw-bold text-dark">${{ number_format($c['total_factura'], 2) }}</td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL NUEVA RECEPCIÓN TÉCNICA -->
<div class="modal fade" id="modalEntradaCompra" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="overflow: visible;">
            <form method="POST" action="{{ route('inventario.entradas') }}" id="formNuevaEntrada" onsubmit="prepararEnvioCompra(event)">
@csrf
                <input type="hidden" name="action" value="registrar_compra">
                <input type="hidden" name="items_json" id="items_json">
                <input type="hidden" name="total_factura" id="total_factura_input" value="0">

                <div class="modal-header bg-primary text-white py-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice me-2"></i> Nueva Recepción Técnica & Entrada de Lotes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4" style="max-height: calc(88vh - 120px); overflow-y: auto; overflow-x: visible;">
                    <!-- DATOS CABECERA DE FACTURA -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Número de Factura *</label>
                            <input type="text" name="numero_factura" class="form-control form-control-sm" required placeholder="Ej: FACT-98745">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Proveedor Farmacéutico *</label>
                            <select name="proveedor_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione Proveedor...</option>
                                
@foreach ($proveedores as $pr)

                                    <option value="{{ $pr['id'] }}">{{ $pr['razon_social'] }} (NIT: {{ $pr['nit'] }})</option>
                                
@endforeach

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Bodega Destino *</label>
                            <select name="bodega_destino_id" class="form-select form-select-sm" required>
                                
@foreach ($bodegas as $b)

                                    <option value="{{ $b['id'] }}">{{ $b['nombre_bodega'] }} ({{ $b['nombre_sede'] }})</option>
                                
@endforeach

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha de Factura *</label>
                            <input type="date" name="fecha_factura" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- GRILLA DINÁMICA DE ÍTEMS Y LOTES -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Medicamentos a Ingresar con Lote y Vencimiento</h6>
                        <button type="button" class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm" onclick="agregarFilaMedicamento()">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Medicamento
                        </button>
                    </div>

                    <div class="mb-3 border rounded-3 p-0" style="overflow: visible;">
                        <table class="table table-bordered align-middle mb-0" id="tablaItemsEntrada" style="table-layout: fixed; width: 100%;">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th style="width: 44%;">Medicamento (Búsqueda en Catálogo Maestro)</th>
                                    <th style="width: 15%;">No. Lote</th>
                                    <th style="width: 14%;">Vencimiento</th>
                                    <th style="width: 10%;">Cantidad</th>
                                    <th style="width: 11%;">Costo Unit. ($)</th>
                                    <th style="width: 6%;" class="text-center"><i class="fa-solid fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyItemsEntrada">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-3 p-3 bg-light rounded-3 border">
                        <span class="fs-5 fw-bold text-dark">Total Factura:</span>
                        <span class="fs-4 fw-bold text-success font-monospace" id="lblTotalFactura">$0.00</span>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 px-4 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm"><i class="fa-solid fa-circle-check me-1"></i> Registrar Entrada de Inventario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.list-medicamentos-dropdown {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    min-width: 380px !important;
    max-height: 280px !important;
    overflow-y: auto !important;
    background: #ffffff !important;
    border: 1px solid #ced4da !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
    z-index: 99999 !important;
}
.dropdown-item-med {
    display: block;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    text-decoration: none;
    transition: background-color 0.15s ease-in-out;
}
.dropdown-item-med:hover, .dropdown-item-med:focus {
    background-color: #f0fdf4;
}
</style>

<script>
let searchTimeout = null;
window._medSearchResults = window._medSearchResults || {};

function abrirModalNuevaEntrada() {
    const tbody = document.getElementById('tbodyItemsEntrada');
    tbody.innerHTML = '';
    agregarFilaMedicamento();
    calcularTotalEntrada();
    new bootstrap.Modal(document.getElementById('modalEntradaCompra')).show();
}

function agregarFilaMedicamento() {
    const tbody = document.getElementById('tbodyItemsEntrada');
    const tr = document.createElement('tr');
    const rowId = 'row_' + Math.random().toString(36).substr(2, 9);
    tr.id = rowId;

    tr.innerHTML = `
        <td class="position-relative" style="overflow: visible;">
            <input type="hidden" class="sel-prod" required>
            
            <!-- Buscador Dinámico en Vivo -->
            <div class="med-search-box position-relative" style="overflow: visible;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control form-control-sm inp-busqueda-med" 
                           placeholder="Escriba SKU, CUMS, Genérico, Comercial o Lab..." 
                           oninput="buscarMedicamentoLive(this)" 
                           onfocus="buscarMedicamentoLive(this)" 
                           autocomplete="off">
                </div>
                <div class="list-medicamentos-dropdown" style="display: none;"></div>
            </div>

            <!-- Ficha Resumen del Medicamento Seleccionado -->
            <div class="selected-med-card p-2 bg-light border rounded-3 mt-1" style="display: none;">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div class="text-truncate w-100 pe-1">
                        <div class="fw-bold text-dark med-title text-truncate" style="line-height: 1.2; font-size: 0.85rem;"></div>
                        <div class="text-muted med-subtitle text-truncate" style="font-size: 0.74rem;"></div>
                        <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                            <span class="badge bg-dark text-white font-monospace med-sku" style="font-size: 0.68rem; padding: 1px 4px;"></span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 med-cums" style="font-size: 0.68rem; padding: 1px 4px;"></span>
                            <span class="badge bg-light text-dark border med-invima" style="font-size: 0.68rem; padding: 1px 4px;"></span>
                            <span class="badge bg-light text-secondary border med-lab text-truncate" style="max-width: 140px; font-size: 0.68rem; padding: 1px 4px;"></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 flex-shrink-0" onclick="cambiarMedicamento(this)" title="Cambiar medicamento">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm inp-lote font-monospace fw-bold" required placeholder="Ej: LT-260901">
        </td>
        <td>
            <input type="date" class="form-control form-control-sm inp-venc" required value="{{ date('Y-m-d', strtotime('+2 years')) }}">
        </td>
        <td>
            <input type="number" min="1" class="form-control form-control-sm inp-cant fw-bold text-center" required value="100" oninput="calcularTotalEntrada()">
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm inp-costo text-end" required value="0.00" oninput="calcularTotalEntrada()">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="eliminarFilaItem(this)">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calcularTotalEntrada();
}

function buscarMedicamentoLive(input) {
    const q = input.value.trim();
    const container = input.closest('td');
    const dropdown = container.querySelector('.list-medicamentos-dropdown');

    if (searchTimeout) clearTimeout(searchTimeout);

    if (q.length < 1) {
        dropdown.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando en catálogo maestro...</div>';
        dropdown.style.display = 'block';

        fetch('{{ route('api.medicamentos.buscar') }}?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(res => {
                if (res.status === 'ok' && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(p => {
                        window._medSearchResults[p.id] = p;
                        html += `
                            <div class="dropdown-item-med" style="cursor: pointer;" onclick="seleccionarMedicamentoById(this, '${p.id}')">
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">${escapeHtml(p.nombre_generico)}</div>
                                <div class="text-muted" style="font-size: 0.74rem;">
                                    ${escapeHtml((p.concentracion ? p.concentracion + ' • ' : '') + (p.forma_farmaceutica ? p.forma_farmaceutica + ' • ' : '') + (p.nombre_comercial || 'Genérico'))}
                                </div>
                                <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                    <span class="badge bg-dark text-white font-monospace" style="font-size: 0.68rem; padding: 1px 4px;">SKU: ${escapeHtml(p.codigo_sku)}</span>
                                    <span class="badge bg-light text-primary border" style="font-size: 0.68rem; padding: 1px 4px;">CUMS: ${escapeHtml(p.codigo_cums || 'N/A')}</span>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem; padding: 1px 4px;">INV: ${escapeHtml(p.registro_invima || 'N/A')}</span>
                                    <span class="badge bg-light text-dark border text-truncate" style="max-width: 140px; font-size: 0.68rem; padding: 1px 4px;">Lab: ${escapeHtml(p.fabricante_laboratorio || 'GENÉRICO')}</span>
                                    ${parseFloat(p.precio_referencia) > 0 ? `<span class="badge bg-success bg-opacity-10 text-success fw-bold ms-auto" style="font-size: 0.68rem; padding: 1px 4px;">$${parseFloat(p.precio_referencia).toFixed(2)}</span>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                } else {
                    dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-exclamation me-1"></i> No se encontraron medicamentos con esos términos.</div>';
                    dropdown.style.display = 'block';
                }
            })
            .catch(err => {
                dropdown.innerHTML = '<div class="p-3 text-center text-danger small">Error en la búsqueda.</div>';
                dropdown.style.display = 'block';
            });
    }, 200);
}

function seleccionarMedicamentoById(element, prodId) {
    const prod = window._medSearchResults[prodId];
    if (!prod) return;

    const tr = element.closest('tr');
    const td = element.closest('td');

    // Asignar ID
    td.querySelector('.sel-prod').value = prod.id;

    // Llenar Ficha Resumen
    const card = td.querySelector('.selected-med-card');
    card.querySelector('.med-title').textContent = prod.nombre_generico;
    card.querySelector('.med-subtitle').textContent = (prod.concentracion ? prod.concentracion + ' • ' : '') + 
                                                      (prod.forma_farmaceutica ? prod.forma_farmaceutica + ' • ' : '') + 
                                                      (prod.nombre_comercial || 'Genérico');
    card.querySelector('.med-sku').textContent = 'SKU: ' + prod.codigo_sku;
    card.querySelector('.med-cums').textContent = 'CUMS: ' + (prod.codigo_cums || 'N/A');
    card.querySelector('.med-invima').textContent = 'INV: ' + (prod.registro_invima || 'N/A');
    card.querySelector('.med-lab').textContent = 'Lab: ' + (prod.fabricante_laboratorio || 'GENÉRICO');

    // Ocultar buscador y mostrar ficha
    td.querySelector('.med-search-box').style.display = 'none';
    td.querySelector('.list-medicamentos-dropdown').style.display = 'none';
    card.style.display = 'block';

    // Auto-completar costo de referencia si está en 0
    const inpCosto = tr.querySelector('.inp-costo');
    if ((parseFloat(inpCosto.value) || 0) === 0 && parseFloat(prod.precio_referencia) > 0) {
        inpCosto.value = parseFloat(prod.precio_referencia).toFixed(2);
    }

    // Foco en lote
    tr.querySelector('.inp-lote').focus();
    calcularTotalEntrada();
}

function cambiarMedicamento(btn) {
    const td = btn.closest('td');
    td.querySelector('.sel-prod').value = '';
    td.querySelector('.selected-med-card').style.display = 'none';
    const searchBox = td.querySelector('.med-search-box');
    searchBox.style.display = 'block';
    const input = searchBox.querySelector('.inp-busqueda-med');
    input.value = '';
    input.focus();
}

// Cerrar dropdowns si se hace clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.med-search-box')) {
        document.querySelectorAll('.list-medicamentos-dropdown').forEach(d => d.style.display = 'none');
    }
});

function eliminarFilaItem(btn) {
    const tbody = document.getElementById('tbodyItemsEntrada');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calcularTotalEntrada();
    } else {
        alert('Debe conservar al menos un medicamento en la factura.');
    }
}

function calcularTotalEntrada() {
    let total = 0;
    const filas = document.querySelectorAll('#tbodyItemsEntrada tr');
    filas.forEach(f => {
        const cant = parseFloat(f.querySelector('.inp-cant').value) || 0;
        const costo = parseFloat(f.querySelector('.inp-costo').value) || 0;
        total += (cant * costo);
    });
    document.getElementById('lblTotalFactura').textContent = '$' + total.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('total_factura_input').value = total.toFixed(2);
}

function prepararEnvioCompra(e) {
    const items = [];
    const filas = document.querySelectorAll('#tbodyItemsEntrada tr');
    let faltanMedicamentos = false;

    filas.forEach(f => {
        const prodId = f.querySelector('.sel-prod').value;
        const lote = f.querySelector('.inp-lote').value.trim();
        const venc = f.querySelector('.inp-venc').value;
        const cant = f.querySelector('.inp-cant').value;
        const costo = f.querySelector('.inp-costo').value;

        if (!prodId) {
            faltanMedicamentos = true;
        }

        if (prodId && lote && venc && cant) {
            items.push({
                producto_id: prodId,
                numero_lote: lote,
                fecha_vencimiento: venc,
                cantidad: cant,
                costo_unitario: costo
            });
        }
    });

    if (faltanMedicamentos || items.length === 0) {
        e.preventDefault();
        alert('Por favor busque y seleccione el medicamento correspondiente para cada fila.');
        return false;
    }
    document.getElementById('items_json').value = JSON.stringify(items);
    return true;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>
@endsection
