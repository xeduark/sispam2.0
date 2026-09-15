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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar.css') }}">
    @stack('estilos')
</head>
<body class="sispam-body">

{{-- Barra superior: solo en móvil, para abrir el sidebar --}}
<nav class="navbar navbar-dark bg-dark d-lg-none sticky-top no-print">
    <div class="container-fluid">
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand d-flex align-items-center gap-2 me-0" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/img/logo_sispam.jpg') }}" alt="SISPAM" class="rounded-circle" style="height: 32px; width: 32px; object-fit: cover;">
            <span class="fw-bold" style="letter-spacing: 1px;">SISPAM</span>
        </a>
    </div>
</nav>

<div class="sispam-shell">
    {{-- Sidebar fijo en escritorio --}}
    <aside class="sidebar d-none d-lg-flex flex-column no-print">
        @include('partials.sidebar')
    </aside>

    {{-- Mismo sidebar como offcanvas en móvil --}}
    <div class="offcanvas offcanvas-start sidebar sidebar-offcanvas d-lg-none no-print" tabindex="-1" id="sidebarOffcanvas" aria-label="Menú principal">
        <div class="offcanvas-header pb-0">
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column pt-0">
            @include('partials.sidebar')
        </div>
    </div>

    <div class="sispam-content d-flex flex-column">
        <main class="container-fluid px-4 py-4 flex-grow-1">
            @yield('content')
        </main>

        <footer class="footer py-3 bg-white border-top text-center text-muted small no-print">
            <span>&copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong>. Todos los derechos reservados.</span>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

@if (auth()->user()?->rol?->nombre === 'Orientador')
    <script src="{{ asset('assets/js/notifications.js') }}"></script>
@endif

@stack('scripts')
</body>
</html>
