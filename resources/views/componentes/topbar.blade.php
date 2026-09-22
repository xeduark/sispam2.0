@php
    $u = auth()->user();
    $iniciales = collect(explode(' ', trim($u->nombre_completo)))
        ->filter()
        ->map(fn ($palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
        ->take(2)
        ->implode('');

    $misSedes = $u->sedesDisponibles();
    $sedeActivaId = (int) (session('active_sede_id') ?? $u->sede_id);
    $sedeActiva = $misSedes->firstWhere('id', $sedeActivaId) ?? \App\Models\Sede::find($sedeActivaId);
    $puedeCambiarSede = $misSedes->count() > 1;
@endphp

<header class="sispam-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn-sidebar-toggle" type="button" onclick="toggleSidebar()" aria-label="Mostrar u ocultar el menú lateral" title="Mostrar / minimizar menú">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <div>
            <h6 class="fw-bold mb-0">@yield('subtitulo', 'Panel de control')</h6>
           
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">

        <div class="dropdown">
            <button class="topbar-pill topbar-pill-sede border-0 {{ $puedeCambiarSede ? 'dropdown-toggle' : '' }}" type="button"
                    @if ($puedeCambiarSede) data-bs-toggle="dropdown" aria-expanded="false" @endif
                    title="{{ $puedeCambiarSede ? 'Cambiar sede de trabajo' : 'Sede de trabajo asignada' }}">
                <i class="fa-solid fa-location-dot text-success"></i>
                <span class="d-none d-md-inline text-muted small">Sede:</span>
                <span class="fw-bold small">{{ $sedeActiva?->nombre_sede ?? 'Sin sede' }}</span>
            </button>
            @if ($puedeCambiarSede)
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" style="min-width: 260px; max-height: 60vh; overflow-y: auto;">
                    <li class="dropdown-header text-uppercase fw-bold text-primary small">
                        <i class="fa-solid fa-shuffle me-1"></i> Cambiar sede de trabajo
                    </li>
                    @foreach ($misSedes as $sedeOpcion)
                        <li>
                            <form method="POST" action="{{ route('sede.cambiar', $sedeOpcion) }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 py-2 d-flex justify-content-between align-items-center {{ $sedeOpcion->id === $sedeActivaId ? 'active fw-bold' : '' }}">
                                    <span><i class="fa-solid fa-building me-2"></i>{{ $sedeOpcion->nombre_sede }}</span>
                                    @if ($sedeOpcion->id === $sedeActivaId)
                                        <i class="fa-solid fa-check"></i>
                                    @endif
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

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
