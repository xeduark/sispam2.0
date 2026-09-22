@extends('layouts.app')

@section('titulo', 'Exportar Inventario - '.config('app.name'))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- CABECERA -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-file-export fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0 fs-4">Exportación de Movimientos Kardex & Cruce ERP</h3>
                    <p class="text-muted small mb-0 mt-1">Genera reportes y extractos en formato CSV / Excel para sincronizar con el software anterior, ERPs o auditorías.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
            <a href="{{ route('inventario.importar') }}" class="btn btn-outline-success fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-file-import me-1"></i> Importar desde Excel
            </a>
            <a href="{{ route('inventario.kardex') }}" class="btn btn-outline-secondary fw-bold rounded-pill shadow-sm">
                <i class="fa-solid fa-receipt me-1"></i> Ver Kardex
            </a>
        </div>
    </div>

    <!-- TARJETAS DE ACCESO RÁPIDO PARA OTRAS EXPORTACIONES -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-success fw-bold small text-uppercase"><i class="fa-solid fa-pills me-1"></i> Catálogo Maestro Completo</span>
                    <i class="fa-solid fa-database text-success fs-4"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">11.880+ Artículos</h4>
                <p class="text-muted small mb-3">Descarga todos los medicamentos, laboratorios, CUMS, INVIMA y costos.</p>
                <a href="{{ route('inventario.exportar') }}?action_export=catalogo" class="btn btn-sm btn-success fw-bold rounded-pill w-100">
                    <i class="fa-solid fa-download me-1"></i> Descargar Catálogo (CSV/Excel)
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-warning fw-bold small text-uppercase"><i class="fa-solid fa-boxes-stacked me-1"></i> Toma Física & Saldos Actuales</span>
                    <i class="fa-solid fa-clipboard-check text-warning fs-4"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Existencias & Lotes</h4>
                <p class="text-muted small mb-3">Reporte de stock actual por bodega con semáforo de vencimiento FEFO.</p>
                <a href="{{ route('inventario.exportar') }}?action_export=saldos" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill w-100">
                    <i class="fa-solid fa-download me-1"></i> Descargar Toma Física (CSV/Excel)
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-primary fw-bold small text-uppercase"><i class="fa-solid fa-arrows-rotate me-1"></i> Cruce con Software Anterior</span>
                    <i class="fa-solid fa-laptop-file text-primary fs-4"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($resumenFiltro['total_count'] ?? 0) }} Movimientos</h4>
                <p class="text-muted small mb-3">Movimientos filtrados listos para exportar en formato compatible.</p>
                <a href="{{ route('inventario.exportar') }}?action_export=kardex&bodega_id={{ urlencode($filtroBodega) }}&tipo_movimiento={{ urlencode($filtroTipo) }}&fecha_desde={{ urlencode($filtroDesde) }}&fecha_hasta={{ urlencode($filtroHasta) }}&buscar={{ urlencode($filtroBuscar) }}" class="btn btn-sm btn-primary fw-bold rounded-pill w-100">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar Kardex Filtrado (1-Clic)
                </a>
            </div>
        </div>
    </div>

    <!-- PANEL DE FILTROS PARA EXPORTACIÓN KARDEX -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-header bg-white py-3 px-4 border-0">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-filter me-2 text-primary"></i> Filtros de Auditoría y Exportación de Kardex</h5>
        </div>
        <div class="card-body p-4 pt-0">
            <form method="GET" action="{{ route('inventario.exportar') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control rounded-3" value="{{ $filtroDesde }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control rounded-3" value="{{ $filtroHasta }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Bodega / Almacén</label>
                    <select name="bodega_id" class="form-select rounded-3">
                        <option value="">-- Todas las Bodegas --</option>
                        
@foreach ($bodegas as $b)

                            <option value="{{ $b['id'] }}" {{ ($filtroBodega == $b['id']) ? 'selected' : '' }}>
                                {{ $b['nombre_bodega'] }}
                            </option>
                        
@endforeach

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Tipo de Movimiento</label>
                    <select name="tipo_movimiento" class="form-select rounded-3">
                        <option value="">-- Todos los Tipos --</option>
                        <option value="SALIDA_DISPENSACION" {{ ($filtroTipo === 'SALIDA_DISPENSACION') ? 'selected' : '' }}>Salida por Dispensación</option>
                        <option value="ENTRADA_COMPRA" {{ ($filtroTipo === 'ENTRADA_COMPRA') ? 'selected' : '' }}>Entrada por Compra</option>
                        <option value="TRASLADO_SALIDA" {{ ($filtroTipo === 'TRASLADO_SALIDA') ? 'selected' : '' }}>Traslado (Salida)</option>
                        <option value="TRASLADO_ENTRADA" {{ ($filtroTipo === 'TRASLADO_ENTRADA') ? 'selected' : '' }}>Traslado (Entrada)</option>
                        <option value="AJUSTE_POSITIVO" {{ ($filtroTipo === 'AJUSTE_POSITIVO') ? 'selected' : '' }}>Ajuste Positivo</option>
                        <option value="AJUSTE_NEGATIVO" {{ ($filtroTipo === 'AJUSTE_NEGATIVO') ? 'selected' : '' }}>Ajuste Negativo</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Buscar Medicamento, SKU, Lote o Doc. Referencia</label>
                    <input type="text" name="buscar" class="form-control rounded-3" placeholder="Ej: Acetaminofen, MED-00101, LT-2024, TKT-0045..." value="{{ $filtroBuscar }}">
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('inventario.exportar') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- VISTA PREVIA DE LOS REGISTROS A EXPORTAR -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-0">
            <div>
                <h5 class="fw-bold text-dark mb-0">Vista Previa de Movimientos (Primeros 50 de {{ number_format($resumenFiltro['total_count'] ?? 0) }})</h5>
                <small class="text-muted">Total Unidades: {{ number_format($resumenFiltro['total_unidades'] ?? 0) }} | Valor: ${{ number_format($resumenFiltro['total_valor'] ?? 0, 0, ',', '.') }}</small>
            </div>
            <div>
                <a href="{{ route('inventario.exportar') }}?action_export=kardex&bodega_id={{ urlencode($filtroBodega) }}&tipo_movimiento={{ urlencode($filtroTipo) }}&fecha_desde={{ urlencode($filtroDesde) }}&fecha_hasta={{ urlencode($filtroHasta) }}&buscar={{ urlencode($filtroBuscar) }}" class="btn btn-success fw-bold rounded-pill shadow-sm px-4">
                    <i class="fa-solid fa-file-arrow-down me-2"></i> Descargar Reporte Completo (CSV / Excel)
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Fecha/Hora</th>
                        <th>Tipo</th>
                        <th>SKU / ID</th>
                        <th>Medicamento</th>
                        <th>Laboratorio / Marca</th>
                        <th>Lote</th>
                        <th>Bodega</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Costo Unit.</th>
                        <th class="text-end">Total</th>
                        <th>Referencia</th>
                        <th class="pe-4">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($previewRows))

                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No se encontraron movimientos en el rango de fechas y filtros seleccionados.
                            </td>
                        </tr>
                    
@else

                        @foreach ($previewRows as $r)
@php
$esSalida = in_array($r['tipo_movimiento'], ['SALIDA_DISPENSACION', 'TRASLADO_SALIDA', 'AJUSTE_NEGATIVO']);
@endphp

                            <tr>
                                <td class="ps-4 text-nowrap"><small>{{ date('d/m/Y H:i', strtotime($r['created_at'])) }}</small></td>
                                <td>
                                    <span class="badge rounded-pill {{ $esSalida ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}">
                                        {{ $r['tipo_movimiento'] }}
                                    </span>
                                </td>
                                <td><code class="text-dark fw-bold">{{ $r['idarticulo'] }}</code></td>
                                <td>
                                    <strong class="d-block text-dark">{{ $r['nombre_comercial'] ?: $r['nombre_generico'] }}</strong>
                                    <small class="text-muted">{{ $r['nombre_generico'] }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $r['laboratorio_marca'] ?: 'GENERICO' }}</span></td>
                                <td><span class="badge bg-light text-primary border">{{ $r['lote'] }}</span></td>
                                <td><small class="text-muted">{{ $r['nombre_bodega'] }}</small></td>
                                <td class="text-center fw-bold {{ $esSalida ? 'text-danger' : 'text-success' }}">
                                    {{ $esSalida ? '-' : '+' }}{{ number_format($r['cantidad']) }}
                                </td>
                                <td class="text-end">${{ number_format($r['costo_unitario'], 2) }}</td>
                                <td class="text-end fw-bold text-dark">${{ number_format($r['costo_total'], 2) }}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $r['referencia_documento'] ?: 'N/A' }}</span></td>
                                <td class="pe-4"><small class="text-muted">{{ $r['usuario_nombre'] ?: 'SISTEMA' }}</small></td>
                            </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
