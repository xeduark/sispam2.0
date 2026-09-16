@extends('layouts.app')

@section('titulo', 'Módulos & Ventanillas - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-door-open me-2"></i> Gestión de Módulos &amp; Ventanillas de Entrega</h4>
        <p class="text-muted small">Crea, modifica nombres y activa/desactiva los módulos de entrega usados para la asignación y Turnero TV.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearModulo">
            <i class="fa-solid fa-plus me-1"></i> Crear Nueva Ventanilla / Módulo
        </button>
    </div>
</div>

@include('partials.alertas')

<div class="card card-glass border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre del Módulo / Ventanilla</th>
                        <th>Descripción / Propósito</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th class="text-end pe-3">Acciones de Edición</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modulos as $m)
                        <tr>
                            <td class="ps-3 fw-bold">#{{ $m->id }}</td>
                            <td class="fw-bold text-primary fs-5">
                                <i class="fa-solid fa-door-closed me-2 text-info"></i> {{ $m->nombre }}
                            </td>
                            <td>{{ $m->descripcion ?: 'Sin descripción' }}</td>
                            <td>
                                @if ($m->estado === 'ACTIVO')
                                    <span class="badge bg-success">Activo (Habilitado para Turnero)</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo (Fuera de Servicio)</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1"
                                        onclick="abrirModalEditarModulo({{ json_encode($m->only('id', 'nombre', 'descripcion', 'estado')) }})">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                </button>

                                <form method="POST" action="{{ route('modulos.toggle', $m) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $m->estado === 'ACTIVO' ? 'btn-outline-danger' : 'btn-outline-success' }}" title="Cambiar Estado">
                                        <i class="fa-solid {{ $m->estado === 'ACTIVO' ? 'fa-power-off' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrearModulo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-door-open me-2"></i> Crear Nuevo Módulo / Ventanilla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('modulos.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_nombre">Nombre de la Ventanilla / Módulo <span class="text-danger">*</span></label>
                        <input type="text" id="crear_nombre" name="nombre" class="form-control" required placeholder="Ej: MÓDULO 5 o VENTANILLA PRIORITARIA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_descripcion">Descripción o Comentario</label>
                        <input type="text" id="crear_descripcion" name="descripcion" class="form-control" placeholder="Ej: Atención rápida o fórmulas de alto costo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Ventanilla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarModulo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Editar Módulo de Entrega</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('modulos.index') }}" id="formEditarModulo">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_mod_nombre">Nombre de la Ventanilla / Módulo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="edit_mod_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_mod_descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="edit_mod_descripcion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_mod_estado">Estado de Servicio</label>
                        <select name="estado" id="edit_mod_estado" class="form-select">
                            <option value="ACTIVO">ACTIVO (En servicio)</option>
                            <option value="INACTIVO">INACTIVO (Fuera de servicio / cerrado)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Módulo</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const URL_EDITAR_MODULO = @json(route('modulos.update', ['modulo' => '__ID__']));

function abrirModalEditarModulo(m) {
    document.getElementById('formEditarModulo').action = URL_EDITAR_MODULO.replace('__ID__', m.id);
    document.getElementById('edit_mod_nombre').value = m.nombre;
    document.getElementById('edit_mod_descripcion').value = m.descripcion || '';
    document.getElementById('edit_mod_estado').value = m.estado;

    new bootstrap.Modal(document.getElementById('modalEditarModulo')).show();
}
</script>
@endpush

@endsection
