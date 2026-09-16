@extends('layouts.app')

@section('titulo', 'Usuarios & Permisos - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-users-gear me-2"></i> Gestión de Usuarios, Empresa, Sede y Permisos</h4>
        <p class="text-muted small">Crea, edita datos de usuarios, asigna empresa/sede de atención y gestiona la matriz de permisos.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
            <i class="fa-solid fa-user-plus me-1"></i> Registrar Nuevo Usuario
        </button>
    </div>
</div>

@include('partials.alertas')

<ul class="nav nav-tabs mb-4 fw-bold">
    <li class="nav-item">
        <a class="nav-link {{ $subtab === 'usuarios' ? 'active text-primary border-bottom border-3 border-primary' : 'text-muted' }}" href="{{ route('usuarios.index', ['subtab' => 'usuarios']) }}">
            <i class="fa-solid fa-users me-1"></i> Lista de Usuarios
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $subtab === 'permisos' ? 'active text-primary border-bottom border-3 border-primary' : 'text-muted' }}" href="{{ route('usuarios.index', ['subtab' => 'permisos']) }}">
            <i class="fa-solid fa-key me-1"></i> Matriz de Permisos por Módulo
        </a>
    </li>
</ul>

@if ($subtab === 'usuarios')
<div class="card card-glass border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre Completo</th>
                        <th>Usuario (Login)</th>
                        <th>Empresa / Sede Asignada</th>
                        <th>Perfil / Rol</th>
                        <th>Estado</th>
                        <th class="text-end pe-3">Acciones de Edición</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $u)
                    @php
                        $camposEdicion = ['id', 'nombre_completo', 'usuario', 'rol_id', 'empresa_id', 'sede_id', 'estado'];
                        $datosEdicion = $u->only($camposEdicion);
                    @endphp
                    <tr>
                        <td class="ps-3 fw-bold">#{{ $u->id }}</td>
                        <td class="fw-bold">{{ $u->nombre_completo }}</td>
                        <td><code>{{ $u->usuario }}</code></td>
                        <td>
                            <div class="fw-semibold text-dark small"><i class="fa-solid fa-building me-1 text-primary"></i> {{ $u->empresa?->razon_social ?? 'Empresa Principal' }}</div>
                            <small class="text-muted"><i class="fa-solid fa-hospital-user me-1 text-success"></i> {{ $u->sede?->nombre_sede ?? 'Sede General' }}</small>
                        </td>
                        <td><span class="badge bg-primary fs-6">{{ $u->rol?->nombre }}</span></td>
                        <td>
                            @if ($u->estado === 'ACTIVO')
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1" onclick='abrirModalEditar(@json($datosEdicion))' title="Editar Datos, Empresa, Sede y Contraseña">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                            </button>

                            <form method="POST" action="{{ route('usuarios.toggle', $u) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $u->estado === 'ACTIVO' ? 'btn-outline-danger' : 'btn-outline-success' }}" title="Cambiar Estado">
                                    <i class="fa-solid {{ $u->estado === 'ACTIVO' ? 'fa-user-xmark' : 'fa-user-check' }}"></i>
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

@else

<div class="card card-glass border-0 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
        <div>
            <h5 class="fw-bold text-primary mb-1"><i class="fa-solid fa-sliders me-2"></i> Asignación de Permisos por Perfil / Rol</h5>
            <p class="text-muted small mb-0">Marca los módulos a los que cada perfil tendrá acceso en el menú y las funciones del sistema.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('usuarios.permisos') }}">
        @csrf

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width: 250px;">Módulo del Sistema</th>
                        @foreach ($roles as $r)
                            <th>{{ $r->nombre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modulosDisponibles as $modKey => $modLabel)
                    <tr>
                        <td class="fw-bold text-dark bg-light ps-3">
                            <i class="fa-solid fa-cube text-primary me-2"></i> {{ $modLabel }}
                        </td>
                        @foreach ($roles as $r)
                            @php
                                $esAdmin = $r->nombre === 'Administrador';
                                $marcado = $esAdmin || in_array($modKey, $r->permisos ?? [], true);
                            @endphp
                            <td class="text-center">
                                <div class="form-check d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="permisos_matriz[{{ $r->id }}][]" value="{{ $modKey }}"
                                           @checked($marcado) @disabled($esAdmin) style="transform: scale(1.3); cursor: pointer;">
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success btn-lg fw-bold px-4 shadow">
                <i class="fa-solid fa-floppy-disk me-2"></i> Guardar Matriz de Permisos
            </button>
        </div>
    </form>
</div>
@endif

<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-glass">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Registrar Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('usuarios.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_empresa_id">Empresa <span class="text-danger">*</span></label>
                            <select name="empresa_id" id="crear_empresa_id" class="form-select" required>
                                @foreach ($empresas as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_sede_id">Sede de Atención Asignada <span class="text-danger">*</span></label>
                            <select name="sede_id" id="crear_sede_id" class="form-select" required>
                                @foreach ($sedes as $sd)
                                    <option value="{{ $sd->id }}">{{ $sd->nombre_sede }} ({{ $sd->empresa?->razon_social }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_nombre_completo">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" id="crear_nombre_completo" name="nombre_completo" class="form-control" required placeholder="Ej: Carlos Mendoza">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_usuario">Nombre de Usuario (Login) <span class="text-danger">*</span></label>
                            <input type="text" id="crear_usuario" name="usuario" class="form-control" required placeholder="Ej: cmendoza">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_password">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="crear_password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="crear_rol_id">Perfil / Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" id="crear_rol_id" class="form-select" required>
                                <option value="">-- Seleccionar Perfil --</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->nombre }} - {{ $r->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-glass">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2 text-warning"></i> Editar Datos de Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('usuarios.update', ['usuario' => '__ID__']) }}" id="formEditarUsuario">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_empresa_id">Empresa <span class="text-danger">*</span></label>
                            <select name="empresa_id" id="edit_empresa_id" class="form-select" required>
                                @foreach ($empresas as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_sede_id">Sede de Atención Asignada <span class="text-danger">*</span></label>
                            <select name="sede_id" id="edit_sede_id" class="form-select" required>
                                @foreach ($sedes as $sd)
                                    <option value="{{ $sd->id }}">{{ $sd->nombre_sede }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_nombre_completo">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_completo" id="edit_nombre_completo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_usuario">Nombre de Usuario (Login) <span class="text-danger">*</span></label>
                            <input type="text" name="usuario" id="edit_usuario" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_rol_id">Perfil / Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" id="edit_rol_id" class="form-select" required>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_estado">Estado de Cuenta</label>
                            <select name="estado" id="edit_estado" class="form-select">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                        <div class="col-md-12 p-3 bg-light rounded border">
                            <label class="form-label fw-semibold text-primary mb-1" for="edit_new_password"><i class="fa-solid fa-key me-1"></i> Cambiar Contraseña (Opcional)</label>
                            <input type="password" name="new_password" id="edit_new_password" class="form-control" placeholder="Dejar en blanco para mantener contraseña actual">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const URL_EDITAR_USUARIO = @json(route('usuarios.update', ['usuario' => '__ID__']));

function abrirModalEditar(u) {
    document.getElementById('formEditarUsuario').action = URL_EDITAR_USUARIO.replace('__ID__', u.id);
    document.getElementById('edit_nombre_completo').value = u.nombre_completo;
    document.getElementById('edit_usuario').value = u.usuario;
    document.getElementById('edit_rol_id').value = u.rol_id;
    document.getElementById('edit_estado').value = u.estado;
    document.getElementById('edit_empresa_id').value = u.empresa_id || 1;
    document.getElementById('edit_sede_id').value = u.sede_id || 1;

    new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
}
</script>
@endpush

@endsection
