@php
    $paciente = $ingreso->paciente;
    $existeArchivo = fn (?string $ruta) => $ruta && file_exists(public_path($ruta));
    $tienePdfTranscripcion = $existeArchivo($ingreso->pdf_transcripcion_url);
    $tienePdfAlistamiento = $existeArchivo($ingreso->pdf_alistamiento);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Entrega Firmada - {{ $ingreso->ticket_numero }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #0f172a;
        }
        .acta-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 20px auto;
            padding: 35px;
        }
        .firma-box {
            border: 2px dashed #94a3b8;
            border-radius: 10px;
            background: #f8fafc;
            padding: 15px;
            text-align: center;
            max-width: 350px;
            margin: 0 auto;
        }
        .firma-img { max-height: 120px; width: auto; }
        .pdf-page-canvas {
            display: block;
            margin: 0 auto 20px auto;
            max-width: 100%;
            height: auto;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff; }
            .acta-card { box-shadow: none; max-width: 100%; margin: 0; padding: 0; }
            .pdf-embed-container { page-break-before: always; }
            .pdf-page-canvas {
                page-break-inside: avoid;
                page-break-after: always;
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="container py-3">
    <div class="no-print d-flex justify-content-between align-items-center mb-3 mx-auto" style="max-width: 900px;">
        <a href="{{ route('entrega.index') }}" class="btn btn-outline-secondary fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a Entrega
        </a>
        <button class="btn btn-success btn-lg fw-bold shadow-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> Imprimir Acta + PDFs de Transcripción &amp; Alistamiento
        </button>
    </div>

    <div class="acta-card">
        <div class="row align-items-center border-bottom pb-3 mb-4">
            <div class="col-md-3 text-center text-md-start">
                @if ($existeArchivo($config->logo_url))
                    <img src="{{ asset($config->logo_url) }}" alt="Logo" style="max-height: 70px;">
                @else
                    <i class="fa-solid fa-prescription-bottle-medical fs-1 text-primary"></i>
                @endif
            </div>
            <div class="col-md-6 text-center">
                <h4 class="fw-bold mb-1 text-uppercase text-primary">{{ $config->razon_social }}</h4>
                <div class="small text-muted">NIT: {{ $config->nit }} | {{ $config->direccion }}</div>
                <div class="small text-muted">Tel: {{ $config->telefono }} | {{ $config->email }}</div>
            </div>
            <div class="col-md-3 text-center text-md-end">
                <div class="p-2 border border-primary rounded bg-light">
                    <small class="text-muted d-block font-weight-bold">TIQUETE DE TURNO</small>
                    <span class="fs-4 fw-bold text-primary">{{ $ingreso->ticket_numero }}</span>
                </div>
            </div>
        </div>

        <h4 class="fw-bold text-center mb-4 text-dark text-uppercase border-bottom pb-2">
            ACTA DIGITAL DE CONFORMIDAD Y ENTREGA DE MEDICAMENTOS
        </h4>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="fw-bold text-primary mb-2 border-bottom pb-1"><i class="fa-solid fa-user me-2"></i> DATOS DEL PACIENTE</div>
                    <div><strong>Nombre Completo:</strong> {{ $paciente->nombres }} {{ $paciente->apellidos }}</div>
                    <div><strong>Documento:</strong> {{ $paciente->tipo_documento }} - {{ $paciente->numero_documento }}</div>
                    <div><strong>EPS:</strong> {{ $paciente->eps_nombre }}</div>
                    <div><strong>Teléfono:</strong> {{ $paciente->telefono ?: 'No especificado' }}</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="fw-bold text-primary mb-2 border-bottom pb-1"><i class="fa-solid fa-hospital-user me-2"></i> DETALLES DE LA ATENCIÓN</div>
                    <div><strong>Fecha de Ingreso:</strong> {{ $ingreso->fecha_ingreso?->format('d/m/Y h:i A') }}</div>
                    <div><strong>Fecha de Entrega:</strong> {{ $ingreso->updated_at?->format('d/m/Y h:i A') }}</div>
                    <div><strong>Ventanilla / Módulo:</strong> <span class="badge bg-success">{{ $ingreso->modulo_entrega_asignado ?: 'Ventanilla General' }}</span></div>
                    <div><strong>Orientador de Ingreso:</strong> {{ $ingreso->orientador?->nombre_completo }}</div>
                </div>
            </div>
        </div>

        @if ($faltantesDetalle)
            <div class="alert alert-warning border-warning mb-4 shadow-sm">
                <div class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-triangle-exclamation text-warning me-1 fs-5"></i> DETALLE DE MEDICAMENTOS Y CANTIDADES FALTANTES / ENTREGAS PARCIALES:
                </div>
                <div class="p-2 bg-white rounded border border-warning text-dark font-monospace small">
                    {!! nl2br(e($faltantesDetalle)) !!}
                </div>
                <div class="small text-muted mt-1">
                    <i class="fa-solid fa-info-circle me-1"></i> El paciente firma el presente recibo estando notificado del saldo o cantidades pendientes descritas arriba.
                </div>
            </div>
        @else
            <div class="alert alert-success border-success mb-4 shadow-sm">
                <div class="fw-bold text-success mb-0">
                    <i class="fa-solid fa-circle-check me-1"></i> ENTREGA 100% COMPLETA: Todos los medicamentos y cantidades formuladas fueron entregadas a satisfacción.
                </div>
            </div>
        @endif

        <div class="p-3 bg-light border rounded mb-4 text-muted small" style="line-height: 1.6;">
            Hago constar que he recibido los medicamentos descritos en el acta correspondiente a la presente atención, verificando cantidades alistadas, fechas de vencimiento y estado físico de los empaques de acuerdo a la normativa vigente.
        </div>

        <div class="row my-4 align-items-center">
            <div class="col-md-6 text-center">
                <div class="firma-box">
                    @if ($existeArchivo($ingreso->firma_paciente_url))
                        <img src="{{ asset($ingreso->firma_paciente_url) }}" alt="Firma Paciente" class="firma-img mb-1">
                    @else
                        <div class="py-4 text-muted small"><i class="fa-solid fa-signature fs-2 me-1 d-block mb-1"></i> FIRMA DIGITAL REGISTRADA</div>
                    @endif
                    <div class="border-top pt-1 fw-bold small text-dark">{{ $paciente->nombres }} {{ $paciente->apellidos }}</div>
                    <div class="text-muted small">{{ $paciente->tipo_documento }}: {{ $paciente->numero_documento }}</div>
                    <small class="text-success fw-semibold"><i class="fa-solid fa-shield-check me-1"></i> Firma Digitalizada y Validada</small>
                </div>
            </div>

            <div class="col-md-6 text-center">
                @if ($existeArchivo($ingreso->foto_paciente_url))
                    <div class="firma-box">
                        <img src="{{ asset($ingreso->foto_paciente_url) }}" alt="Foto Paciente" class="firma-img mb-1 rounded border shadow-sm" style="max-height: 130px; object-fit: contain;">
                        <div class="border-top pt-1 fw-bold small text-dark">REGISTRO FOTOGRÁFICO DE RECEPCIÓN</div>
                        <small class="text-muted"><i class="fa-solid fa-camera me-1"></i> Foto registrada en ventanilla</small>
                    </div>
                @else
                    <div class="border-top pt-3 text-muted small" style="max-width: 300px; margin: 0 auto;">
                        <div class="fw-bold text-dark">FIRMA Y SELLO DE FARMACIA</div>
                        <div>Servicio Farmacéutico / Entrega</div>
                        <div>{{ $config->razon_social }}</div>
                        <div class="mt-2 text-muted small">Copia expediente físico / digital</div>
                    </div>
                @endif
            </div>
        </div>

        @if ($tienePdfTranscripcion)
            <div class="pdf-embed-container mt-5 border-top pt-4">
                <h5 class="fw-bold text-primary mb-3">
                    <i class="fa-solid fa-file-pdf text-danger me-2"></i> ANEXO 1: ORDEN MÉDICA TRANSCRITA
                </h5>
                <div id="pdf-transcripcion-canvas-list" class="text-center my-3">
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Cargando páginas de transcripción...</span>
                    </div>
                </div>
            </div>
        @endif

        @if ($tienePdfAlistamiento)
            <div class="pdf-embed-container mt-5 border-top pt-4">
                <h5 class="fw-bold text-success mb-3">
                    <i class="fa-solid fa-boxes-packing text-success me-2"></i> ANEXO 2: COMPROBANTE DE EMPAQUE / ALISTAMIENTO
                </h5>
                <div id="pdf-alistamiento-canvas-list" class="text-center my-3">
                    <div class="spinner-border text-success my-4" role="status">
                        <span class="visually-hidden">Cargando páginas de alistamiento...</span>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    function renderizarPdfCompleto(pdfUrl, containerId) {
        if (!pdfUrl) return;
        const container = document.getElementById(containerId);
        if (!container) return;

        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            container.innerHTML = '';
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                pdf.getPage(pageNum).then(function(page) {
                    const scale = 1.5;
                    const viewport = page.getViewport({ scale: scale });

                    const canvas = document.createElement('canvas');
                    canvas.className = 'pdf-page-canvas border rounded mb-3';

                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    page.render({ canvasContext: context, viewport: viewport });
                    container.appendChild(canvas);
                });
            }
        }).catch(function(err) {
            console.error("Error al renderizar PDF:", err);
            container.innerHTML = `<div class="alert alert-info">Documento adjunto: <code>${pdfUrl}</code></div>`;
        });
    }

    @if ($tienePdfTranscripcion)
        renderizarPdfCompleto(@json(asset($ingreso->pdf_transcripcion_url)), 'pdf-transcripcion-canvas-list');
    @endif

    @if ($tienePdfAlistamiento)
        renderizarPdfCompleto(@json(asset($ingreso->pdf_alistamiento)), 'pdf-alistamiento-canvas-list');
    @endif
});
</script>

</body>
</html>
