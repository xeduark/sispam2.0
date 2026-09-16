<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', config('app.name'))</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <script>
        (function () {
            try {
                var tema = localStorage.getItem('sispam-theme');
                document.documentElement.setAttribute('data-theme', tema === 'dark' ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v={{ filemtime(public_path('assets/css/theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/topbar.css') }}?v={{ filemtime(public_path('assets/css/topbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar.css') }}?v={{ filemtime(public_path('assets/css/sidebar.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ filemtime(public_path('assets/css/custom.css')) }}">
    {{-- Tailwind (utilidades únicamente, sin preflight) para el sidebar y el
    formulario de Ingreso. Recompilar con `npm run build:css` tras editar
    resources/css/tailwind.css o las clases usadas en los .blade.php. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.build.css') }}?v={{ filemtime(public_path('assets/css/tailwind.build.css')) }}">
    @stack('estilos')
</head>
<body class="sispam-body">

<div class="sispam-shell">
    {{-- Overlay oscuro detrás del sidebar cuando está abierto en móvil --}}
    <div id="sispam-sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 z-30 hidden bg-black/50 md:hidden"></div>

    {{-- Sidebar: drawer deslizante en móvil, fijo (y minimizable) en pantallas md+ --}}
    <aside id="sispam-sidebar" class="no-print fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col overflow-hidden bg-[var(--sidebar-bg)] p-3 transition-all duration-200 ease-in-out md:sticky md:top-0 md:h-screen md:translate-x-0">
        @include('componentes.sidebar')
    </aside>
    <script>
        // Restaura el estado minimizado antes de pintar, para que no parpadee.
        (function () {
            try {
                if (localStorage.getItem('sispam-sidebar-mini') === '1' && window.matchMedia('(min-width: 768px)').matches) {
                    document.getElementById('sispam-sidebar').classList.add('is-mini');
                }
            } catch (e) {}
        })();
    </script>

    <div class="sispam-content d-flex flex-column">
        @include('componentes.topbar')

        <main class="container-fluid px-4 py-4 flex-grow-1">
            @yield('content')
        </main>

        <footer class="footer py-3 border-top text-center text-muted small no-print">
            <span>&copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong>. Todos los derechos reservados.</span>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/theme.js') }}?v={{ filemtime(public_path('assets/js/theme.js')) }}"></script>
<script>
    // En escritorio el botón minimiza el sidebar a solo iconos; en móvil abre
    // y cierra el drawer, donde no tiene sentido dejar una tira de iconos fija.
    function toggleSidebar() {
        var sidebar = document.getElementById('sispam-sidebar');

        if (window.matchMedia('(min-width: 768px)').matches) {
            var mini = sidebar.classList.toggle('is-mini');
            try {
                localStorage.setItem('sispam-sidebar-mini', mini ? '1' : '0');
            } catch (e) {}

            return;
        }

        sidebar.classList.toggle('-translate-x-full');
        document.getElementById('sispam-sidebar-overlay').classList.toggle('hidden');
    }
</script>

@if (auth()->user()?->rol?->nombre === 'Orientador')
    <script src="{{ asset('assets/js/notifications.js') }}"></script>
@endif

@stack('scripts')
</body>
</html>
