@extends('layouts.app')

@section('titulo', 'Bodegas - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-warehouse fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4 d-flex align-items-center gap-2 flex-wrap">
                        <span>Gestión & Administración de Bodegas Farmacéuticas</span>
                    </h3>
                    <p class="text-muted small mb-0 mt-1">
                        Control de almacenes centrales, bodegas satélites de sedes y puntos de entrega según Res. 1403/2007.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm px-3" onclick="abrirModalNuevaBodega()">
                <i class="fa-solid fa-plus me-1"></i> Nueva Bodega
            </button>
            <a href="{{ route('inventario.dispensacion') }}" class="btn btn-outline-success fw-bold rounded-pill shadow-sm px-3">
                <i class="fa-solid fa-pills me-1"></i> Dispensación FEFO
            </a>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-dark fw-bold rounded-pill shadow-sm px-3">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Tablero FEFO
            </a>
        </div>
    </div>

    <!-- TARJETAS KPIS -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Bodegas Activas</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalActivas }} <small class="text-muted fs-6 fw-normal">/ {{ $totalBodegas }}</small></h3>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4">
                        <i class="fa-solid fa-warehouse fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Bodegas de Sedes</span>
                        <h3 class="fw-bold mb-0 text-info">{{ $totalSatelites }}</h3>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-4">
                        <i class="fa-solid fa-building fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Ventanillas Entrega</span>
                        <h3 class="fw-bold mb-0 text-success">{{ $totalVentanillas }}</h3>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-4">
                        <i class="fa-solid fa-person-shelter fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Stock Físico Total</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($stockTotalUnidades, 0, ',', '.') }} <small class="text-muted fs-6 fw-normal">unidades</small></h3>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4">
                        <i class="fa-solid fa-cubes-stacked fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS DE BÚSQUEDA -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="inventario_bodegas">
                
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Buscar por código, nombre o ubicación..." value="{{ $filtroBuscar }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="sede_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Sedes --</option>
                        
@foreach ($sedes as $s)

                            <option value="{{ $s['id'] }}" {{ ($filtroSede == $s['id']) ? 'selected' : '' }}>
                                {{ $s['nombre_sede'] }} ({{ $s['codigo_sede'] }})
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-md-2">
                    <select name="tipo_bodega" class="form-select form-select-sm">
                        <option value="">-- Tipo de Bodega --</option>
                        <option value="PRINCIPAL" {{ ($filtroTipo === 'PRINCIPAL') ? 'selected' : '' }}>Almacén Principal</option>
                        <option value="SATELITE_SEDE" {{ ($filtroTipo === 'SATELITE_SEDE') ? 'selected' : '' }}>Satélite de Sede</option>
                        <option value="DISPENSACION_VENTANILLA" {{ ($filtroTipo === 'DISPENSACION_VENTANILLA') ? 'selected' : '' }}>Ventanilla</option>
                        <option value="CUARENTENA" {{ ($filtroTipo === 'CUARENTENA') ? 'selected' : '' }}>Cuarentena</option>
                        <option value="DEVOLUCIONES" {{ ($filtroTipo === 'DEVOLUCIONES') ? 'selected' : '' }}>Devoluciones</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Estado --</option>
                        <option value="1" {{ ($filtroEstado === 1) ? 'selected' : '' }}>Solo Activas</option>
                        <option value="0" {{ ($filtroEstado === 0) ? 'selected' : '' }}>Solo Inactivas</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold" title="Aplicar Filtros">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    
@if (!empty($filtroBuscar) || !empty($filtroSede) || !empty($filtroTipo) || $filtroEstado !== null)

                        <a href="{{ route('inventario.bodegas') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    
@endif

                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE BODEGAS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i> Listado de Bodegas y Almacenes ({{ count($bodegas) }})
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nombre & Ubicación</th>
                        <th>Tipo de Bodega</th>
                        <th>Sede Asignada</th>
                        <th>Responsable / Regente</th>
                        <th class="text-center">Existencias</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($bodegas))

                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-warehouse fs-1 mb-3 text-secondary d-block"></i>
                                No se encontraron bodegas con los criterios de búsqueda especificados.
                            </td>
                        </tr>
                    
@else

                        @foreach ($bodegas as $b)

                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark font-monospace border fs-6 px-2 py-1">
                                        {{ $b['codigo_bodega'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $b['nombre_bodega'] }}</div>
                                    <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>{{ $b['ubicacion_fisica'] ?: 'Ubicación General' }}</small>
                                </td>
                                <td>
                                    
<?php switch ($b['tipo_bodega']) {
                                            case 'PRINCIPAL':
                                                echo '<span class="badge text-white shadow-sm" style="background-color: #6f42c1;"><i class="fa-solid fa-crown me-1"></i> Almacén Principal</span>';
                                                break;
                                            case 'SATELITE_SEDE':
                                                echo '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="fa-solid fa-building me-1"></i> Satélite de Sede</span>';
                                                break;
                                            case 'DISPENSACION_VENTANILLA':
                                                echo '<span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-person-shelter me-1"></i> Ventanilla</span>';
                                                break;
                                            case 'CUARENTENA':
                                                echo '<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Cuarentena</span>';
                                                break;
                                            case 'DEVOLUCIONES':
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-rotate-left me-1"></i> Devoluciones</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">' . htmlspecialchars($b['tipo_bodega']) . '</span>';
                                                break;
                                        } ?>

                                </td>
                                <td>
                                    
@if (!empty($b['sede_id']))

                                        <span class="fw-semibold text-dark">
                                            <i class="fa-solid fa-hospital text-info me-1"></i> {{ $b['nombre_sede'] }}
                                        </span>
                                    
@else

                                        <span class="badge bg-light text-secondary border">
                                            <i class="fa-solid fa-globe me-1"></i> Global / Central
                                        </span>
                                    
@endif

                                </td>
                                <td>
                                    
@if (!empty($b['responsable_nombre']))

                                        <span class="text-dark small"><i class="fa-solid fa-user-tie text-muted me-1"></i>{{ $b['responsable_nombre'] }}</span>
                                    
@else

                                        <span class="text-muted small fst-italic">Sin asignar</span>
                                    
@endif

                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-dark">{{ number_format($b['total_unidades_stock'] ?? 0, 0, ',', '.') }} <small class="text-muted">unid.</small></div>
                                    <small class="text-muted">{{ intval($b['total_productos_distintos'] ?? 0) }} productos</small>
                                </td>
                                <td class="text-center">
                                    
@if ($b['es_activa'] == 1)

                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="cursor: pointer;" onclick="toggleEstadoBodega({{ $b['id'] }}, 0)" title="Click para inactivar">
                                            <i class="fa-solid fa-circle-check me-1"></i> Activa
                                        </span>
                                    
@else

                                        <span class="badge bg-secondary text-white px-2 py-1" style="cursor: pointer;" onclick="toggleEstadoBodega({{ $b['id'] }}, 1)" title="Click para activar">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> Inactiva
                                        </span>
                                    
@endif

                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Opciones
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('inventario.dispensacion') }}?bodega_id={{ $b['id'] }}">
                                                    <i class="fa-solid fa-pills text-success me-2"></i> Dispensar desde esta Bodega
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('inventario.index') }}?bodega_id={{ $b['id'] }}">
                                                    <i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Ver Stock FEFO
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('inventario.kardex') }}?bodega_id={{ $b['id'] }}">
                                                    <i class="fa-solid fa-receipt text-secondary me-2"></i> Ver Kardex de Movimientos
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a class="dropdown-item py-2" href="javascript:void(0)" onclick="editarBodega({{ $b['id'] }})">
                                                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i> Editar Configuración
                                                </a>
                                            </li>
                                            
@if ($b['es_activa'] == 1)

                                                <li>
                                                    <a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="toggleEstadoBodega({{ $b['id'] }}, 0)">
                                                        <i class="fa-solid fa-ban text-danger me-2"></i> Inactivar Bodega
                                                    </a>
                                                </li>
                                            
@else

                                                <li>
                                                    <a class="dropdown-item py-2 text-success" href="javascript:void(0)" onclick="toggleEstadoBodega({{ $b['id'] }}, 1)">
                                                        <i class="fa-solid fa-check text-success me-2"></i> Activar Bodega
                                                    </a>
                                                </li>
                                            
@endif

                                        </ul>
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

<!-- MODAL CREAR / EDITAR BODEGA -->
<div class="modal fade" id="modalBodega" tabindex="-1" aria-labelledby="modalBodegaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="modalBodegaLabel">
                    <i class="fa-solid fa-warehouse me-2"></i> Configuración de Bodega Farmacéutica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formBodega" onsubmit="guardarBodegaForm(event)">
                <input type="hidden" name="action" value="guardar_bodega">
                <input type="hidden" name="id" id="bodega_id" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Código de Bodega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm font-monospace text-uppercase" name="codigo_bodega" id="bodega_codigo" placeholder="Ej: BOD-PRADO-01" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Tipo de Bodega <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="tipo_bodega" id="bodega_tipo" required>
                                <option value="SATELITE_SEDE">Satélite de Sede (Farmacia)</option>
                                <option value="PRINCIPAL">Almacén Central / Distribución</option>
                                <option value="DISPENSACION_VENTANILLA">Punto de Entrega / Ventanilla</option>
                                <option value="CUARENTENA">Área de Cuarentena</option>
                                <option value="DEVOLUCIONES">Área de Devoluciones / Averías</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Nombre de la Bodega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm fw-bold" name="nombre_bodega" id="bodega_nombre" placeholder="Ej: Bodega Farmacia - Sede Prado" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Sede Asignada</label>
                            <select class="form-select form-select-sm" name="sede_id" id="bodega_sede_id">
                                <option value="">-- Sin Sede (Almacén Global) --</option>
                                
@foreach ($sedes as $s)

                                    <option value="{{ $s['id'] }}">
                                        {{ $s['nombre_sede'] }} ({{ $s['codigo_sede'] }})
                                    </option>
                                
@endforeach

                            </select>
                            <small class="text-muted d-block mt-1">Los usuarios de esta sede podrán dispensar de aquí.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Responsable / Regente</label>
                            <select class="form-select form-select-sm" name="responsable_user_id" id="bodega_responsable">
                                <option value="">-- Sin Asignar --</option>
                                
@foreach ($usuarios as $u)

                                    <option value="{{ $u['id'] }}">
                                        {{ $u['nombre_completo'] }} ({{ $u['usuario'] }})
                                    </option>
                                
@endforeach

                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Ubicación Física dentro de la Sede</label>
                            <input type="text" class="form-control form-control-sm" name="ubicacion_fisica" id="bodega_ubicacion" placeholder="Ej: Piso 1 - Módulo de Farmacia y Ventanilla 2">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="es_activa" id="bodega_activa" value="1" checked>
                                <label class="form-check-label fw-bold small text-dark" for="bodega_activa">Bodega Habilitada y Activa para Operaciones</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" id="btnGuardarBodega">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Bodega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var modalBodegaBS = null;

document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('modalBodega');
    if (modalEl) {
        modalBodegaBS = new bootstrap.Modal(modalEl);
    }
});

function abrirModalNuevaBodega() {
    document.getElementById('formBodega').reset();
    document.getElementById('bodega_id').value = '0';
    document.getElementById('bodega_activa').checked = true;
    document.getElementById('modalBodegaLabel').innerHTML = '<i class="fa-solid fa-warehouse me-2"></i> Nueva Bodega Farmacéutica';
    if (modalBodegaBS) modalBodegaBS.show();
}

function editarBodega(id) {
    var formData = new FormData();
    formData.append('action', 'get_bodega');
    formData.append('id', id);

    fetch('{{ route('inventario.bodegas') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok' && res.data) {
            var b = res.data;
            document.getElementById('bodega_id').value = b.id;
            document.getElementById('bodega_codigo').value = b.codigo_bodega;
            document.getElementById('bodega_nombre').value = b.nombre_bodega;
            document.getElementById('bodega_tipo').value = b.tipo_bodega;
            document.getElementById('bodega_sede_id').value = b.sede_id || '';
            document.getElementById('bodega_responsable').value = b.responsable_user_id || '';
            document.getElementById('bodega_ubicacion').value = b.ubicacion_fisica || '';
            document.getElementById('bodega_activa').checked = (b.es_activa == 1);
            
            document.getElementById('modalBodegaLabel').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i> Editar Bodega: ' + b.nombre_bodega;
            if (modalBodegaBS) modalBodegaBS.show();
        } else {
            alert(res.message || 'No se pudo cargar la información de la bodega.');
        }
    })
    .catch(function(err) {
        alert('Error de conexión al cargar la bodega.');
    });
}

function guardarBodegaForm(e) {
    e.preventDefault();
    var form = document.getElementById('formBodega');
    var btn = document.getElementById('btnGuardarBodega');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';

    var formData = new FormData(form);

    fetch('{{ route('inventario.bodegas') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            alert(res.message);
            window.location.reload();
        } else {
            alert('Atención: ' + res.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Bodega';
        }
    })
    .catch(function(err) {
        alert('Error al guardar la bodega.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Bodega';
    });
}

function toggleEstadoBodega(id, nuevoEstado) {
    var accionTxt = (nuevoEstado === 1) ? 'activar' : 'inactivar';
    if (!confirm('¿Está seguro de ' + accionTxt + ' esta bodega?')) return;

    var formData = new FormData();
    formData.append('action', 'cambiar_estado');
    formData.append('id', id);
    formData.append('estado', nuevoEstado);

    fetch('{{ route('inventario.bodegas') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.status === 'ok') {
            window.location.reload();
        } else {
            alert(res.message || 'Error al cambiar estado.');
        }
    })
    .catch(function(err) {
        alert('Error de conexión con el servidor.');
    });
}
</script>
@endsection
