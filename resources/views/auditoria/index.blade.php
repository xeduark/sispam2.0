@extends('layouts.app')

@section('titulo', 'Log de Auditoría - ' . config('app.name'))

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-clock-rotate-left me-2"></i> Log de Auditoría &amp; Trazabilidad del Sistema</h4>
        <p class="text-muted small mb-0">Registro histórico de todas las acciones, modificaciones y eventos realizados por los usuarios en SISPAM.</p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <a href="{{ route('auditoria.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-success fw-bold shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Log a Excel (CSV)
        </a>
    </div>
</div>

<!-- Tarjetas KPI Estadísticas de Log -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-glass border-start border-4 border-primary p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">ACCIONES REGISTRADAS HOY</div>
                    <div class="fs-2 fw-bold text-primary">{{ number_format($stats['total_hoy']) }}</div>
                </div>
                <div class="bg-primary text-white p-3 rounded-circle">
                    <i class="fa-solid fa-list-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-glass border-start border-4 border-success p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">USUARIOS ACTIVOS HOY</div>
                    <div class="fs-2 fw-bold text-success">{{ number_format($stats['usuarios_activos_hoy']) }}</div>
                </div>
                <div class="bg-success text-white p-3 rounded-circle">
                    <i class="fa-solid fa-users fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-glass border-start border-4 border-info p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">MÓDULO MÁS OPERADO</div>
                    <div class="fs-6 fw-bold text-info text-truncate" style="max-width: 160px;" title="{{ $stats['modulo_mas_activo'] }}">
                        {{ $stats['modulo_mas_activo'] }}
                    </div>
                </div>
                <div class="bg-info text-dark p-3 rounded-circle">
                    <i class="fa-solid fa-chart-line fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-glass border-start border-4 border-warning p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">INICIOS DE SESIÓN HOY</div>
                    <div class="fs-2 fw-bold text-warning">{{ number_format($stats['inicios_sesion_hoy']) }}</div>
                </div>
                <div class="bg-warning text-dark p-3 rounded-circle">
                    <i class="fa-solid fa-key fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Búsqueda de Auditoría -->
<div class="card card-glass border-0 shadow-sm p-3 mb-4">
    <form method="GET" action="{{ route('auditoria.index') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-calendar me-1"></i> Desde:</label>
            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $filters['fecha_desde'] }}">
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-calendar me-1"></i> Hasta:</label>
            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $filters['fecha_hasta'] }}">
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-cubes me-1"></i> Módulo:</label>
            <select name="modulo" class="form-select form-select-sm">
                <option value="">-- Todos los Módulos --</option>
                @php 
                $nombresModulos = [
                    'AUTENTICACION' => 'Autenticación & Inicios de Sesión',
                    'INGRESO'       => 'Admisión / Ingreso de Pacientes',
                    'TRANSCRIPCION' => 'Transcripción & Verificación de Stock',
                    'MONITOREO'     => 'Monitoreo & Verificación Técnica',
                    'ALISTAMIENTO'  => 'Supervisión de Alistamiento (Picking)',
                    'ENTREGA'       => 'Factura & Entrega con Firma Digital',
                    'EXPEDIENTES'   => 'Consulta de Expedientes',
                    'USUARIOS'      => 'Gestión de Usuarios y Permisos',
                    'EMPRESA'       => 'Parametrización de Empresa & Turneros',
                    'INVENTARIO'    => 'Inventario & Bodegas Farmacéuticas',
                    'FACTURACION'   => 'Facturación Electrónica & RIPS',
                    'SALIDA'        => 'Salida & Cierre de Atención'
                ];
                @endphp
                @foreach ($modulos as $mod)
                    @php $label = $nombresModulos[$mod] ?? $mod; @endphp
                    <option value="{{ $mod }}" {{ $filters['modulo'] === $mod ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-user me-1"></i> Usuario:</label>
            <select name="usuario_id" class="form-select form-select-sm">
                <option value="">-- Todos los Usuarios --</option>
                @foreach ($usuarios as $u)
                    <option value="{{ $u['id'] }}" {{ (int) $filters['usuario_id'] === (int) $u['id'] ? 'selected' : '' }}>{{ $u['nombre_completo'] }} ({{ $u['usuario'] }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Búsqueda libre / Palabra clave:</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar por detalle, ID o acción..." value="{{ $filters['q'] }}">
        </div>

        <div class="col-md-1 text-end">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla de Trazas de Log de Auditoría -->
<div class="card card-glass border-0 shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-bold"><i class="fa-solid fa-table-list me-2"></i> Trazas de Eventos (Mostrando {{ count($logs) }} registros)</span>
        <small class="text-muted">Orden cronológico inverso (Más recientes primero)</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm" style="font-size: 0.88rem;">
                <thead class="table-secondary">
                    <tr>
                        <th class="ps-3" style="width: 150px;">Fecha y Hora</th>
                        <th style="width: 180px;">Usuario &amp; Rol</th>
                        <th style="width: 130px;">Módulo</th>
                        <th style="width: 180px;">Acción Realizada</th>
                        <th style="width: 90px;">Registro ID</th>
                        <th>Detalles del Cambio / Evento</th>
                        <th class="pe-3" style="width: 120px;">Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    @if (empty($logs))
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                            No se encontraron registros de auditoría que coincidan con los filtros seleccionados.
                        </td>
                    </tr>
                    @else
                    @foreach ($logs as $log)
                    @php
                        $modBadge = 'bg-secondary';
                        switch ($log['modulo']) {
                            case 'AUTENTICACION': $modBadge = 'bg-dark'; break;
                            case 'INGRESO': $modBadge = 'bg-primary'; break;
                            case 'TRANSCRIPCION': $modBadge = 'bg-indigo'; break;
                            case 'MONITOREO': $modBadge = 'bg-info text-dark'; break;
                            case 'ALISTAMIENTO': $modBadge = 'bg-warning text-dark'; break;
                            case 'ENTREGA': $modBadge = 'bg-success'; break;
                            case 'USUARIOS': $modBadge = 'bg-purple text-white'; break;
                            case 'EMPRESA': $modBadge = 'bg-danger'; break;
                            case 'INVENTARIO': $modBadge = 'bg-success'; break;
                            case 'FACTURACION': $modBadge = 'bg-primary'; break;
                            case 'SALIDA': $modBadge = 'bg-secondary'; break;
                        }

                        $accionIcon = 'fa-arrow-right';
                        if (str_contains($log['accion'], 'CREAR') || str_contains($log['accion'], 'REGISTRAR')) $accionIcon = 'fa-plus-circle text-success';
                        elseif (str_contains($log['accion'], 'ACTUALIZAR') || str_contains($log['accion'], 'MODIFICAR') || str_contains($log['accion'], 'GUARDAR')) $accionIcon = 'fa-pen-to-square text-primary';
                        elseif (str_contains($log['accion'], 'LOGIN')) $accionIcon = 'fa-key text-warning';
                        elseif (str_contains($log['accion'], 'VERIFICACION') || str_contains($log['accion'], 'APROBAR')) $accionIcon = 'fa-circle-check text-success';
                        elseif (str_contains($log['accion'], 'ELIMINAR') || str_contains($log['accion'], 'FALLIDO')) $accionIcon = 'fa-triangle-exclamation text-danger';
                    @endphp
                    <tr>
                        <td class="ps-3 fw-bold text-nowrap">
                            <i class="fa-regular fa-clock me-1 text-muted"></i>
                            {{ date('d/m/Y h:i:s A', strtotime($log['created_at'])) }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $log['usuario_nombre'] }}</div>
                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-user-shield me-1"></i> {{ $log['rol_nombre'] }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $modBadge }} fw-semibold">{{ $log['modulo'] }}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">
                                <i class="fa-solid {{ $accionIcon }} me-1"></i> {{ $log['accion'] }}
                            </div>
                        </td>
                        <td>
                            @if ($log['registro_id'])
                                <span class="badge bg-outline-primary text-primary border border-primary">#{{ $log['registro_id'] }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-dark">{{ $log['detalles'] ?? 'Sin detalles adicionales' }}</div>
                        </td>
                        <td class="pe-3 font-monospace small text-muted">
                            <i class="fa-solid fa-network-wired me-1"></i> {{ $log['ip_address'] ?? '127.0.0.1' }}
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

