@php
    $u = auth()->user();
    $esAdmin = $u->esAdministrador();
    $verConfig = $u->hasPermission('empresa') || $u->hasPermission('usuarios') || $u->hasPermission('modulos') || $esAdmin;
    $configAbierto = request()->routeIs('empresa.*', 'usuarios.*', 'modulos.*', 'pacientes.*');
    $verOperacion = $u->hasPermission('ingreso') || $u->hasPermission('transcripcion') || $u->hasPermission('alistamiento') || $u->hasPermission('entrega');
    $operacionAbierta = request()->routeIs('ingreso.*', 'transcripcion.*', 'alistamiento.*', 'entrega.*');
    $turnerosAbiertos = request()->routeIs('turnero.*');

    // Clases compartidas: un solo lugar para el color de link normal / activo,
    // así cualquier item nuevo hereda el mismo contraste sin repetirlo.
    // .sidebar-label marca el texto que se oculta al minimizar (ver sidebar.css).
    $linkBase = 'sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-200 whitespace-nowrap no-underline transition-colors hover:bg-white/10 hover:text-white';
    $linkActive = 'bg-sky-600 text-white font-semibold hover:bg-sky-600';
    $subLinkBase = 'sidebar-item sidebar-subitem flex items-center gap-2 rounded-lg py-2 pl-9 pr-3 text-sm text-slate-300 whitespace-nowrap no-underline transition-colors hover:bg-white/10 hover:text-white';
    $sectionTitle = 'sidebar-section px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400 whitespace-nowrap first:pt-0';
    $summaryBase = 'sidebar-item flex list-none items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-200 whitespace-nowrap transition-colors hover:bg-white/10 hover:text-white cursor-pointer [&::-webkit-details-marker]:hidden';
@endphp

<a href="{{ route('dashboard') }}" class="sidebar-item mb-1 flex items-center gap-2 px-2 pb-3 text-white no-underline" title="SISPAM">
    <img src="{{ asset('assets/img/logo_sispam.jpg') }}" alt="SISPAM" class="h-9 w-9 shrink-0 rounded-full border border-sky-400 object-cover shadow-sm">
    <span class="sidebar-label text-lg font-bold tracking-wide">SISPAM</span>
</a>

<nav class="sidebar-nav flex-1 overflow-y-auto overflow-x-hidden pb-2">
    <p class="{{ $sectionTitle }}">Principal</p>
    <a href="{{ route('dashboard') }}" title="Inicio" class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : '' }}">
        <i class="fa-solid fa-house-medical w-5 shrink-0 text-center text-sky-400"></i>
        <span class="sidebar-label flex-1 truncate">Inicio</span>
    </a>

    @if ($verOperacion)
        <p class="{{ $sectionTitle }}">Operación</p>
        <details class="group" @if ($operacionAbierta) open @endif>
            <summary class="{{ $summaryBase }} {{ $operacionAbierta ? 'text-white' : '' }}" title="Flujo Asistencial">
                <i class="fa-solid fa-heart-pulse w-5 shrink-0 text-center text-blue-400"></i>
                <span class="sidebar-label flex-1 truncate">Flujo Asistencial</span>
                <i class="fa-solid fa-chevron-right sidebar-caret text-xs transition-transform duration-200 group-open:rotate-90"></i>
            </summary>
            <div class="mt-0.5 flex flex-col gap-0.5">
                @if ($u->hasPermission('ingreso'))
                    <a href="{{ route('ingreso.index') }}" title="Admisión / Ingreso" class="{{ $subLinkBase }} {{ request()->routeIs('ingreso.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-user-plus w-5 shrink-0 text-center text-blue-400"></i>
                        <span class="sidebar-label truncate">Admisión / Ingreso</span>
                    </a>
                @endif
                @if ($u->hasPermission('transcripcion'))
                    <a href="{{ route('transcripcion.index') }}" title="Transcripción &amp; Stock" class="{{ $subLinkBase }} {{ request()->routeIs('transcripcion.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-file-signature w-5 shrink-0 text-center text-cyan-400"></i>
                        <span class="sidebar-label truncate">Transcripción &amp; Stock</span>
                    </a>
                @endif
                @if ($u->hasPermission('alistamiento'))
                    <a href="{{ route('alistamiento.index') }}" title="Alistamiento" class="{{ $subLinkBase }} {{ request()->routeIs('alistamiento.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-boxes-stacked w-5 shrink-0 text-center text-emerald-400"></i>
                        <span class="sidebar-label truncate">Alistamiento</span>
                    </a>
                @endif
                @if ($u->hasPermission('entrega'))
                    <a href="{{ route('entrega.index') }}" title="Entrega &amp; Factura" class="{{ $subLinkBase }} {{ request()->routeIs('entrega.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-hand-holding-medical w-5 shrink-0 text-center text-rose-400"></i>
                        <span class="sidebar-label truncate">Entrega &amp; Factura</span>
                    </a>
                @endif
            </div>
        </details>
    @endif

    @if ($u->hasPermission('ingreso') || $u->hasPermission('transcripcion'))
        <p class="{{ $sectionTitle }}">IA &amp; Digitalización</p>
        <a href="{{ route('ia_scanner.index') }}" title="Escáner IA" class="{{ $linkBase }} {{ request()->routeIs('ia_scanner.index') ? $linkActive : '' }}">
            <i class="fa-solid fa-wand-magic-sparkles w-5 shrink-0 text-center text-amber-400"></i>
            <span class="sidebar-label flex-1 truncate">Escáner IA</span>
        </a>
        <a href="{{ route('ia_scanner.cola') }}" title="Cola de Procesamiento IA" class="{{ $linkBase }} {{ request()->routeIs('ia_scanner.cola') ? $linkActive : '' }}">
            <i class="fa-solid fa-list-check w-5 shrink-0 text-center text-amber-400"></i>
            <span class="sidebar-label flex-1 truncate">Cola IA</span>
        </a>
    @endif

    @if ($u->hasPermission('expedientes') || $u->hasPermission('ingreso') || $u->hasPermission('reportes'))
        <p class="{{ $sectionTitle }}">Analítica</p>
        @if ($u->hasPermission('expedientes') || $u->hasPermission('ingreso'))
            <a href="{{ route('expedientes.index') }}" title="Consulta Órdenes" class="{{ $linkBase }} {{ request()->routeIs('expedientes.*') ? $linkActive : '' }}">
                <i class="fa-solid fa-folder-open w-5 shrink-0 text-center text-blue-400"></i>
                <span class="sidebar-label flex-1 truncate">Consulta Órdenes</span>
            </a>
        @endif
        @if ($u->hasPermission('reportes'))
            <a href="{{ route('reportes.index') }}" title="Reportes &amp; SLA" class="{{ $linkBase }} {{ request()->routeIs('reportes.*') ? $linkActive : '' }}">
                <i class="fa-solid fa-chart-pie w-5 shrink-0 text-center text-cyan-400"></i>
                <span class="sidebar-label flex-1 truncate">Reportes &amp; SLA</span>
            </a>
        @endif
    @endif

    @if ($verConfig)
        <p class="{{ $sectionTitle }}">Sistema</p>
        <details class="group" @if ($configAbierto) open @endif>
            <summary class="{{ $summaryBase }} {{ $configAbierto ? 'text-white' : '' }}" title="Configuración">
                <i class="fa-solid fa-gears w-5 shrink-0 text-center text-slate-400"></i>
                <span class="sidebar-label flex-1 truncate">Configuración</span>
                <i class="fa-solid fa-chevron-right sidebar-caret text-xs transition-transform duration-200 group-open:rotate-90"></i>
            </summary>
            <div class="mt-0.5 flex flex-col gap-0.5">
                @if ($u->hasPermission('empresa') || $u->hasPermission('usuarios') || $esAdmin)
                    <a href="{{ route('pacientes.index') }}" title="Directorio Pacientes" class="{{ $subLinkBase }} {{ request()->routeIs('pacientes.index') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-hospital-user w-5 shrink-0 text-center text-blue-400"></i>
                        <span class="sidebar-label truncate">Directorio Pacientes</span>
                    </a>
                    <a href="{{ route('pacientes.importar') }}" title="Carga Masiva (CSV)" class="{{ $subLinkBase }} {{ request()->routeIs('pacientes.importar*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-file-csv w-5 shrink-0 text-center text-cyan-400"></i>
                        <span class="sidebar-label truncate">Carga Masiva (CSV)</span>
                    </a>
                @endif
                @if ($u->hasPermission('empresa') || $esAdmin)
                    <a href="{{ route('empresa.edit') }}" title="Empresa &amp; Sede" class="{{ $subLinkBase }} {{ request()->routeIs('empresa.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-building w-5 shrink-0 text-center text-blue-400"></i>
                        <span class="sidebar-label truncate">Empresa &amp; Sede</span>
                    </a>
                @endif
                @if ($u->hasPermission('usuarios') || $esAdmin)
                    <a href="{{ route('usuarios.index') }}" title="Usuarios &amp; Permisos" class="{{ $subLinkBase }} {{ request()->routeIs('usuarios.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-users w-5 shrink-0 text-center text-emerald-400"></i>
                        <span class="sidebar-label truncate">Usuarios &amp; Permisos</span>
                    </a>
                @endif
                @if ($u->hasPermission('modulos') || $esAdmin)
                    <a href="{{ route('modulos.index') }}" title="Ventanillas &amp; Módulos" class="{{ $subLinkBase }} {{ request()->routeIs('modulos.*') ? 'text-white font-semibold' : '' }}">
                        <i class="fa-solid fa-door-open w-5 shrink-0 text-center text-amber-400"></i>
                        <span class="sidebar-label truncate">Ventanillas &amp; Módulos</span>
                    </a>
                @endif
            </div>
        </details>
    @endif

    <p class="{{ $sectionTitle }}">Sala de Espera</p>
    <details class="group" @if ($turnerosAbiertos) open @endif>
        <summary class="{{ $summaryBase }} {{ $turnerosAbiertos ? 'text-white' : '' }}" title="Turneros TV">
            <i class="fa-solid fa-tv w-5 shrink-0 text-center text-amber-400"></i>
            <span class="sidebar-label flex-1 truncate">Turneros TV</span>
            <i class="fa-solid fa-chevron-right sidebar-caret text-xs transition-transform duration-200 group-open:rotate-90"></i>
        </summary>
        <div class="mt-0.5 flex flex-col gap-0.5">
            <a href="{{ route('turnero.uno') }}" target="_blank" title="Turnero 1 (En Proceso)" class="{{ $subLinkBase }}">
                <i class="fa-solid fa-desktop w-5 shrink-0 text-center text-cyan-400"></i>
                <span class="sidebar-label truncate">Turnero 1 (En Proceso)</span>
            </a>
            <a href="{{ route('turnero.dos') }}" target="_blank" title="Turnero 2 (Listo Entrega)" class="{{ $subLinkBase }}">
                <i class="fa-solid fa-bullhorn w-5 shrink-0 text-center text-rose-400"></i>
                <span class="sidebar-label truncate">Turnero 2 (Listo Entrega)</span>
            </a>
        </div>
    </details>
</nav>

<div class="sidebar-footer flex shrink-0 items-center justify-between gap-2 whitespace-nowrap border-t border-white/10 px-2 pt-3 text-[11px] text-slate-400">
    <span title="SISPAM 2.0"><i class="fa-solid fa-shield-halved text-emerald-400"></i> <span class="sidebar-label">SISPAM 2.0</span></span>
    <span class="sidebar-label rounded-full border border-slate-600 bg-black/40 px-2 py-0.5 text-sky-300">Laravel</span>
</div>
