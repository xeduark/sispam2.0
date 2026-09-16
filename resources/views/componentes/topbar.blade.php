@php
    $u = auth()->user();
    $iniciales = collect(explode(' ', trim($u->nombre_completo)))
        ->filter()
        ->map(fn ($palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sispam-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-toggle" type="button" onclick="toggleSidebar()" aria-label="Mostrar u ocultar el menú lateral" title="Mostrar / minimizar menú">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <div>
            <h6 class="fw-bold mb-0">@yield('subtitulo', 'Panel de control')</h6>
            <span class="text-muted small">@yield('subtitulo_desc', config('app.name'))</span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn-theme-toggle" data-theme-toggle title="Cambiar tema claro/oscuro">
            <i class="fa-solid fa-moon"></i>
        </button>

        <div class="dropdown">
            <button class="user-badge-topbar dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="user-avatar-circle">{{ $iniciales ?: 'U' }}</span>
                <span class="text-start d-none d-md-block">
                    <span class="d-block fw-bold small text-truncate" style="max-width: 140px;">{{ $u->nombre_completo }}</span>
                    <span class="d-block text-muted" style="font-size: .7rem;">{{ $u->rol?->nombre ?: 'Usuario' }}</span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2">
                <li class="px-3 py-2 border-bottom mb-2">
                    <div class="fw-bold">{{ $u->nombre_completo }}</div>
                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 mt-1">{{ $u->rol?->nombre ?: 'Usuario' }}</span>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item rounded-3 py-2 text-danger fw-bold">
                            <i class="fa-solid fa-power-off me-2"></i> Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
