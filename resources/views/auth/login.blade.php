<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - {{ config('app.name') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card card-glass text-dark border-0 p-4 shadow-lg">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3">
                        <img src="{{ asset('assets/img/logo_sispam.jpg') }}" alt="SISPAM Logo" class="rounded-circle shadow-lg border border-primary border-3" style="width: 90px; height: 90px; object-fit: cover;">
                    </div>
                    <h3 class="fw-bold text-dark mb-1" style="letter-spacing: 1.5px;">SISPAM</h3>
                    <p class="text-muted small">Sistema Integral de Gestión Farmacéutica &amp; Dispensación</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $errors->first() }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="usuario">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" class="form-control" placeholder="Ej: admin, orientador..." required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="password">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
