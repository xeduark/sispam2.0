<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - {{ config('app.name') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo_sispam.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --sispam-cyan: #00f2fe;
            --sispam-cyan-glow: rgba(0, 242, 254, 0.45);
            --sispam-blue: #0088ff;
            --sispam-dark-bg: #030712;
            --sispam-card-bg: #060e20;
            --sispam-panel-right: #081226;
            --sispam-border: rgba(30, 58, 138, 0.4);
            --sispam-border-input: #1e293b;
            --sispam-input-bg: #030816;
            --sispam-text-muted: #94a3b8;
            --sispam-text-dim: #64748b;
            --sispam-emerald: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background-color: var(--sispam-dark-bg);
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.4) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.4) 1px, transparent 1px);
            background-size: 36px 36px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .sispam-main-card {
            width: 100%;
            max-width: 1160px;
            background: #060e20;
            border: 1px solid rgba(56, 189, 248, 0.18);
            border-radius: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px -10px rgba(0, 242, 254, 0.08);
            display: grid;
            grid-template-columns: 1.22fr 1fr;
            overflow: hidden;
        }

        .left-panel {
            padding: 3.25rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: radial-gradient(circle at 15% 15%, #0b1f3f 0%, #060e20 60%);
            border-right: 1px solid rgba(56, 189, 248, 0.12);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .brand-logo-badge {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #030816;
            border: 2px solid #00f2fe;
            box-shadow: 0 0 24px var(--sispam-cyan-glow), inset 0 0 12px rgba(0, 242, 254, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .logo-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: inherit;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.75rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            color: #ffffff;
            line-height: 1;
        }

        .brand-subtitle {
            font-family: 'Outfit', sans-serif;
            font-size: 0.72rem;
            color: #00f2fe;
            letter-spacing: 0.22em;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 0.35rem;
        }

        .brand-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2.2rem;
            max-width: 480px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .module-item {
            background: rgba(11, 23, 48, 0.45);
            border: 1px solid rgba(56, 189, 248, 0.12);
            border-radius: 18px;
            padding: 1.1rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            transition: all 0.25s ease;
        }

        .module-item:hover {
            background: rgba(11, 23, 48, 0.75);
            border-color: rgba(0, 242, 254, 0.35);
            transform: translateY(-2px);
        }

        .module-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-shield {
            background: rgba(2, 132, 199, 0.18);
            border: 1px solid rgba(2, 132, 199, 0.4);
            color: #38bdf8;
        }

        .icon-file {
            background: rgba(6, 182, 212, 0.18);
            border: 1px solid rgba(6, 182, 212, 0.4);
            color: #22d3ee;
        }

        .icon-signature {
            background: rgba(16, 185, 129, 0.18);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .icon-box {
            background: rgba(99, 102, 241, 0.18);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: #a5b4fc;
        }

        .module-info h4 {
            font-size: 0.88rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.2rem;
        }

        .module-info p {
            font-size: 0.76rem;
            color: #94a3b8;
            line-height: 1.35;
        }

        .left-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--sispam-text-dim);
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .status-pill {
            background: rgba(6, 78, 59, 0.5);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #6ee7b7;
            padding: 0.3rem 0.8rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 8px #10b981;
        }

        .right-panel {
            padding: 3.25rem 3rem;
            background: #081226;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .form-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .form-subtitle {
            font-size: 0.88rem;
            color: #94a3b8;
            margin-bottom: 1.75rem;
        }

        .alert-error {
            background: rgba(153, 27, 27, 0.4);
            border: 1px solid rgba(239, 68, 68, 0.6);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.82rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .form-group {
            margin-bottom: 1.15rem;
        }

        .form-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #cbd5e1;
            margin-bottom: 0.45rem;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1.1rem;
            color: #64748b;
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .custom-input {
            width: 100%;
            height: 48px;
            background: var(--sispam-input-bg);
            border: 1px solid var(--sispam-border-input);
            border-radius: 12px;
            padding: 0 1rem 0 2.85rem;
            font-size: 0.9rem;
            color: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .custom-input:focus {
            border-color: #00f2fe;
            box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.15);
            background: #040b1e;
        }

        .custom-input::placeholder {
            color: #475569;
        }

        select.custom-input {
            padding-left: 1rem;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .btn-toggle-eye {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 0.25rem;
            transition: color 0.2s;
        }

        .btn-toggle-eye:hover {
            color: #cbd5e1;
        }

        .forgot-link {
            color: #38bdf8;
            font-size: 0.76rem;
            text-decoration: none;
            transition: color 0.2s;
            background: none;
            border: none;
            cursor: pointer;
        }

        .forgot-link:hover {
            color: #00f2fe;
            text-decoration: underline;
        }

        .sede-toggle-btn {
            background: none;
            border: none;
            color: #00f2fe;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
            padding: 0;
            transition: opacity 0.2s;
        }

        .sede-toggle-btn:hover {
            opacity: 0.85;
        }

        .sede-toggle-btn .fa-chevron-down {
            transition: transform 0.2s ease;
            font-size: 0.68rem;
        }

        .sede-toggle-btn.open .fa-chevron-down {
            transform: rotate(180deg);
        }

        .sede-dropdown-wrapper {
            display: none;
            margin-bottom: 1.25rem;
        }

        .sede-dropdown-wrapper.active {
            display: block;
        }

        .btn-submit-main {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #0066ff 0%, #00d2ff 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 153, 255, 0.5);
            transition: all 0.25s ease;
            margin-bottom: 1rem;
        }

        .btn-submit-main:hover {
            box-shadow: 0 14px 30px -5px rgba(0, 210, 255, 0.65);
            transform: translateY(-2px);
        }

        .btn-submit-main:active {
            transform: translateY(0);
        }

        .tls-banner {
            background: #030816;
            border: 1px solid var(--sispam-border-input);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.74rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .tls-banner svg, .tls-banner i {
            color: #10b981;
            flex-shrink: 0;
        }

        .demo-section {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 1.25rem;
        }

        .demo-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.65rem;
        }

        .demo-card {
            background: #030816;
            border: 1px solid var(--sispam-border-input);
            border-radius: 10px;
            padding: 0.65rem 0.85rem;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .demo-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
            background: #06112a;
        }

        .demo-role {
            font-size: 0.76rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .demo-role.dispensador { color: #60a5fa; }
        .demo-role.regente { color: #67e8f9; }
        .demo-role.orientador { color: #34d399; }
        .demo-role.alistador { color: #fbbf24; }
        .demo-role.entregador { color: #f472b6; }

        .demo-user {
            font-size: 0.68rem;
            color: #64748b;
            font-family: 'JetBrains Mono', monospace;
            display: block;
            margin-top: 0.15rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            .sispam-main-card {
                grid-template-columns: 1fr;
            }

            .left-panel {
                padding: 2.5rem 1.75rem;
                border-right: none;
                border-bottom: 1px solid rgba(56, 189, 248, 0.12);
            }

            .right-panel {
                padding: 2.5rem 1.75rem;
            }

            .brand-title {
                font-size: 2.25rem;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="sispam-main-card">
    <div class="left-panel">
        <div>
            <div class="brand-header">
                <div class="brand-logo-badge">
                    <img src="{{ asset('assets/img/logo_sispam.jpg') }}" alt="SISPAM" class="logo-image">
                </div>
                <div>
                    <div class="brand-title">SISPAM</div>
                    <div class="brand-subtitle">Gestión Farmacéutica</div>
                </div>
            </div>

            <p class="brand-desc">
                Plataforma integral para admisión de pacientes, transcripción de fórmulas, alistamiento de medicamentos y entrega con firma digital — con trazabilidad completa en cada etapa.
            </p>

            <div class="modules-grid">
                <div class="module-item">
                    <div class="module-icon-box icon-shield"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="module-info">
                        <h4>Admisión Segura</h4>
                        <p>Registro de pacientes con validación de documentos RIPS/SGSSS.</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="module-icon-box icon-file"><i class="fa-solid fa-file-signature"></i></div>
                    <div class="module-info">
                        <h4>Transcripción Digital</h4>
                        <p>Verificación de stock y control de concurrencia por registro.</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="module-icon-box icon-box"><i class="fa-solid fa-boxes-packing"></i></div>
                    <div class="module-info">
                        <h4>Alistamiento</h4>
                        <p>Picking semaforizado con asignación equitativa de ventanilla.</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="module-icon-box icon-signature"><i class="fa-solid fa-signature"></i></div>
                    <div class="module-info">
                        <h4>Entrega &amp; Firma</h4>
                        <p>Acta de entrega con firma digital y registro fotográfico.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="left-footer">
            <span class="status-pill"><span class="status-dot"></span> Sistema Operativo</span>
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        </div>
    </div>

    <div class="right-panel">
        <div>
            <h1 class="form-title">Bienvenido</h1>
            <p class="form-subtitle">Ingrese sus credenciales para acceder al sistema</p>

            @if ($errors->any())
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" id="formLogin">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario</label>
                    <div class="input-box">
                        <span class="input-icon-left"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" class="custom-input" placeholder="Ej: admin, orientador..." required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-box">
                        <span class="input-icon-left"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="custom-input" placeholder="••••••••" required style="padding-right: 2.85rem;">
                        <button type="button" class="btn-toggle-eye" id="btnToggleEye" title="Mostrar/ocultar contraseña">
                            <i class="fa-solid fa-eye" id="iconToggleEye"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="sede-toggle-btn" id="btnSedeToggle">
                    <i class="fa-solid fa-hospital"></i> Seleccionar sede de atención (opcional)
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="sede-dropdown-wrapper" id="sedeDropdownWrapper">
                    <div class="input-box">
                        <span class="input-icon-left"><i class="fa-solid fa-map-pin"></i></span>
                        <select name="sede_id" class="custom-input">
                            <option value="">-- Todas las sedes --</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre_sede }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="forgot-link" onclick="alert('Contacte al administrador del sistema para restablecer su contraseña.')">¿Olvidó su contraseña?</button>
                </div>

                <button type="submit" class="btn-submit-main">
                    <i class="fa-solid fa-right-to-bracket"></i> Acceder
                </button>
            </form>

            <div class="tls-banner">
                <i class="fa-solid fa-shield-halved"></i> Conexión cifrada SSL/TLS
            </div>
        </div>

        <div class="demo-section">
            <div class="demo-title"><i class="fa-solid fa-flask"></i> Accesos de Prueba</div>
            <div class="demo-grid">
                <button type="button" class="demo-card" onclick="autocompletar('admin')">
                    <div class="demo-role dispensador"><i class="fa-solid fa-user-shield"></i> Admin</div>
                    <span class="demo-user">admin</span>
                </button>
                <button type="button" class="demo-card" onclick="autocompletar('orientador')">
                    <div class="demo-role orientador"><i class="fa-solid fa-user-plus"></i> Orientador</div>
                    <span class="demo-user">orientador</span>
                </button>
                <button type="button" class="demo-card" onclick="autocompletar('transcriptor')">
                    <div class="demo-role regente"><i class="fa-solid fa-file-signature"></i> Transcripción</div>
                    <span class="demo-user">transcriptor</span>
                </button>
                <button type="button" class="demo-card" onclick="autocompletar('alistador')">
                    <div class="demo-role alistador"><i class="fa-solid fa-boxes-packing"></i> Alistamiento</div>
                    <span class="demo-user">alistador</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('btnToggleEye').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('iconToggleEye');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        icon.classList.toggle('fa-eye', showing);
        icon.classList.toggle('fa-eye-slash', !showing);
    });

    document.getElementById('btnSedeToggle').addEventListener('click', function () {
        this.classList.toggle('open');
        document.getElementById('sedeDropdownWrapper').classList.toggle('active');
    });

    function autocompletar(usuario) {
        document.getElementById('usuario').value = usuario;
        document.getElementById('password').value = 'admin123';
    }
</script>
</body>
</html>
