@extends('layouts.app')

@section('titulo', 'Inventario - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-boxes-stacked fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-5 d-flex align-items-center gap-2 flex-wrap">
                        <span>Gestión de Inventario & Bodegas Farmacéuticas</span>
                        <span class="badge bg-light text-success border border-success border-opacity-25 fs-6 fw-bold px-2 py-1 rounded-pill">
                            <i class="fa-solid fa-building me-1"></i> {{ sesion('active_sede_nombre') ?? 'Sede Principal' }}
                        </span>
                    </h3>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                        Control FEFO (Res. 1403/2007), trazabilidad CUMS/INVIMA y saldos en tiempo real.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex flex-wrap gap-1 justify-content-md-end">
            <a href="{{ route('inventario.importar') }}" class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-import me-1"></i> Importar
            </a>
            <a href="{{ route('inventario.exportar') }}" class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-export me-1"></i> Exportar
            </a>
            <a href="{{ route('inventario.entradas') }}" class="btn btn-sm btn-outline-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-invoice me-1"></i> Entradas
            </a>
            <a href="{{ route('inventario.bodegas') }}" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-warehouse me-1"></i> Bodegas</a>
            <a href="{{ route('inventario.traslados') }}" class="btn btn-sm btn-outline-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-truck-ramp-box me-1"></i> Traslados
            </a>
            <a href="{{ route('inventario.productos') }}" class="btn btn-sm btn-outline-dark fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-pills me-1"></i> Catálogo
            </a>
        </div>
    </div>

    <!-- TARJETAS KPIS & SEMÁFORO FEFO INVIMA -->
    <div class="row g-2 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-2 px-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total Lotes en Stock</div>
                        <h4 class="fw-bold text-dark mb-0 mt-0">{{ number_format($kpis['total_lotes']) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">{{ number_format($kpis['total_unidades']) }} unid. disp.</small>
                    </div>
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle fs-5">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-2 px-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-success small fw-bold text-uppercase" style="font-size: 0.72rem;">🟢 Vigentes (> 6 Meses)</div>
                        <h4 class="fw-bold text-success mb-0 mt-0">{{ number_format($kpis['semaforo_verde']) }} Lotes</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">Excelente vigencia</small>
                    </div>
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle fs-5">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-2 px-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-warning small fw-bold text-uppercase" style="font-size: 0.72rem;">🟡 Rotación FEFO (3-6 Meses)</div>
                        <h4 class="fw-bold text-warning mb-0 mt-0">{{ number_format($kpis['semaforo_amarillo']) }} Lotes</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">Dispensación prioritaria</small>
                    </div>
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle fs-5">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-2 px-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-danger small fw-bold text-uppercase" style="font-size: 0.72rem;">🔴 Críticos & En Riesgo</div>
                        <h4 class="fw-bold text-danger mb-0 mt-0">{{ number_format($kpis['semaforo_rojo'] + $kpis['semaforo_vencido'] + ($kpis['total_agotados'] ?? 0)) }} Lotes</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            {{ number_format($kpis['semaforo_rojo']) }} por vencer (&lt; 3m) &bull; {{ number_format($kpis['semaforo_vencido']) }} vencidos{{ ($kpis['total_agotados'] ?? 0) > 0 ? ' &bull; ' . number_format($kpis['total_agotados']) . ' agotados' : '' }}
                        </small>
                    </div>
                    <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-circle fs-5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS Y BÚSQUEDA -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
        <div class="card-body p-2 px-3">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="inventario">
                
                <div class="col-lg-4 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-secondary-subtle"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-secondary-subtle" placeholder="Buscar medicamento, CUMS, lote..." value="{{ $busqueda }}">
                    </div>
                </div>

                <div class="col-lg-3 col-md-3">
                    <select name="bodega_id" class="form-select form-select-sm border-secondary-subtle" title="Seleccionar Bodega">
                        <option value="">🏢 Todas las Bodegas</option>
                        
@foreach ($bodegas as $b)

                            <option value="{{ $b['id'] }}" {{ $filtroBodega === (int)$b['id'] ? 'selected' : '' }}>
                                {{ $b['nombre_bodega'] }} ({{ $b['codigo_bodega'] }})
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-lg-2 col-md-2">
                    <select name="semaforo" class="form-select form-select-sm border-secondary-subtle" title="Filtrar por Semáforo FEFO">
                        <option value="">🚦 Semáforo: Todos</option>
                        <option value="VERDE" {{ $filtroSemaforo === 'VERDE' ? 'selected' : '' }}>🟢 Verde (> 6m)</option>
                        <option value="AMARILLO" {{ $filtroSemaforo === 'AMARILLO' ? 'selected' : '' }}>🟡 Amarillo (3-6m)</option>
                        <option value="ROJO" {{ $filtroSemaforo === 'ROJO' ? 'selected' : '' }}>🔴 Rojo (< 3m)</option>
                        <option value="VENCIDO" {{ $filtroSemaforo === 'VENCIDO' ? 'selected' : '' }}>⚫ Vencidos</option>
                        <option value="AGOTADO" {{ $filtroSemaforo === 'AGOTADO' ? 'selected' : '' }}>🚫 Sin Stock (0)</option>
                    </select>
                </div>

                <div class="col-lg-1 col-md-2">
                    <select name="per_page" class="form-select form-select-sm border-secondary-subtle" onchange="this.form.submit()" title="Registros por página">
                        <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25 / pág</option>
                        <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50 / pág</option>
                        <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100 / pág</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-12 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary fw-bold flex-grow-1 rounded-3 shadow-sm">
                        <i class="fa-solid fa-filter me-1"></i> Filtrar
                    </button>
                    
@if (!empty($busqueda) || !empty($filtroBodega) || !empty($filtroSemaforo) || $perPage != 25)

                        <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary rounded-3" title="Limpiar"><i class="fa-solid fa-xmark"></i></a>
                    
@endif

                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE EXISTENCIAS POR LOTE & FEFO (AJUSTADA 100% SIN SCROLL HORIZONTAL) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <i class="fa-solid fa-table-list text-primary"></i>
                <h6 class="fw-bold text-dark mb-0">Existencias Físicas y Lotes en Bodega</h6>
                <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 0.72rem;">{{ number_format($totalRegistros) }} Total</span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1" style="font-size: 0.72rem;">Pág. {{ $pageCurrent }} / {{ $totalPages }}</span>
            </div>
            <div>
                <a href="{{ route('inventario.kardex') }}" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold py-1 px-3" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-receipt me-1"></i> Kardex
                </a>
            </div>
        </div>

        <div class="table-responsive w-100" style="overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%; font-size: 0.82rem;">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 32%;">Medicamento / Principio Activo</th>
                        <th style="width: 14%;">CUMS / INVIMA</th>
                        <th style="width: 12%;">Bodega / Sede</th>
                        <th style="width: 13%;">Lote / Laboratorio</th>
                        <th style="width: 13%;">Vencimiento / Semáforo</th>
                        <th class="text-center" style="width: 8%;">Stock</th>
                        <th class="text-end pe-3" style="width: 8%;">Costo</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($stockList))

                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-box-open fs-2 mb-2 d-block opacity-40"></i>
                                No se encontraron registros con los criterios seleccionados.
                            </td>
                        </tr>
                    
@else

                        @foreach ($stockList as $row)
@php
$sem = $row['semaforo'];
$esCadenaFrio = ($row['requiere_cadena_frio'] == 1);
$esControlEsp = ($row['es_control_especial'] == 1);
$cantStock = intval($row['cantidad_actual']);
$sinStock = ($cantStock <= 0);
@endphp

                        <tr class="{{ $sinStock ? 'table-danger bg-danger bg-opacity-10' : '' }}">
                            <td class="ps-3 text-break">
                                <div class="fw-bold text-dark {{ $sinStock ? 'text-danger' : '' }}" style="line-height: 1.2;">
                                    {{ $row['nombre_generico'] }}
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                                    {{ ($row['concentracion'] ? $row['concentracion'] . ' • ' : '') . ($row['forma_farmaceutica'] ? $row['forma_farmaceutica'] . ' • ' : '') . ($row['nombre_comercial'] ?: 'Genérico') }}
                                </div>
                                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.68rem; padding: 1px 4px;">SKU: {{ $row['codigo_sku'] }}</span>
                                    
@if ($sinStock)

                                        <span class="badge bg-danger text-white" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>SIN STOCK</span>
                                    
@endif

                                    @if ($esCadenaFrio)

                                        <span class="badge bg-info bg-opacity-25 text-primary border border-info border-opacity-50" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-snowflake me-1"></i>2°C-8°C</span>
                                    
@endif

                                    @if ($esControlEsp)

                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50" style="font-size: 0.65rem; padding: 1px 4px;"><i class="fa-solid fa-shield-halved me-1"></i>FNE</span>
                                    
@endif

                                </div>
                            </td>
                            <td class="text-break">
                                <div class="fw-bold text-primary font-monospace" style="font-size: 0.76rem;">
                                    <i class="fa-solid fa-barcode text-secondary me-1"></i>{{ $row['codigo_cums'] ?: '--' }}
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.72rem;">
                                    INV: {{ $row['registro_invima'] ?: 'En trámite' }}
                                </div>
                            </td>
                            <td class="text-break">
                                <span class="badge bg-light text-dark border fw-bold text-truncate d-inline-block" style="max-width: 100%; font-size: 0.72rem; padding: 2px 4px;">
                                    <i class="fa-solid fa-warehouse text-primary me-1"></i>{{ $row['nombre_bodega'] ?? 'Sin asignar' }}
                                </span>
                                <div class="text-muted text-truncate" style="font-size: 0.70rem;">{{ $row['nombre_sede'] ?? 'N/A' }}</div>
                            </td>
                            <td class="text-break">
                                
@if (!empty($row['numero_lote']) && $row['numero_lote'] !== 'SIN LOTE REGISTRADO')

                                    <span class="badge bg-dark text-white font-monospace fw-bold" style="font-size: 0.72rem; padding: 2px 5px;">
                                        {{ $row['numero_lote'] }}
                                    </span>
                                
@else

                                    <span class="badge bg-secondary text-white font-monospace" style="font-size: 0.68rem; padding: 1px 4px;">
                                        Sin Lote
                                    </span>
                                
@endif

                                <div class="text-muted text-truncate" style="font-size: 0.70rem;" title="{{ $row['fabricante_laboratorio'] ?: 'GENÉRICO' }}">
                                    {{ $row['fabricante_laboratorio'] ?: 'GENÉRICO' }}
                                </div>
                            </td>
                            <td class="text-break">
                                
@if (!empty($row['fecha_vencimiento']))

                                    <div class="fw-bold text-dark" style="font-size: 0.76rem;">
                                        <i class="fa-solid fa-calendar-day me-1 text-secondary"></i>{{ date('d/m/Y', strtotime($row['fecha_vencimiento'])) }}
                                    </div>
                                    <span class="badge {{ $sem['clase'] }} rounded-pill text-truncate d-inline-block" style="max-width: 100%; font-size: 0.68rem; padding: 1px 5px;">
                                        <i class="fa-solid {{ $sem['icono'] }} me-1"></i>{{ $sem['texto'] }}
                                    </span>
                                
@else

                                    <span class="badge bg-danger rounded-pill" style="font-size: 0.68rem; padding: 1px 5px;">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i>Agotado
                                    </span>
                                
@endif

                            </td>
                            <td class="text-center">
                                
@if ($sinStock)

                                    <span class="badge bg-danger text-white fw-bold" style="font-size: 0.72rem; padding: 2px 4px;">0</span>
                                
@else

                                    <div class="fw-bold text-success font-monospace" style="font-size: 0.95rem;">
                                        {{ number_format($cantStock) }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.68rem;">unid.</div>
                                
@endif

                            </td>
                            <td class="text-end pe-3">
                                <div class="fw-bold text-dark" style="font-size: 0.78rem;">
                                    ${{ number_format($row['costo_unitario'], 2) }}
                                </div>
                                <div class="text-muted" style="font-size: 0.68rem;">
                                    ${{ number_format($cantStock * $row['costo_unitario'], 2) }}
                                </div>
                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>

        <!-- BARRA DE PAGINACIÓN -->
        
@if ($totalRegistros > 0)

            <div class="card-footer bg-white py-2 px-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" style="font-size: 0.75rem;">
                    Mostrando <strong>{{ number_format(min($offset + 1, $totalRegistros)) }}</strong> a <strong>{{ number_format(min($offset + count($stockList), $totalRegistros)) }}</strong> de <strong>{{ number_format($totalRegistros) }}</strong> registros
                </div>

                
@if ($totalPages > 1)

                    <nav aria-label="Navegación de páginas">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            
@php
$queryParams = $_GET;
                            unset($queryParams['p']);
                            $baseQuery = http_build_query($queryParams);
@endphp

                            <!-- Botón Primero -->
                            <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                                <a class="page-link rounded-pill px-2 py-1" href="index.php?{{ $baseQuery }}&p=1" title="Primera página" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-angles-left"></i>
                                </a>
                            </li>

                            <!-- Botón Anterior -->
                            <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                                <a class="page-link rounded-pill px-2 py-1" href="index.php?{{ $baseQuery }}&p={{ $pageCurrent - 1 }}" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-angle-left"></i> Ant.
                                </a>
                            </li>

                            <!-- Números con ventana inteligente -->
                            
@php
$rango = 2;
$startPage = max(1, $pageCurrent - $rango);
$endPage   = min($totalPages, $pageCurrent + $rango);
@endphp
@if ($startPage > 1)
@php
echo '<li class="page-item"><a class="page-link rounded-pill px-2 py-1" style="font-size: 0.75rem;" href="index.php?' . $baseQuery . '&p=1">1</a></li>';
@endphp
@if ($startPage > 2)
@php
echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0 py-1" style="font-size: 0.75rem;">...</span></li>';
@endphp
@endif
@endif
@for ($i = $startPage; $i <= $endPage; $i++)

                                <li class="page-item {{ ($i == $pageCurrent) ? 'active' : '' }}">
                                    <a class="page-link rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;" href="index.php?{{ $baseQuery }}&p={{ $i }}">
                                        {{ $i }}
                                    </a>
                                </li>
                            
@endfor


                            @php
if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0 py-1" style="font-size: 0.75rem;">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link rounded-pill px-2 py-1" style="font-size: 0.75rem;" href="index.php?' . $baseQuery . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                            }
@endphp


                            <!-- Botón Siguiente -->
                            <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                                <a class="page-link rounded-pill px-2 py-1" style="font-size: 0.75rem;" href="index.php?{{ $baseQuery }}&p={{ $pageCurrent + 1 }}">
                                    Sig. <i class="fa-solid fa-angle-right"></i>
                                </a>
                            </li>

                            <!-- Botón Último -->
                            <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                                <a class="page-link rounded-pill px-2 py-1" style="font-size: 0.75rem;" href="index.php?{{ $baseQuery }}&p={{ $totalPages }}" title="Última página">
                                    <i class="fa-solid fa-angles-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                
@endif

            </div>
        
@endif

    </div>

</div>
@endsection
