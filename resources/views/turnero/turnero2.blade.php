
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - Entrega de Medicamentos - {{ $config['razon_social'] }} ({{ $nombre_sede_mostrar }})</title>
    <!-- Favicon SISPAM -->
    <link rel="icon" type="image/jpeg" href="assets/img/logo_sispam.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="assets/img/logo_sispam.jpg">
    <link rel="apple-touch-icon" href="assets/img/logo_sispam.jpg">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --savia-green: #04ac8c;
            --savia-green-dark: #007a63;
            --savia-green-light: #e6f7f4;
            --savia-dark: #132a33;
            --savia-teal: #1e4554;
            --tv-bg: #f8fafc;
            --tv-card-border: #e2e8f0;
            --tv-text-dark: #0f172a;
            --tv-text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: var(--tv-text-dark);
            min-height: 100vh;
            margin: 0;
            padding-bottom: 55px;
            overflow-x: hidden;
        }

        /* Header Institucional Corporativo Savia Salud */
        .turnero-header {
            background: #ffffff;
            border-bottom: 3.5px solid var(--savia-green);
            box-shadow: 0 4px 16px rgba(19, 42, 51, 0.07);
            color: var(--tv-text-dark);
        }

        .text-savia {
            color: var(--tv-text-dark) !important;
        }

        .bg-savia {
            background-color: var(--savia-green) !important;
            color: #ffffff !important;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Tarjetas de Turnos Compactas, Blancas y Elegantes */
        .savia-card {
            background: #ffffff;
            border: 1.5px solid var(--tv-card-border);
            border-left: 5px solid var(--savia-green);
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }

        .savia-card-active {
            border: 2px solid var(--savia-green);
            border-left: 6.5px solid var(--savia-green);
            box-shadow: 0 6px 20px rgba(4, 172, 140, 0.18);
            background: #ffffff;
            position: relative;
        }

        /* Efecto de pulso elegante en el último llamado */
        @keyframes pulseSaviaCompact {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(4, 172, 140, 0.35); }
            70% { transform: scale(1.003); box-shadow: 0 0 0 8px rgba(4, 172, 140, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(4, 172, 140, 0); }
        }

        .pulse-active {
            animation: pulseSaviaCompact 2.8s infinite;
        }

        /* Tipografía del Nombre del Paciente en Negro Puro */
        .paciente-nombre-destacado {
            color: #000000 !important;
            font-weight: 900 !important;
            font-size: clamp(1.8rem, 2.7vw, 2.55rem) !important;
            line-height: 1.12;
            letter-spacing: -0.3px;
        }

        .paciente-nombre-subtexto {
            color: #0f172a !important;
            font-weight: 800;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* Badge de Tiquete Ancho con Letras Negras de Alto Contraste */
        .badge-ticket-tv {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 2px solid var(--savia-green);
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(1.35rem, 2.1vw, 1.95rem);
            font-weight: 900;
            letter-spacing: 1.5px;
            border-radius: 8px;
            padding: 5px 18px;
            display: inline-block;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            white-space: nowrap !important;
        }

        /* Píldoras de Sede, Módulo y Prioridad con Texto Negro */
        .badge-sede-pill {
            background-color: #f8fafc;
            color: #0f172a;
            border: 1.5px solid var(--savia-green);
            font-weight: 800;
            font-size: 0.86rem;
            border-radius: 6px;
            padding: 3px 9px;
        }

        .badge-modulo-pill {
            background-color: var(--savia-green);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.86rem;
            border-radius: 6px;
            padding: 3px 9px;
            box-shadow: 0 2px 5px rgba(4, 172, 140, 0.25);
        }

        .badge-preferencial {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            font-weight: 800;
            border-radius: 6px;
            padding: 3px 9px;
            font-size: 0.86rem;
        }

        /* Video Institucional Responsive */
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #000;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 12px;
            object-fit: cover;
        }

        /* Marquesina Inferior Corporativa */
        .marquesina-savia {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #132a33 0%, #1e4554 50%, #04ac8c 100%);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 600;
            padding: 8px 0;
            box-shadow: 0 -4px 16px rgba(19, 42, 51, 0.2);
            z-index: 1050;
            overflow: hidden;
            white-space: nowrap;
        }

        .marquesina-track {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 32s linear infinite;
        }

        @keyframes marquee {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-100%, 0); }
        }

        /* Badges de Barra Superior */
        .badge-savia {
            background-color: #04ac8c;
            color: #ffffff;
            font-weight: 700;
            border-radius: 8px;
            padding: 6px 14px;
        }

        /* Overlay Modal Intermitente de Rellamado en TV */
        .overlay-rellamado-tv {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(19, 42, 51, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }

        .overlay-rellamado-tv.visible {
            opacity: 1;
            pointer-events: auto;
        }

        .card-rellamado-tv {
            background: #ffffff;
            border: 4.5px solid #d97706;
            border-radius: 24px;
            padding: 35px 45px;
            max-width: 900px;
            width: 92%;
            text-align: center;
            box-shadow: 0 16px 50px rgba(19, 42, 51, 0.35);
            animation: modalIntermitenteSavia 1.1s infinite ease-in-out;
            transform: scale(0.95);
            transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .overlay-rellamado-tv.visible .card-rellamado-tv {
            transform: scale(1);
        }

        @keyframes modalIntermitenteSavia {
            0%, 100% {
                border-color: #d97706;
                box-shadow: 0 0 35px rgba(217, 119, 6, 0.55);
            }
            50% {
                border-color: #dc2626;
                box-shadow: 0 0 55px rgba(220, 38, 38, 0.8);
            }
        }

        .badge-rellamado-pulsante {
            display: inline-block;
            background: linear-gradient(90deg, #dc2626 0%, #d97706 100%);
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 10px 28px;
            border-radius: 50px;
            box-shadow: 0 5px 16px rgba(220, 38, 38, 0.35);
            animation: pulseBadge 0.9s infinite alternate;
        }

        @keyframes pulseBadge {
            0% { transform: scale(1); }
            100% { transform: scale(1.04); }
        }

        .tiquete-rellamado-gigante {
            background: #132a33;
            color: #04ac8c;
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            font-weight: 800;
            letter-spacing: 2px;
            display: inline-block;
            padding: 8px 30px;
            border-radius: 14px;
            margin: 10px 0;
            box-shadow: 0 6px 18px rgba(19, 42, 51, 0.18);
        }

        .paciente-rellamado-gigante {
            font-size: clamp(2rem, 3.8vw, 3.2rem);
            font-weight: 900;
            line-height: 1.15;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 12px 0;
        }
    </style>
</head>
<body>

<!-- Encabezado Turnero TV (Tema Ejecutivo Corporativo Savia Salud) -->
<header class="turnero-header py-2 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        
@if (!empty($config['logo_url']) && file_exists(public_path() . '/' . $config['logo_url']))

            <img src="{{ $config['logo_url'] }}" alt="Logo" style="max-height: 48px;" class="rounded shadow-sm">
        
@else

            <div class="p-2 rounded-3 bg-savia text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                <i class="fa-solid fa-hospital fs-5"></i>
            </div>
        
@endif

        <div>
            <h4 class="fw-bold mb-0 text-dark tracking-wide" style="font-size: 1.25rem;">{{ $config['razon_social'] }}</h4>
            <div class="small d-flex align-items-center gap-2 flex-wrap mt-0">
                <span class="badge-sede-pill"><i class="fa-solid fa-location-dot text-danger me-1"></i> SEDE: {{ $nombre_sede_mostrar }}</span>
                <span class="badge bg-light text-dark border py-1 px-2 fw-bold" style="font-size: 0.8rem;"><i class="fa-solid fa-hand-holding-medical text-savia me-1"></i> EPS SAVIA SALUD</span>
                <span class="text-muted fw-bold" style="font-size: 0.82rem;">SALA DE ESPERA • ENTREGA DE MEDICAMENTOS</span>
            </div>
        </div>
    </div>
    
    <!-- Reloj Digital y Fecha -->
    <div class="text-end">
        <div id="reloj-digital" class="font-mono text-savia fw-bold" style="font-size: 1.9rem; line-height: 1.05;">00:00:00</div>
        <div id="fecha-digital" class="small text-muted text-capitalize fw-semibold" style="font-size: 0.78rem;">--</div>
    </div>
</header>

<!-- Contenido Principal: Grilla de Pacientes Llamados -->
<main class="container-fluid p-3 p-lg-4">
    <div class="w-100" style="max-width: 1700px; margin: 0 auto;">
        
        <!-- Barra Superior de Control y Estado -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-1">
            <div class="d-flex align-items-center gap-2">
                <span class="badge-savia text-uppercase fw-bold py-2 px-3 shadow-sm fs-6" id="total-llamados-badge">
                    <i class="fa-solid fa-users me-2"></i> PACIENTES EN VENTANILLA
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap" id="audio-controls-container">
                <button class="btn btn-sm btn-warning text-dark fw-bold px-3 py-2 shadow-sm rounded-3" id="btn-audio" onclick="habilitarAudio()">
                    <i class="fa-solid fa-volume-high me-1"></i> Activar Voz de Llamados
                </button>
                <button class="btn btn-sm btn-outline-light fw-bold px-3 py-2 shadow-sm rounded-3" id="btn-rellamar" onclick="reLlamarActual()" style="display: none;">
                    <i class="fa-solid fa-rotate me-1"></i> Re-llamar
                </button>
                <span class="small text-white-50 d-none d-md-inline" id="audio-status-msg" style="font-size: 0.8rem;">
                    (Haga clic para habilitar voz)
                </span>
            </div>
        </div>

        <!-- Contenedor Dinámico: Grilla de Tarjetas de Turnos -->
        <div class="row g-3 g-xl-4 justify-content-center" id="contenedor-grilla-turnos">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-accent mb-2" role="status"></div>
                <div class="text-white-50">Cargando turnos en llamado...</div>
            </div>
        </div>

        <!-- Tarjeta Video Institucional (Oculta pero preservada para cuando se requiera) -->
        <div id="seccion-video-turnero" class="savia-card p-3 d-none flex-column mt-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold text-white small d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-play text-accent"></i>
                    <span>Información y Promoción en Salud</span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick="configurarVideo()" title="Cambiar enlace de video" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-gear"></i>
                </button>
            </div>
            <div class="video-container shadow">
                <iframe id="iframeVideoInstitucional" 
                        src="https://www.youtube.com/embed/videoseries?list=PL_Xv_X_default&autoplay=1&mute=1&loop=1&controls=0&modestbranding=1" 
                        title="Video Institucional" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
            <div class="text-white-50 text-center mt-2" style="font-size: 0.75rem;">
                <i class="fa-solid fa-shield-heart text-accent me-1"></i> EPS Savia Salud • Cuidamos tu bienestar
            </div>
        </div>

    </div>
</main>

<!-- Overlay Modal Intermitente de Rellamado a Ventanilla (Solo Pitido + Parpadeo) -->
<div id="overlay-rellamado-tv" class="overlay-rellamado-tv">
    <div class="card-rellamado-tv">
        <div class="badge-rellamado-pulsante">
            <i class="fa-solid fa-bell fa-shake me-2"></i> ¡ATENCIÓN • RELLAMADO A VENTANILLA!
        </div>
        <div class="mt-3">
            <span class="text-white-50 fs-5 fw-bold text-uppercase">TIQUETE ASIGNADO:</span><br>
            <div class="tiquete-rellamado-gigante font-mono" id="rellamado-modal-ticket">--</div>
        </div>
        <div class="mt-2">
            <span class="text-accent fs-6 fw-bold text-uppercase"><i class="fa-solid fa-user-check me-1"></i> LLAMADO A ENTREGA:</span>
            <div class="paciente-rellamado-gigante" id="rellamado-modal-nombre">--</div>
        </div>
        <div class="mt-3 text-white fs-5 fw-semibold">
            <i class="fa-solid fa-hand-point-right text-warning me-2"></i> Por favor acérquese al módulo de entrega de medicamentos
        </div>
    </div>
</div>

<!-- Marquesina Informativa Inferior -->
<footer class="marquesina-savia">
    <div class="marquesina-track" id="marquesina-content">
        <i class="fa-solid fa-circle-info text-warning me-2"></i>
        {{ $config['marquesina_turnero'] ?? 'Bienvenido a la Sala de Espera de Savia Salud EPS. Por favor permanezca atento a su llamado en pantalla. Recuerde presentar su documento de identidad original al momento de la entrega de medicamentos.' }}
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <i class="fa-solid fa-notes-medical text-accent me-2"></i>
        Verifique sus medicamentos, dosis y fechas de vencimiento antes de retirarse del punto de atención.
    </div>
</footer>

<!-- Modal Configuración Rápida de Video -->
<div class="modal fade" id="modalConfigVideo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title fw-bold small"><i class="fa-solid fa-video me-1 text-accent"></i> URL de Video Institucional</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <label class="form-label text-white-50 small">Enlace YouTube o MP4:</label>
                <input type="text" id="inputUrlVideo" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="https://www.youtube.com/embed/...">
                <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Se guardará localmente en el navegador de este televisor.</small>
            </div>
            <div class="modal-footer border-secondary py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-success fw-bold px-3" onclick="guardarUrlVideo()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/turnero_speech.js?v={{ time() }}"></script>
<script>
let lastCalledTicketId = null;
let currentTicketObj = null;
let audioEnabled = false;
let isInitialLoad = true;
let calledTicketIds = new Set();
let lastProcessedRellamadoKey = null;
let timerOcultarRellamado = null;

function ejecutarAlertaRellamadoTV(info) {
    if (!info) return;

    // 1. Alerta sonora: Solo pitido/chime (Sin voz)
    if (window.turneroSpeech) {
        window.turneroSpeech.playAlertBeep();
    }

    // 2. Mostrar Modal / Overlay intermitente con animación
    const overlay = document.getElementById('overlay-rellamado-tv');
    const ticketEl = document.getElementById('rellamado-modal-ticket');
    const nombreEl = document.getElementById('rellamado-modal-nombre');

    if (ticketEl) ticketEl.textContent = info.ticket_numero || '--';
    if (nombreEl) {
        const nom = (info.nombre_completo || info.nombre_habeas || 'PACIENTE').toUpperCase();
        nombreEl.textContent = nom;
    }

    if (overlay) {
        overlay.classList.add('visible');
    }

    // 3. Durar unos segundos (6.5s) y cerrarse automáticamente
    if (timerOcultarRellamado) {
        clearTimeout(timerOcultarRellamado);
    }
    timerOcultarRellamado = setTimeout(() => {
        if (overlay) {
            overlay.classList.remove('visible');
        }
    }, 6500);
}

// Manejo de Video Institucional (Persistente en LocalStorage)
const DEFAULT_VIDEO_URL = "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&loop=1&playlist=dQw4w9WgXcQ&controls=0";

function cargarVideoGuardado() {
    const urlGuardada = localStorage.getItem('savia_turnero_video_url') || "https://www.youtube-nocookie.com/embed/live_stream?channel=SaviaSaludEPS&autoplay=1&mute=1&loop=1";
    const iframe = document.getElementById('iframeVideoInstitucional');
    if (iframe) {
        iframe.src = urlGuardada;
    }
}

function configurarVideo() {
    const input = document.getElementById('inputUrlVideo');
    input.value = localStorage.getItem('savia_turnero_video_url') || '';
    const modalEl = document.getElementById('modalConfigVideo');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function guardarUrlVideo() {
    const input = document.getElementById('inputUrlVideo');
    let url = input.value.trim();
    if (url) {
        if (url.includes('youtube.com/watch?v=')) {
            const vId = url.split('watch?v=')[1].split('&')[0];
            url = `https://www.youtube.com/embed/${vId}?autoplay=1&mute=1&loop=1&playlist=${vId}&controls=0`;
        } else if (url.includes('youtu.be/')) {
            const vId = url.split('youtu.be/')[1].split('?')[0];
            url = `https://www.youtube.com/embed/${vId}?autoplay=1&mute=1&loop=1&playlist=${vId}&controls=0`;
        }
        localStorage.setItem('savia_turnero_video_url', url);
    } else {
        localStorage.removeItem('savia_turnero_video_url');
    }
    cargarVideoGuardado();
    bootstrap.Modal.getInstance(document.getElementById('modalConfigVideo')).hide();
}

document.addEventListener('DOMContentLoaded', () => {
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    cargarVideoGuardado();

    actualizarTurnero2();
    setInterval(actualizarTurnero2, 2500);
});

function habilitarAudio() {
    audioEnabled = true;
    if (window.turneroSpeech) {
        window.turneroSpeech.unlockAudio();
    }
    const btnAudio = document.getElementById('btn-audio');
    const btnRellamar = document.getElementById('btn-rellamar');
    const statusMsg = document.getElementById('audio-status-msg');

    if (btnAudio) {
        btnAudio.className = 'btn btn-sm btn-success fw-bold px-3 py-2 shadow-sm rounded-3';
        btnAudio.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i> Audio Activado';
    }
    if (btnRellamar) {
        btnRellamar.style.display = 'inline-block';
    }
    if (statusMsg) {
        statusMsg.innerHTML = '<span class="text-accent fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> Audio activo</span>';
    }

    if (currentTicketObj) {
        lastCalledTicketId = currentTicketObj.id;
        calledTicketIds.add(currentTicketObj.id);
        const nombreHablado = (currentTicketObj.nombre_completo || currentTicketObj.nombre_habeas || '').trim();
        window.turneroSpeech.speak(nombreHablado, '');
    } else {
        window.turneroSpeech.playChime();
    }
}

function reLlamarActual() {
    if (currentTicketObj) {
        const nombreHablado = (currentTicketObj.nombre_completo || currentTicketObj.nombre_habeas || '').trim();
        window.turneroSpeech.speak(nombreHablado, '');
    } else {
        alert('No hay ningún paciente en cola para llamar.');
    }
}

function reLlamarTurnoEspecifico(nombre, modulo) {
    if (window.turneroSpeech) {
        window.turneroSpeech.speak(nombre, modulo || 'Ventanilla');
    }
}

function actualizarReloj() {
    const now = new Date();
    document.getElementById('reloj-digital').innerText = now.toLocaleTimeString('es-CO');
    document.getElementById('fecha-digital').innerText = now.toLocaleDateString('es-CO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}

const urlParamsSede = new URLSearchParams(window.location.search);
let activeSedeId = urlParamsSede.get('sede_id') || localStorage.getItem('savia_turnero_sede_id') || '{{ $active_sede_id }}';
if (!urlParamsSede.has('sede_id')) {
    urlParamsSede.set('sede_id', activeSedeId);
    window.history.replaceState({}, '', window.location.pathname + '?' + urlParamsSede.toString());
}
localStorage.setItem('savia_turnero_sede_id', activeSedeId);

function actualizarTurnero2() {
    fetch('{{ route('api.turnero.data') }}?type=2&sede_id=' + encodeURIComponent(activeSedeId))
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('contenedor-grilla-turnos');
            const badgeTotal = document.getElementById('total-llamados-badge');
            if (!container) return;

            // Detección y activación de Rellamado en pantalla TV (disparo inmediato en el 1er clic)
            if (data.ultimo_rellamado && data.ultimo_rellamado.id) {
                const rellamadoKey = `${data.ultimo_rellamado.id}_${data.ultimo_rellamado.fecha_rellamado || ''}_${data.ultimo_rellamado.contador_rellamados || 0}`;
                if (!isInitialLoad && lastProcessedRellamadoKey !== rellamadoKey) {
                    ejecutarAlertaRellamadoTV(data.ultimo_rellamado);
                }
                lastProcessedRellamadoKey = rellamadoKey;
            }

            if (data.turnos && data.turnos.length > 0) {
                const total = data.turnos.length;
                const primerTurno = data.turnos[0];
                currentTicketObj = primerTurno;

                if (badgeTotal) {
                    badgeTotal.innerHTML = `<i class="fa-solid fa-users me-2"></i> ${total} PACIENTE${total > 1 ? 'S' : ''} EN VENTANILLA`;
                }

                // Control inteligente de llamado por voz:
                // En la carga inicial se registran los pacientes ya visibles para no re-anunciarlos
                if (isInitialLoad) {
                    data.turnos.forEach(t => calledTicketIds.add(t.id));
                    lastCalledTicketId = primerTurno.id;
                } else {
                    // Solo disparar la voz si es un paciente NUEVO que no haya sido llamado antes
                    if (!calledTicketIds.has(primerTurno.id)) {
                        calledTicketIds.add(primerTurno.id);
                        lastCalledTicketId = primerTurno.id;
                        if (audioEnabled) {
                            const nombreHablado = (primerTurno.nombre_completo || primerTurno.nombre_habeas || '').trim();
                            window.turneroSpeech.speak(nombreHablado, '');
                        }
                    }
                }

                let html = '';
                data.turnos.forEach((row, idx) => {
                    const esUltimo = (idx === 0);
                    const cardClass = esUltimo ? 'savia-card savia-card-active pulse-active' : 'savia-card';
                    const badgeTop = esUltimo
                        ? `<span class="badge bg-danger text-white text-uppercase px-3 py-2 fs-6 fw-bold shadow-sm"><i class="fa-solid fa-bell fa-shake me-1"></i> ÚLTIMO LLAMADO</span>`
                        : `<span class="badge bg-secondary text-white text-uppercase px-3 py-2 fs-6 fw-bold"><i class="fa-solid fa-bullhorn me-1"></i> LLAMADO ACTIVO</span>`;

                    const nombreCompleto = (row.nombre_completo || row.nombre_habeas || '').trim();
                    const nombreUpper = escapeHtml(nombreCompleto.toUpperCase());
                    const ticketNum = escapeHtml(row.ticket_numero || '--');
                    const moduloNombre = escapeHtml((row.modulo_entrega_asignado || 'VENTANILLA').toUpperCase());
                    const sedeNombre = escapeHtml((row.nombre_sede || data.sede_nombre || 'SEDE PRINCIPAL').toUpperCase());

                    const esPreferencial = (row.prioridad && row.prioridad !== 'NORMAL');
                    const badgePrio = esPreferencial 
                        ? `<span class="badge bg-warning text-dark fs-6 px-3 py-2 fw-bold shadow-sm"><i class="fa-solid fa-star me-1"></i> PREFERENCIAL</span>`
                        : '';

                    html += `
                        <div class="col-12">
                            <div class="${cardClass} p-2 p-md-3 px-3 px-md-4 mb-2">
                                <div class="row align-items-center g-2 g-md-3">
                                    
                                    <!-- Columna 1: Badges, Sede, Módulo y Tiquete (Más Ancho para evitar salto de línea) -->
                                    <div class="col-12 col-md-6 col-lg-6 col-xl-6 text-center text-md-start">
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-1 gap-md-2 mb-1 flex-wrap">
                                            ${badgeTop}
                                            <span class="badge-sede-pill shadow-sm">
                                                <i class="fa-solid fa-location-dot text-danger me-1"></i> ${sedeNombre}
                                            </span>
                                            <span class="badge-modulo-pill shadow-sm">
                                                <i class="fa-solid fa-desktop me-1"></i> ${moduloNombre}
                                            </span>
                                            ${badgePrio}
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mt-1">
                                            <span class="badge-ticket-tv">
                                                TIQUETE: ${ticketNum}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Columna 2: Nombre Completo del Paciente en Negro Ejecutivo -->
                                    <div class="col-12 col-md-6 col-lg-6 col-xl-6 text-center text-md-start ps-md-4" style="border-left: 2px solid #e2e8f0;">
                                        <div class="paciente-nombre-subtexto mb-0" style="font-size: 0.82rem;">
                                            <i class="fa-solid fa-user-check me-1" style="color: #04ac8c;"></i> LLAMADO A ENTREGA:
                                        </div>
                                        <div class="paciente-nombre-destacado">
                                            ${nombreUpper}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            } else {
                currentTicketObj = null;
                if (badgeTotal) {
                    badgeTotal.innerHTML = `<i class="fa-solid fa-users me-2"></i> 0 PACIENTES EN VENTANILLA`;
                }

                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="savia-card p-5 d-inline-block shadow-lg text-center" style="max-width: 700px;">
                            <i class="fa-solid fa-clipboard-check fa-4x mb-3 text-primary opacity-50"></i>
                            <h3 class="text-dark fw-bold mb-2">SALA DE ESPERA • ENTREGA DE MEDICAMENTOS</h3>
                            <p class="text-muted mb-0 fs-5">Esperando próximo llamado a ventanilla...</p>
                        </div>
                    </div>
                `;
            }
            isInitialLoad = false;
        })
        .catch(err => {
            console.error("Error al actualizar turnero 2:", err);
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>
</body>
</html>
