
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden Unificada + Tiquete - {{ $ingreso['ticket_numero'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- PDF.js CDN para renderizado directo de páginas PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #0f172a;
        }
        .print-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            max-width: 1100px;
            margin: 15px auto;
            padding: 18px 22px;
            transition: max-width 0.2s ease;
        }
        .print-table-wrapper {
            width: 100%;
            border-collapse: collapse;
        }
        .ticket-header-band {
            background: #f8fafc;
            border: 2px solid #0284c7;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 12px;
        }
        .logo-print {
            max-height: 42px;
        }
        .empresa-titulo {
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1.15;
        }
        .sede-subtitulo {
            font-size: 0.8rem;
        }
        .paciente-nombre {
            font-size: 1.22rem;
            font-weight: 800;
            line-height: 1.15;
            color: #0f172a;
        }
        .paciente-detalles {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.2;
        }
        .tiquete-label {
            font-size: 0.72rem;
            letter-spacing: 0.5px;
        }
        .ticket-number-badge {
            background-color: #0284c7;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 6px;
            letter-spacing: 1px;
            display: inline-block;
            line-height: 1.2;
        }
        .seccion-header-titulo {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .formula-zoom-container {
            width: 100%;
            overflow-x: auto;
            text-align: center;
        }
        .pdf-page-canvas {
            display: block;
            margin: 0 auto 12px auto;
            width: 100%;
            max-width: 100%;
            height: auto;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            transition: transform 0.2s ease;
        }
        .formula-img-print {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            transition: transform 0.2s ease;
        }

        /* CONFIGURACIÓN EXCLUSIVA DE IMPRESIÓN (ENCABEZADO REPETIDO EN CADA HOJA) */
        @media print {
            @page {
                size: letter portrait;
                margin: 4mm 5mm 4mm 5mm;
            }
            .no-print, .seccion-header-titulo {
                display: none !important;
            }
            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                color: #000000 !important;
                width: 100% !important;
            }
            .container, .print-card {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            thead {
                display: table-header-group !important; /* Fuerza la repetición del encabezado en TODAS las páginas */
            }
            tbody {
                display: table-row-group !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
            .ticket-header-band {
                border: 2px solid #000000 !important;
                background: #f8fafc !important;
                padding: 4px 8px !important;
                margin-bottom: 4px !important;
                border-radius: 6px !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .logo-print {
                max-height: 36px !important;
            }
            .empresa-titulo {
                font-size: 0.85rem !important;
            }
            .sede-subtitulo {
                font-size: 0.78rem !important;
            }
            .paciente-nombre {
                font-size: 1.18rem !important;
                font-weight: 800 !important;
                line-height: 1.15 !important;
                color: #000000 !important;
            }
            .paciente-detalles {
                font-size: 0.82rem !important;
                color: #222222 !important;
                line-height: 1.1 !important;
            }
            .tiquete-label {
                font-size: 0.72rem !important;
            }
            .ticket-number-badge {
                background-color: #000000 !important;
                color: #ffffff !important;
                font-size: 1.45rem !important;
                font-weight: 800 !important;
                padding: 2px 10px !important;
                border-radius: 4px !important;
                line-height: 1.15 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .formula-zoom-container, .formula-render-area, .pdf-canvas-container {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }
            .pdf-page-canvas, .formula-img-print {
                display: block !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                max-height: none !important;
                object-fit: contain !important;
                border: none !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container py-2">
    <!-- Barra de Controles y Zoom en Pantalla -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3 mx-auto flex-wrap gap-2" style="max-width: 1100px;">
        <a href="{{ route('alistamiento.index') }}" class="btn btn-outline-secondary fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
        
        <!-- Controles de Tamaño / Zoom -->
        <div class="btn-group shadow-sm" role="group">
            <button type="button" class="btn btn-light border fw-semibold" onclick="cambiarZoom(-0.15)" title="Reducir">
                <i class="fa-solid fa-magnifying-glass-minus me-1"></i> Reducir
            </button>
            <button type="button" class="btn btn-light border fw-semibold" onclick="resetZoom()" title="Restablecer tamaño">
                <span id="zoom-level-badge">100%</span>
            </button>
            <button type="button" class="btn btn-light border fw-semibold" onclick="cambiarZoom(0.15)" title="Agrandar">
                <i class="fa-solid fa-magnifying-glass-plus me-1 text-primary"></i> Agrandar
            </button>
            <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleAnchoCompleto()" title="Alternar Ancho Completo">
                <i class="fa-solid fa-arrows-left-right me-1"></i> Ancho Total
            </button>
        </div>

        <button class="btn btn-primary btn-lg fw-bold shadow-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> Imprimir Orden Unificada
        </button>
    </div>

    <div class="print-card" id="print-card-container">
        <!-- Tabla con thead que el navegador replica automáticamente en cada hoja física de impresión -->
        <table class="print-table-wrapper w-100 border-0">
            <thead>
                <tr>
                    <th class="p-0 border-0 fw-normal text-start">
                        <!-- FRANJA DEL TIQUETE DE TURNO E INFORMACIÓN DEL PACIENTE -->
                        <div class="ticket-header-band">
                            <div class="row align-items-center">
                                <div class="col-md-3 col-3 text-start mb-0">
                                    <div class="d-flex align-items-center gap-2">
                                        
@if (!empty($config['logo_url']) && file_exists(public_path() . '/' . $config['logo_url']))

                                            <img src="{{ $config['logo_url'] }}" alt="Logo" class="logo-print">
                                        
@else

                                            <i class="fa-solid fa-prescription-bottle-medical fs-2 text-primary"></i>
                                        
@endif

                                        <div>
                                            <div class="empresa-titulo text-dark">{{ $config['razon_social'] }}</div>
                                            <div class="sede-subtitulo text-primary fw-semibold"><i class="fa-solid fa-location-dot text-warning me-1"></i> {{ $nombre_sede }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-6 text-center border-start border-end px-2 mb-0">
                                    <div class="paciente-nombre text-uppercase">{{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}</div>
                                    <div class="paciente-detalles d-flex align-items-center justify-content-center gap-1 flex-wrap" style="font-size: 0.76rem;">
                                        <span><strong>Doc:</strong> {{ $ingreso['tipo_documento'] . ' ' . $ingreso['numero_documento'] }}</span>
                                        <span>•</span>
                                        <span><strong>EPS:</strong> {{ $ingreso['eps_nombre'] }}</span>
                                        <span>•</span>
                                        <span>{!! get_prioridad_badge($ingreso['prioridad'] ?? 'NORMAL') !!}</span>
                                        <span>•</span>
                                        <span><i class="fa-solid fa-clock me-1"></i> {{ date('d/m/Y h:i A', strtotime($ingreso['fecha_ingreso'])) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap text-muted mt-1" style="font-size: 0.72rem;">
                                        
@if (!empty($ingreso['ips_remite']) && $ingreso['ips_remite'] !== 'NO ESPECIFICADA')

                                            <span><strong>IPS:</strong> {{ $ingreso['ips_remite'] }}</span>
                                            <span>•</span>
                                        
@endif

                                        @if (!empty($ingreso['medico_nombre']) && $ingreso['medico_nombre'] !== 'NO REGISTRA')

                                            <span><strong>Médico:</strong> {{ $ingreso['medico_nombre'] }}</span>
                                            <span>•</span>
                                        
@endif

                                        @if (!empty($ingreso['diagnostico_cie10']) && $ingreso['diagnostico_cie10'] !== 'NO REGISTRA')

                                            <span><strong>CIE-10:</strong> {{ $ingreso['diagnostico_cie10'] }}</span>
                                            <span>•</span>
                                        
@endif

                                        @if (!empty($ingreso['numero_mipres']) && $ingreso['numero_mipres'] !== 'NO REGISTRA')

                                            <span><strong>MIPRES:</strong> {{ $ingreso['numero_mipres'] }}</span>
                                        
@endif

                                    </div>
                                </div>

                                <div class="col-md-3 col-3 text-end mb-0">
                                    <div class="d-inline-flex flex-column align-items-center">
                                        <span class="tiquete-label text-muted font-monospace fw-bold">TIQUETE DE TURNO</span>
                                        <div class="ticket-number-badge">{{ $ingreso['ticket_numero'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="p-0 border-0">
                        <!-- SECCIÓN: FÓRMULA MÉDICA TRANSCRITA (TAMAÑO AMPLIO) -->
                        <div class="formula-render-area">
                            <div class="seccion-header-titulo d-flex align-items-center justify-content-between w-100 no-print">
                                <span><i class="fa-solid fa-file-medical text-primary me-2"></i> FÓRMULA MÉDICA TRANSCRITA PARA ALISTAMIENTO</span>
                                <small class="text-muted font-monospace">Tiquete: {{ $ingreso['ticket_numero'] }}</small>
                            </div>

                            
@php
$db = \Illuminate\Support\Facades\DB::connection()->getPdo();
                                $stmtIA = $db->prepare("SELECT * FROM ingreso_formulas_ia WHERE ingreso_id = :id ORDER BY id DESC LIMIT 1");
                                $stmtIA->execute([':id' => $id]);
                                $iaRow = $stmtIA->fetch(PDO::FETCH_ASSOC);

                                $medsAlistar = [];
                                $medsFaltantes = [];
                                $obsAlistamiento = '';

                                if ($iaRow) {
                                    $iaData = json_decode($iaRow['datos_extraidos_json'] ?? '{}', true) ?: [];
                                    if (!empty($iaData['alistamiento']['items_dispensar'])) {
                                        $medsAlistar = $iaData['alistamiento']['items_dispensar'];
                                    }
                                    if (!empty($iaData['alistamiento']['items_faltantes'])) {
                                        $medsFaltantes = $iaData['alistamiento']['items_faltantes'];
                                    }
                                    $obsAlistamiento = $iaData['alistamiento']['observaciones'] ?? '';

                                    if (empty($medsAlistar) && !empty($iaData['medicamentos'])) {
                                        foreach ($iaData['medicamentos'] as $m) {
                                            $medsAlistar[] = [
                                                'nombre_medicamento' => $m['nombre_medicamento'] ?? $m['descripcion'] ?? 'MEDICAMENTO',
                                                'cantidad_entregar'  => $m['cantidad_dispensar'] ?? $m['cantidad_solicitada'] ?? 30,
                                                'numero_lote'        => 'FEFO',
                                                'posologia'          => $m['posologia'] ?? $m['dosis'] ?? ''
                                            ];
                                        }
                                    }
                                }

                                $archivosTrans = !empty($ingreso['transcripciones_archivos']) ? $ingreso['transcripciones_archivos'] : [];
                                if (empty($archivosTrans) && !empty($ingreso['pdf_transcripcion_url'])) {
                                    $archivosTrans = [['url' => $ingreso['pdf_transcripcion_url'], 'nombre' => 'Orden Transcrita']];
                                }
                                if (empty($archivosTrans)) {
                                    $stmtDocsU = $db->prepare("SELECT ruta_archivo, nombre_original FROM ingreso_documentos WHERE ingreso_id = :id AND tipo_documento IN ('ORDEN_MEDICA', 'FORMULA', 'MIPRES') ORDER BY id DESC");
                                    $stmtDocsU->execute([':id' => $id]);
                                    $docsFound = $stmtDocsU->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($docsFound as $df) {
                                        if (!empty($df['ruta_archivo'])) {
                                            $archivosTrans[] = ['url' => $df['ruta_archivo'], 'nombre' => $df['nombre_original'] ?: 'Fórmula Médica'];
                                        }
                                    }
                                }
@endphp


                            @if (!empty($medsAlistar) || !empty($medsFaltantes))

                                <div class="mb-3 p-3 bg-white rounded border border-primary border-opacity-50 shadow-sm" style="page-break-inside: avoid;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-primary border-opacity-25">
                                        <span class="fw-bold text-primary fs-6">
                                            <i class="fa-solid fa-boxes-packing me-1"></i> LISTA DE ALISTAMIENTO FÍSICO EN BODEGA (PICKING)
                                        </span>
                                        <span class="badge bg-primary text-white font-monospace">{{ count($medsAlistar) }} medicamento(s) a alistar</span>
                                    </div>

                                    <table class="table table-sm table-bordered align-middle mb-2" style="font-size: 0.88rem;">
                                        <thead class="table-dark text-uppercase" style="font-size: 0.78rem;">
                                            <tr>
                                                <th class="text-center" style="width: 45px;">Check</th>
                                                <th class="text-center" style="width: 35px;">#</th>
                                                <th>Medicamento / Descripción</th>
                                                <th style="width: 30%;">Lote & Laboratorio / Marca</th>
                                                <th>Posología / Indicación</th>
                                                <th class="text-center" style="width: 90px;">Cant. Alistar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            
@foreach ($medsAlistar as $iM => $m)
@php
$esMulti = (!empty($m['es_multimes']) || (isset($m['cantidad_prescrita']) && intval($m['cantidad_prescrita']) > intval($m['cantidad_entregar'])) || (isset($m['total_periodos']) && intval($m['total_periodos']) > 1));
$labMarca = $m['fabricante_laboratorio'] ?? ($m['laboratorio'] ?? '');
@endphp

                                                <tr>
                                                    <td class="text-center py-2">
                                                        <div style="width: 20px; height: 20px; border: 2px solid #000; border-radius: 3px; margin: 0 auto;"></div>
                                                    </td>
                                                    <td class="text-center fw-bold text-muted">{{ $iM + 1 }}</td>
                                                    <td>
                                                        <strong class="text-dark">{{ $m['nombre_medicamento'] ?? '' }}</strong>
                                                        
@if ($esMulti)

                                                            <span class="badge bg-info bg-opacity-10 text-primary border border-info border-opacity-25 small ms-1">
                                                                <i class="fa-solid fa-calendar-days me-1"></i>Multimes: Cuota 1
                                                            </span>
                                                        
@endif

                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark font-monospace mb-1">
                                                            <i class="fa-solid fa-barcode text-secondary me-1"></i> Lote: <span class="text-primary">{{ $m['numero_lote'] ?? 'FEFO' }}</span>
                                                        </div>
                                                        
@if (!empty($labMarca))

                                                            <div class="small text-muted mb-1">
                                                                <i class="fa-solid fa-industry text-secondary me-1"></i> Lab: <strong class="text-dark">{{ $labMarca }}</strong>
                                                            </div>
                                                        
@endif

                                                        @if (!empty($m['fecha_vencimiento']))

                                                            <small class="text-muted d-block" style="font-size: 0.76rem;">
                                                                <i class="fa-solid fa-calendar-day me-1"></i> Vence: {{ $m['fecha_vencimiento'] }}
                                                            </small>
                                                        
@endif

                                                    </td>
                                                    <td class="small text-muted">
                                                        {{ $m['posologia'] ?? 'Según fórmula' }}
                                                    </td>
                                                    <td class="text-center py-2">
                                                        <span class="badge bg-primary fs-6 px-3 py-1 fw-bold font-monospace">
                                                            {{ intval($m['cantidad_entregar'] ?? 0) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            
@endforeach

                                        </tbody>
                                    </table>

                                    
@if (!empty($medsFaltantes))

                                        <div class="alert alert-warning py-2 px-3 mb-0 small border-warning" style="font-size: 0.82rem;">
                                            <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-truck me-1"></i> Faltantes Direccionados para Domicilio / Compra:</div>
                                            <ul class="mb-0 ps-3">
                                                
@foreach ($medsFaltantes as $f)

                                                    <li><strong>{{ $f['nombre_medicamento'] ?? '' }}</strong>: <span class="text-danger fw-bold">{{ intval($f['cantidad_pendiente'] ?? 0) }} unid.</span> ({{ $f['observaciones'] ?? 'Sin stock en bodega' }})</li>
                                                
@endforeach

                                            </ul>
                                        </div>
                                    
@endif

                                </div>
                            
@endif


                            <div class="formula-zoom-container" id="formula-zoom-wrapper">
                                
@if (!empty($archivosTrans))

                                    @foreach ($archivosTrans as $idxTrans => $tFile)

                                        @php
$urlT = $tFile['url'];
                                            $extTrans = strtolower(pathinfo($urlT, PATHINFO_EXTENSION));
                                            $canvasId = 'pdf-transcripcion-canvas-' . $idxTrans;
@endphp

                                        <div class="w-100 mb-2">
                                            
@if (count($archivosTrans) > 1)

                                                <div class="badge bg-primary text-white mb-1 fs-6 no-print">
                                                    <i class="fa-solid fa-file-pdf me-1"></i> {{ $tFile['nombre'] ?? ('Documento #' . ($idxTrans + 1)) }}
                                                </div>
                                            
@endif


                                            @if (in_array($extTrans, ['jpg', 'jpeg', 'png', 'webp', 'gif']))

                                                <div class="text-center">
                                                    <img src="{{ $urlT }}" class="img-fluid rounded formula-img-print zoomable-element">
                                                </div>
                                            
@else

                                                <div id="{{ $canvasId }}" class="pdf-canvas-container text-center" data-pdf-url="{{ $urlT }}">
                                                    <div class="spinner-border text-primary my-3" role="status">
                                                        <span class="visually-hidden">Cargando páginas de la fórmula transcrita...</span>
                                                    </div>
                                                </div>
                                            
@endif

                                        </div>
                                    
@endforeach

                                @php
elseif (!empty($ingreso['transcripcion_texto'])):
@endphp

                                    <div class="p-4 bg-light rounded border font-monospace text-dark text-start w-100" style="white-space: pre-wrap; font-size: 1.1rem; line-height: 1.6;">
                                        {{ $ingreso['transcripcion_texto'] }}
                                    </div>
                                
@else

                                    <div class="alert alert-info text-center py-3 w-100">
                                        <i class="fa-solid fa-file-invoice fa-2x mb-1 d-block"></i>
                                        <strong>Orden transcrita registrada sin archivo PDF adjunto.</strong>
                                    </div>
                                
@endif

                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let currentZoom = 1.0;

function cambiarZoom(delta) {
    currentZoom = Math.min(Math.max(0.6, currentZoom + delta), 2.2);
    aplicarZoom();
}

function resetZoom() {
    currentZoom = 1.0;
    aplicarZoom();
}

function toggleAnchoCompleto() {
    const card = document.getElementById('print-card-container');
    if (card.style.maxWidth === '100%') {
        card.style.maxWidth = '1100px';
    } else {
        card.style.maxWidth = '100%';
    }
}

function aplicarZoom() {
    document.getElementById('zoom-level-badge').innerText = Math.round(currentZoom * 100) + '%';
    const elementos = document.querySelectorAll('.zoomable-element, .pdf-page-canvas');
    elementos.forEach(el => {
        el.style.width = (currentZoom * 100) + '%';
        el.style.maxWidth = (currentZoom * 100) + '%';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    function renderizarPdfCompleto(pdfUrl, containerId) {
        if (!pdfUrl) return Promise.resolve();
        const container = document.getElementById(containerId);
        if (!container) return Promise.resolve();

        return pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            container.innerHTML = '';
            let renderPromises = [];
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                renderPromises.push(
                    pdf.getPage(pageNum).then(function(page) {
                        const scale = 2.5;
                        const viewport = page.getViewport({ scale: scale });

                        const canvas = document.createElement('canvas');
                        canvas.className = 'pdf-page-canvas zoomable-element';

                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        return page.render({ canvasContext: context, viewport: viewport }).promise.then(() => {
                            container.appendChild(canvas);
                        });
                    })
                );
            }
            return Promise.all(renderPromises);
        }).catch(function(err) {
            console.error("Error al renderizar PDF:", err);
            container.innerHTML = `<div class="alert alert-warning">No se pudo cargar la vista previa del PDF. <a href="${pdfUrl}" target="_blank">Abrir PDF original</a></div>`;
        });
    }

    const containers = document.querySelectorAll('.pdf-canvas-container');
    if (containers.length > 0) {
        let promises = [];
        containers.forEach(cont => {
            const pdfUrl = cont.getAttribute('data-pdf-url');
            if (pdfUrl) {
                promises.push(renderizarPdfCompleto(pdfUrl, cont.id));
            }
        });
        Promise.all(promises).then(() => {
            
@if (request()->has('auto_print') && request()->query('auto_print') == '1')

                setTimeout(() => { window.print(); }, 600);
            
@endif

        });
    } else {
        
@if (request()->has('auto_print') && request()->query('auto_print') == '1')

            setTimeout(() => { window.print(); }, 500);
        
@endif

    }
});
</script>

</body>
</html>
