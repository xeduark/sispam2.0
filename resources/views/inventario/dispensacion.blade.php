@extends('layouts.app')

@section('titulo', 'Dispensación - '.config('app.name'))

@section('content')
<style>
/* Estilos para inputs numéricos compactos y legibles (FORM y ENTR) */
input.med-cant-form::-webkit-outer-spin-button,
input.med-cant-form::-webkit-inner-spin-button,
input.med-cant-entr::-webkit-outer-spin-button,
input.med-cant-entr::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}
input.med-cant-form,
input.med-cant-entr {
    -moz-appearance: textfield !important;
    appearance: textfield !important;
    font-size: 0.88rem !important;
    font-weight: 700 !important;
    text-align: center !important;
    padding: 0.2rem 0.15rem !important;
    height: 32px !important;
    min-width: 52px !important;
    width: 100% !important;
    max-width: 62px !important;
    border-radius: 6px !important;
    display: inline-block !important;
    box-sizing: border-box !important;
}
input.med-cant-form {
    color: #0f172a !important;
    background-color: #f8fafc !important;
    border: 1px solid #94a3b8 !important;
}
input.med-cant-entr {
    color: #0369a1 !important;
    background-color: #f0fdf4 !important;
    border: 1.5px solid #0ea5e9 !important;
}
input.med-cant-entr:focus {
    background-color: #ffffff !important;
    box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.25) !important;
}
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-success text-white rounded-4 shadow d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-pills fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Dispensación Farmacéutica FEFO & Actas</h3>
                    <p class="text-muted small mb-0 mt-1">
                        Verifica la fórmula médica original contra el inventario real de bodega. Si un medicamento no existe en stock, se reportará estrictamente como faltante para entrega a domicilio.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-5 text-lg-end d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
            <!-- SELECTOR DINÁMICO DE BODEGA -->
            <div class="d-inline-flex align-items-center bg-white border border-primary border-opacity-50 shadow-sm rounded-pill px-3 py-1 text-start">
                <i class="fa-solid fa-warehouse text-primary me-2 fs-5"></i>
                <div class="me-2">
                    <span class="text-muted d-block" style="font-size: 0.70rem; line-height: 1; font-weight: 600;">Bodega de Salida:</span>
                    <select class="form-select form-select-sm border-0 p-0 fw-bold text-dark bg-transparent" style="box-shadow: none; font-size: 0.85rem; cursor: pointer;" onchange="cambiarBodegaDispensacion(this.value)">
                        
@foreach ($bodegasAutorizadas as $b)

                            <option value="{{ $b['id'] }}" {{ ($b['id'] == $bodegaId) ? 'selected' : '' }}>
                                {{ $b['nombre_bodega'] }} ({{ !empty($b['nombre_sede']) ? htmlspecialchars($b['nombre_sede']) : 'Central' }})
                            </option>
                        
@endforeach

                    </select>
                </div>
                <a href="{{ route('inventario.bodegas') }}" class="btn btn-sm btn-light border rounded-circle p-1 text-muted" title="Administrar Bodegas">
                    <i class="fa-solid fa-gear"></i>
                </a>
            </div>

            <a href="{{ route('ia_scanner.cola') }}" class="btn btn-outline-primary rounded-pill fw-bold px-3">
                <i class="fa-solid fa-list-check me-1"></i> Cola IA
            </a>
            <a href="{{ route('inventario.pendientes') }}" class="btn btn-outline-warning text-dark rounded-pill fw-bold px-3">
                <i class="fa-solid fa-truck-ramp-box me-1"></i> Domicilios
            </a>
        </div>
    </div>

    <!-- SELECTOR RÁPIDO DE PACIENTES LISTOS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 p-3">
        <div class="d-flex align-items-center gap-2 overflow-auto py-1">
            <span class="small fw-bold text-muted text-nowrap me-2"><i class="fa-solid fa-user-clock text-primary"></i> Órdenes Listas:</span>
            
@if (empty($ordenesListas))

                <span class="text-muted small">No hay órdenes pendientes en este momento. Escanea una nueva fórmula para iniciar.</span>
            
@else

                @foreach ($ordenesListas as $ord)

                    @php
$esActivo = ($ord['ingreso_id'] == $ingresoIdSeleccionado);
@endphp

                    <a href="{{ route('inventario.dispensacion') }}?ingreso_id={{ $ord['ingreso_id'] }}" 
                       class="btn btn-sm {{ $esActivo ? 'btn-primary shadow text-white' : 'btn-outline-secondary bg-light' }} rounded-pill px-3 py-2 text-nowrap fw-bold">
                        <span class="font-monospace me-1">{{ $ord['ticket_numero'] }}</span> - 
                        {{ $ord['nombres'] }}
                        @if ($ord['estado_ia'] === 'DISPENSADO')

                            <span class="badge bg-success ms-1"><i class="fa-solid fa-check"></i></span>
                        
@endif

                    </a>
                
@endforeach

            @endif

        </div>
    </div>


@php
    // Se calculan aquí (y no solo dentro del bloque de detalle) porque el modal de visor HD, más abajo,
    // se renderiza siempre en el DOM aunque no haya una orden seleccionada todavía.
    $iaDoc   = $detalleOrden['ia_record'] ?? ($detalleOrden['formula_ia'] ?? []);
    $documentos = $detalleOrden['documentos'] ?? [];
    $docPrincipal = ! empty($documentos) ? $documentos[0] : [];
    $fileUrl = $docPrincipal['ruta_archivo'] ?? ($iaDoc['ruta_archivo'] ?? ($detalleOrden['ingreso']['pdf_transcripcion_url'] ?? ''));
    $isPdf   = (!empty($fileUrl) && strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION)) === 'pdf');
@endphp

@if (!$detalleOrden)

        <div class="alert alert-info rounded-4 border-0 p-5 text-center shadow-sm">
            <i class="fa-solid fa-file-prescription fs-1 mb-3 text-primary d-block"></i>
            <h4 class="fw-bold text-dark">No hay ninguna orden seleccionada</h4>
            <p class="text-muted">Selecciona una orden de la lista superior o digitaliza una nueva fórmula para dispensar.</p>
        </div>

@else


        @php
$ingreso        = $detalleOrden['ingreso'] ?? [];
            $iaDoc          = $detalleOrden['ia_record'] ?? ($detalleOrden['formula_ia'] ?? []);
            $documentos     = $detalleOrden['documentos'] ?? [];
            $docPrincipal   = !empty($documentos) ? $documentos[0] : [];
            
            $medsPrescritos = $detalleOrden['medicamentos_extraidos'] ?? [];
            if (empty($medsPrescritos) && !empty($iaDoc['datos_extraidos_json'])) {
                $parsed = json_decode($iaDoc['datos_extraidos_json'], true);
                $medsPrescritos = $parsed['medicamentos'] ?? (is_array($parsed) ? $parsed : []);
            }
            
            $fileUrl = $docPrincipal['ruta_archivo'] ?? ($iaDoc['ruta_archivo'] ?? ($ingreso['pdf_transcripcion_url'] ?? ''));
            $isPdf   = (!empty($fileUrl) && strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION)) === 'pdf');
@endphp


        <!-- LAYOUT DE 2 COLUMNAS: VISOR ORIGINAL (IZQUIERDA) vs GRILLA DE DISPENSACIÓN (DERECHA) -->
        <div class="row g-3" id="contenedorDispensacionRow">

            <!-- COLUMNA IZQUIERDA: VISOR DEL DOCUMENTO ORIGINAL ESCANEADO CON ZOOM -->
            <div class="col-xl-4 col-lg-5" id="colVisorOriginal">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-header bg-white border-0 pt-2 pb-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark small">
                                <i class="fa-solid fa-file-medical text-primary me-1"></i> Documento Original Escaneado
                            </h6>
                            <small class="text-muted" style="font-size: 0.68rem;">{{ $iaDoc['nombre_original'] ?? 'Fórmula Digitalizada' }}</small>
                        </div>
                        
                        <!-- BOTONES DE ZOOM Y HERRAMIENTAS -->
                        <div class="d-flex align-items-center gap-1">
                            
@if (!empty($fileUrl))

                                @if (!$isPdf)

                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0" onclick="zoomDocDispensacion(0.2)" title="Acercar (Zoom +)" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-magnifying-glass-plus text-primary"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0" onclick="zoomDocDispensacion(-0.2)" title="Alejar (Zoom -)" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-magnifying-glass-minus text-primary"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0 fw-bold" onclick="resetDocDispensacion()" id="badgeZoomDocLevel" title="Restablecer 100%" style="font-size: 0.68rem;">
                                            100%
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0" onclick="rotarDocDispensacion()" title="Rotar 90°" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-rotate-right text-secondary"></i>
                                        </button>
                                    </div>
                                
@endif

                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0" onclick="abrirModalVisorHD()" title="Ver en Pantalla Completa / HD" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-expand"></i>
                                </button>
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" style="font-size: 0.72rem;" title="Abrir en pestaña nueva">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            
@endif

                        </div>
                    </div>
                    
                    <div class="card-body p-0 d-flex flex-column position-relative" style="min-height: 500px; background-color: #0f172a; border-radius: 0 0 1rem 1rem; overflow: hidden;">
                        
@if (empty($fileUrl))

                            <div class="m-auto text-center p-4">
                                <i class="fa-solid fa-file-excel fs-1 mb-2 text-white-50"></i>
                                <p class="mb-0 small text-white-50">No se adjuntó archivo visual a esta orden.</p>
                            </div>
                        
@php
elseif ($isPdf):
@endphp

                            <iframe id="iframeDocDispensacion" src="{{ $fileUrl }}#toolbar=1&navpanes=0&zoom=page-width" class="w-100 flex-grow-1 border-0" style="min-height: 520px;"></iframe>
                        
@else

                            <div id="containerImgVisorDispensacion" class="w-100 flex-grow-1 d-flex align-items-center justify-content-center p-2" style="min-height: 520px; overflow: auto; cursor: grab; user-select: none;">
                                <img id="imgVisorFormulaDoc" src="{{ $fileUrl }}" class="rounded shadow-sm" style="max-height: 500px; max-width: 100%; object-fit: contain; transition: transform 0.12s ease-out; transform-origin: center center; transform: scale(1) rotate(0deg);" alt="Fórmula médica">
                            </div>
                        
@endif

                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: GRILLA DE DISPENSACIÓN CON CONTROL ESTRICTO DE STOCK & LOTES FEFO -->
            <div class="col-xl-8 col-lg-7" id="colGrillaDispensacion">
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
                    <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-clipboard-check text-success me-1"></i>
                                Paciente: {{ $ingreso['nombres'] . ' ' . $ingreso['apellidos'] }}
                            </h6>
                            <small class="text-muted" style="font-size: 0.72rem;">
                                <strong>Doc:</strong> {{ $ingreso['tipo_documento'] . ' ' . $ingreso['numero_documento'] }} | 
                                <strong>EPS:</strong> {{ $ingreso['eps_nombre'] ?? 'N/A' }} | 
                                <strong>Tiquete:</strong> <span class="badge bg-primary font-monospace">{{ $ingreso['ticket_numero'] }}</span>
                            </small>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" onclick="toggleVisorPDF()" id="btnToggleVisor" style="font-size: 0.75rem;" title="Ocultar/Mostrar Visor de Fórmula">
                                <i class="fa-solid fa-expand me-1"></i> <span id="lblToggleVisorText">Expandir Grilla</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" onclick="agregarFilaDispensacion()" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-plus me-1"></i> Adicionar
                            </button>
                        </div>
                    </div>

                    <div class="card-body px-3 pb-2 pt-1">
                        
                        <!-- PANEL DINÁMICO DE ALERTA DE DUPLICIDAD MENSUAL -->
                        <div id="panelAlertaDuplicadosMes" class="d-none"></div>

                        <div class="alert alert-light border border-secondary border-opacity-25 rounded-3 py-1 px-3 mb-2 small d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 0.72rem;">
                            <span>
                                <i class="fa-solid fa-microchip text-primary me-1"></i> <strong>Extracción Inteligente:</strong> Cruce automático contra stock de bodega.
                                <span id="badgeReservaStockTimer" class="badge bg-warning-subtle text-dark border border-warning ms-2 d-none"></span>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success">{{ count($medsPrescritos) }} med(s) en fórmula</span>
                        </div>

                        <!-- TABLA COMPACTA DE MEDICAMENTOS -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" id="tablaDispensacionMeds" style="font-size: 0.76rem;">
                                <thead class="table-light">
                                    <tr class="text-muted fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.3px;">
                                        <th style="width: 38%; padding: 0.35rem 0.3rem;">MEDICAMENTO EN FÓRMULA</th>
                                        <th class="text-center" style="width: 68px; min-width: 62px; padding: 0.35rem 0.15rem;" title="Cantidad Formulada">FORM.</th>
                                        <th class="text-center" style="width: 68px; min-width: 62px; padding: 0.35rem 0.15rem;" title="Cantidad a Entregar Hoy">ENTR.</th>
                                        <th style="width: 42%; padding: 0.35rem 0.3rem;">LOTE DISPONIBLE FEFO (BODEGA)</th>
                                        <th class="text-center" style="width: 26px; padding: 0.35rem 0.15rem;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyMedsDispensacion">
                                    
@if (empty($medsPrescritos))

                                        <tr data-index="0">
                                            <td class="py-1 px-1">
                                                <input type="text" class="form-control form-control-sm fw-semibold med-nombre text-dark" value="ACETAMINOFEN 500 MG TABLETA" placeholder="Nombre" style="font-size: 0.76rem; padding: 0.2rem 0.35rem;" oninput="rebuscarLotesParaFila(0)">
                                                <div class="text-muted d-flex align-items-center gap-1 mt-1" style="font-size: 0.68rem;">
                                                    <i class="fa-solid fa-clock-rotate-left text-primary" style="font-size: 0.62rem;"></i>
                                                    <span>1 cada 8 horas</span>
                                                </div>
                                                <input type="hidden" class="med-prod-id" value="0">
                                                <input type="hidden" class="med-posologia" value="1 cada 8 horas">
                                            </td>
                                            <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;">
                                                <input type="number" class="form-control form-control-sm text-center med-cant-form" value="30" min="0" oninput="validarStockFila(0)">
                                            </td>
                                            <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;">
                                                <input type="number" class="form-control form-control-sm text-center med-cant-entr" value="0" min="0" oninput="validarStockFila(0)">
                                            </td>
                                            <td class="py-1 px-1">
                                                <select class="form-select form-select-sm med-lote-sel" style="font-size: 0.74rem; padding: 0.2rem 0.3rem; width: 100%;" onchange="validarStockFila(0)"></select>
                                                <small class="d-block stock-info-lbl mt-1" style="font-size: 0.66rem;"></small>
                                            </td>
                                            <td class="py-1 px-1 text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="eliminarFila(this)" title="Quitar fila"><i class="fa-solid fa-trash-can"></i></button></td>
                                        </tr>
                                    
@else

                                        @foreach ($medsPrescritos as $idx => $m)

                                            @php
$nombreRaw = $m['descripcion'] ?? $m['medicamento'] ?? $m['nombre_generico'] ?? $m['nombre_estandarizado'] ?? '';
                                                $nombreLimpio = trim(preg_replace('/^(?:MX\d+\s*[-—–]?\s*|[-—–]\s*)/i', '', $nombreRaw));
                                                if (empty($nombreLimpio)) $nombreLimpio = 'MEDICAMENTO FORMULADO';
                                                
                                                $posologia = $m['dosis'] ?? $m['posologia'] ?? $m['dosis_indicacion'] ?? 'Según indicación médica';
                                                $duracion = $m['duracion'] ?? ($m['dias_tratamiento'] ?? '');
                                                $observaciones = $m['observaciones'] ?? '';
                                                $frecuencia = $m['frecuencia'] ?? '';

                                                $cantTotalPrescrita = intval($m['cantidad_solicitada'] ?? $m['cantidad_formulada'] ?? $m['cantidad_prescrita'] ?? 0);
                                                $cantDispensar = intval($m['cantidad_dispensar'] ?? $m['cantidad_periodo_actual'] ?? 0);
                                                $totalPeriodos = intval($m['total_periodos'] ?? 1);

                                                // Detección inteligente de multimes por texto de duración / observaciones / posología
                                                $textoBusquedaDur = $duracion . ' ' . $observaciones . ' ' . $posologia . ' ' . $frecuencia;
                                                $diasDetectados = 30;
                                                $mesesDetectados = 1;

                                                if (preg_match('/(\d+)\s*mes(?:es)?/iu', $textoBusquedaDur, $matchMes)) {
                                                    $mesesDetectados = max(1, intval($matchMes[1]));
                                                    $diasDetectados = $mesesDetectados * 30;
                                                } elseif (preg_match('/(\d+)\s*d[ií]as?/iu', $textoBusquedaDur, $matchDias)) {
                                                    $diasDetectados = intval($matchDias[1]);
                                                    if ($diasDetectados > 30) {
                                                        $mesesDetectados = (int)ceil($diasDetectados / 30);
                                                    }
                                                }

                                                if ($mesesDetectados > 1 && $totalPeriodos <= 1) {
                                                    $totalPeriodos = $mesesDetectados;
                                                }

                                                if ($totalPeriodos > 1 && $cantTotalPrescrita > 0) {
                                                    $cantFormulada = (int)ceil($cantTotalPrescrita / $totalPeriodos);
                                                    $cantProxima = max(0, $cantTotalPrescrita - $cantFormulada);
                                                    $esMultimes = true;
                                                } else if ($cantDispensar > 0 && $cantDispensar < $cantTotalPrescrita) {
                                                    $cantFormulada = $cantDispensar;
                                                    $cantProxima = $cantTotalPrescrita - $cantFormulada;
                                                    $totalPeriodos = max(2, (int)ceil($cantTotalPrescrita / $cantFormulada));
                                                    $esMultimes = true;
                                                } else {
                                                    $cantFormulada = ($cantDispensar > 0) ? $cantDispensar : ($cantTotalPrescrita > 0 ? $cantTotalPrescrita : 30);
                                                    $cantProxima = max(0, $cantTotalPrescrita - $cantFormulada);
                                                    $esMultimes = ($totalPeriodos > 1 || $cantProxima > 0);
                                                }

                                                if ($cantFormulada <= 0) $cantFormulada = 30;
@endphp

                                            <tr data-index="{{ $idx }}">
                                                <td class="py-1 px-1">
                                                    <input type="text" class="form-control form-control-sm fw-semibold text-dark med-nombre" value="{{ $nombreLimpio }}" placeholder="Medicamento" style="font-size: 0.76rem; padding: 0.2rem 0.35rem;" oninput="rebuscarLotesParaFila({{ $idx }})">
                                                    <div class="text-muted d-flex align-items-center gap-1 mt-1" style="font-size: 0.68rem;">
                                                        <i class="fa-solid fa-clock-rotate-left text-primary" style="font-size: 0.62rem;"></i>
                                                        <span class="text-truncate" title="{{ $posologia }}">{{ $posologia }}</span>
                                                    </div>
                                                    
@if ($esMultimes)

                                                        <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" style="font-size: 0.66rem; line-height: 1.1;">
                                                                <i class="fa-solid fa-calendar-days me-1"></i>Multimes: {{ $cantTotalPrescrita }} tot (Mes 1: {{ $cantFormulada }} hoy{{ $cantProxima > 0 ? ' | Próx: ' . $cantProxima : '' }})
                                                            </span>
                                                        </div>
                                                    
@endif

                                                    <input type="hidden" class="med-prod-id" value="0">
                                                    <input type="hidden" class="med-posologia" value="{{ $posologia }}">
                                                </td>
                                                <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;">
                                                    <input type="number" class="form-control form-control-sm text-center med-cant-form" value="{{ $cantFormulada }}" min="0" oninput="validarStockFila({{ $idx }})">
                                                </td>
                                                <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;">
                                                    <input type="number" class="form-control form-control-sm text-center med-cant-entr" value="0" min="0" oninput="validarStockFila({{ $idx }})">
                                                </td>
                                                <td class="py-1 px-1">
                                                    <select class="form-select form-select-sm med-lote-sel" style="font-size: 0.74rem; padding: 0.2rem 0.3rem; width: 100%;" onchange="validarStockFila({{ $idx }})">
                                                        <!-- Se llena en JS con lotes reales estrictos -->
                                                    </select>
                                                    <small class="d-block stock-info-lbl mt-1" style="font-size: 0.66rem; line-height: 1.1;"></small>
                                                </td>
                                                <td class="py-1 px-1 text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="eliminarFila(this)" title="Quitar fila">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        
@endforeach

                                    @endif

                                </tbody>
                            </table>
                        </div>

                        <!-- PANEL DESTACADO DE ALERTA DE FALTANTES (COMPACTO Y VISIBLE) -->
                        <div id="panelAlertaFaltantes" class="alert alert-warning border-0 shadow-sm rounded-4 p-2 px-3 mt-2 d-none" style="font-size: 0.76rem;">
                            <div class="d-flex align-items-start gap-2">
                                <div class="p-1 px-2 bg-warning text-dark rounded-circle fs-6 mt-1">
                                    <i class="fa-solid fa-truck-ramp-box"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">Medicamentos Faltantes / Despacho a Domicilio Programado:</strong>
                                    <div id="listaItemsFaltantesVisual" class="bg-white p-2 rounded-3 border border-warning font-monospace mt-1" style="font-size: 0.74rem; max-height: 120px; overflow-y: auto;">
                                        <!-- Lista de faltantes generada en vivo -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OBSERVACIONES DE DISPENSACIÓN -->
                        <div class="mt-2">
                            <label class="form-label fw-bold text-dark small mb-1" style="font-size: 0.75rem;">Observaciones de la Entrega</label>
                            <input type="text" id="inputObservacionesEntrega" class="form-control form-control-sm" placeholder="Ej: Entrega conforme con fórmula médica original." style="font-size: 0.76rem;">
                        </div>
                    </div>

                    <!-- FOOTER: BOTÓN DE GUARDAR Y GENERAR ORDEN DE ALISTAMIENTO -->
                    <div class="card-footer bg-light p-2 px-3 border-0 d-flex justify-content-between align-items-center rounded-bottom-4">
                        <span class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-boxes-packing text-primary me-1"></i> Asignación de stock para picking físico (No descuenta Kardex en este paso)</span>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" id="btnGuardarAlistamientoDispensacion" onclick="guardarYGenerarOrdenAlistamiento()" style="font-size: 0.85rem;">
                            <i class="fa-solid fa-boxes-packing me-1"></i> Guardar & Generar Orden de Alistamiento
                        </button>
                    </div>
                </div>
            </div>

        </div>

    
@endif


</div>

<!-- MODAL DE ÉXITO DE ALISTAMIENTO Y ACCESO DIRECTO A LA ORDEN DE ALISTAMIENTO (PICKING) -->
<div class="modal fade" id="modalExitoAlistamiento" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 10950;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary mb-2 shadow-sm" style="width: 70px; height: 70px; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-boxes-packing fs-2 text-primary"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">¡Orden de Alistamiento Generada con Éxito!</h4>
                <p class="text-secondary small mb-0">La orden ha quedado en estado <strong>ALISTADO</strong> y fue remitida a la lista de entrega. El inventario se descontará y el acta oficial se generará cuando se realice la entrega física con firma en ventanilla.</p>
            </div>

            <!-- SECCIÓN DE DETALLE DE MEDICAMENTOS A ALISTAR / FALTANTES -->
            <div id="modalExitoDetalleAlistamiento" class="mb-3 text-start small"></div>

            <div class="d-grid gap-2">
                <a id="btnModalImprimirOrdenDirecto" href="#" target="_blank" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-2">
                    <i class="fa-solid fa-print me-2"></i> Imprimir Orden de Alistamiento (Picking)
                </a>

                <a href="{{ route('entrega.index') }}" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm py-2">
                    <i class="fa-solid fa-hand-holding-medical me-2"></i> Ir al Módulo de Entrega en Ventanilla
                </a>

                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <a href="{{ route('ia_scanner.cola') }}" class="btn btn-outline-primary rounded-pill w-100 fw-semibold small py-2">
                            <i class="fa-solid fa-list-check me-1"></i> Cola IA
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('inventario.dispensacion') }}" class="btn btn-outline-secondary rounded-pill w-100 fw-semibold small py-2">
                            <i class="fa-solid fa-pills me-1"></i> Siguiente Orden
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE VISOR EN ALTA RESOLUCIÓN Y PANTALLA COMPLETA CON ZOOM -->
<div class="modal fade" id="modalVisorOriginalHD" tabindex="-1" aria-hidden="true" style="z-index: 10900;">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0 bg-dark text-white">
            <div class="modal-header border-0 bg-black py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-medical text-primary fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white small">Fórmula Original Escaneada - Vista HD & Zoom</h6>
                        <small class="text-white-50" style="font-size: 0.70rem;">{{ $iaDoc['nombre_original'] ?? 'Documento Digitalizado' }}</small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    
@if (!$isPdf && !empty($fileUrl))

                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="zoomModalHD(0.25)" title="Acercar (Zoom +)"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="zoomModalHD(-0.25)" title="Alejar (Zoom -)"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                            <button type="button" class="btn btn-outline-light btn-sm fw-bold" onclick="resetModalHD()" id="badgeZoomModalHD">100%</button>
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="rotarModalHD()" title="Rotar 90°"><i class="fa-solid fa-rotate-right"></i> Rotar</button>
                        </div>
                    
@endif

                    
                    @if (!empty($fileUrl))

                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-semibold">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Abrir en Pestaña
                        </a>
                    
@endif

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            
            <div class="modal-body p-0 d-flex align-items-center justify-content-center position-relative overflow-auto" id="containerModalVisorHD" style="background-color: #0b0f19; cursor: grab; user-select: none;">
                
@if ($isPdf)

                    <iframe src="{{ $fileUrl }}#toolbar=1&navpanes=0&zoom=100" class="w-100 h-100 border-0" style="min-height: 88vh;"></iframe>
                
@php
elseif (!empty($fileUrl)):
@endphp

                    <img id="imgModalTargetHD" src="{{ $fileUrl }}" class="img-fluid rounded shadow-lg" style="max-height: 88vh; object-fit: contain; transition: transform 0.15s ease-out; transform-origin: center center; transform: scale(1) rotate(0deg);">
                
@endif

            </div>
        </div>
    </div>
</div>

<script>
var ingresoIdActual = {{ $ingresoIdSeleccionado }};
var productosCatalogo = {!! json_encode($todosProductos, JSON_UNESCAPED_UNICODE) !!};
var lotesBD = {!! json_encode($lotesDisponiblesBD, JSON_UNESCAPED_UNICODE) !!};
var bodegaIdActiva = {{ $bodegaId }};

// Variables y lógica de Zoom para Visor de Fórmula
var zoomLevelDoc = 1.0;
var rotacionDoc = 0;
var isDraggingDoc = false;
var startXDoc, startYDoc, scrollLeftDoc, scrollTopDoc;

function zoomDocDispensacion(delta) {
    zoomLevelDoc = Math.max(0.4, Math.min(3.5, Math.round((zoomLevelDoc + delta) * 10) / 10));
    aplicarTransformDoc();
}

function rotarDocDispensacion() {
    rotacionDoc = (rotacionDoc + 90) % 360;
    aplicarTransformDoc();
}

function resetDocDispensacion() {
    zoomLevelDoc = 1.0;
    rotacionDoc = 0;
    aplicarTransformDoc();
}

function aplicarTransformDoc() {
    var img = document.getElementById('imgVisorFormulaDoc');
    var badge = document.getElementById('badgeZoomDocLevel');
    if (img) {
        img.style.transform = `scale(${zoomLevelDoc}) rotate(${rotacionDoc}deg)`;
    }
    if (badge) {
        badge.textContent = Math.round(zoomLevelDoc * 100) + '%';
    }
}

// Modal HD Zoom
var zoomLevelModal = 1.0;
var rotacionModal = 0;

function abrirModalVisorHD() {
    zoomLevelModal = 1.0;
    rotacionModal = 0;
    aplicarTransformModalHD();
    var modalEl = document.getElementById('modalVisorOriginalHD');
    if (modalEl) {
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function zoomModalHD(delta) {
    zoomLevelModal = Math.max(0.4, Math.min(4.0, Math.round((zoomLevelModal + delta) * 10) / 10));
    aplicarTransformModalHD();
}

function rotarModalHD() {
    rotacionModal = (rotacionModal + 90) % 360;
    aplicarTransformModalHD();
}

function resetModalHD() {
    zoomLevelModal = 1.0;
    rotacionModal = 0;
    aplicarTransformModalHD();
}

function aplicarTransformModalHD() {
    var img = document.getElementById('imgModalTargetHD');
    var badge = document.getElementById('badgeZoomModalHD');
    if (img) {
        img.style.transform = `scale(${zoomLevelModal}) rotate(${rotacionModal}deg)`;
    }
    if (badge) {
        badge.textContent = Math.round(zoomLevelModal * 100) + '%';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarLotesEnTabla();
    inicializarCanvasFirma();

    // Soporte para arrastrar la imagen (pan) cuando hay zoom
    var cImg = document.getElementById('containerImgVisorDispensacion');
    if (cImg) {
        cImg.addEventListener('mousedown', function(e) {
            isDraggingDoc = true;
            cImg.style.cursor = 'grabbing';
            startXDoc = e.pageX - cImg.offsetLeft;
            startYDoc = e.pageY - cImg.offsetTop;
            scrollLeftDoc = cImg.scrollLeft;
            scrollTopDoc = cImg.scrollTop;
        });
        cImg.addEventListener('mouseleave', function() {
            isDraggingDoc = false;
            cImg.style.cursor = 'grab';
        });
        cImg.addEventListener('mouseup', function() {
            isDraggingDoc = false;
            cImg.style.cursor = 'grab';
        });
        cImg.addEventListener('mousemove', function(e) {
            if (!isDraggingDoc) return;
            e.preventDefault();
            var x = e.pageX - cImg.offsetLeft;
            var y = e.pageY - cImg.offsetTop;
            var walkX = (x - startXDoc) * 1.5;
            var walkY = (y - startYDoc) * 1.5;
            cImg.scrollLeft = scrollLeftDoc - walkX;
            cImg.scrollTop = scrollTopDoc - walkY;
        });
        cImg.addEventListener('wheel', function(e) {
            if (e.ctrlKey || e.altKey) {
                e.preventDefault();
                zoomDocDispensacion(e.deltaY < 0 ? 0.15 : -0.15);
            }
        });
    }

    var cModal = document.getElementById('containerModalVisorHD');
    if (cModal) {
        var isDraggingM = false, sXM, sYM, sLM, sTM;
        cModal.addEventListener('mousedown', function(e) {
            isDraggingM = true;
            cModal.style.cursor = 'grabbing';
            sXM = e.pageX - cModal.offsetLeft;
            sYM = e.pageY - cModal.offsetTop;
            sLM = cModal.scrollLeft;
            sTM = cModal.scrollTop;
        });
        cModal.addEventListener('mouseleave', function() { isDraggingM = false; cModal.style.cursor = 'grab'; });
        cModal.addEventListener('mouseup', function() { isDraggingM = false; cModal.style.cursor = 'grab'; });
        cModal.addEventListener('mousemove', function(e) {
            if (!isDraggingM) return;
            e.preventDefault();
            var x = e.pageX - cModal.offsetLeft;
            var y = e.pageY - cModal.offsetTop;
            cModal.scrollLeft = sLM - (x - sXM) * 1.5;
            cModal.scrollTop = sTM - (y - sYM) * 1.5;
        });
        cModal.addEventListener('wheel', function(e) {
            if (e.ctrlKey || e.altKey) {
                e.preventDefault();
                zoomModalHD(e.deltaY < 0 ? 0.2 : -0.2);
            }
        });
    }
});

// Normalizar texto para búsqueda
function cambiarBodegaDispensacion(bodegaId) {
    var url = new URL(window.location.href);
    url.searchParams.set('bodega_id', bodegaId);
    window.location.href = url.toString();
}
function normalizarTexto(txt) {
    if (!txt) return '';
    return txt.toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim();
}

// Búsqueda estricta de lotes para el medicamento
function buscarLotesParaMedicamento(nombreMed) {
    var term = normalizarTexto(nombreMed);
    var matchLotes = [];

    if (!term || !lotesBD || lotesBD.length === 0) {
        return [];
    }

    // Modificadores de sales / radicales químicos que NO son el principio activo
    var saltModifiers = [
        'SODICA', 'SODICO', 'POTASICO', 'POTASICA', 'CLORHIDRATO', 'DIHIDRATO', 'TRIHIDRATO', 
        'MALEATO', 'SULFATO', 'FOSFATO', 'ACETATO', 'BROMURO', 'NITRATO', 'CITRATO', 
        'CALCICO', 'CALCICA', 'MAGNESICO', 'MAGNESICA', 'SUCCINATO', 'TARTRATO', 'VALERATO', 
        'PROPIONATO', 'DIPROPIONATO', 'FUROATO', 'BUTILBROMURO', 'BESILATO', 'MESILATO', 
        'GLUCONATO', 'LACTATO', 'SAL', 'COMPUESTO', 'COMPUESTA', 'ACIDO'
    ];

    // Stopwords farmacéuticas comunes (formas, posología, vías)
    var stopwords = [
        'TABLETA','TABLETAS','CAPSULA','CAPSULAS','SOLUCION','INYECTABLE','JARABE','SUSPENSION',
        'GOTAS','CREMA','UNGUENTO','POMADA','POLVO','SOBRE','SOBRES','VIAL','VIALES','AMPOLLA',
        'AMPOLLAS','CADA','HORAS','DIAS','MESES','POR','MG','ML','MCG','UI','G','GR','KG','INS',
        'PO','VO','IM','IV','SC','ORAL','TOPICA','OFTALMICA','NASAL','RECTAL','VAGINAL','SUBLINGUAL'
    ];

    // Extraer palabras significativas del nombre formulado
    var allWords = term.split(/[^A-Z0-9]+/).filter(function(w) {
        return w.length >= 4 && isNaN(w) && !stopwords.includes(w);
    });

    // Identificar el principio activo (excluyendo sales y stopwords)
    var primaryMolecules = allWords.filter(function(w) {
        return !saltModifiers.includes(w);
    });

    // Si no hay moléculas principales, no podemos asegurar coincidencia
    if (primaryMolecules.length === 0) {
        return [];
    }

    var principioActivo = primaryMolecules[0]; // Ej: DICLOFENACO, ENOXAPARINA, ACETAMINOFEN

    lotesBD.forEach(function(l) {
        var nomGen = normalizarTexto(l.nombre_generico);
        var nomCom = normalizarTexto(l.nombre_comercial);
        var cums   = normalizarTexto(l.codigo_cums);
        var sku    = normalizarTexto(l.codigo_sku);

        var coincide = false;

        // El principio activo principal DEBE estar presente obligatoriamente en el genérico o comercial
        if (nomGen.includes(principioActivo) || nomCom.includes(principioActivo)) {
            coincide = true;
        } else if (cums && (term.includes(cums) || cums.includes(term))) {
            coincide = true;
        } else if (sku && term.includes(sku)) {
            coincide = true;
        }

        if (coincide && parseInt(l.cantidad_actual) > 0) {
            matchLotes.push(l);
        }
    });

    // Ordenar lotes encontrados por FEFO (vencimiento ascendente)
    matchLotes.sort(function(a, b) {
        return new Date(a.fecha_vencimiento) - new Date(b.fecha_vencimiento);
    });

    return matchLotes;
}

function inicializarLotesEnTabla() {
    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    filas.forEach(function(tr, idx) {
        poblarLotesFila(tr, idx, true);
    });
    revisarFaltantesGlobales();
    verificarDuplicadosMesGlobal();
    sincronizarReservaTemporalStock();
}

// Sincronizar reservas temporales de stock con el servidor (Heartbeat cada 45 segundos)
var heartbeatReservaTimer = null;
function sincronizarReservaTemporalStock() {
    if (!ingresoIdActual || ingresoIdActual <= 0) return;

    var itemsReservar = [];
    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    filas.forEach(function(tr) {
        var prodId    = parseInt(tr.querySelector('.med-prod-id').value) || 0;
        var cantEntr  = parseInt(tr.querySelector('.med-cant-entr').value) || 0;
        var selLote   = tr.querySelector('.med-lote-sel');
        var loteId    = selLote ? parseInt(selLote.value) || 0 : 0;

        if (prodId > 0 && loteId > 0 && cantEntr > 0) {
            itemsReservar.push({
                producto_id: prodId,
                lote_id: loteId,
                bodega_id: bodegaIdActiva,
                cantidad: cantEntr
            });
        }
    });

    var fd = new FormData();
    fd.append('action', 'reservar_stock_temporal');
    fd.append('ingreso_id', ingresoIdActual);
    fd.append('items', JSON.stringify(itemsReservar));

    fetch('{{ route('inventario.dispensacion') }}', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(res => {
        if (res.status === 'ok') {
            var badgeRes = document.getElementById('badgeReservaStockTimer');
            if (badgeRes) {
                badgeRes.innerHTML = `<i class="fa-solid fa-lock text-warning me-1"></i> Stock reservado para este ticket (Expira: ${res.expires_at.substr(11, 5)})`;
                badgeRes.classList.remove('d-none');
            }
        }
    }).catch(e => {});

    if (heartbeatReservaTimer) clearInterval(heartbeatReservaTimer);
    heartbeatReservaTimer = setInterval(sincronizarReservaTemporalStock, 45000);
}

// Liberar reserva si el usuario cierra o navega fuera
window.addEventListener('beforeunload', function() {
    if (ingresoIdActual > 0) {
        var fd = new FormData();
        fd.append('action', 'liberar_stock_temporal');
        fd.append('ingreso_id', ingresoIdActual);
        navigator.sendBeacon('{{ route('inventario.dispensacion') }}', fd);
    }
});

// Detector de Duplicidad en el Mismo Mes
function verificarDuplicadosMesGlobal() {
    var pacId = {{ intval($ingreso['paciente_id'] ?? 0) }};
    if (pacId <= 0) return;

    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    var contenedorAlerta = document.getElementById('panelAlertaDuplicadosMes');
    if (!contenedorAlerta) return;

    var nombres = [];
    filas.forEach(function(tr) {
        var nom = tr.querySelector('.med-nombre').value.trim();
        if (nom) nombres.push(nom);
    });

    if (nombres.length === 0) {
        contenedorAlerta.classList.add('d-none');
        return;
    }

    var duplicadosEncontrados = [];
    var promises = nombres.map(function(nom) {
        return fetch(`{{ route('inventario.dispensacion') }}?ajax_verificar_duplicados_mes=1&paciente_id=${pacId}&medicamento=` + encodeURIComponent(nom))
            .then(r => r.json())
            .then(res => {
                if (res.status === 'ok' && res.duplicados && res.duplicados.length > 0) {
                    res.duplicados.forEach(d => duplicadosEncontrados.push(d));
                }
            }).catch(e => {});
    });

    Promise.all(promises).then(function() {
        if (duplicadosEncontrados.length > 0) {
            var html = `<div class="alert alert-warning border border-warning shadow-sm rounded-4 p-3 mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-4"></i>
                    <h6 class="fw-bold mb-0 text-dark">⚠️ Alerta de Posible Duplicidad / Doble Entrega en el Mismo Mes</h6>
                </div>
                <p class="small text-muted mb-2">Este paciente registra dispensaciones previas de estos medicamentos en los últimos 30 días:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white small mb-0">
                        <thead class="table-light">
                            <tr><th>Medicamento Entregado</th><th>Cant.</th><th>Fecha Entrega</th><th>Ticket Anterior</th><th>Dispensado Por</th></tr>
                        </thead>
                        <tbody>`;
            duplicadosEncontrados.forEach(function(d) {
                html += `<tr>
                    <td><strong>${d.nombre_generico || d.nombre_comercial}</strong></td>
                    <td class="text-center fw-bold text-primary">${d.cantidad_entregada}</td>
                    <td>${d.fecha_dispensacion}</td>
                    <td><span class="badge bg-secondary font-monospace">${d.ticket_numero}</span></td>
                    <td>${d.dispensado_por_nombre || 'Farmacia'}</td>
                </tr>`;
            });
            html += `</tbody></table></div></div>`;
            contenedorAlerta.innerHTML = html;
            contenedorAlerta.classList.remove('d-none');
        } else {
            contenedorAlerta.classList.add('d-none');
        }
    });
}

function rebuscarLotesParaFila(idx) {
    var tr = document.querySelector(`#tbodyMedsDispensacion tr[data-index="${idx}"]`);
    if (!tr) return;
    poblarLotesFila(tr, idx, false);
    revisarFaltantesGlobales();
}

function toggleVisorPDF() {
    var colVisor = document.getElementById('colVisorOriginal');
    var colGrilla = document.getElementById('colGrillaDispensacion');
    var lbl = document.getElementById('lblToggleVisorText');
    if (!colVisor || !colGrilla) return;

    if (colVisor.classList.contains('d-none')) {
        colVisor.classList.remove('d-none');
        colGrilla.className = 'col-xl-8 col-lg-7';
        if (lbl) lbl.textContent = 'Expandir Grilla';
    } else {
        colVisor.classList.add('d-none');
        colGrilla.className = 'col-12';
        if (lbl) lbl.textContent = 'Ver Fórmula Original';
    }
}

function poblarLotesFila(tr, idx, esInicio) {
    var nomMed = tr.querySelector('.med-nombre').value;
    var sel    = tr.querySelector('.med-lote-sel');
    var cantFormInput = tr.querySelector('.med-cant-form');
    var cantEntrInput = tr.querySelector('.med-cant-entr');
    var prodIdInput   = tr.querySelector('.med-prod-id');
    
    sel.innerHTML = '';

    var lotes = buscarLotesParaMedicamento(nomMed);

    if (lotes.length > 0) {
        // Encontró lotes reales con stock
        prodIdInput.value = lotes[0].producto_id;

        var primerLoteStock = parseInt(lotes[0].cantidad_actual) || 0;
        var cantForm = parseInt(cantFormInput.value) || 0;

        lotes.forEach(function(l) {
            var opt = document.createElement('option');
            opt.value = l.id;
            opt.setAttribute('data-stock', l.cantidad_actual);
            opt.setAttribute('data-prod-id', l.producto_id);
            opt.setAttribute('data-num-lote', l.numero_lote || '');
            opt.setAttribute('data-vence', l.fecha_vencimiento || '');
            opt.textContent = `Lote: ${l.numero_lote} | Disp: ${l.cantidad_actual} | Vence: ${l.fecha_vencimiento}`;
            sel.appendChild(opt);
        });

        if (esInicio) {
            // Sugerir entrega: lo que haya disponible hasta el total formulado
            var entregaSugerida = Math.min(cantForm, primerLoteStock);
            cantEntrInput.value = entregaSugerida;
        }
    } else {
        // NO HAY STOCK O EL PRODUCTO NO ESTÁ EN EL INVENTARIO DE ESTA BODEGA
        prodIdInput.value = '0';
        
        var opt = document.createElement('option');
        opt.value = '0';
        opt.setAttribute('data-stock', '0');
        opt.setAttribute('data-prod-id', '0');
        opt.textContent = '⚠️ SIN STOCK EN BODEGA (Faltante 100%)';
        opt.selected = true;
        sel.appendChild(opt);

        // Automáticamente 0 unidades para entregar hoy (100% faltante)
        cantEntrInput.value = 0;
    }

    validarStockFila(idx);
}

function validarStockFila(idx) {
    var tr = document.querySelector(`#tbodyMedsDispensacion tr[data-index="${idx}"]`);
    if (!tr) return;

    var cantForm = parseInt(tr.querySelector('.med-cant-form').value) || 0;
    var cantEntr = parseInt(tr.querySelector('.med-cant-entr').value) || 0;
    var selLote  = tr.querySelector('.med-lote-sel');
    var stockLbl = tr.querySelector('.stock-info-lbl');
    var cantEntrInput = tr.querySelector('.med-cant-entr');

    var selectedOpt = selLote.options[selLote.selectedIndex];
    var stockDisp   = selectedOpt ? parseInt(selectedOpt.getAttribute('data-stock')) || 0 : 0;
    var loteId      = selectedOpt ? parseInt(selectedOpt.value) || 0 : 0;

    if (loteId === 0 || stockDisp <= 0) {
        // Sin existencia
        cantEntrInput.value = 0;
        cantEntr = 0;
        stockLbl.innerHTML = `<span class="badge bg-danger-subtle text-danger border border-danger fw-bold" style="font-size: 0.66rem;"><i class="fa-solid fa-circle-xmark"></i> SIN STOCK (100% Domicilio)</span>`;
    } else if (cantEntr > stockDisp) {
        // Excede stock disponible en el lote seleccionado
        stockLbl.innerHTML = `<span class="badge bg-danger text-white fw-bold" style="font-size: 0.66rem;"><i class="fa-solid fa-triangle-exclamation"></i> Insuficiente (Máx lote: ${stockDisp})</span>`;
    } else if (cantEntr === 0 && cantForm > 0) {
        // Usuario decide no entregar nada
        stockLbl.innerHTML = `<span class="badge bg-warning-subtle text-dark border border-warning fw-bold" style="font-size: 0.66rem;"><i class="fa-solid fa-truck"></i> Faltante: ${cantForm} unid. a Domicilio</span>`;
    } else if (cantEntr < cantForm) {
        // Entrega parcial
        var faltante = cantForm - cantEntr;
        stockLbl.innerHTML = `<span class="badge bg-warning-subtle text-dark border border-warning fw-bold" style="font-size: 0.66rem;"><i class="fa-solid fa-truck"></i> Parcial: Faltan ${faltante} unid. Domicilio</span>`;
    } else {
        // Entrega completa
        stockLbl.innerHTML = `<span class="badge bg-success-subtle text-success border border-success fw-bold" style="font-size: 0.66rem;"><i class="fa-solid fa-check"></i> Stock completo (${stockDisp} unid.)</span>`;
    }

    revisarFaltantesGlobales();
}

function revisarFaltantesGlobales() {
    var hayFaltante = false;
    var htmlFaltantes = '';
    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    
    filas.forEach(function(tr) {
        var nomMed   = tr.querySelector('.med-nombre').value.trim();
        var cantForm = parseInt(tr.querySelector('.med-cant-form').value) || 0;
        var cantEntr = parseInt(tr.querySelector('.med-cant-entr').value) || 0;
        var selLote  = tr.querySelector('.med-lote-sel');
        var loteId   = selLote ? parseInt(selLote.value) || 0 : 0;
        
        if (cantEntr < cantForm || loteId === 0) {
            hayFaltante = true;
            var falt = cantForm - cantEntr;
            var motivo = (loteId === 0) ? '<span class="badge bg-danger ms-1">SIN STOCK</span>' : '<span class="badge bg-warning text-dark ms-1">PARCIAL</span>';
            htmlFaltantes += `<div class="mb-1">• <strong>${nomMed}</strong>: Solicitadas ${cantForm}, Entregadas: ${cantEntr} → <span class="text-danger fw-bold">Pendiente Domicilio: ${falt} unid.</span> ${motivo}</div>`;
        }
    });

    var panel = document.getElementById('panelAlertaFaltantes');
    var listaVisual = document.getElementById('listaItemsFaltantesVisual');
    
    if (hayFaltante) {
        panel.classList.remove('d-none');
        listaVisual.innerHTML = htmlFaltantes;
    } else {
        panel.classList.add('d-none');
        listaVisual.innerHTML = '';
    }
}

function eliminarFila(btn) {
    var tr = btn.closest('tr');
    tr.remove();
    revisarFaltantesGlobales();
}

function agregarFilaDispensacion() {
    var tbody = document.getElementById('tbodyMedsDispensacion');
    var newIdx = tbody.children.length;
    var tr = document.createElement('tr');
    tr.setAttribute('data-index', newIdx);
    tr.innerHTML = `
        <td class="py-1 px-1">
            <input type="text" class="form-control form-control-sm fw-semibold text-dark med-nombre" placeholder="Nombre de medicamento" style="font-size: 0.76rem; padding: 0.2rem 0.35rem;" oninput="rebuscarLotesParaFila(${newIdx})">
            <div class="text-muted d-flex align-items-center gap-1 mt-1" style="font-size: 0.68rem;">
                <i class="fa-solid fa-clock-rotate-left text-primary" style="font-size: 0.62rem;"></i>
                <span>Según indicación médica</span>
            </div>
            <input type="hidden" class="med-prod-id" value="0">
            <input type="hidden" class="med-posologia" value="Según indicación médica">
        </td>
        <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;"><input type="number" class="form-control form-control-sm text-center med-cant-form" value="30" min="0" oninput="validarStockFila(${newIdx})"></td>
        <td class="py-1 px-1 text-center" style="width: 68px; min-width: 62px;"><input type="number" class="form-control form-control-sm text-center med-cant-entr" value="0" min="0" oninput="validarStockFila(${newIdx})"></td>
        <td class="py-1 px-1">
            <select class="form-select form-select-sm med-lote-sel" style="font-size: 0.74rem; padding: 0.2rem 0.3rem; width: 100%;" onchange="validarStockFila(${newIdx})">
                <option value="0" data-stock="0">⚠️ SIN STOCK EN BODEGA (Faltante 100%)</option>
            </select>
            <small class="d-block stock-info-lbl mt-1" style="font-size: 0.66rem; line-height: 1.1;"></small>
        </td>
        <td class="py-1 px-1 text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="eliminarFila(this)" title="Quitar fila"><i class="fa-solid fa-trash-can"></i></button></td>
    `;
    tbody.appendChild(tr);
    poblarLotesFila(tr, newIdx, false);
    revisarFaltantesGlobales();
}

// Canvas de firma digital
var canvas = null;
var ctx = null;
var dibujando = false;

function inicializarCanvasFirma() {
    canvas = document.getElementById('canvasFirmaDigital');
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0f172a';

    canvas.addEventListener('mousedown', function(e) { dibujando = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); });
    canvas.addEventListener('mousemove', function(e) { if (dibujando) { ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); } });
    canvas.addEventListener('mouseup', function() { dibujando = false; });
    canvas.addEventListener('mouseleave', function() { dibujando = false; });

    canvas.addEventListener('touchstart', function(e) {
        dibujando = true;
        var rect = canvas.getBoundingClientRect();
        var touch = e.touches[0];
        ctx.beginPath();
        ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        e.preventDefault();
    });
    canvas.addEventListener('touchmove', function(e) {
        if (dibujando) {
            var rect = canvas.getBoundingClientRect();
            var touch = e.touches[0];
            ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
            ctx.stroke();
        }
        e.preventDefault();
    });
    canvas.addEventListener('touchend', function() { dibujando = false; });
}

function limpiarCanvasFirma() {
    if (ctx && canvas) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
}

function abrirModalFirmaYConfirmacion() {
    limpiarCanvasFirma();

    // Llenar listas en el modal
    var listaEntr = document.getElementById('modalListaEntregados');
    var listaFalt = document.getElementById('modalListaFaltantes');
    listaEntr.innerHTML = '';
    listaFalt.innerHTML = '';

    var totalEntrCount = 0;
    var totalFaltCount = 0;

    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    filas.forEach(function(tr) {
        var nombre   = tr.querySelector('.med-nombre').value.trim();
        var cantForm = parseInt(tr.querySelector('.med-cant-form').value) || 0;
        var cantEntr = parseInt(tr.querySelector('.med-cant-entr').value) || 0;
        var selLote  = tr.querySelector('.med-lote-sel');
        var loteId   = selLote ? parseInt(selLote.value) || 0 : 0;
        
        if (cantEntr > 0 && loteId > 0) {
            totalEntrCount++;
            var liE = document.createElement('li');
            liE.innerHTML = `<strong>${nombre}</strong>: ${cantEntr} unidad(es) entregada(s) con lote asignado.`;
            listaEntr.appendChild(liE);
        }

        if (cantEntr < cantForm || loteId === 0) {
            totalFaltCount++;
            var falt = cantForm - cantEntr;
            var liF = document.createElement('li');
            var motivo = (loteId === 0) ? '(Sin stock en inventario)' : '(Entrega parcial)';
            liF.innerHTML = `<strong>${nombre}</strong>: <span class="text-danger fw-bold">${falt} unidad(es)</span> pendiente(s) ${motivo}.`;
            listaFalt.appendChild(liF);
        }
    });

    if (totalEntrCount === 0) listaEntr.innerHTML = '<li class="text-muted fst-italic">Ningún medicamento a entregar físicamente hoy (Todos pendientes a domicilio).</li>';
    if (totalFaltCount === 0) listaFalt.innerHTML = '<li class="text-success fw-bold">¡Entrega 100% Completa! Sin faltantes.</li>';

    var modal = new bootstrap.Modal(document.getElementById('modalFirmaDispensacion'));
    modal.show();
}

function guardarYGenerarOrdenAlistamiento() {
    var btn = document.getElementById('btnGuardarAlistamientoDispensacion');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando orden de alistamiento...';
    }

    var itemsDispensar = [];
    var itemsFaltantes = [];

    var filas = document.querySelectorAll('#tbodyMedsDispensacion tr');
    filas.forEach(function(tr) {
        var nombre    = tr.querySelector('.med-nombre').value.trim();
        var prodId    = parseInt(tr.querySelector('.med-prod-id').value) || 0;
        var cantForm  = parseInt(tr.querySelector('.med-cant-form').value) || 0;
        var cantEntr  = parseInt(tr.querySelector('.med-cant-entr').value) || 0;
        var selLote   = tr.querySelector('.med-lote-sel');
        var loteId    = selLote ? parseInt(selLote.value) || 0 : 0;
        var posologia = tr.querySelector('.med-posologia') ? tr.querySelector('.med-posologia').value : '';

        var opt = selLote ? selLote.options[selLote.selectedIndex] : null;
        var loteNumero = opt ? (opt.getAttribute('data-num-lote') || '') : '';
        var loteVence  = opt ? (opt.getAttribute('data-vence') || '') : '';

        // Si se va a alistar con lote válido
        if (cantEntr > 0 && loteId > 0) {
            itemsDispensar.push({
                producto_id: prodId,
                nombre_medicamento: nombre,
                lote_id: loteId,
                numero_lote: loteNumero,
                fecha_vencimiento: loteVence,
                bodega_id: bodegaIdActiva,
                cantidad_prescrita: cantForm,
                cantidad_entregar: cantEntr,
                posologia: posologia
            });
        }

        // Si hay faltante (parcial o 100% sin stock)
        if (cantEntr < cantForm || loteId === 0) {
            var pendiente = (loteId === 0) ? cantForm : (cantForm - cantEntr);
            itemsFaltantes.push({
                producto_id: prodId > 0 ? prodId : null,
                nombre_medicamento: nombre,
                cantidad_solicitada: cantForm,
                cantidad_pendiente: pendiente,
                observaciones: (loteId === 0) ? 'Sin existencia en bodega en alistamiento' : 'Alistamiento parcial'
            });
        }
    });

    var obs = document.getElementById('inputObservacionesEntrega').value.trim();

    var formData = new FormData();
    formData.append('action', 'guardar_orden_alistamiento');
    formData.append('ingreso_id', ingresoIdActual);
    formData.append('items_dispensar', JSON.stringify(itemsDispensar));
    formData.append('items_faltantes', JSON.stringify(itemsFaltantes));
    formData.append('observaciones', obs);

    fetch('{{ route('inventario.dispensacion') }}', {
        method: 'POST',
        body: formData
    })
    .then(function(res) {
        return res.json();
    })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-boxes-packing me-1"></i> Guardar & Generar Orden de Alistamiento';
        }

        if (res.status === 'ok') {
            // Configurar enlace de impresión de orden unificada de alistamiento
            var printUrl = res.imprimir_url || ('{{ route('alistamiento.orden_unificada', ['ingreso' => 0]) }}'.replace('/0', '/' + ingresoIdActual) + '?auto_print=1');
            var btnImprimir = document.getElementById('btnModalImprimirOrdenDirecto');
            if (btnImprimir) btnImprimir.href = printUrl;

            // Renderizar desglose de alistamiento
            var detEl = document.getElementById('modalExitoDetalleAlistamiento');
            if (detEl) {
                var html = '<div class="p-3 bg-light rounded-3 border">';
                if (itemsDispensar.length > 0) {
                    html += '<div class="fw-bold text-success mb-1"><i class="fa-solid fa-boxes-packing me-1"></i> Medicamentos a Alistar (' + itemsDispensar.length + '):</div><ul class="ps-3 mb-2 small text-dark">';
                    itemsDispensar.forEach(function(d) {
                        html += '<li><strong>' + d.nombre_medicamento + '</strong>: ' + d.cantidad_entregar + ' unid. (Lote: ' + (d.numero_lote || 'FEFO') + ')</li>';
                    });
                    html += '</ul>';
                }
                if (itemsFaltantes.length > 0) {
                    html += '<div class="fw-bold text-warning mb-1"><i class="fa-solid fa-truck me-1"></i> Faltantes para Domicilio (' + itemsFaltantes.length + '):</div><ul class="ps-3 mb-0 small text-danger">';
                    itemsFaltantes.forEach(function(f) {
                        html += '<li><strong>' + f.nombre_medicamento + '</strong>: ' + f.cantidad_pendiente + ' unid.</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
                detEl.innerHTML = html;
            }

            var modalExitoEl = document.getElementById('modalExitoAlistamiento');
            var modalExito = bootstrap.Modal.getOrCreateInstance(modalExitoEl);
            modalExito.show();

            // Abrir automáticamente la orden en una nueva pestaña para imprimir
            var win = window.open(printUrl, '_blank');
            if (win) {
                win.focus();
            }
        } else {
            alert('Atención: ' + (res.message || 'Error desconocido al guardar el alistamiento.'));
        }
    })
    .catch(function(err) {
        console.error('Error al guardar alistamiento:', err);
        alert('Error de red o comunicación al guardar la orden de alistamiento.');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-boxes-packing me-1"></i> Guardar & Generar Orden de Alistamiento';
        }
    });
}
</script>
@endsection
