    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Informe Analítico de Tiquetes & SLA - SISPAM</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #212529;
            }
            .print-page {
                background: #fff;
                max-width: 1050px;
                margin: 20px auto;
                padding: 30px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
            .header-report {
                border-bottom: 2px solid #0d6efd;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .kpi-box {
                border: 1px solid #dee2e6;
                border-radius: 6px;
                padding: 12px;
                text-align: center;
                background: #fdfdfd;
            }
            .table-print th {
                background-color: #f1f5f9 !important;
                color: #334155;
                font-size: 0.8rem;
                text-transform: uppercase;
                border-bottom: 2px solid #cbd5e1;
            }
            .table-print td {
                font-size: 0.85rem;
                padding: 6px 10px;
            }
            .no-print-bar {
                background: #1e293b;
                color: #fff;
                padding: 10px 20px;
                position: sticky;
                top: 0;
                z-index: 9999;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            @media print {
                body {
                    background: #fff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .no-print-bar {
                    display: none !important;
                }
                .print-page {
                    box-shadow: none !important;
                    margin: 0 !important;
                    padding: 10px !important;
                    max-width: 100% !important;
                    border-radius: 0 !important;
                }
                .table-print th {
                    background-color: #eee !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                @page {
                    size: letter portrait;
                    margin: 1.2cm;
                }
            }
        </style>
    </head>
    <body>

        <!-- Barra de Control para Pantalla -->
        <div class="no-print-bar d-flex justify-content-between align-items-center">
            <div class="fw-bold fs-6">
                <i class="fa-solid fa-file-invoice me-2 text-info"></i> Vista Previa de Impresión / PDF
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Imprimir / Guardar como PDF
                </button>
                <button class="btn btn-sm btn-outline-light fw-bold px-3" onclick="window.close()">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>
        </div>

        <div class="print-page">
            <!-- Encabezado Oficial -->
            <div class="header-report d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold text-primary mb-0">SISPAM</h3>
                    <div class="text-muted small fw-semibold">Sistema Integral de Seguimiento y Pacientes en Medicamentos</div>
                    <h5 class="fw-bold text-dark mt-2 mb-0">INFORME ANALÍTICO DE TIQUETES Y TIEMPOS SLA POR SEDE</h5>
                </div>
                <div class="text-end small text-muted">
                    <div><strong>Rango:</strong> {{ date('d/m/Y', strtotime($fecha_desde)) }} al {{ date('d/m/Y', strtotime($fecha_hasta)) }}</div>
                    <div><strong>Sede:</strong> {{ $nombreSedeFiltro }}</div>
                    <div><strong>Generado:</strong> {{ date('d/m/Y H:i') }}</div>
                    <div><strong>Usuario:</strong> {{ sesion('nombre_completo') ?? 'Administrador' }}</div>
                </div>
            </div>

            <!-- Resumen de Métricas Globales -->
            <div class="row g-2 mb-4">
                <div class="col-3">
                    <div class="kpi-box" style="border-left: 4px solid #0d6efd;">
                        <small class="text-muted text-uppercase fw-bold d-block">Total Creados</small>
                        <span class="fs-4 fw-bold text-primary">{{ number_format($globalCreados) }}</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="kpi-box" style="border-left: 4px solid #198754;">
                        <small class="text-muted text-uppercase fw-bold d-block">Tiquetes Cerrados</small>
                        <span class="fs-4 fw-bold text-success">{{ number_format($globalCerrados) }}</span>
                        <div class="small text-success fw-bold">{{ $globalTasaCierre }}% Conclusión</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="kpi-box" style="border-left: 4px solid {{ $globalPendientes > 0 ? '#ffc107' : '#0dcaf0' }};">
                        <small class="text-muted text-uppercase fw-bold d-block">Pendientes de Salida</small>
                        <span class="fs-4 fw-bold {{ $globalPendientes > 0 ? 'text-warning' : 'text-info' }}">{{ number_format($globalPendientes) }}</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="kpi-box" style="border-left: 4px solid #6f42c1;">
                        <small class="text-muted text-uppercase fw-bold d-block">SLA Promedio Global</small>
                        <span class="fs-4 fw-bold" style="color: #6f42c1;">{{ $globalAvgSlaFmt }}</span>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $globalAvgSlaMin !== null ? "{$globalAvgSlaMin} min promedio" : '---' }}</div>
                    </div>
                </div>
            </div>

            <!-- Consolidado por Sede -->
            <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                <i class="fa-solid fa-building me-1 text-primary"></i> Consolidado de Operación por Sede
            </h6>
            <table class="table table-bordered table-print align-middle mb-4">
                <thead>
                    <tr>
                        <th>Sede</th>
                        <th>Código</th>
                        <th class="text-center">Total Creados</th>
                        <th class="text-center">Total Cerrados</th>
                        <th class="text-center">Pendientes</th>
                        <th class="text-center">% Conclusión</th>
                        <th class="text-center">SLA Promedio (Horas:Min)</th>
                    </tr>
                </thead>
                <tbody>
                    
@foreach ($consolidadoSedes as $cs)

                        <tr>
                            <td class="fw-bold">{{ $cs['nombre_sede'] }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $cs['codigo_sede'] }}</span></td>
                            <td class="text-center fw-bold text-primary">{{ $cs['total_creados'] }}</td>
                            <td class="text-center fw-bold text-success">{{ $cs['total_cerrados'] }}</td>
                            <td class="text-center fw-bold {{ $cs['total_pendientes'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $cs['total_pendientes'] }}</td>
                            <td class="text-center fw-bold">{{ $cs['porcentaje_cierre'] }}%</td>
                            <td class="text-center fw-bold">
                                {{ formatear_minutos_horas($cs['avg_minutos_sla']) }}
                                @if ($cs['avg_minutos_sla'] !== null)

                                    <small class="text-muted fw-normal">({{ $cs['avg_minutos_sla'] }} min)</small>
                                
@endif

                            </td>
                        </tr>
                    
@endforeach

                </tbody>
            </table>

            <!-- Matriz Cronológica Diaria -->
            <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                <i class="fa-solid fa-calendar-days me-1 text-primary"></i> Detalle Diario de Tiquetes por Sede
            </h6>
            <table class="table table-bordered table-striped table-print align-middle">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día / Tipo</th>
                        <th>Sede</th>
                        <th class="text-center">Creados</th>
                        <th class="text-center">Cerrados</th>
                        <th class="text-center">Pendientes</th>
                        <th class="text-center">% Cierre</th>
                        <th class="text-center">SLA Promedio (Horas:Min)</th>
                    </tr>
                </thead>
                <tbody>
                    
@if (empty($reporteDiario))

                        <tr><td colspan="8" class="text-center py-3 text-muted">No se registran tiquetes en el rango de fechas.</td></tr>
                    
@else

                        @foreach ($reporteDiario as $row)

                            @php
$diasEs = [
                                    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
                                    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
                                ];
                                $diaIngles = date('l', strtotime($row['fecha']));
                                $diaNombre = $diasEs[$diaIngles] ?? $diaIngles;
@endphp

                            <tr>
                                <td class="fw-bold">{{ date('d/m/Y', strtotime($row['fecha'])) }}</td>
                                <td>{{ $diaNombre }} <small class="text-muted">({{ $row['tipo_dia'] }})</small></td>
                                <td>{{ $row['nombre_sede'] }}</td>
                                <td class="text-center fw-bold">{{ $row['total_creados'] }}</td>
                                <td class="text-center fw-bold text-success">{{ $row['total_cerrados'] }}</td>
                                <td class="text-center fw-bold {{ $row['total_pendientes'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $row['total_pendientes'] }}</td>
                                <td class="text-center fw-bold">{{ $row['porcentaje_cierre'] }}%</td>
                                <td class="text-center fw-bold">
                                    {{ formatear_minutos_horas($row['avg_minutos_sla']) }}
                                    @if ($row['avg_minutos_sla'] !== null)

                                        <small class="text-muted fw-normal">({{ $row['avg_minutos_sla'] }} m)</small>
                                    
@endif

                                </td>
                            </tr>
                        
@endforeach

                    @endif

                </tbody>
            </table>

            <!-- Pie de Reporte -->
            <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                <div>SISPAM - Módulo Analítico & Tiempos SLA</div>
                <div>Página 1 de 1</div>
            </div>
        </div>

        <script>
            window.addEventListener('load', () => {
                setTimeout(() => {
                    window.print();
                }, 400);
            });
        </script>
    </body>
    </html>
