@extends('layouts.app')

@section('titulo', 'Catálogo de Productos - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    
@if (!empty($mensaje))

        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ $mensaje }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    @if (!empty($error))

        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    
@endif


    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-pills fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4 d-flex align-items-center gap-2">
                        <span>Catálogo Maestro de Productos & Medicamentos</span>
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 fs-6 fw-bold px-3 py-1 rounded-pill">
                            {{ number_format($totalProductos) }} Artículos
                        </span>
                    </h3>
                    <p class="text-muted small mb-0 mt-1">Estructura oficial SGSSS Colombia: Medicamentos, Dispositivos Médicos, Insumos y Medicamentos UNIRS.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
            <a href="{{ route('inventario.importar') }}" class="btn btn-outline-success fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-import me-1"></i> Importar Excel
            </a>
            <a href="{{ route('inventario.exportar') }}?action_export=catalogo" class="btn btn-outline-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-export me-1"></i> Exportar Catálogo
            </a>
            <button type="button" class="btn btn-success fw-bold rounded-pill shadow-sm" onclick="abrirModalProducto()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Producto
            </button>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Stock
            </a>
        </div>
    </div>

    <!-- BUSCADOR Y SELECTOR DE REGISTROS POR PÁGINA -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="inventario_productos">
                <input type="hidden" name="p" value="1">

                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-secondary-subtle"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-secondary-subtle" placeholder="Buscar por Genérico, Comercial, Laboratorio, SKU, CUMS o INVIMA..." value="{{ $buscar }}">
                        
@if (!empty($buscar))

                            <a href="{{ route('inventario.productos') }}?tipo_producto={{ urlencode($filtroTipo) }}" class="btn btn-outline-secondary border-secondary-subtle"><i class="fa-solid fa-times"></i></a>
                        
@endif

                    </div>
                </div>
                <div class="col-md-3">
                    <select name="tipo_producto" class="form-select border-secondary-subtle fw-semibold" onchange="this.form.submit()">
                        <option value="">-- Todos los Tipos (Medicamentos / Dispositivos / Insumos) --</option>
                        
@foreach ($tiposProducto as $kTipo => $cfg)

                            <option value="{{ $kTipo }}" {{ $filtroTipo === $kTipo ? 'selected' : '' }}>
                                {{ $cfg['label'] }}
                            </option>
                        
@endforeach

                    </select>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light small border-secondary-subtle text-muted">Ver</span>
                        <select name="per_page" class="form-select border-secondary-subtle" onchange="this.form.submit()">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 por pág</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 por pág</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 por pág</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-3">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- RESUMEN DE PAGINACIÓN ARRIBA -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
        <div class="text-muted small">
            Mostrando <strong>{{ number_format($startRecord) }}</strong> a <strong>{{ number_format($endRecord) }}</strong> de <strong>{{ number_format($totalProductos) }}</strong> medicamentos
            
@if (!empty($buscar))

                (Filtrado por: "<em>{{ $buscar }}</em>")
            
@endif

        </div>
        <div class="text-muted small">
            Página <strong>{{ $pageCurrent }}</strong> de <strong>{{ $totalPages }}</strong>
        </div>
    </div>

    <!-- GRILLA DE PRODUCTOS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive w-100" style="overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%; font-size: 0.82rem;">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 9%;">Código / SKU</th>
                        <th style="width: 13%;">Tipo de Artículo</th>
                        <th style="width: 28%;">Producto (Genérico / Comercial)</th>
                        <th style="width: 14%;">Laboratorio / NIT</th>
                        <th style="width: 14%;">Concentración & Forma</th>
                        <th style="width: 13%;">CUMS / INVIMA</th>
                        <th class="text-center" style="width: 4%;">Stock</th>
                        <th class="text-end pe-3" style="width: 5%;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($productos))

                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No se encontraron productos que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                    
@else

                        @foreach ($productos as $p)

                        @php
$tipoP = $p['tipo_producto'] ?? 'MEDICAMENTOS';
                            $cfgTipo = $tiposProducto[$tipoP] ?? [
                                'label' => $tipoP,
                                'badge' => 'bg-secondary text-white',
                                'icon'  => 'fa-box'
                            ];
                            $styleB = isset($cfgTipo['style']) ? 'style="' . $cfgTipo['style'] . '"' : '';
@endphp

                        <tr>
                            <td class="ps-3 text-break">
                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem; padding: 2px 5px;">{{ $p['codigo_sku'] }}</span>
                            </td>
                            <td class="text-break">
                                <span class="badge {{ $cfgTipo['badge'] }} rounded-pill text-truncate d-inline-block" {{ $styleB }} style="max-width: 100%; font-size: 0.68rem; padding: 3px 6px;">
                                    <i class="fa-solid {{ $cfgTipo['icon'] }} me-1"></i>{{ $cfgTipo['label'] }}
                                </span>
                            </td>
                            <td class="text-break">
                                <div class="fw-bold text-dark" style="line-height: 1.2;">{{ $p['nombre_generico'] }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ $p['nombre_comercial'] ?: 'Sin nombre comercial' }}</div>
                                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                    
@if ($p['requiere_cadena_frio'])

                                        <span class="badge bg-info text-dark" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-snowflake me-1"></i>2°C-8°C</span>
                                    
@endif

                                    @if ($p['es_control_especial'])

                                        <span class="badge bg-danger text-white" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-shield me-1"></i>FNE</span>
                                    
@endif

                                    @if ($p['es_alto_costo'])

                                        <span class="badge bg-warning text-dark" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-star me-1"></i>Alto Costo</span>
                                    
@endif

                                    @if (isset($p['es_pos']) && $p['es_pos'] == 0)

                                        <span class="badge bg-secondary text-white" style="font-size: 0.65rem; padding: 1px 4px;">NO POS</span>
                                    
@endif

                                </div>
                            </td>
                            <td class="text-break">
                                <span class="badge bg-light text-dark border fw-bold text-truncate d-inline-block" style="max-width: 100%; font-size: 0.72rem; padding: 2px 4px;" title="{{ $p['fabricante_laboratorio'] ?: 'GENERICO' }}">
                                    <i class="fa-solid fa-building me-1 text-secondary"></i> {{ $p['fabricante_laboratorio'] ?: 'GENERICO' }}
                                </span>
                                
@if (!empty($p['laboratorio_nit']))

                                    <div class="text-muted text-truncate" style="font-size: 0.70rem;">NIT: {{ $p['laboratorio_nit'] }}</div>
                                
@endif

                            </td>
                            <td class="text-break">
                                <div class="fw-bold text-truncate" style="font-size: 0.76rem;">{{ $p['concentracion'] ?: '--' }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.70rem;">{{ $p['forma_farmaceutica'] ?: '--' }}</div>
                            </td>
                            <td class="text-break">
                                <div class="font-monospace fw-bold text-primary text-truncate" style="font-size: 0.75rem;">CUMS: {{ $p['codigo_cums'] ?: '--' }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.70rem;">INV: {{ $p['registro_invima'] ?: '--' }}</div>
                            </td>
                            <td class="text-center">
                                
@if ($p['stock_total_disponible'] > 0)

                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.82rem;">
                                        {{ number_format($p['stock_total_disponible']) }}
                                    </span>
                                
@else

                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1 rounded-pill" style="font-size: 0.82rem;">
                                        0
                                    </span>
                                
@endif

                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;" onclick='editarProducto({!! json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!})'>
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                </button>
                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>

        <!-- BARRA DE PAGINACIÓN -->
        
@if ($totalPages > 1)

            <div class="card-footer bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Página <strong>{{ $pageCurrent }}</strong> de <strong>{{ $totalPages }}</strong>
                </div>

                <nav aria-label="Navegación de páginas">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <!-- Botón Primero -->
                        <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ route('inventario.productos') }}?q={{ urlencode($buscar) }}&per_page={{ $perPage }}&p=1">
                                <i class="fa-solid fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Botón Anterior -->
                        <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ route('inventario.productos') }}?q={{ urlencode($buscar) }}&per_page={{ $perPage }}&p={{ $pageCurrent - 1 }}">
                                <i class="fa-solid fa-angle-left"></i> Anterior
                            </a>
                        </li>

                        <!-- Números de Página con Ventana Inteligente -->
                        
@php
$rango = 2;
$startPage = max(1, $pageCurrent - $rango);
$endPage   = min($totalPages, $pageCurrent + $rango);
@endphp
@if ($startPage > 1)
@php
echo '<li class="page-item"><a class="page-link rounded-pill px-3" href="'.route('inventario.productos').'?q=' . urlencode($buscar) . '&per_page=' . $perPage . '&p=1">1</a></li>';
@endphp
@if ($startPage > 2)
@php
echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0">...</span></li>';
@endphp
@endif
@endif
@for ($i = $startPage; $i <= $endPage; $i++)

                            <li class="page-item {{ ($i == $pageCurrent) ? 'active' : '' }}">
                                <a class="page-link rounded-pill px-3 fw-bold" href="{{ route('inventario.productos') }}?q={{ urlencode($buscar) }}&per_page={{ $perPage }}&p={{ $i }}">
                                    {{ $i }}
                                </a>
                            </li>
                        
@endfor


                        @php
if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link rounded-pill px-3" href="'.route('inventario.productos').'?q=' . urlencode($buscar) . '&per_page=' . $perPage . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                        }
@endphp


                        <!-- Botón Siguiente -->
                        <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ route('inventario.productos') }}?q={{ urlencode($buscar) }}&per_page={{ $perPage }}&p={{ $pageCurrent + 1 }}">
                                Siguiente <i class="fa-solid fa-angle-right"></i>
                            </a>
                        </li>

                        <!-- Botón Último -->
                        <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ route('inventario.productos') }}?q={{ urlencode($buscar) }}&per_page={{ $perPage }}&p={{ $totalPages }}">
                                <i class="fa-solid fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        
@endif

    </div>

</div>

<!-- MODAL CREAR / EDITAR MEDICAMENTO (ESTRUCTURA COMPLETA MAESTRA) -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="max-height: 90vh; display: flex; flex-direction: column;">
            <form method="POST" action="{{ route('inventario.productos') }}" class="d-flex flex-column h-100" style="min-height: 0; margin: 0;">
@csrf
                <input type="hidden" name="action" value="guardar_producto">
                <input type="hidden" name="id" id="prod_id" value="0">

                <div class="modal-header bg-primary text-white py-3 px-4 flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="modalProdTitulo"><i class="fa-solid fa-pills me-2"></i> Nuevo Medicamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(90vh - 135px);">
                    
                    <!-- 1. CLASIFICACIÓN OFICIAL, IDENTIFICACIÓN Y CÓDIGOS -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom"><i class="fa-solid fa-tags me-2"></i> 1. Clasificación Oficial, Identificación y Códigos</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary"><i class="fa-solid fa-boxes-stacked me-1"></i> Tipo de Producto / Artículo *</label>
                            <select name="tipo_producto" id="prod_tipo_producto" class="form-select rounded-3 border-primary fw-bold shadow-sm" required onchange="cambiarTipoProductoFormulario(this.value, 'cambio')">
                                <option value="MEDICAMENTOS">💊 MEDICAMENTOS (Fármacos PBS / Comercial)</option>
                                <option value="DISPOSITIVOS MEDICOS">🩺 DISPOSITIVOS MEDICOS (Jeringas, Catéteres, Equipos)</option>
                                <option value="INSUMOS">🩹 INSUMOS (Material Quirúrgico, Gasas, Toallas)</option>
                                <option value="MEDICAMENTOS UNIRS">⚡ MEDICAMENTOS UNIRS (No PBS / MIPRES)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">IDARTICULO / SKU *</label>
                            <input type="text" name="codigo_sku" id="prod_sku" class="form-control rounded-3 font-monospace" required placeholder="Ej: DM0008, MED-0012">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Estado en el Catálogo</label>
                            <select name="estado_activo" id="prod_estado" class="form-select rounded-3">
                                <option value="Activo">Activo (Habilitado)</option>
                                <option value="Inactivo">Inactivo (Deshabilitado)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lbl_prod_cums">Código CUMS (INVIMA)</label>
                            <input type="text" name="codigo_cums" id="prod_cums" class="form-control rounded-3 font-monospace" placeholder="Expediente-Consecutivo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Registro Sanitario INVIMA</label>
                            <input type="text" name="registro_invima" id="prod_invima" class="form-control rounded-3" placeholder="INVIMA 2020M-00... o 2024DM-00...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Vigencia INVIMA</label>
                            <input type="date" name="fecha_vigencia_invima" id="prod_vigencia_invima" class="form-control rounded-3">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lbl_prod_atc">Código ATC (IDGENERICO)</label>
                            <input type="text" name="codigo_atc" id="prod_atc" class="form-control rounded-3 font-monospace" placeholder="Ej: N02BE01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Código IUM</label>
                            <input type="text" name="codigo_ium" id="prod_ium" class="form-control rounded-3 font-monospace" placeholder="Código IUM">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" id="prod_barras" class="form-control rounded-3 font-monospace" placeholder="770...">
                        </div>
                    </div>

                    <!-- 2. DESCRIPCIÓN TÉCNICA / FARMACOLÓGICA Y TITULAR -->
                    <h6 class="fw-bold text-success mb-3 pb-2 border-bottom" id="sec_desc_titulo"><i class="fa-solid fa-capsules me-2"></i> 2. Descripción Técnica / Farmacológica & Titular</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" id="lbl_prod_generico">Nombre Genérico / DCI (DESCGENERICO) *</label>
                            <input type="text" name="nombre_generico" id="prod_generico" class="form-control rounded-3" required placeholder="Ej: ACETAMINOFEN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" id="lbl_prod_comercial">Nombre Comercial / Referencia (DESCRIPCION) *</label>
                            <input type="text" name="nombre_comercial" id="prod_comercial" class="form-control rounded-3" required placeholder="Ej: ACETAMINOFEN 500 MG TABLETA">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" id="lbl_prod_principio">Principio Activo (DESCPRINCIPIO)</label>
                            <input type="text" name="principio_activo" id="prod_principio" class="form-control rounded-3" placeholder="Ej: ACETAMINOFEN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Clase Terapéutica / Categoría (DESCLASE)</label>
                            <select name="clase_terapeutica" id="prod_clase" class="form-select rounded-3">
                                <option value="">-- Seleccionar Clase / Categoría --</option>
                                
@foreach ($listaClases as $cl)

                                    <option value="{{ $cl }}">{{ $cl }}</option>
                                
@endforeach

                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fabricante / Titular / Marca (RAZONSOCIAL) <small class="text-muted">(470+ opciones)</small></label>
                            <input type="text" name="fabricante_laboratorio" id="prod_laboratorio" list="lista_laboratorios" class="form-control rounded-3" placeholder="Seleccione o escriba el fabricante o laboratorio..." oninput="autoCompletarNit(this.value)" autocomplete="off">
                            <datalist id="lista_laboratorios">
                                
@foreach ($listaLaboratorios as $lab)

                                    <option value="{{ $lab['fabricante_laboratorio'] }}">{{ $lab['fabricante_laboratorio'] }} (NIT: {{ $lab['laboratorio_nit'] ?: 'S/N' }})</option>
                                
@endforeach

                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">NIT del Fabricante / Titular</label>
                            <input type="text" name="laboratorio_nit" id="prod_lab_nit" class="form-control rounded-3" placeholder="Auto-completado al elegir fabricante...">
                        </div>
                    </div>

                    <!-- 3. PRESENTACIÓN, DOSIS Y COSTOS -->
                    <h6 class="fw-bold text-info mb-3 pb-2 border-bottom"><i class="fa-solid fa-shapes me-2"></i> 3. Concentración, Forma, Vía y Precios</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lbl_prod_concentracion">Concentración (DESCUNIDAD) *</label>
                            <input type="text" name="concentracion" id="prod_concentracion" class="form-control rounded-3" required placeholder="Ej: 500 mg, 50 mg/ml">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lbl_prod_forma">Forma Farmacéutica / Presentación (DESCFORMAFAR) *</label>
                            <input type="text" name="forma_farmaceutica" id="prod_forma" list="lista_formas" class="form-control rounded-3" required placeholder="Seleccione o escriba la forma..." autocomplete="off">
                            <datalist id="lista_formas">
                                
@foreach ($listaFormas as $fm)

                                    <option value="{{ $fm }}">{{ $fm }}</option>
                                
@endforeach

                            </datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Presentación / Unidad Venta (UNID_VENTA)</label>
                            <input type="text" name="presentacion_comercial" id="prod_presentacion" list="lista_presentaciones" class="form-control rounded-3" placeholder="Seleccione o escriba la presentación..." autocomplete="off">
                            <datalist id="lista_presentaciones">
                                
@foreach ($listaPresentaciones as $pr)

                                    <option value="{{ $pr }}">{{ $pr }}</option>
                                
@endforeach

                            </datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Vía de Administración</label>
                            <select name="via_administracion" id="prod_via" class="form-select rounded-3">
                                <option value="ORAL">ORAL</option>
                                <option value="NO APLICA">NO APLICA (Dispositivos / Insumos)</option>
                                <option value="INTRAVENOSA">INTRAVENOSA</option>
                                <option value="INTRAMUSCULAR">INTRAMUSCULAR</option>
                                <option value="SUBCUTANEA">SUBCUTÁNEA</option>
                                <option value="TOPICA">TÓPICA</option>
                                <option value="OFTALMICA">OFTÁLMICA</option>
                                <option value="INHALATORIA">INHALATORIA</option>
                                <option value="RECTAL">RECTAL</option>
                                <option value="OTICA">ÓTICA</option>
                                <option value="NASAL">NASAL</option>
                                <option value="VAGINAL">VAGINAL</option>
                                <option value="SUBLINGUAL">SUBLINGUAL</option>
                                <option value="TRANSDERMICA">TRANSDÉRMICA</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Precio Costo / Referencia ($) (PCOSTO)</label>
                            <input type="number" step="0.01" name="precio_referencia" id="prod_precio" class="form-control rounded-3" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Stock Mínimo Alerta</label>
                            <input type="number" name="stock_minimo_alerta" id="prod_minimo" class="form-control rounded-3" value="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Stock Máximo</label>
                            <input type="number" name="stock_maximo" id="prod_maximo" class="form-control rounded-3" value="1000">
                        </div>
                    </div>

                    <!-- 4. ATRIBUTOS DE CONTROL SANITARIO Y REGULATORIO -->
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="fa-solid fa-clipboard-check me-2"></i> 4. Atributos de Control y Normativa SGSSS</h6>
                    <div class="p-3 bg-light rounded-4 border mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="requiere_cadena_frio" id="prod_frio" value="1">
                                    <label class="form-check-label fw-bold small" for="prod_frio"><i class="fa-solid fa-snowflake text-info me-1"></i> Cadena de Frío (2°C - 8°C)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="es_control_especial" id="prod_control" value="1">
                                    <label class="form-check-label fw-bold small" for="prod_control"><i class="fa-solid fa-shield-halved text-danger me-1"></i> Control Especial (FNE)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="es_alto_costo" id="prod_alto_costo" value="1">
                                    <label class="form-check-label fw-bold small" for="prod_alto_costo"><i class="fa-solid fa-star text-warning me-1"></i> Alto Costo</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="es_pos" id="prod_es_pos" value="1" checked>
                                    <label class="form-check-label fw-bold small" for="prod_es_pos"><i class="fa-solid fa-notes-medical text-primary me-1"></i> Incluido en PBS / POS (NOPOS = 0)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="uso_institucional" id="prod_institucional" value="1">
                                    <label class="form-check-label fw-bold small" for="prod_institucional"><i class="fa-solid fa-hospital text-secondary me-1"></i> Uso Institucional</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="es_biologico" id="prod_biologico" value="1">
                                    <label class="form-check-label fw-bold small" for="prod_biologico"><i class="fa-solid fa-dna text-success me-1"></i> Medicamento Biológico</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. OBSERVACIONES -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observaciones / Notas de Auditoría</label>
                        <textarea name="observaciones" id="prod_observaciones" class="form-control rounded-3" rows="2" placeholder="Notas sobre el medicamento, restricciones de compra, etc."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light py-3 px-4 border-0 flex-shrink-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm"><i class="fa-solid fa-save me-1"></i> Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cambiarTipoProductoFormulario(tipo, modo) {
    modo = modo || 'nuevo';
    const esDispositivo = (tipo === 'DISPOSITIVOS MEDICOS' || tipo === 'INSUMOS');
    const tituloElem = document.getElementById('modalProdTitulo');
    
    if (modo === 'nuevo' || modo === 'cambio') {
        if (tipo === 'DISPOSITIVOS MEDICOS') {
            tituloElem.innerHTML = '<i class="fa-solid fa-stethoscope me-2"></i> Nuevo Dispositivo Médico';
        } else if (tipo === 'INSUMOS') {
            tituloElem.innerHTML = '<i class="fa-solid fa-box-tissue me-2"></i> Nuevo Insumo / Material';
        } else if (tipo === 'MEDICAMENTOS UNIRS') {
            tituloElem.innerHTML = '<i class="fa-solid fa-prescription me-2"></i> Nuevo Medicamento UNIRS';
        } else {
            tituloElem.innerHTML = '<i class="fa-solid fa-pills me-2"></i> Nuevo Medicamento';
        }
    }

    const lblGen = document.getElementById('lbl_prod_generico');
    const inputGen = document.getElementById('prod_generico');
    const lblCom = document.getElementById('lbl_prod_comercial');
    const lblPrinc = document.getElementById('lbl_prod_principio');
    const inputPrinc = document.getElementById('prod_principio');
    const inputAtc = document.getElementById('prod_atc');
    const lblConc = document.getElementById('lbl_prod_concentracion');
    const inputConc = document.getElementById('prod_concentracion');
    const selectVia = document.getElementById('prod_via');

    if (esDispositivo) {
        if (lblGen) lblGen.innerHTML = 'Descripción Genérica / Básica <small class="text-muted">(Opcional)</small>';
        if (inputGen) {
            inputGen.required = false;
            inputGen.placeholder = 'Ej: JERINGA DESECHABLE, o NO APLICA';
        }
        if (lblCom) lblCom.innerHTML = 'Nombre Comercial / Referencia / Marca (DESCRIPCION) *';
        if (lblPrinc) lblPrinc.innerHTML = 'Principio Activo <small class="text-muted">(No aplica)</small>';
        if (inputPrinc) inputPrinc.placeholder = 'No aplica para dispositivos o insumos';
        if (inputAtc) inputAtc.placeholder = 'No aplica para dispositivos';
        if (lblConc) lblConc.innerHTML = 'Calibre / Medida / Capacidad (DESCUNIDAD)';
        if (inputConc) {
            inputConc.required = false;
            inputConc.placeholder = 'Ej: 21G X 1 1/2, 10 ML, 45 MM X 85 MM';
        }
        if (modo === 'nuevo' && selectVia && selectVia.value === 'ORAL') {
            selectVia.value = 'NO APLICA';
        }
    } else {
        if (lblGen) lblGen.innerHTML = 'Nombre Genérico / DCI (DESCGENERICO) *';
        if (inputGen) {
            inputGen.required = true;
            inputGen.placeholder = 'Ej: ACETAMINOFEN';
        }
        if (lblCom) lblCom.innerHTML = 'Nombre Comercial (DESCRIPCION) *';
        if (lblPrinc) lblPrinc.innerHTML = 'Principio Activo (DESCPRINCIPIO)';
        if (inputPrinc) inputPrinc.placeholder = 'Ej: ACETAMINOFEN';
        if (inputAtc) inputAtc.placeholder = 'Ej: N02BE01';
        if (lblConc) lblConc.innerHTML = 'Concentración (DESCUNIDAD) *';
        if (inputConc) {
            inputConc.required = true;
            inputConc.placeholder = 'Ej: 500 mg, 50 mg/ml';
        }
        if (modo === 'nuevo' && selectVia && selectVia.value === 'NO APLICA') {
            selectVia.value = 'ORAL';
        }
    }
}

function abrirModalProducto() {
    document.getElementById('modalProdTitulo').innerHTML = '<i class="fa-solid fa-pills me-2"></i> Nuevo Medicamento';
    document.getElementById('prod_id').value = '0';
    document.getElementById('prod_tipo_producto').value = 'MEDICAMENTOS';
    document.getElementById('prod_sku').value = '';
    document.getElementById('prod_cums').value = '';
    document.getElementById('prod_invima').value = '';
    document.getElementById('prod_vigencia_invima').value = '';
    document.getElementById('prod_generico').value = '';
    document.getElementById('prod_comercial').value = '';
    document.getElementById('prod_principio').value = '';
    document.getElementById('prod_laboratorio').value = '';
    document.getElementById('prod_lab_nit').value = '';
    document.getElementById('prod_concentracion').value = '';
    document.getElementById('prod_forma').value = '';
    document.getElementById('prod_presentacion').value = '';
    document.getElementById('prod_atc').value = '';
    document.getElementById('prod_ium').value = '';
    document.getElementById('prod_barras').value = '';
    document.getElementById('prod_clase').value = '';
    document.getElementById('prod_via').value = 'ORAL';
    document.getElementById('prod_precio').value = '0.00';
    document.getElementById('prod_minimo').value = '10';
    document.getElementById('prod_maximo').value = '1000';
    document.getElementById('prod_estado').value = 'Activo';
    document.getElementById('prod_frio').checked = false;
    document.getElementById('prod_control').checked = false;
    document.getElementById('prod_alto_costo').checked = false;
    document.getElementById('prod_es_pos').checked = true;
    document.getElementById('prod_institucional').checked = false;
    document.getElementById('prod_biologico').checked = false;
    document.getElementById('prod_observaciones').value = '';
    cambiarTipoProductoFormulario('MEDICAMENTOS', 'nuevo');
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

function editarProducto(p) {
    const tipo = p.tipo_producto || 'MEDICAMENTOS';
    document.getElementById('prod_id').value = p.id;
    document.getElementById('prod_tipo_producto').value = tipo;
    document.getElementById('prod_sku').value = p.codigo_sku || '';
    document.getElementById('prod_cums').value = p.codigo_cums || '';
    document.getElementById('prod_invima').value = p.registro_invima || '';
    document.getElementById('prod_vigencia_invima').value = p.fecha_vigencia_invima || '';
    document.getElementById('prod_generico').value = p.nombre_generico || '';
    document.getElementById('prod_comercial').value = p.nombre_comercial || '';
    document.getElementById('prod_principio').value = p.principio_activo || '';
    document.getElementById('prod_laboratorio').value = p.fabricante_laboratorio || '';
    document.getElementById('prod_lab_nit').value = p.laboratorio_nit || '';
    document.getElementById('prod_concentracion').value = p.concentracion || '';
    document.getElementById('prod_forma').value = p.forma_farmaceutica || '';
    document.getElementById('prod_presentacion').value = p.presentacion_comercial || '';
    document.getElementById('prod_atc').value = p.codigo_atc || '';
    document.getElementById('prod_ium').value = p.codigo_ium || '';
    document.getElementById('prod_barras').value = p.codigo_barras || '';
    document.getElementById('prod_clase').value = p.clase_terapeutica || '';
    document.getElementById('prod_via').value = p.via_administracion || 'ORAL';
    document.getElementById('prod_precio').value = p.precio_referencia || '0.00';
    document.getElementById('prod_minimo').value = p.stock_minimo_alerta || '10';
    document.getElementById('prod_maximo').value = p.stock_maximo || '1000';
    document.getElementById('prod_estado').value = p.estado_activo || 'Activo';
    document.getElementById('prod_frio').checked = (p.requiere_cadena_frio == 1);
    document.getElementById('prod_control').checked = (p.es_control_especial == 1);
    document.getElementById('prod_alto_costo').checked = (p.es_alto_costo == 1);
    document.getElementById('prod_es_pos').checked = (p.es_pos === null || p.es_pos === undefined || p.es_pos == 1);
    document.getElementById('prod_institucional').checked = (p.uso_institucional == 1);
    document.getElementById('prod_biologico').checked = (p.es_biologico == 1);
    document.getElementById('prod_observaciones').value = p.observaciones || '';
    
    cambiarTipoProductoFormulario(tipo, 'editar');
    let labelTipo = 'Medicamento';
    if (tipo === 'DISPOSITIVOS MEDICOS') labelTipo = 'Dispositivo Médico';
    else if (tipo === 'INSUMOS') labelTipo = 'Insumo';
    else if (tipo === 'MEDICAMENTOS UNIRS') labelTipo = 'Medicamento UNIRS';
    document.getElementById('modalProdTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i> Editar ' + labelTipo;

    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

// Mapa de Laboratorios con sus NITs para auto-completar instantáneamente
const mapaLaboratorios = {!! json_encode($listaLaboratorios, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!};

function autoCompletarNit(labName) {
    if (!labName) return;
    const cleanName = labName.trim().toUpperCase();
    const match = mapaLaboratorios.find(l => l.fabricante_laboratorio.toUpperCase() === cleanName);
    if (match && match.laboratorio_nit) {
        document.getElementById('prod_lab_nit').value = match.laboratorio_nit;
    }
}
</script>
@endsection
