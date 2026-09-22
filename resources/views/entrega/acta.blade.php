
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Entrega Firmada - {{ $ingreso['ticket_numero'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- PDF.js CDN para renderizado directo de páginas PDF en alta resolución -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .acta-container {
            max-width: 920px;
            margin: 20px auto;
        }
        .acta-sheet {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            padding: 24px 28px;
            margin-bottom: 25px;
            box-sizing: border-box;
        }
        
        /* HEADER INSTITUCIONAL */
        .acta-header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .acta-title-badge {
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            text-align: center;
            padding: 4px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        /* TARJETAS DE INFORMACIÓN CLÍNICA */
        .info-card-acta {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .info-card-header {
            padding: 5px 10px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #cbd5e1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header-patient {
            background: #eff6ff !important;
            color: #1e40af !important;
            border-bottom-color: #bfdbfe !important;
        }
        .header-doctor {
            background: #faf5ff !important;
            color: #6b21a8 !important;
            border-bottom-color: #e9d5ff !important;
        }
        .header-service {
            background: #f0fdf4 !important;
            color: #166534 !important;
            border-bottom-color: #bbf7d0 !important;
        }
        .info-card-body {
            padding: 8px 10px;
            font-size: 0.78rem;
            line-height: 1.25;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #ffffff;
        }
        .info-meta-row {
            display: flex;
            flex-direction: column;
        }
        .info-meta-label {
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-bottom: 1px;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .info-meta-value {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }
        .info-meta-value-highlight {
            color: #0f172a;
            font-weight: 700;
            font-size: 0.82rem;
        }
        .info-badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #f1f5f9 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-badge-primary-soft {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #eff6ff !important;
            color: #1e40af !important;
            border: 1px solid #bfdbfe;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-badge-success-soft {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #f0fdf4 !important;
            color: #166534 !important;
            border: 1px solid #bbf7d0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-badge-purple-soft {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #faf5ff !important;
            color: #7e22ce !important;
            border: 1px solid #e9d5ff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-badge-warning-soft {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #fffbeb !important;
            color: #b45309 !important;
            border: 1px solid #fde68a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-meta-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        /* TABLAS DE DISPENSACIÓN */
        .acta-table {
            font-size: 0.78rem;
            border-color: #cbd5e1;
            margin-bottom: 0;
        }
        .acta-table th {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            padding: 4px 6px;
            border-color: #cbd5e1 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .acta-table td {
            padding: 4px 6px;
            vertical-align: middle;
            border-color: #cbd5e1 !important;
        }

        /* FIRMAS */
        .firma-box {
            border: 1.5px dashed #94a3b8;
            border-radius: 8px;
            background: #f8fafc;
            padding: 8px 12px;
            text-align: center;
            max-width: 330px;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .firma-img-wrapper {
            height: 72px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .firma-img {
            max-height: 68px;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        /* CUADRICULA DE CUOTAS MULTIMES */
        .cuota-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px 8px;
            background: #ffffff;
            font-size: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pdf-page-canvas {
            display: block;
            margin: 0 auto 15px auto;
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        /* ======================================================= */
        /* OPTIMIZACIÓN ULTRA NÍTIDA PARA IMPRESIÓN RICOH / LÁSER */
        /* ======================================================= */
        @page {
            size: letter portrait;
            margin: 7mm 7mm 7mm 7mm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 8.5pt !important;
            }
            .acta-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .acta-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                background: #ffffff !important;
            }
            .acta-hoja-1 {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                
@if ($tieneMultimes)

                page-break-after: always !important;
                break-after: page !important;
                
@endif

            }
            .acta-hoja-2 {
                page-break-before: always !important;
                break-before: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                padding-top: 5px !important;
            }
            .pdf-embed-container {
                page-break-before: always !important;
                break-before: page !important;
            }
            .pdf-page-canvas {
                page-break-inside: avoid !important;
                page-break-after: always !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
            .info-card-acta {
                border: 1px solid #64748b !important;
            }
            .info-card-header {
                border-bottom: 1px solid #64748b !important;
            }
            .acta-table {
                border: 1px solid #64748b !important;
            }
            .acta-table th, .acta-table td {
                border: 1px solid #64748b !important;
            }
            .firma-box {
                border: 1.5px solid #475569 !important;
                background: #ffffff !important;
            }
            .cuota-card {
                border: 1px solid #64748b !important;
            }
        }
    </style>
</head>
<body>

<div class="acta-container">
    <!-- Botones de Acción / Impresión en Pantalla -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('entrega.index') }}" class="btn btn-outline-secondary fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a Entrega
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary fw-bold" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Imprimir en Ricoh (Formato 2 Hojas)
            </button>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- HOJA 1: ACTA OFICIAL DE ENTREGA, DISPENSACIÓN Y CONFORMIDAD      -->
    <!-- ================================================================= -->
    <div class="acta-sheet acta-hoja-1">
        <!-- Encabezado de la Empresa -->
        <div class="row align-items-center acta-header">
            <div class="col-3 text-start">
                
@if (!empty($config['logo_url']) && file_exists(public_path() . '/' . $config['logo_url']))

                    <img src="{{ $config['logo_url'] }}" alt="Logo" style="max-height: 52px; max-width: 100%; object-fit: contain;">
                
@else

                    <i class="fa-solid fa-prescription-bottle-medical fs-2 text-primary"></i>
                
@endif

            </div>
            <div class="col-6 text-center">
                <h5 class="fw-bold mb-0 text-uppercase text-primary" style="font-size: 1rem;">{{ $config['razon_social'] }}</h5>
                <div class="small text-muted" style="font-size: 0.72rem;">NIT: {{ $config['nit'] }} | {{ $config['direccion'] }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">Tel: {{ $config['telefono'] }} | {{ $config['email'] }}</div>
            </div>
            <div class="col-3 text-end">
                <div class="p-1 px-2 border border-primary rounded bg-light d-inline-block text-center" style="min-width: 110px;">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.6rem;">TIQUETE TURNO</small>
                    <span class="fs-5 fw-bold text-primary font-monospace">{{ $ingreso['ticket_numero'] }}</span>
                </div>
            </div>
        </div>

        
@php
$es100PendienteActa = empty($medsDispensadosActa) && $esFaltanteReal;
            $tituloDoc = $es100PendienteActa 
                ? "COMPROBANTE DE RADICACIÓN DE PENDIENTES Y COMPROMISO DE ENTREGA A DOMICILIO" 
                : "ACTA DIGITAL DE CONFORMIDAD Y ENTREGA DE MEDICAMENTOS";
@endphp


        <div class="acta-title-badge">
            <i class="fa-solid fa-file-signature text-primary me-1"></i> {{ $tituloDoc }}
        </div>

        
@if ($es100PendienteActa)

        <!-- BANNER DE COMPROMISO DOMICILIARIO -->
        <div class="alert alert-warning border-warning p-2 mb-2 rounded small" style="font-size: 0.76rem;">
            <i class="fa-solid fa-truck-fast text-warning me-1"></i>
            Por agotamiento transitorio de inventario en sede, la totalidad de los medicamentos formulados han sido radicados para <strong>despacho a domicilio (SLA 48-72h)</strong> conforme a la Resolución 1403/2007. El usuario valida sus datos de contacto.
        </div>
        
@endif


        <!-- Tarjetas de Información Clínica y Administrativa -->
        <div class="row g-2 mb-2">
            <!-- Card 1: Datos del Paciente -->
            <div class="col-4">
                <div class="info-card-acta">
                    <div class="info-card-header header-patient">
                        <i class="fa-solid fa-user me-1 text-primary"></i> DATOS DEL PACIENTE
                    </div>
                    <div class="info-card-body">
                        <div class="info-meta-row">
                            <span class="info-meta-label">Paciente</span>
                            <span class="info-meta-value-highlight text-primary">
                                {{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}
                            </span>
                        </div>
                        
                        <div class="info-meta-grid-2">
                            <div class="info-meta-row">
                                <span class="info-meta-label">Documento</span>
                                <span class="info-meta-value font-monospace" style="font-size: 0.74rem;">
                                    {{ $ingreso['tipo_documento'] . ' ' . $ingreso['numero_documento'] }}
                                </span>
                            </div>
                            <div class="info-meta-row">
                                <span class="info-meta-label">EPS</span>
                                <span class="info-meta-value fw-bold text-truncate" style="font-size: 0.74rem;" title="{{ $ingreso['eps_nombre'] }}">
                                    {{ $ingreso['eps_nombre'] }}
                                </span>
                            </div>
                        </div>

                        <div class="info-meta-row">
                            <span class="info-meta-label">Teléfono</span>
                            <span class="info-meta-value" style="font-size: 0.74rem;">
                                <i class="fa-solid fa-phone text-muted me-1 small"></i>
                                {{ $ingreso['telefono'] ?? $ingreso['numero_celular'] ?? 'No especificado' }}
                            </span>
                        </div>

                        
@php
$dirPacActa = !empty($ingreso['direccion_residencia']) ? $ingreso['direccion_residencia'] : (!empty($ingreso['direccion']) ? $ingreso['direccion'] : '');
                        $barrioRawActa = trim($ingreso['barrio'] ?? '');
                        $barrPacActa = (defined('BARRIOS_MEDELLIN') && isset(BARRIOS_MEDELLIN[$barrioRawActa]))
                            ? BARRIOS_MEDELLIN[$barrioRawActa]
                            : (!empty($barrioRawActa) ? $barrioRawActa : (!empty($ingreso['ciudad_residencia']) ? $ingreso['ciudad_residencia'] : ''));
@endphp

                        <div class="info-meta-row">
                            <span class="info-meta-label">Dirección</span>
                            <span class="info-meta-value text-truncate" style="font-size: 0.72rem;" title="{{ $dirPacActa . (!empty($barrPacActa) ? ' (' . $barrPacActa . ')' : '') }}">
                                <i class="fa-solid fa-location-dot text-danger me-1 small"></i>
                                {{ $dirPacActa ?: 'No registra' }}
                                @if (!empty($barrPacActa))

                                    <span class="text-muted">({{ $barrPacActa }})</span>
                                
@endif

                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Médico & Prescripción -->
            <div class="col-4">
                <div class="info-card-acta">
                    <div class="info-card-header header-doctor">
                        <i class="fa-solid fa-user-doctor me-1 text-purple"></i> MÉDICO & PRESCRIPCIÓN
                    </div>
                    <div class="info-card-body">
                        <div class="info-meta-row">
                            <span class="info-meta-label">Médico Tratante</span>
                            <span class="info-meta-value-highlight text-truncate" style="font-size: 0.76rem;" title="{{ !empty($ingreso['medico_nombre']) ? $ingreso['medico_nombre'] : 'NO REGISTRA' }}">
                                {{ !empty($ingreso['medico_nombre']) ? $ingreso['medico_nombre'] : 'NO REGISTRA' }}
                            </span>
                        </div>

                        <div class="info-meta-grid-2">
                            <div class="info-meta-row">
                                <span class="info-meta-label">Reg. / C.C.</span>
                                <span class="info-meta-value font-monospace small" style="font-size: 0.72rem;">
                                    {{ !empty($ingreso['medico_identificacion']) ? $ingreso['medico_identificacion'] : 'NO REG.' }}
                                </span>
                            </div>
                            <div class="info-meta-row">
                                <span class="info-meta-label">Especialidad</span>
                                <span class="info-meta-value text-truncate small" style="font-size: 0.72rem;">
                                    {{ !empty($ingreso['medico_especialidad']) ? $ingreso['medico_especialidad'] : 'MED. GENERAL' }}
                                </span>
                            </div>
                        </div>

                        
@php
$ipsActa = !empty($ingreso['ips_remite']) && $ingreso['ips_remite'] !== 'NO ESPECIFICADA' ? $ingreso['ips_remite'] : ($iaDataActa['prescripcion']['ips_emisora'] ?? ($iaDataActa['paciente']['ips'] ?? 'No especificada'));
                        $cieActa = !empty($ingreso['diagnostico_cie10']) && $ingreso['diagnostico_cie10'] !== 'NO REGISTRA' ? $ingreso['diagnostico_cie10'] : ($iaDataActa['prescripcion']['diagnostico_cie10'] ?? ($iaDataActa['paciente']['diagnostico_cie10'] ?? ''));
                        $mipresActa = !empty($ingreso['numero_mipres']) && $ingreso['numero_mipres'] !== 'NO REGISTRA' ? $ingreso['numero_mipres'] : ($iaDataActa['prescripcion']['numero_mipres'] ?? ($iaDataActa['paciente']['numero_mipres'] ?? ''));
                        $autActa = !empty($ingreso['numero_autorizacion']) && $ingreso['numero_autorizacion'] !== 'NO REGISTRA' ? $ingreso['numero_autorizacion'] : ($iaDataActa['prescripcion']['numero_autorizacion'] ?? ($iaDataActa['paciente']['numero_autorizacion'] ?? ''));
                        $venceActa = !empty($ingreso['fecha_vencimiento_formula']) && $ingreso['fecha_vencimiento_formula'] !== 'NO REGISTRA' ? $ingreso['fecha_vencimiento_formula'] : ($iaDataActa['prescripcion']['fecha_vencimiento_formula'] ?? ($iaDataActa['paciente']['fecha_vencimiento_formula'] ?? ''));
@endphp


                        <div class="info-meta-row">
                            <span class="info-meta-label">IPS Prescriptora</span>
                            <span class="info-meta-value text-truncate" style="font-size: 0.72rem;" title="{{ $ipsActa }}">
                                {{ $ipsActa }}
                            </span>
                        </div>

                        <div class="info-meta-grid-2">
                            <div class="info-meta-row">
                                <span class="info-meta-label">CIE-10</span>
                                <span class="info-meta-value text-truncate small" style="font-size: 0.7rem;">
                                    {{ $cieActa ?: 'N/A' }}
                                </span>
                            </div>
                            <div class="info-meta-row">
                                <span class="info-meta-label">MIPRES / AUT.</span>
                                <span class="info-meta-value font-monospace small" style="font-size: 0.7rem;">
                                    {{ $mipresActa ?: ($autActa ?: 'N/A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Detalles de la Atención -->
            <div class="col-4">
                <div class="info-card-acta">
                    <div class="info-card-header header-service">
                        <i class="fa-solid fa-hospital-user me-1 text-success"></i> DETALLES DE ATENCIÓN
                    </div>
                    <div class="info-card-body">
                        <div class="info-meta-row">
                            <span class="info-meta-label">Sede de Dispensación</span>
                            <span class="info-meta-value text-truncate" style="font-size: 0.74rem;">
                                <i class="fa-solid fa-location-dot me-1 text-primary"></i> {{ $ingreso['nombre_sede'] ?? ($ingreso['sede_nombre'] ?? 'Sede Principal') }}
                            </span>
                        </div>

                        <div class="info-meta-grid-2">
                            <div class="info-meta-row">
                                <span class="info-meta-label">Fecha Ingreso</span>
                                <span class="info-meta-value small" style="font-size: 0.72rem;">
                                    {{ date('d/m/Y h:i A', strtotime($ingreso['fecha_ingreso'])) }}
                                </span>
                            </div>
                            <div class="info-meta-row">
                                <span class="info-meta-label">Fecha Entrega</span>
                                <span class="info-meta-value text-success fw-bold small" style="font-size: 0.72rem;">
                                    {{ date('d/m/Y h:i A', strtotime($ingreso['updated_at'])) }}
                                </span>
                            </div>
                        </div>

                        <div class="info-meta-row">
                            <span class="info-meta-label">Ventanilla</span>
                            <span class="info-meta-value" style="font-size: 0.74rem;">
                                <span class="info-badge-success-soft">
                                    <i class="fa-solid fa-desktop me-1"></i> {{ $ingreso['modulo_entrega_asignado'] ?? 'Ventanilla General' }}
                                </span>
                            </span>
                        </div>

                        <div class="info-meta-row">
                            <span class="info-meta-label">Responsable</span>
                            <span class="info-meta-value text-truncate small" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-user-check text-muted me-1"></i> {{ $ingreso['orientador_nombre'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE MEDICAMENTOS DISPENSADOS HOY -->
        
@if (!empty($medsDispensadosActa))

        <div class="mb-2">
            <div class="fw-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.76rem;">
                <span><i class="fa-solid fa-pills text-success me-1"></i> MEDICAMENTOS ENTREGADOS A SATISFACCIÓN (TRAZABILIDAD FEFO):</span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0">Entregado en Ventanilla</span>
            </div>
            <table class="table table-bordered table-sm acta-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 25px;">#</th>
                        <th>DESCRIPCIÓN DEL MEDICAMENTO</th>
                        <th style="width: 120px;">CUMS / SKU</th>
                        <th class="text-center" style="width: 100px;">LOTE</th>
                        <th class="text-center" style="width: 90px;">VENCE</th>
                        <th class="text-center" style="width: 70px;">CANT.</th>
                    </tr>
                </thead>
                <tbody>
                    
@foreach ($medsDispensadosActa as $i => $dm)
@php
$nomMostrar = !empty($dm['nombre_medicamento']) ? $dm['nombre_medicamento'] : (!empty($dm['nombre_comercial']) ? $dm['nombre_comercial'] : $dm['nombre_generico']);
$dciGen = $dm['nombre_generico'] ?? '';
@endphp

                        <tr>
                            <td class="text-center fw-bold">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $nomMostrar }}</strong>
                                <div class="text-muted" style="font-size: 0.68rem;">
                                    {{ $dm['concentracion'] . ' - ' . $dm['forma_farmaceutica'] }}
                                    @if (!empty($dciGen) && strtoupper($dciGen) !== strtoupper($nomMostrar))

                                        | <span class="text-primary">DCI: {{ $dciGen }}</span>
                                    
@endif

                                    | Lab: {{ $dm['fabricante_laboratorio'] ?: 'Genérico' }}
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace small">{{ $dm['codigo_cums'] ?: $dm['codigo_sku'] }}</span>
                            </td>
                            <td class="text-center font-monospace fw-bold">{{ $dm['numero_lote'] }}</td>
                            <td class="text-center">{{ $dm['fecha_vencimiento'] }}</td>
                            <td class="text-center fs-6 fw-bold text-success">{{ intval($dm['cantidad_entregada']) }}</td>
                        </tr>
                    
@endforeach

                </tbody>
            </table>
        </div>
        
@endif


        <!-- TABLA DE MEDICAMENTOS PENDIENTES / FALTANTES -->
        
@if ($esFaltanteReal)

        <div class="mb-2">
            <div class="fw-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.76rem;">
                <span><i class="fa-solid fa-truck-ramp-box text-warning me-1"></i> MEDICAMENTOS PENDIENTES RADICADOS PARA ENTREGA A DOMICILIO (SLA 48-72h):</span>
                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2 py-0">Pendiente Domicilio</span>
            </div>
            
@if (!empty($medsPendientesActa))

                <table class="table table-bordered table-sm acta-table">
                    <thead>
                        <tr class="table-warning">
                            <th class="text-center" style="width: 25px;">#</th>
                            <th>MEDICAMENTO PENDIENTE</th>
                            <th class="text-center" style="width: 110px;">CANT. SOLICITADA</th>
                            <th class="text-center" style="width: 110px;">CANT. PENDIENTE</th>
                            <th class="text-center" style="width: 140px;">ESTADO RADICACIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        
@foreach ($medsPendientesActa as $ip => $pItem)

                            <tr>
                                <td class="text-center fw-bold">{{ $ip + 1 }}</td>
                                <td>
                                    <strong>{{ $pItem['nombre_medicamento'] }}</strong>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">{{ $pItem['concentracion'] . ' ' . $pItem['forma_farmaceutica'] }}</small>
                                </td>
                                <td class="text-center">{{ intval($pItem['cantidad_solicitada']) }}</td>
                                <td class="text-center fw-bold text-danger">{{ intval($pItem['cantidad_pendiente']) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-1 fw-bold">PENDIENTE DOMICILIO</span>
                                </td>
                            </tr>
                        
@endforeach

                    </tbody>
                </table>
            
@else

                <div class="p-1 px-2 bg-light rounded border text-dark font-monospace small mb-1" style="font-size: 0.72rem;">
                    {!! nl2br(htmlspecialchars($faltantesDetalle)) !!}
                </div>
            
@endif

        </div>
        
@endif


        <!-- Declaración de Conformidad Legal -->
        <div class="p-2 bg-light border rounded mb-3 text-muted" style="font-size: 0.7rem; line-height: 1.35;">
            <strong>Declaración de Conformidad:</strong> Hago constar que he recibido a entera satisfacción los medicamentos descritos en el acta correspondiente a la presente atención, verificando cantidades dispensadas, fechas de vencimiento, lote y estado físico de los empaques conforme a la Resolución 1403/2007.
        </div>

        <!-- Recuadro de Firmas y Validación Digital -->
        <div class="row align-items-center mb-0">
            <div class="col-6 text-center">
                <div class="firma-box">
                    
@php
$firmaPacUrl = $ingreso['firma_paciente_url'] ?? '';
                        $firmaPacAbs = !empty($firmaPacUrl) ? public_path() . '/' . ltrim($firmaPacUrl, '/\\') : '';
                        $tieneFirmaPac = !empty($firmaPacUrl) && (file_exists($firmaPacAbs) || file_exists(public_path() . '/' . $firmaPacUrl) || str_starts_with($firmaPacUrl, 'data:image'));
@endphp

                    @if ($tieneFirmaPac)

                        <div class="firma-img-wrapper">
                            <img src="{{ $firmaPacUrl }}" alt="Firma Paciente" class="firma-img">
                        </div>
                    
@else

                        <div class="py-3 text-muted small"><i class="fa-solid fa-signature fs-3 me-1 d-block mb-1"></i> FIRMA DIGITAL REGISTRADA</div>
                    
@endif

                    <div class="border-top pt-1 fw-bold text-dark" style="font-size: 0.75rem;">{{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}</div>
                    <div class="text-muted" style="font-size: 0.68rem;">{{ $ingreso['tipo_documento'] . ': ' . $ingreso['numero_documento'] }}</div>
                    <small class="text-success fw-bold d-block" style="font-size: 0.65rem;"><i class="fa-solid fa-shield-check me-1"></i> Firma Digitalizada y Validada</small>
                </div>
            </div>

            <div class="col-6 text-center">
                
@php
$fotoUrl = $ingreso['foto_paciente_url'] ?? '';
                    $fotoAbsoluta = !empty($fotoUrl) ? public_path() . '/' . ltrim($fotoUrl, '/\\') : '';
                    $tieneFoto = !empty($fotoUrl) && (file_exists($fotoAbsoluta) || file_exists(public_path() . '/' . $fotoUrl) || str_starts_with($fotoUrl, 'data:image'));
@endphp

                @if ($tieneFoto)

                    <div class="firma-box">
                        <div class="firma-img-wrapper">
                            <img src="{{ $fotoUrl }}" alt="Foto Paciente" class="firma-img rounded border shadow-sm">
                        </div>
                        <div class="border-top pt-1 fw-bold text-dark" style="font-size: 0.75rem;">REGISTRO FOTOGRÁFICO RECEPCIÓN</div>
                        <small class="text-muted d-block" style="font-size: 0.65rem;"><i class="fa-solid fa-camera me-1"></i> Verificación Biométrica en Ventanilla</small>
                    </div>
                
@else

                    <div class="firma-box py-3">
                        <div class="border-bottom pb-2 mb-2 text-muted fw-bold" style="font-size: 0.72rem;">FIRMA Y SELLO DE FARMACIA</div>
                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">{{ $config['razon_social'] }}</div>
                        <div class="text-muted" style="font-size: 0.68rem;">Servicio Farmacéutico / Dispensación</div>
                        <small class="text-primary fw-semibold d-block mt-1" style="font-size: 0.65rem;"><i class="fa-solid fa-file-circle-check me-1"></i> Copia Archivo Físico / Digital</small>
                    </div>
                
@endif

            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- HOJA 2: CRONOGRAMA DE ENTREGAS PROGRAMADAS (TRATAMIENTO CRÓNICO) -->
    <!-- ================================================================= -->
    
@if ($tieneMultimes)

    <div class="acta-sheet acta-hoja-2">
        <!-- Encabezado Hoja 2 -->
        <div class="row align-items-center acta-header pb-2 mb-2">
            <div class="col-3 text-start">
                
@if (!empty($config['logo_url']) && file_exists(public_path() . '/' . $config['logo_url']))

                    <img src="{{ $config['logo_url'] }}" alt="Logo" style="max-height: 45px; max-width: 100%; object-fit: contain;">
                
@else

                    <i class="fa-solid fa-prescription-bottle-medical fs-3 text-primary"></i>
                
@endif

            </div>
            <div class="col-6 text-center">
                <h6 class="fw-bold mb-0 text-uppercase text-primary" style="font-size: 0.88rem;">{{ $config['razon_social'] }}</h6>
                <div class="text-muted" style="font-size: 0.68rem;">PLAN DE CONTINUIDAD TERAPÉUTICA Y DESPACHO A DOMICILIO</div>
            </div>
            <div class="col-3 text-end">
                <div class="p-1 px-2 border border-primary rounded bg-light d-inline-block text-center" style="min-width: 110px;">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.58rem;">TIQUETE TURNO</small>
                    <span class="fs-6 fw-bold text-primary font-monospace">{{ $ingreso['ticket_numero'] }}</span>
                </div>
            </div>
        </div>

        <div class="acta-title-badge mb-2">
            <i class="fa-solid fa-calendar-check text-primary me-1"></i> CRONOGRAMA DE ENTREGAS PROGRAMADAS (TRATAMIENTO CRÓNICO / MULTIMES)
        </div>

        <!-- Barra de Datos del Paciente y Destino Domicilio -->
        <div class="p-2 bg-light border rounded mb-2" style="font-size: 0.75rem;">
            <div class="row g-2 align-items-center">
                <div class="col-4">
                    <span class="text-muted fw-bold d-block" style="font-size: 0.65rem;">PACIENTE BENEFICIARIO:</span>
                    <strong class="text-primary">{{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}</strong>
                    <div class="text-muted small">{{ $ingreso['tipo_documento'] . ' ' . $ingreso['numero_documento'] }} | EPS: {{ $ingreso['eps_nombre'] }}</div>
                </div>
                <div class="col-4">
                    <span class="text-muted fw-bold d-block" style="font-size: 0.65rem;">DIRECCIÓN DE DESPACHO DOMICILIARIO:</span>
                    <i class="fa-solid fa-location-dot text-danger me-1"></i>
                    <strong>{{ $dirPacActa ?: 'Dirección no registrada' }}</strong>
                    
@if (!empty($barrPacActa))

                        <span class="text-muted">({{ $barrPacActa }})</span>
                    
@endif

                </div>
                <div class="col-4">
                    <span class="text-muted fw-bold d-block" style="font-size: 0.65rem;">TELÉFONO DE CONTACTO / SLA:</span>
                    <i class="fa-solid fa-phone text-success me-1"></i>
                    <strong>{{ $ingreso['telefono'] ?? $ingreso['numero_celular'] ?? 'No especificado' }}</strong>
                    <div class="text-muted small">SLA Despacho: 48 - 72 Horas Hábiles</div>
                </div>
            </div>
        </div>

        <!-- Resumen de Medicamentos Crónicos Autorizados -->
        <div class="mb-2">
            <div class="fw-bold text-dark mb-1" style="font-size: 0.76rem;">
                <i class="fa-solid fa-clipboard-check text-primary me-1"></i> TRATAMIENTO(S) CONTINUO(S) FORMULADO(S):
            </div>
            <table class="table table-bordered table-sm acta-table mb-2">
                <thead>
                    <tr class="table-primary">
                        <th class="text-center" style="width: 25px;">#</th>
                        <th>MEDICAMENTO CRÓNICO / POSOLOGÍA</th>
                        <th class="text-center" style="width: 120px;">TOTAL FORMULADO</th>
                        <th class="text-center" style="width: 110px;">CUOTA MENSUAL</th>
                        <th class="text-center" style="width: 120px;">TOTAL PERIODOS</th>
                        <th class="text-center" style="width: 140px;">MODALIDAD DESPACHO</th>
                    </tr>
                </thead>
                <tbody>
                    
@php
$idxG = 1;
@endphp
@if (!empty($gruposMultimes))
@foreach ($gruposMultimes as $gm)
@php
$cantTotFormulada = ($gm['total_periodos'] == 12 && $gm['cantidad_periodo'] == 30) ? 365 : ($gm['cantidad_periodo'] * $gm['total_periodos']);
@endphp

                        <tr>
                            <td class="text-center fw-bold">{{ $idxG++ }}</td>
                            <td>
                                <strong>{{ $gm['nombre'] }}</strong>
                                <div class="text-muted small" style="font-size: 0.68rem;">{{ $gm['posologia'] }}</div>
                            </td>
                            <td class="text-center fw-bold text-dark">{{ $cantTotFormulada }} unid.</td>
                            <td class="text-center fw-bold text-primary">{{ $gm['cantidad_periodo'] }} unid. / mes</td>
                            <td class="text-center fw-bold">{{ $gm['total_periodos'] }} Meses</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-truck text-warning me-1"></i> {{ $gm['modalidad'] }}</span>
                            </td>
                        </tr>
                    
@endforeach
@elseif (!empty($medsMultimesFormula))
@foreach ($medsMultimesFormula as $mf)
@php
$cTot = intval($mf['cantidad_total_tratamiento'] ?? ($mf['cantidad_solicitada'] ?? 0));
$cHoy = intval($mf['cantidad_dispensar'] ?? $mf['cantidad_periodo_actual'] ?? 0);
$totP = intval($mf['total_periodos'] ?? 2);
@endphp
@if ($totP == 12 && $cHoy == 30 && ($cTot == 360 || $cTot == 0))
@php
$cTot = 365;
@endphp
@elseif ($cTot <= $cHoy && $totP > 1)
@php
$cTot = $cHoy * $totP;
@endphp
@endif

                        <tr>
                            <td class="text-center fw-bold">{{ $idxG++ }}</td>
                            <td>
                                <strong>{{ $mf['descripcion'] ?? $mf['nombre_medicamento'] ?? 'Medicamento' }}</strong>
                                <div class="text-muted small" style="font-size: 0.68rem;">{{ $mf['observaciones'] ?? $mf['dosis'] ?? '' }}</div>
                            </td>
                            <td class="text-center fw-bold text-dark">{{ $cTot }} unid.</td>
                            <td class="text-center fw-bold text-primary">{{ $cHoy }} unid. / mes</td>
                            <td class="text-center fw-bold">{{ $totP }} Meses</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-truck text-warning me-1"></i> DOMICILIO</span>
                            </td>
                        </tr>
                    
@endforeach
@endif

                </tbody>
            </table>
        </div>

        <!-- CALENDARIO DE ENTREGAS MENSUALES (GRILLA COMPACTA DE 2 COLUMNAS) -->
        <div class="mb-2">
            <div class="fw-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.76rem;">
                <span><i class="fa-solid fa-calendar-days text-primary me-1"></i> CALENDARIO DE CUOTAS Y FECHAS PROGRAMADAS:</span>
                <small class="text-muted">Tratamiento Autorizado 100%</small>
            </div>

            
@php
// Preparar listado unificado de cuotas
            $todasCuotas = [];
            if (!empty($gruposMultimes)) {
                foreach ($gruposMultimes as $gm) {
                    // Determinar si Cuota 1 fue dispensada en ventanilla o faltante
                    $esFaltanteGm = false;
                    if (!empty($medsPendientesActa)) {
                        foreach ($medsPendientesActa as $pa) {
                            $nomPa = mb_strtoupper(trim($pa['nombre_medicamento'] ?? ''), 'UTF-8');
                            $nomGm = mb_strtoupper(trim($gm['nombre']), 'UTF-8');
                            if (mb_stripos($nomPa, $nomGm) !== false || mb_stripos($nomGm, $nomPa) !== false) {
                                $esFaltanteGm = true;
                                break;
                            }
                        }
                    }
                    $dispensadoHoyGm = !$esFaltanteGm;

                    // Cuota 1
                    $todasCuotas[] = [
                        'periodo' => 1,
                        'nombre_med' => $gm['nombre'],
                        'cantidad' => $gm['cantidad_periodo'],
                        'fecha' => date('d/m/Y', strtotime($ingreso['fecha_ingreso'])),
                        'modalidad' => $dispensadoHoyGm ? 'VENTANILLA' : 'DOMICILIO',
                        'estado' => $dispensadoHoyGm ? 'ENTREGADO HOY' : 'RADICADO DOMICILIO (48-72h)',
                        'badge_class' => $dispensadoHoyGm ? 'bg-success text-white' : 'bg-warning text-dark'
                    ];

                    // Cuotas futuras
                    foreach ($gm['periodos_futuros'] as $pf) {
                        $fProg = !empty($pf['fecha_programada']) ? date('d/m/Y', strtotime($pf['fecha_programada'])) : 'A definir';
                        $todasCuotas[] = [
                            'periodo' => $pf['periodo_numero'],
                            'nombre_med' => $gm['nombre'],
                            'cantidad' => $pf['cantidad'],
                            'fecha' => $fProg,
                            'modalidad' => $pf['modalidad'],
                            'estado' => 'PROGRAMADO DOMICILIO',
                            'badge_class' => 'bg-primary text-white'
                        ];
                    }
                }
            } elseif (!empty($medsMultimesFormula)) {
                foreach ($medsMultimesFormula as $mf) {
                    $cHoy = intval($mf['cantidad_dispensar'] ?? $mf['cantidad_periodo_actual'] ?? 30);
                    $totP = intval($mf['total_periodos'] ?? 2);
                    $nomMf = $mf['descripcion'] ?? $mf['nombre_medicamento'] ?? 'Medicamento';

                    $todasCuotas[] = [
                        'periodo' => 1,
                        'nombre_med' => $nomMf,
                        'cantidad' => $cHoy,
                        'fecha' => date('d/m/Y', strtotime($ingreso['fecha_ingreso'])),
                        'modalidad' => 'VENTANILLA',
                        'estado' => 'ENTREGADO HOY',
                        'badge_class' => 'bg-success text-white'
                    ];

                    for ($p = 2; $p <= $totP; $p++) {
                        $fProgP = date('d/m/Y', strtotime($ingreso['fecha_ingreso'] . ' +' . (($p - 1) * 30) . ' days'));
                        $todasCuotas[] = [
                            'periodo' => $p,
                            'nombre_med' => $nomMf,
                            'cantidad' => $cHoy,
                            'fecha' => $fProgP,
                            'modalidad' => 'DOMICILIO',
                            'estado' => 'PROGRAMADO DOMICILIO',
                            'badge_class' => 'bg-primary text-white'
                        ];
                    }
                }
            }
@endphp


            <div class="row g-2">
                
@foreach ($todasCuotas as $c)

                    <div class="col-6">
                        <div class="cuota-card">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $c['badge_class'] }} fw-bold" style="font-size: 0.68rem; min-width: 60px;">
                                    MES {{ $c['periodo'] }}
                                </span>
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 0.74rem;">Fecha: {{ $c['fecha'] }}</strong>
                                    <small class="text-muted" style="font-size: 0.68rem;">{{ $c['nombre_med'] }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.72rem;">{{ $c['cantidad'] }} unid.</span>
                                <small class="d-block text-muted" style="font-size: 0.65rem;">{{ $c['modalidad'] }}</small>
                            </div>
                        </div>
                    </div>
                
@endforeach

            </div>
        </div>

        <!-- CONDICIONES Y COMPROMISOS DEL SERVICIO DOMICILIARIO -->
        <div class="p-2 bg-light border rounded mb-2 text-dark" style="font-size: 0.7rem; line-height: 1.35;">
            <div class="fw-bold mb-1 text-primary"><i class="fa-solid fa-circle-info me-1"></i> CONDICIONES DEL SERVICIO DOMICILIARIO (RESOLUCIÓN 1403/2007):</div>
            <ul class="mb-0 ps-3">
                <li>Las cuotas mensuales programadas se despacharán a la dirección registrada sin costo adicional.</li>
                <li>El operador logístico se comunicará previamente al teléfono registrado para confirmar la entrega.</li>
                <li>Para recibir el medicamento se debe presentar el documento de identidad original del paciente o acudiente autorizado.</li>
                <li>Si requiere cambio de dirección o teléfono, favor notificar con 5 días de anticipación al canal de atención farmacéutica.</li>
            </ul>
        </div>

        <!-- Recuadro de Conformidad y Ratificación de Cronograma -->
        <div class="row align-items-center mt-2">
            <div class="col-6 text-center">
                <div class="border-top pt-2" style="max-width: 280px; margin: 0 auto;">
                    <div class="fw-bold text-dark" style="font-size: 0.75rem;">{{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}</div>
                    <div class="text-muted" style="font-size: 0.68rem;">Firma / Conformidad Paciente o Acudiente</div>
                    <small class="text-success fw-semibold" style="font-size: 0.64rem;"><i class="fa-solid fa-check-double me-1"></i> Cronograma Notificado y Aceptado</small>
                </div>
            </div>
            <div class="col-6 text-center">
                <div class="border-top pt-2" style="max-width: 280px; margin: 0 auto;">
                    <div class="fw-bold text-dark" style="font-size: 0.75rem;">{{ $config['razon_social'] }}</div>
                    <div class="text-muted" style="font-size: 0.68rem;">Servicio Farmacéutico / Responsable de Despacho</div>
                    <small class="text-primary fw-semibold" style="font-size: 0.64rem;"><i class="fa-solid fa-shield-halved me-1"></i> Trazabilidad del Tratamiento Crónico</small>
                </div>
            </div>
        </div>
    </div>
    
@endif


    <!-- ================================================================= -->
    <!-- HOJA 3+: ANEXOS DIGITALES (PDFs de Transcripción / Alistamiento)  -->
    <!-- ================================================================= -->
    
@php
$archivosTransActa = !empty($ingreso['transcripciones_archivos']) ? $ingreso['transcripciones_archivos'] : [];
        if (empty($archivosTransActa) && !empty($ingreso['pdf_transcripcion_url'])) {
            $archivosTransActa = [['url' => $ingreso['pdf_transcripcion_url'], 'nombre' => 'Orden Médica Transcrita']];
        }
@endphp

    @if (!empty($archivosTransActa))

        @foreach ($archivosTransActa as $idxT => $tDoc)

            @if (file_exists(public_path() . '/' . $tDoc['url']))

            <div class="acta-sheet pdf-embed-container mt-4 pt-3">
                <h6 class="fw-bold text-primary mb-2" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-file-pdf text-danger me-2"></i> ANEXO: {{ $tDoc['nombre'] ?? 'ORDEN MÉDICA TRANSCRITA' }}
                </h6>
                <div id="pdf-transcripcion-canvas-{{ $idxT }}" class="pdf-acta-trans-canvas text-center my-2" data-pdf-url="{{ $tDoc['url'] }}">
                    <div class="spinner-border spinner-border-sm text-primary my-3" role="status">
                        <span class="visually-hidden">Cargando páginas...</span>
                    </div>
                </div>
            </div>
            
@endif

        @endforeach

    @endif


    @if (!empty($ingreso['pdf_alistamiento']) && file_exists(public_path() . '/' . $ingreso['pdf_alistamiento']))

    <div class="acta-sheet pdf-embed-container mt-4 pt-3">
        <h6 class="fw-bold text-success mb-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-file-shield text-success me-2"></i> ANEXO: COMPROBANTE DE ALISTAMIENTO
        </h6>
        <div id="pdf-alistamiento-canvas-list" class="text-center my-2">
            <div class="spinner-border spinner-border-sm text-success my-3" role="status">
                <span class="visually-hidden">Cargando páginas...</span>
            </div>
        </div>
    </div>
    
@endif


    @if (!empty($ingreso['pdf_formula_final_url']) && file_exists(public_path() . '/' . $ingreso['pdf_formula_final_url']) && strtolower(pathinfo($ingreso['pdf_formula_final_url'], PATHINFO_EXTENSION)) === 'pdf')

    <div class="acta-sheet pdf-embed-container mt-4 pt-3">
        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> ANEXO: COMPROBANTE / FACTURA TERCEROS
        </h6>
        <div id="pdf-formula-final-canvas-list" class="text-center my-2">
            <div class="spinner-border spinner-border-sm text-dark my-3" role="status">
                <span class="visually-hidden">Cargando factura...</span>
            </div>
        </div>
    </div>
    
@endif

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
                    const scale = 1.4;
                    const viewport = page.getViewport({ scale: scale });

                    const canvas = document.createElement('canvas');
                    canvas.className = 'pdf-page-canvas border rounded mb-2';

                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    page.render({ canvasContext: context, viewport: viewport });
                    container.appendChild(canvas);
                });
            }
        }).catch(function(err) {
            console.error("Error al renderizar PDF:", err);
            container.innerHTML = `<div class="alert alert-info py-1 small">Documento adjunto: <code>${pdfUrl}</code></div>`;
        });
    }

    const transContainers = document.querySelectorAll('.pdf-acta-trans-canvas');
    transContainers.forEach(cont => {
        const url = cont.getAttribute('data-pdf-url');
        if (url) {
            renderizarPdfCompleto(url, cont.id);
        }
    });

    
@if (!empty($ingreso['pdf_alistamiento']) && file_exists(public_path() . '/' . $ingreso['pdf_alistamiento']))

        renderizarPdfCompleto('{{ $ingreso['pdf_alistamiento'] }}', 'pdf-alistamiento-canvas-list');
    
@endif


    @if (!empty($ingreso['pdf_formula_final_url']) && file_exists(public_path() . '/' . $ingreso['pdf_formula_final_url']) && strtolower(pathinfo($ingreso['pdf_formula_final_url'], PATHINFO_EXTENSION)) === 'pdf')

        renderizarPdfCompleto('{{ $ingreso['pdf_formula_final_url'] }}', 'pdf-formula-final-canvas-list');
    
@endif

});
</script>

</body>
</html>
