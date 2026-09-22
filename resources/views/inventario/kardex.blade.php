@extends('layouts.app')

@section('titulo', 'Kardex - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-receipt fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Kardex de Movimientos Físico & Valorado</h3>
                    <p class="text-muted small mb-0 mt-1">Auditoría cronológica y trazabilidad de Entradas, Salidas por Dispensación, Traslados y Saldos por Lote.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2 flex-wrap">
            <a href="{{ route('inventario.exportar') }}?bodega_id={{ urlencode((string)$filtroBodega) }}&tipo_movimiento={{ urlencode($filtroTipo) }}&fecha_desde={{ urlencode($filtroDesde) }}&fecha_hasta={{ urlencode($filtroHasta) }}&buscar={{ urlencode($filtroBuscar) }}" class="btn btn-primary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-export me-1"></i> Exportar Kardex / ERP
            </a>
            <a href="{{ route('inventario.dispensacion') }}" class="btn btn-outline-success fw-bold rounded-pill">
                <i class="fa-solid fa-pills me-1"></i> Dispensación
            </a>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Stock Físico
            </a>
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN (TOTALES GLOBALES FILTRADOS) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Movimientos Totales</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalMovimientos) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Registros auditados</small>
                    </div>
                    <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-list-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Total Entradas / Compras</span>
                        <h3 class="fw-bold mb-0 text-success">+{{ number_format($totalEntradas) }} <small class="fs-6 fw-normal text-muted">unid</small></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Ingresos al inventario</small>
                    </div>
                    <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-arrow-trend-up fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Total Salidas / Dispensadas</span>
                        <h3 class="fw-bold mb-0 text-danger">-{{ number_format($totalSalidas) }} <small class="fs-6 fw-normal text-muted">unid</small></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Entregas a pacientes y bajas</small>
                    </div>
                    <div class="p-3 rounded-circle bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-arrow-trend-down fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Valor Total Movilizado</span>
                        <h3 class="fw-bold mb-0 text-primary">${{ number_format($valorTotal, 0, ',', '.') }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Costo valorado en pesos</small>
                    </div>
                    <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-sack-dollar fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS AVANZADOS -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="inventario_kardex">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Buscar Medicamento / Lote / Ref:</label>
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Ej: Acetaminofén, Lote, TK-260907, SKU..." value="{{ $filtroBuscar }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-warehouse me-1"></i> Bodega:</label>
                    <select name="bodega_id" class="form-select form-select-sm">
                        <option value="">Todas las Bodegas</option>
                        
@foreach ($bodegas as $b)

                            <option value="{{ $b['id'] }}" {{ $filtroBodega === (int)$b['id'] ? 'selected' : '' }}>
                                {{ $b['nombre_bodega'] }}
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-layer-group me-1"></i> Tipo de Movimiento:</label>
                    <select name="tipo_movimiento" class="form-select form-select-sm">
                        <option value="">Todos los Tipos de Movimiento</option>
                        <option value="SALIDA_DISPENSACION" {{ $filtroTipo === 'SALIDA_DISPENSACION' ? 'selected' : '' }}>Salida por Dispensación</option>
                        <option value="ENTRADA_COMPRA" {{ $filtroTipo === 'ENTRADA_COMPRA' ? 'selected' : '' }}>Entrada por Compra</option>
                        <option value="TRASLADO_SALIDA" {{ $filtroTipo === 'TRASLADO_SALIDA' ? 'selected' : '' }}>Traslado (Salida)</option>
                        <option value="TRASLADO_ENTRADA" {{ $filtroTipo === 'TRASLADO_ENTRADA' ? 'selected' : '' }}>Traslado (Entrada)</option>
                        <option value="AJUSTE_POSITIVO" {{ $filtroTipo === 'AJUSTE_POSITIVO' ? 'selected' : '' }}>Ajuste Positivo (+)</option>
                        <option value="AJUSTE_NEGATIVO" {{ $filtroTipo === 'AJUSTE_NEGATIVO' ? 'selected' : '' }}>Ajuste Negativo (-)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-calendar me-1"></i> Rango Fechas:</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="fecha_desde" class="form-control" title="Desde" value="{{ $filtroDesde }}">
                        <input type="date" name="fecha_hasta" class="form-control" title="Hasta" value="{{ $filtroHasta }}">
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-bold w-100 shadow-sm"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
                    <a href="{{ route('inventario.kardex') }}" class="btn btn-sm btn-light border" title="Limpiar Filtros"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA KARDEX OPTIMIZADA CON PAGINACIÓN -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-timeline text-primary fs-5"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Historial Detallado de Movimientos Kardex</h6>
                    <small class="text-muted">
                        
@if ($totalMovimientos > 0)

                            Mostrando registros del <strong>{{ number_format($startRecord) }}</strong> al <strong>{{ number_format($endRecord) }}</strong> de <strong>{{ number_format($totalMovimientos) }}</strong>
                        
@else

                            0 registros encontrados
                        
@endif

                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-bold mb-0 d-none d-sm-inline">Ver por pág:</label>
                <select class="form-select form-select-sm" style="width: 80px;" onchange="window.location.href=this.value">
                    <option value="{{ buildKardexUrl(1, 25, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="{{ buildKardexUrl(1, 50, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="{{ buildKardexUrl(1, 100, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                    Página {{ $pageCurrent }} / {{ $totalPages }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 w-100" style="font-size: 0.83rem;">
                <thead class="table-light text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-3" style="width: 130px;">Fecha / Hora</th>
                        <th>Medicamento & Lote</th>
                        <th style="width: 175px;">Bodega / Documento</th>
                        <th style="width: 165px;">Tipo Movimiento</th>
                        <th class="text-center" style="width: 80px;">Cant.</th>
                        <th class="text-center" style="width: 85px;">Saldo</th>
                        <th class="text-end" style="width: 95px;">Costo Unit.</th>
                        <th class="text-end pe-3" style="width: 120px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($movimientos))

                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="p-4 text-muted">
                                    <i class="fa-solid fa-box-open fs-1 text-secondary opacity-50 d-block mb-3"></i>
                                    <h5 class="fw-bold text-dark mb-1">No se encontraron movimientos</h5>
                                    <p class="small mb-0">No hay registros que coincidan con los filtros seleccionados en el Kardex.</p>
                                </div>
                            </td>
                        </tr>
                    
@else

                        <?php foreach ($movimientos as $m): 
                            $tipoMov = $m['tipo_movimiento'] ?: 'SALIDA_DISPENSACION';
                            $esEntrada = in_array($tipoMov, ['ENTRADA_COMPRA', 'TRASLADO_ENTRADA', 'AJUSTE_POSITIVO', 'DEVOLUCION']);
                            
                            // Configurar etiqueta y estilo según tipo
                            $badgeHtml = '';
                            switch ($tipoMov) {
                                case 'SALIDA_DISPENSACION':
                                    $badgeHtml = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-arrow-down-long me-1"></i>Salida Dispensación</span>';
                                    break;
                                case 'ENTRADA_COMPRA':
                                    $badgeHtml = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-arrow-up-long me-1"></i>Entrada Compra</span>';
                                    break;
                                case 'TRASLADO_SALIDA':
                                    $badgeHtml = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-truck-arrow-right me-1"></i>Traslado (Salida)</span>';
                                    break;
                                case 'TRASLADO_ENTRADA':
                                    $badgeHtml = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-truck-ramp-box me-1"></i>Traslado (Entrada)</span>';
                                    break;
                                case 'AJUSTE_POSITIVO':
                                    $badgeHtml = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-circle-plus me-1"></i>Ajuste (+)</span>';
                                    break;
                                case 'AJUSTE_NEGATIVO':
                                    $badgeHtml = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5"><i class="fa-solid fa-circle-minus me-1"></i>Ajuste (-)</span>';
                                    break;
                                default:
                                    $badgeHtml = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5">' . htmlspecialchars($tipoMov) . '</span>';
                                    break;
                            } ?>

                        <tr>
                            <td class="ps-3 text-nowrap">
                                <span class="fw-bold text-dark d-block">{{ date('d/m/Y', strtotime($m['created_at'])) }}</span>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>{{ date('h:i A', strtotime($m['created_at'])) }}</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 280px;" title="{{ $m['nombre_generico'] ?: ($m['nombre_comercial'] ?: 'Medicamento') }}">
                                    {{ $m['nombre_generico'] ?: ($m['nombre_comercial'] ?: 'Medicamento') }}
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-wrap mt-0.5">
                                    <span class="badge bg-dark font-monospace px-1.5 py-0.5" style="font-size: 0.7rem;">{{ $m['numero_lote'] ?: '--' }}</span>
                                    
@if (!empty($m['codigo_sku']))

                                        <span class="badge bg-light text-muted border px-1 py-0.5" style="font-size: 0.68rem;">{{ $m['codigo_sku'] }}</span>
                                    
@endif

                                    @if (!empty($m['concentracion']))

                                        <small class="text-muted" style="font-size: 0.72rem;">{{ $m['concentracion'] }}</small>
                                    
@endif

                                    @if (!empty($m['fecha_vencimiento']))

                                        <small class="text-muted" style="font-size: 0.72rem;">• Vence: {{ date('m/Y', strtotime($m['fecha_vencimiento'])) }}</small>
                                    
@endif

                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark d-block text-truncate" style="max-width: 170px;" title="{{ $m['nombre_bodega'] }}">{{ $m['nombre_bodega'] }}</span>
                                <span class="font-monospace text-primary fw-bold" style="font-size: 0.75rem;">{{ $m['referencia_documento'] ?: '--' }}</span>
                            </td>
                            <td>
                                {{ $badgeHtml }}
                                @if (!empty($m['observaciones']))

                                    <small class="text-muted d-block text-truncate" style="max-width: 160px; font-size: 0.72rem;" title="{{ $m['observaciones'] }}">
                                        {{ $m['observaciones'] }}
                                    </small>
                                
@endif

                            </td>
                            <td class="text-center fw-bold fs-6 {{ $esEntrada ? 'text-success' : 'text-danger' }}">
                                {{ $esEntrada ? '+' : '-' }}{{ number_format(intval($m['cantidad'])) }}
                            </td>
                            <td class="text-center font-monospace fw-bold text-dark fs-6">
                                {{ number_format(intval($m['stock_nuevo'])) }}
                            </td>
                            <td class="text-end font-monospace small text-muted">
                                ${{ number_format(floatval($m['costo_unitario'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="text-end pe-3 small text-muted text-truncate" style="max-width: 120px;" title="{{ $m['usuario_nombre'] ?: 'Sistema' }}">
                                <i class="fa-regular fa-user me-1"></i>{{ $m['usuario_nombre'] ?: 'Sistema' }}
                            </td>
                        </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>

        <!-- BARRA DE PAGINACIÓN MODERNA -->
        
@if ($totalPages > 1)

            <div class="card-footer bg-white py-3 px-3 px-md-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Página <strong>{{ $pageCurrent }}</strong> de <strong>{{ $totalPages }}</strong> (Total: <strong>{{ number_format($totalMovimientos) }}</strong> movimientos)
                </div>

                <nav aria-label="Navegación de Kardex">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <!-- Primero -->
                        <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ buildKardexUrl(1, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" title="Primera página">
                                <i class="fa-solid fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Anterior -->
                        <li class="page-item {{ ($pageCurrent <= 1) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ buildKardexUrl($pageCurrent - 1, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" title="Página anterior">
                                <i class="fa-solid fa-angle-left"></i> Anterior
                            </a>
                        </li>

                        <!-- Números con ventana flotante inteligente -->
                        
@php
$rango     = 2;
$startPage = max(1, $pageCurrent - $rango);
$endPage   = min($totalPages, $pageCurrent + $rango);
@endphp
@if ($startPage > 1)
@php
echo '<li class="page-item"><a class="page-link rounded-pill px-3" href="' . buildKardexUrl(1, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) . '">1</a></li>';
@endphp
@if ($startPage > 2)
@php
echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0">...</span></li>';
@endphp
@endif
@endif
@for ($i = $startPage; $i <= $endPage; $i++)

                            <li class="page-item {{ ($i == $pageCurrent) ? 'active' : '' }}">
                                <a class="page-link rounded-pill px-3 fw-bold" href="{{ buildKardexUrl($i, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}">
                                    {{ $i }}
                                </a>
                            </li>
                        
@endfor


                        @php
if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link rounded-pill border-0">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link rounded-pill px-3" href="' . buildKardexUrl($totalPages, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) . '">' . $totalPages . '</a></li>';
                        }
@endphp


                        <!-- Siguiente -->
                        <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ buildKardexUrl($pageCurrent + 1, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" title="Página siguiente">
                                Siguiente <i class="fa-solid fa-angle-right"></i>
                            </a>
                        </li>

                        <!-- Último -->
                        <li class="page-item {{ ($pageCurrent >= $totalPages) ? 'disabled' : '' }}">
                            <a class="page-link rounded-pill px-3" href="{{ buildKardexUrl($totalPages, $perPage, $filtroBodega, $filtroTipo, $filtroBuscar, $filtroDesde, $filtroHasta) }}" title="Última página">
                                <i class="fa-solid fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        
@endif

    </div>

</div>
@endsection
