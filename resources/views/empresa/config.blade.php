@extends('layouts.app')

@section('titulo', 'Empresa & Sede - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1">
            <i class="fa-solid fa-building me-2"></i> Administrador Multi-Empresa &amp; Multi-Sede
        </h4>
        <p class="text-muted small">Gestiona las razones sociales corporativas, las sedes de atención farmacéutica y la configuración de turneros TV.</p>
    </div>
</div>

@include('partials.alertas')

<ul class="nav nav-tabs mb-4 fw-bold">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'general' ? 'active text-primary border-bottom border-3 border-primary' : 'text-muted' }}" href="{{ route('empresa.edit', ['tab' => 'general']) }}">
            <i class="fa-solid fa-gears me-1"></i> Configuración General &amp; Turneros
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'empresas' ? 'active text-primary border-bottom border-3 border-primary' : 'text-muted' }}" href="{{ route('empresa.edit', ['tab' => 'empresas']) }}">
            <i class="fa-solid fa-building me-1"></i> Empresas ({{ $empresas->count() }})
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'sedes' ? 'active text-primary border-bottom border-3 border-primary' : 'text-muted' }}" href="{{ route('empresa.edit', ['tab' => 'sedes']) }}">
            <i class="fa-solid fa-hospital-user me-1"></i> Sedes de Atención ({{ $sedes->count() }})
        </a>
    </li>
</ul>

@if ($tab === 'general')
<div class="card card-glass p-4 shadow-sm border-0">
    <form method="POST" action="{{ route('empresa.general') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="razon_social">Razón Social Principal</label>
                <input type="text" id="razon_social" name="razon_social" class="form-control" value="{{ $config->razon_social }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold" for="nit">NIT / RUT</label>
                <input type="text" id="nit" name="nit" class="form-control" value="{{ $config->nit }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold" for="direccion">Dirección Sede Principal</label>
                <input type="text" id="direccion" name="direccion" class="form-control" value="{{ $config->direccion }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold" for="telefono">Teléfono Contacto</label>
                <input type="text" id="telefono" name="telefono" class="form-control" value="{{ $config->telefono }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold" for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ $config->email }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-primary" for="hora_apertura_atencion">
                    <i class="fa-solid fa-clock me-1"></i> Hora Oficial Apertura / Inicio Atención SLA
                </label>
                <input type="time" id="hora_apertura_atencion" name="hora_apertura_atencion" class="form-control" value="{{ substr($config->hora_apertura_atencion ?? '07:20', 0, 5) }}" required>
                <div class="form-text small">Los tiquetes creados antes de esta hora iniciarán su contador de tiempo farmacéutico SLA a partir de esta hora.</div>
            </div>

            <hr class="my-4">

            <h5 class="fw-bold text-dark"><i class="fa-solid fa-ticket me-2 text-primary"></i> Personalización de Tiquete e Impresión</h5>

            <div class="col-md-8">
                <label class="form-label fw-semibold" for="pie_tiquete">Pie de Página en Tiquete Impreso</label>
                <textarea id="pie_tiquete" name="pie_tiquete" class="form-control" rows="3">{{ $config->pie_tiquete }}</textarea>
                <div class="form-text">Texto legal o recordatorios impresos al final del ticket térmico.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold" for="logo">Logo Institucional</label>
                <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                @if ($config->logo_url && file_exists(public_path($config->logo_url)))
                    <div class="mt-2 text-center p-2 bg-light rounded">
                        <img src="{{ asset($config->logo_url) }}" alt="Logo" style="max-height: 60px;">
                    </div>
                @endif
            </div>

            <hr class="my-4">

            <h5 class="fw-bold text-dark"><i class="fa-solid fa-tv me-2 text-info"></i> Configuración de Turneros TV (Pantallas)</h5>

            <div class="col-md-6">
                <label class="form-label fw-semibold" for="video_turnero_url">URL del Video Institucional (MP4 / Web)</label>
                <input type="url" id="video_turnero_url" name="video_turnero_url" class="form-control" value="{{ $config->video_turnero_url }}">
                <div class="form-text">Video reproducido en bucle en las pantallas del Turnero.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold" for="marquesina_turnero">Marquesina Informativa (Texto en movimiento)</label>
                <input type="text" id="marquesina_turnero" name="marquesina_turnero" class="form-control" value="{{ $config->marquesina_turnero }}">
                <div class="form-text">Mensaje continuo en la parte inferior de las pantallas.</div>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>

@elseif ($tab === 'empresas')
<div class="card card-glass border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-building me-2 text-primary"></i> Empresas Registradas</h5>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearEmpresa">
            <i class="fa-solid fa-plus me-1"></i> Registrar Nueva Empresa
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Razón Social</th>
                        <th>NIT</th>
                        <th>Dirección</th>
                        <th>Teléfono / Email</th>
                        <th>Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $emp)
                    <tr>
                        <td class="ps-3 fw-bold">#{{ $emp->id }}</td>
                        <td class="fw-bold text-primary">{{ $emp->razon_social }}</td>
                        <td><code>{{ $emp->nit }}</code></td>
                        <td>{{ $emp->direccion ?? 'N/A' }}</td>
                        <td>
                            <small class="d-block">{{ $emp->telefono }}</small>
                            <small class="text-muted">{{ $emp->email }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $emp->estado === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $emp->estado }}</span>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="abrirModalEditarEmpresa({{ $emp->toJson() }})">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@elseif ($tab === 'sedes')
<div class="card card-glass border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-hospital-user me-2 text-success"></i> Sedes de Atención Farmacéutica</h5>
        <button class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearSede">
            <i class="fa-solid fa-plus me-1"></i> Registrar Nueva Sede
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre Sede</th>
                        <th>Código Sede</th>
                        <th>Empresa Pertenece</th>
                        <th>Ciudad / Dirección</th>
                        <th>Teléfono</th>
                        <th>Hora Apertura SLA</th>
                        <th>Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sedes as $sd)
                    <tr>
                        <td class="ps-3 fw-bold">#{{ $sd->id }}</td>
                        <td class="fw-bold text-dark"><i class="fa-solid fa-building-circle-check text-success me-1"></i> {{ $sd->nombre_sede }}</td>
                        <td><span class="badge bg-info text-dark">{{ $sd->codigo_sede ?? 'N/A' }}</span></td>
                        <td><span class="fw-semibold text-primary">{{ $sd->empresa?->razon_social }}</span></td>
                        <td>
                            <div class="fw-bold small">{{ $sd->ciudad }}</div>
                            <small class="text-muted">{{ $sd->direccion }}</small>
                        </td>
                        <td>{{ $sd->telefono }}</td>
                        <td>
                            <span class="badge bg-light text-primary border border-primary fw-bold">
                                <i class="fa-solid fa-clock me-1"></i> {{ \Illuminate\Support\Carbon::parse($sd->hora_apertura_atencion ?? '07:20:00')->format('h:i A') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $sd->estado === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $sd->estado }}</span>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="abrirModalEditarSede({{ $sd->toJson() }})">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="modalCrearEmpresa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-building me-2"></i> Registrar Nueva Empresa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('empresa.empresas.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_emp_razon">Razón Social <span class="text-danger">*</span></label>
                        <input type="text" id="crear_emp_razon" name="razon_social" class="form-control" required placeholder="Ej: Farmacéutica del Norte S.A.S.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_emp_nit">NIT / RUT <span class="text-danger">*</span></label>
                        <input type="text" id="crear_emp_nit" name="nit" class="form-control" required placeholder="Ej: 901.123.456-7">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_emp_direccion">Dirección</label>
                        <input type="text" id="crear_emp_direccion" name="direccion" class="form-control" placeholder="Ej: Calle 50 # 40-20">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="crear_emp_telefono">Teléfono</label>
                            <input type="text" id="crear_emp_telefono" name="telefono" class="form-control" placeholder="Ej: 6043221100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="crear_emp_email">Correo Electrónico</label>
                            <input type="email" id="crear_emp_email" name="email" class="form-control" placeholder="contacto@empresa.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Guardar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarEmpresa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-building-circle-gear me-2 text-warning"></i> Editar Empresa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('empresa.empresas.update', ['empresa' => '__ID__']) }}" id="formEditarEmpresa">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_empresa_razon">Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="razon_social" id="edit_empresa_razon" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_empresa_nit">NIT / RUT <span class="text-danger">*</span></label>
                        <input type="text" name="nit" id="edit_empresa_nit" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_empresa_direccion">Dirección</label>
                        <input type="text" name="direccion" id="edit_empresa_direccion" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="edit_empresa_telefono">Teléfono</label>
                            <input type="text" name="telefono" id="edit_empresa_telefono" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="edit_empresa_email">Correo Electrónico</label>
                            <input type="email" name="email" id="edit_empresa_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_empresa_estado">Estado</label>
                        <select name="estado" id="edit_empresa_estado" class="form-select">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrearSede" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-hospital-user me-2"></i> Registrar Nueva Sede</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('empresa.sedes.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_sede_empresa">Empresa a la que pertenece <span class="text-danger">*</span></label>
                        <select name="empresa_id" id="crear_sede_empresa" class="form-select" required>
                            @foreach ($empresas as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold" for="crear_sede_nombre">Nombre de la Sede <span class="text-danger">*</span></label>
                            <input type="text" id="crear_sede_nombre" name="nombre_sede" class="form-control" required placeholder="Ej: Sede Laureles">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold" for="crear_sede_codigo">Código Sede</label>
                            <input type="text" id="crear_sede_codigo" name="codigo_sede" class="form-control" placeholder="Ej: LRL">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_sede_ciudad">Ciudad</label>
                        <input type="text" id="crear_sede_ciudad" name="ciudad" class="form-control" value="MEDELLIN">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_sede_direccion">Dirección</label>
                        <input type="text" id="crear_sede_direccion" name="direccion" class="form-control" placeholder="Ej: Circular 4 # 73-12">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="crear_sede_telefono">Teléfono Contacto</label>
                        <input type="text" id="crear_sede_telefono" name="telefono" class="form-control" placeholder="Ej: 6044440033">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-primary" for="crear_sede_hora"><i class="fa-solid fa-clock me-1"></i> Hora Oficial Apertura / Inicio Atención SLA</label>
                        <input type="time" id="crear_sede_hora" name="hora_apertura_atencion" class="form-control" value="07:20" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="fa-solid fa-save me-1"></i> Guardar Sede</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarSede" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-glass">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Editar Sede de Atención</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('empresa.sedes.update', ['sede' => '__ID__']) }}" id="formEditarSede">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_sede_empresa_id">Empresa a la que pertenece <span class="text-danger">*</span></label>
                        <select name="empresa_id" id="edit_sede_empresa_id" class="form-select" required>
                            @foreach ($empresas as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold" for="edit_sede_nombre">Nombre de la Sede <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_sede" id="edit_sede_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold" for="edit_sede_codigo">Código Sede</label>
                            <input type="text" name="codigo_sede" id="edit_sede_codigo" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_sede_ciudad">Ciudad</label>
                        <input type="text" name="ciudad" id="edit_sede_ciudad" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_sede_direccion">Dirección</label>
                        <input type="text" name="direccion" id="edit_sede_direccion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_sede_telefono">Teléfono Contacto</label>
                        <input type="text" name="telefono" id="edit_sede_telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-primary" for="edit_sede_hora_apertura"><i class="fa-solid fa-clock me-1"></i> Hora Oficial Apertura / Inicio Atención SLA</label>
                        <input type="time" name="hora_apertura_atencion" id="edit_sede_hora_apertura" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_sede_estado">Estado</label>
                        <select name="estado" id="edit_sede_estado" class="form-select">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Sede</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const URL_EDITAR_EMPRESA = @json(route('empresa.empresas.update', ['empresa' => '__ID__']));
const URL_EDITAR_SEDE = @json(route('empresa.sedes.update', ['sede' => '__ID__']));

function abrirModalEditarEmpresa(e) {
    document.getElementById('formEditarEmpresa').action = URL_EDITAR_EMPRESA.replace('__ID__', e.id);
    document.getElementById('edit_empresa_razon').value = e.razon_social;
    document.getElementById('edit_empresa_nit').value = e.nit;
    document.getElementById('edit_empresa_direccion').value = e.direccion || '';
    document.getElementById('edit_empresa_telefono').value = e.telefono || '';
    document.getElementById('edit_empresa_email').value = e.email || '';
    document.getElementById('edit_empresa_estado').value = e.estado || 'Activo';

    new bootstrap.Modal(document.getElementById('modalEditarEmpresa')).show();
}

function abrirModalEditarSede(s) {
    document.getElementById('formEditarSede').action = URL_EDITAR_SEDE.replace('__ID__', s.id);
    document.getElementById('edit_sede_empresa_id').value = s.empresa_id;
    document.getElementById('edit_sede_nombre').value = s.nombre_sede;
    document.getElementById('edit_sede_codigo').value = s.codigo_sede || '';
    document.getElementById('edit_sede_ciudad').value = s.ciudad || 'MEDELLIN';
    document.getElementById('edit_sede_direccion').value = s.direccion || '';
    document.getElementById('edit_sede_telefono').value = s.telefono || '';
    document.getElementById('edit_sede_hora_apertura').value = (s.hora_apertura_atencion || '07:20:00').substring(0,5);
    document.getElementById('edit_sede_estado').value = s.estado || 'Activo';

    new bootstrap.Modal(document.getElementById('modalEditarSede')).show();
}
</script>
@endpush

@endsection
