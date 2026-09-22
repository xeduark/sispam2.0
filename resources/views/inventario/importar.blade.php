@extends('layouts.app')

@section('titulo', 'Importar Inventario - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- MENSAJES DE ESTADO -->
    
@if (!empty($mensaje))

        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0 border-start border-5 border-success" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">¡Importación Exitosa!</h5>
                    <p class="mb-0">{{ $mensaje }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if (!empty($error))

        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0 border-start border-5 border-danger" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Atención en la Importación</h5>
                    <p class="mb-0">{{ $error }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    <!-- RESUMEN DE RESULTADOS DE IMPORTACIÓN -->
    
@if ($resumenImportacion)

        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
            <div class="card-header bg-gradient bg-primary text-white py-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-pie me-2"></i> Balance de la Carga de Inventario</h5>
                    <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">
                        Tiempo: {{ $resumenImportacion['duracion'] }} seg
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 bg-light">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Registros Leídos</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ number_format($resumenImportacion['total_filas']) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 bg-success bg-opacity-10 text-success">
                            <span class="small fw-bold text-uppercase d-block mb-1">Nuevos Registrados</span>
                            <h3 class="fw-bold mb-0 text-success">+{{ number_format($resumenImportacion['insertados']) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 bg-info bg-opacity-10 text-info">
                            <span class="small fw-bold text-uppercase d-block mb-1">Actualizados / Lotes Sumados</span>
                            <h3 class="fw-bold mb-0 text-info">{{ number_format($resumenImportacion['actualizados']) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 bg-secondary bg-opacity-10 text-secondary">
                            <span class="small fw-bold text-uppercase d-block mb-1">Omitidos / En Blanco</span>
                            <h3 class="fw-bold mb-0 text-secondary">{{ number_format($resumenImportacion['omitidos']) }}</h3>
                        </div>
                    </div>
                </div>

                
@if (!empty($resumenImportacion['errores']))

                    <div class="mt-4">
                        <h6 class="fw-bold text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> Avisos y Errores Detectados ({{ count($resumenImportacion['errores']) }}):</h6>
                        <div class="alert alert-warning rounded-3 small mb-0" style="max-height: 180px; overflow-y: auto;">
                            <ul class="mb-0 ps-3">
                                
@foreach (array_slice($resumenImportacion['errores'], 0, 10) as $err)

                                    <li>{{ $err }}</li>
                                
@endforeach

                                @if (count($resumenImportacion['errores']) > 10)

                                    <li class="fw-bold">... y {{ count($resumenImportacion['errores']) - 10 }} avisos más.</li>
                                
@endif

                            </ul>
                        </div>
                    </div>
                
@endif


                <div class="mt-4 text-end">
                    <a href="{{ route('inventario.index') }}" class="btn btn-primary fw-bold rounded-pill px-4 me-2">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Ver Tablero de Stock
                    </a>
                    <a href="{{ route('inventario.kardex') }}" class="btn btn-outline-dark fw-bold rounded-pill px-4">
                        <i class="fa-solid fa-receipt me-1"></i> Ver Movimientos Kardex
                    </a>
                </div>
            </div>
        </div>
    
@endif


    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-file-excel fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Importación Masiva & Carga de Inventarios</h3>
                    <p class="text-muted small mb-0 mt-1">Carga el Catálogo Maestro de Medicamentos o alimenta lotes y existencias a bodegas desde Excel (.xlsx / .csv).</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
            <a href="{{ route('inventario.exportar') }}" class="btn btn-outline-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-export me-1"></i> Exportar Inventarios / ERP
            </a>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Stock
            </a>
        </div>
    </div>

    <!-- PESTAÑAS DE MODALIDAD -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-4">
            
            <ul class="nav nav-pills nav-fill gap-2 p-1 bg-light rounded-4 mb-4" id="importTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-bold py-3" id="tab-catalogo-btn" data-bs-toggle="tab" data-bs-target="#tab-catalogo" type="button" role="tab" onclick="setModo('catalogo')">
                        <i class="fa-solid fa-pills me-2"></i> 1. Catálogo Maestro de Medicamentos
                        <small class="d-block text-muted fw-normal mt-1" style="font-size: 0.75rem;">Actualizar CUMS, INVIMA, Laboratorios y Artículos Base</small>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold py-3" id="tab-saldos-btn" data-bs-toggle="tab" data-bs-target="#tab-saldos" type="button" role="tab" onclick="setModo('saldos')">
                        <i class="fa-solid fa-boxes-packing me-2"></i> 2. Carga de Saldos & Lotes por Bodega
                        <small class="d-block text-muted fw-normal mt-1" style="font-size: 0.75rem;">Ingresar existencias físicas, lotes FEFO y costos a un almacén</small>
                    </button>
                </li>
            </ul>

            <form method="POST" action="{{ route('inventario.importar') }}" enctype="multipart/form-data" id="formImportarInventario">
@csrf
                <input type="hidden" name="action_importar" value="1">
                <input type="hidden" name="modo_importacion" id="inputModoImportacion" value="catalogo">

                <div class="tab-content" id="importTabsContent">

                    <!-- MODO 1: CATÁLOGO MAESTRO -->
                    <div class="tab-pane fade show active" id="tab-catalogo" role="tabpanel">
                        <div class="alert alert-info rounded-4 border-0 bg-info bg-opacity-10 mb-4 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-circle-info fs-3 text-info"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Instrucciones para Carga de Catálogo Maestro</h6>
                                    <p class="small text-muted mb-0">
                                        Compatible con el archivo maestro del software anterior (40 columnas) o formato estándar SISPAM. Si el medicamento ya existe (mismo SKU o CUMS), actualizará sus datos técnicos.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fa-solid fa-rotate me-1 text-primary"></i> Acción ante Medicamentos Existentes</label>
                                <select name="sobrescribir_existentes" class="form-select rounded-3 py-2">
                                    <option value="1" selected>Actualizar datos técnicos con el archivo (Recomendado)</option>
                                    <option value="0">Omitir y conservar los datos actuales de SISPAM</option>
                                </select>
                            </div>
                            <div class="col-md-6 text-md-end d-flex align-items-end justify-content-md-end">
                                <a href="{{ route('inventario.importar') }}?download_template=catalogo_csv" class="btn btn-outline-success fw-bold rounded-pill">
                                    <i class="fa-solid fa-file-csv me-1"></i> Descargar Plantilla Catálogo (CSV)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- MODO 2: SALDOS Y LOTES POR BODEGA -->
                    <div class="tab-pane fade" id="tab-saldos" role="tabpanel">
                        <div class="alert alert-warning rounded-4 border-0 bg-warning bg-opacity-10 mb-4 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-warehouse fs-3 text-warning"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Carga Masiva de Lotes a una Bodega Específica</h6>
                                    <p class="small text-muted mb-0">
                                        Seleccione la bodega de destino. Los lotes se crearán con fecha de vencimiento y semáforo FEFO automático, generando el respectivo movimiento en el Kardex.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fa-solid fa-building-circle-arrow-right me-1 text-success"></i> Bodega de Destino *</label>
                                <select name="bodega_id" id="selectBodegaDestino" class="form-select rounded-3 py-2 fw-bold text-primary">
                                    
@php
$defaultBodegaId = 0;
                                    foreach ($bodegas as $b) {
                                        if (!empty($active_sede_id) && intval($b['sede_id'] ?? 0) === intval($active_sede_id)) {
                                            $defaultBodegaId = $b['id'];
                                            break;
                                        }
                                    }
                                    if (!$defaultBodegaId && !empty($bodegas)) {
                                        $defaultBodegaId = $bodegas[0]['id'];
                                    }
@endphp

                                    @foreach ($bodegas as $b)

                                        <option value="{{ $b['id'] }}" {{ ($b['id'] == $defaultBodegaId) ? 'selected' : '' }}>
                                            {{ $b['nombre_bodega'] }} ({{ $b['nombre_sede'] }})
                                        </option>
                                    
@endforeach

                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fa-solid fa-tag me-1 text-primary"></i> Concepto de Ingreso Kardex</label>
                                <select name="tipo_movimiento" class="form-select rounded-3 py-2">
                                    <option value="ENTRADA_COMPRA" selected>ENTRADA_COMPRA (Recepción Inicial / Factura)</option>
                                    <option value="AJUSTE_POSITIVO">AJUSTE_POSITIVO (Toma Física de Inventario)</option>
                                </select>
                            </div>
                            <div class="col-12 text-md-end">
                                <a href="{{ route('inventario.importar') }}?download_template=saldos_csv" class="btn btn-outline-success fw-bold rounded-pill">
                                    <i class="fa-solid fa-file-csv me-1"></i> Descargar Plantilla Lotes & Saldos (CSV)
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ZONA DE CARGA DE ARCHIVO (DROPZONE) -->
                <div class="card border border-2 border-dashed rounded-4 p-4 text-center bg-light mb-4" id="dropZoneContainer" style="cursor: pointer;">
                    <input type="file" name="archivo_importar" id="fileInputInventario" class="d-none" accept=".xlsx, .csv, .txt" required onchange="mostrarInfoArchivo(this)">
                    <div class="py-3">
                        <div class="p-3 bg-white text-success rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-cloud-arrow-up fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Arrastra tu archivo Excel o CSV aquí</h5>
                        <p class="text-muted small mb-2">Soporta libros de cálculo <code>.xlsx</code> o archivos <code>.csv</code> delimitados por punto y coma</p>
                        <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" onclick="document.getElementById('fileInputInventario').click()">
                            <i class="fa-solid fa-folder-open me-1"></i> Examinar en mi Equipo
                        </button>
                    </div>
                    <div id="fileSelectedInfo" class="d-none mt-3 p-3 bg-white rounded-3 shadow-sm text-start">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-file-excel fs-2 text-success"></i>
                                <div>
                                    <strong class="d-block text-dark" id="fileNameDisplay">nombre_archivo.xlsx</strong>
                                    <small class="text-muted" id="fileSizeDisplay">0 KB</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="limpiarArchivo()"><i class="fa-solid fa-trash me-1"></i> Quitar</button>
                        </div>
                    </div>
                </div>

                <!-- BOTÓN DE ACCIÓN PRINCIPAL -->
                <div class="text-end">
                    <button type="submit" id="btnIniciarImportacion" class="btn btn-success btn-lg fw-bold rounded-pill px-5 shadow">
                        <i class="fa-solid fa-bolt-lightning me-2"></i> Iniciar Importación Masiva
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- TARJETA INFORMATIVA: MAPEO DE COLUMNAS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-white py-3 px-4 border-0">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table-list me-2 text-primary"></i> Estructura y Compatibilidad de Columnas</h5>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 h-100">
                        <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-circle-check me-1"></i> Catálogo Maestro (Formato Legado & SISPAM)</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><strong>IDARTICULO / codigo_sku</strong>: Código único del producto.</li>
                            <li><strong>DESCRIPCION / nombre_comercial</strong>: Nombre comercial del medicamento.</li>
                            <li><strong>RAZONSOCIAL / fabricante_laboratorio</strong>: Laboratorio fabricante / Marca.</li>
                            <li><strong>DESCGENERICO / nombre_generico</strong>: Denominación Común Internacional (DCI).</li>
                            <li><strong>CODCUM / codigo_cums</strong>: Código CUMS oficial de Colombia.</li>
                            <li><strong>REGINVIMA / registro_invima</strong>: Registro Sanitario INVIMA.</li>
                            <li><strong>DESCFORMAFAR / forma_farmaceutica</strong>: Tableta, Jarabe, Ampolla, etc.</li>
                            <li><strong>DESCUNIDAD / concentracion</strong>: 500 mg, 50 mg/ml, etc.</li>
                            <li><strong>PCOSTO / precio_referencia</strong>: Costo o precio base de compra.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 h-100">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-boxes-stacked me-1"></i> Carga de Lotes y Saldos a Bodega</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><strong>codigo_sku / IDARTICULO</strong>: Código del medicamento a cargar.</li>
                            <li><strong>numero_lote</strong>: Identificador del lote de producción.</li>
                            <li><strong>fecha_vencimiento</strong>: Fecha de caducidad (AAAA-MM-DD).</li>
                            <li><strong>fabricante_laboratorio</strong>: Laboratorio / Marca del lote.</li>
                            <li><strong>cantidad</strong>: Unidades físicas a ingresar en la bodega.</li>
                            <li><strong>costo_unitario</strong>: Valor monetario por unidad.</li>
                            <li><strong>ubicacion_estante</strong> (Opcional): Pasillo / Estante físico.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODAL DE PROGRESO DURANTE IMPORTACIÓN -->
<div class="modal fade" id="modalCargandoImportacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg p-4 text-center">
            <div class="spinner-border text-success my-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h5 class="fw-bold text-dark mb-1">Procesando Importación Masiva...</h5>
            <p class="text-muted small mb-0">Por favor espere. SISPAM está sincronizando miles de registros de forma atómica en la base de datos.</p>
        </div>
    </div>
</div>

<script>
function setModo(modo) {
    document.getElementById('inputModoImportacion').value = modo;
}

function mostrarInfoArchivo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('fileNameDisplay').textContent = file.name;
        document.getElementById('fileSizeDisplay').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('fileSelectedInfo').classList.remove('d-none');
    }
}

function limpiarArchivo() {
    const input = document.getElementById('fileInputInventario');
    input.value = '';
    document.getElementById('fileSelectedInfo').classList.add('d-none');
}

// Drag & drop dropzone
const dropZone = document.getElementById('dropZoneContainer');
const fileInput = document.getElementById('fileInputInventario');

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
    }, false);
});

dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files.length) {
        fileInput.files = files;
        mostrarInfoArchivo(fileInput);
    }
});

// Mostrar modal de carga al enviar el formulario
document.getElementById('formImportarInventario').addEventListener('submit', function(e) {
    const modo = document.getElementById('inputModoImportacion').value;
    if (modo === 'saldos') {
        const selBod = document.getElementById('selectBodegaDestino').value;
        if (!selBod) {
            e.preventDefault();
            alert('Por favor seleccione una bodega de destino para cargar los lotes.');
            return;
        }
    }
    const modal = new bootstrap.Modal(document.getElementById('modalCargandoImportacion'));
    modal.show();
});
</script>
@endsection
