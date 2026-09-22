@extends('layouts.app')

@section('titulo', 'Proveedores - '.config('app.name'))

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
                    <i class="fa-solid fa-truck-field fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Proveedores Farmacéuticos & Distribuidores</h3>
                    <p class="text-muted small mb-0 mt-1">Control de laboratorios y distribuidores con verificación de Concepto Sanitario.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end">
            <button type="button" class="btn btn-success fw-bold rounded-pill shadow-sm" onclick="abrirModalProveedor()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Proveedor
            </button>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Stock
            </a>
        </div>
    </div>

    <!-- TABLA PROVEEDORES -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">NIT</th>
                        <th>Razón Social / Laboratorio</th>
                        <th>Contacto & Teléfono</th>
                        <th>Ciudad / Dirección</th>
                        <th>Concepto Sanitario</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($proveedores))

                        <tr><td colspan="7" class="text-center py-5 text-muted">No hay proveedores registrados.</td></tr>
                    
@else

                        @foreach ($proveedores as $pr)

                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-primary">{{ $pr['nit'] }}</td>
                            <td><div class="fw-bold text-dark">{{ $pr['razon_social'] }}</div></td>
                            <td>
                                <div>{{ $pr['nombre_contacto'] ?: 'Principal' }}</div>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i> {{ $pr['telefono'] ?: '--' }}</small>
                            </td>
                            <td>
                                <div>{{ $pr['ciudad'] }}</div>
                                <small class="text-muted">{{ $pr['direccion'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i> {{ $pr['concepto_sanitario'] ?: 'VIGENTE' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $pr['estado'] === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $pr['estado'] }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick='editarProveedor({!! json_encode($pr, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                </button>
                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL CREAR / EDITAR PROVEEDOR -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form method="POST" action="{{ route('inventario.proveedores') }}">
@csrf
                <input type="hidden" name="action" value="guardar_proveedor">
                <input type="hidden" name="id" id="prov_id" value="0">

                <div class="modal-header bg-primary text-white py-3 px-4">
                    <h5 class="modal-title fw-bold" id="modalProvTit"><i class="fa-solid fa-truck-field me-2"></i> Nuevo Proveedor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIT *</label>
                        <input type="text" name="nit" id="prov_nit" class="form-control" required placeholder="Ej: 900.123.456-7">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Razón Social *</label>
                        <input type="text" name="razon_social" id="prov_razon" class="form-control" required placeholder="Ej: LABORATORIOS MK">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre Contacto</label>
                            <input type="text" name="nombre_contacto" id="prov_contacto" class="form-control" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="prov_tel" class="form-control" placeholder="Ej: 6041234567">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ciudad</label>
                            <input type="text" name="ciudad" id="prov_ciudad" class="form-control" value="MEDELLIN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Dirección</label>
                            <input type="text" name="direccion" id="prov_dir" class="form-control" placeholder="Calle...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Concepto Sanitario</label>
                        <input type="text" name="concepto_sanitario" id="prov_concepto" class="form-control" value="FAVORABLE VIGENTE">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalProveedor() {
    document.getElementById('modalProvTit').innerHTML = '<i class="fa-solid fa-truck-field me-2"></i> Nuevo Proveedor';
    document.getElementById('prov_id').value = '0';
    document.getElementById('prov_nit').value = '';
    document.getElementById('prov_razon').value = '';
    document.getElementById('prov_contacto').value = '';
    document.getElementById('prov_tel').value = '';
    document.getElementById('prov_ciudad').value = 'MEDELLIN';
    document.getElementById('prov_dir').value = '';
    document.getElementById('prov_concepto').value = 'FAVORABLE VIGENTE';
    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
}

function editarProveedor(p) {
    document.getElementById('modalProvTit').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i> Editar Proveedor';
    document.getElementById('prov_id').value = p.id;
    document.getElementById('prov_nit').value = p.nit || '';
    document.getElementById('prov_razon').value = p.razon_social || '';
    document.getElementById('prov_contacto').value = p.nombre_contacto || '';
    document.getElementById('prov_tel').value = p.telefono || '';
    document.getElementById('prov_ciudad').value = p.ciudad || 'MEDELLIN';
    document.getElementById('prov_dir').value = p.direccion || '';
    document.getElementById('prov_concepto').value = p.concepto_sanitario || 'FAVORABLE VIGENTE';
    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
}
</script>
@endsection
