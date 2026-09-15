@php
    $u = auth()->user();
    $esAdmin = $u->esAdministrador();
    $verConfig = $u->hasPermission('empresa') || $u->hasPermission('usuarios') || $u->hasPermission('modulos') || $esAdmin;
    $configAbierto = request()->routeIs('empresa.*', 'usuarios.*', 'modulos.*', 'pacientes.*');
@endphp

<a class="sidebar-brand d-flex align-items-center gap-2 text-white text-decoration-none" href="{{ route('dashboard') }}">
    <img src="{{ asset('assets/img/logo_sispam.jpg') }}" alt="SISPAM" class="rounded-circle border border-info shadow-sm" style="height: 38px; width: 38px; object-fit: cover;">
    <span class="fw-bold fs-5" style="letter-spacing: 1px;">SISPAM</span>
</a>

<ul class="nav nav-pills flex-column sidebar-nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="fa-solid fa-chart-line"></i> <span>Inicio</span>
        </a>
    </li>

    @if ($u->hasPermission('ingreso'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('ingreso.*') ? 'active' : '' }}" href="{{ route('ingreso.index') }}">
                <i class="fa-solid fa-user-plus"></i> <span>Admisión / Ingreso</span>
            </a>
        </li>
    @endif

    @if ($u->hasPermission('transcripcion'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('transcripcion.*') ? 'active' : '' }}" href="{{ route('transcripcion.index') }}">
                <i class="fa-solid fa-file-signature"></i> <span>Transcripción &amp; Stock</span>
            </a>
        </li>
    @endif

    @if ($u->hasPermission('alistamiento'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('alistamiento.*') ? 'active' : '' }}" href="{{ route('alistamiento.index') }}">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Alistamiento</span>
            </a>
        </li>
    @endif

    @if ($u->hasPermission('entrega'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('entrega.*') ? 'active' : '' }}" href="{{ route('entrega.index') }}">
                <i class="fa-solid fa-hand-holding-medical"></i> <span>Entrega &amp; Factura</span>
            </a>
        </li>
    @endif

    @if ($u->hasPermission('reportes'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                <i class="fa-solid fa-chart-pie"></i> <span>Reportes &amp; SLA</span>
            </a>
        </li>
    @endif

    @if ($u->hasPermission('expedientes') || $u->hasPermission('ingreso'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('expedientes.*') ? 'active' : '' }}" href="{{ route('expedientes.index') }}">
                <i class="fa-solid fa-folder-open"></i> <span>Consulta Órdenes</span>
            </a>
        </li>
    @endif

    @if ($verConfig)
        <li class="nav-item">
            <a class="nav-link sidebar-submenu-toggle {{ $configAbierto ? 'active' : 'collapsed' }}"
               data-bs-toggle="collapse" href="#menuConfig" role="button"
               aria-expanded="{{ $configAbierto ? 'true' : 'false' }}" aria-controls="menuConfig">
                <i class="fa-solid fa-gears"></i> <span>Configuración</span>
                <i class="fa-solid fa-chevron-down sidebar-caret ms-auto"></i>
            </a>
            <div class="collapse {{ $configAbierto ? 'show' : '' }}" id="menuConfig">
                <ul class="nav nav-pills flex-column sidebar-submenu">
                    @if ($u->hasPermission('empresa') || $esAdmin)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('empresa.*') ? 'active' : '' }}" href="{{ route('empresa.edit') }}">
                                <i class="fa-solid fa-building text-primary"></i> <span>Empresa &amp; Sede</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('pacientes.importar*') ? 'active' : '' }}" href="{{ route('pacientes.importar') }}">
                                <i class="fa-solid fa-file-csv text-info"></i> <span>Carga Masiva (CSV)</span>
                            </a>
                        </li>
                    @endif
                    @if ($u->hasPermission('usuarios') || $esAdmin)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                                <i class="fa-solid fa-users text-success"></i> <span>Usuarios &amp; Permisos</span>
                            </a>
                        </li>
                    @endif
                    @if ($u->hasPermission('modulos') || $esAdmin)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('modulos.*') ? 'active' : '' }}" href="{{ route('modulos.index') }}">
                                <i class="fa-solid fa-door-open text-warning"></i> <span>Ventanillas &amp; Módulos</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>
    @endif

    <li class="nav-item">
        <a class="nav-link sidebar-submenu-toggle collapsed" data-bs-toggle="collapse" href="#menuTurneros"
           role="button" aria-expanded="false" aria-controls="menuTurneros">
            <i class="fa-solid fa-tv"></i> <span>Turneros TV</span>
            <i class="fa-solid fa-chevron-down sidebar-caret ms-auto"></i>
        </a>
        <div class="collapse" id="menuTurneros">
            <ul class="nav nav-pills flex-column sidebar-submenu">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('turnero.uno') }}" target="_blank">
                        <i class="fa-solid fa-desktop text-info"></i> <span>Turnero 1 (En Proceso)</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('turnero.dos') }}" target="_blank">
                        <i class="fa-solid fa-bullhorn text-danger"></i> <span>Turnero 2 (Listo Entrega)</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>
</ul>

<div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2 mb-2 text-white">
        <i class="fa-solid fa-circle-user fs-4 text-info"></i>
        <div class="overflow-hidden">
            <div class="fw-bold small text-truncate">{{ $u->nombre_completo }}</div>
            <span class="badge bg-info text-dark">{{ $u->rol?->nombre ?: 'Usuario' }}</span>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-light btn-sm w-100">
            <i class="fa-solid fa-right-from-bracket me-1"></i> Cerrar Sesión
        </button>
    </form>
</div>
