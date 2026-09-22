<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Services\FlujoIngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesController extends Controller
{
    public function __construct(private FlujoIngresoService $flujo) {}

    public function index(Request $request): View|StreamedResponse
    {
        $tab = (string) $request->query('tab', 'pacientes');
        $desde = (string) $request->query('fecha_desde', date('Y-m-d', strtotime('-6 days')));
        $hasta = (string) $request->query('fecha_hasta', date('Y-m-d'));
        $sede = (string) $request->query('sede_id', '');
        $eps = (string) $request->query('eps', '');
        $estado = (string) $request->query('estado', '');

        if ($request->query('export') === 'csv') {
            return $this->exportarCsv($tab, $desde, $hasta, $eps, $estado, $sede);
        }

        // Paginación en servidor (25/50/100/200)
        $pagina = max(1, (int) $request->query('pagina', 1));
        $porPagina = in_array((int) $request->query('por_pagina', 50), [25, 50, 100, 200], true) ? (int) $request->query('por_pagina', 50) : 50;

        return view('reportes.index', [
            'ingresoModel' => $this->flujo,
            'sedes_disponibles' => Sede::todasConEmpresa(),
            'tab' => $tab,
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'sede_filtro' => $sede,
            'eps_filtro' => $eps,
            'estado_filtro' => $estado,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'offset' => ($pagina - 1) * $porPagina,
        ]);
    }

    private function exportarCsv(string $tab, string $desde, string $hasta, string $eps, string $estado, string $sede): StreamedResponse
    {
        return response()->streamDownload(function () use ($tab, $desde, $hasta, $eps, $estado, $sede) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel

            match ($tab) {
                'tiempos' => $this->csvTiempos($out, $desde, $hasta, $sede),
                'salidas' => $this->csvSalidas($out, $desde, $hasta, $sede),
                'pendientes' => $this->csvPendientes($out, $desde, $hasta, $sede),
                default => $this->csvPacientes($out, $desde, $hasta, $eps, $estado, $sede),
            };

            fclose($out);
        }, "reporte_{$tab}_".date('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    private function csvPacientes($out, $desde, $hasta, $eps, $estado, $sede): void
    {
        fputcsv($out, ['Tiquete', 'Sede', 'Tipo Doc', 'Documento', 'Nombres', 'Apellidos', 'EPS', 'Teléfono', 'Email', 'Fecha/Hora Ingreso', 'Fecha/Hora Entrega (TV)', 'Fecha/Hora Salida', 'Tiempo SLA (Min)', 'Estado', 'Orientador', 'Cerrado Por']);
        foreach ($this->flujo->getReportePacientes($desde, $hasta, $eps, $estado, $sede) as $r) {
            fputcsv($out, [
                $r['ticket_numero'], $r['nombre_sede'] ?? 'Sede Principal', $r['tipo_documento'], $r['numero_documento'], $r['nombres'], $r['apellidos'],
                $r['eps_nombre'], $r['telefono'], $r['email'], $r['fecha_ingreso'], $r['fecha_llamado_entrega'] ?? '', $r['fecha_salida'] ?? '',
                $r['minutos_totales_sla'] ?? 0, $r['estado_tramite'], $r['orientador_nombre'], $r['usuario_salida_nombre'] ?? '',
            ]);
        }
    }

    private function csvTiempos($out, $desde, $hasta, $sede): void
    {
        fputcsv($out, ['Tiquete', 'Sede', 'Tipo Doc', 'Documento', 'Paciente', 'EPS', 'Fecha/Hora Ingreso', 'Fecha/Hora Entrega (TV)', 'Fecha/Hora Salida', 'Tiempo Total (Minutos)', 'Tiempo Trámite SLA (Min)', 'Estado Tramite', 'Cerrado Por']);
        foreach ($this->flujo->getReporteTiemposSLA($desde, $hasta, $sede) as $r) {
            fputcsv($out, [
                $r['ticket_numero'], $r['nombre_sede'] ?? 'Sede Principal', $r['tipo_documento'], $r['numero_documento'], $r['nombres'].' '.$r['apellidos'],
                $r['eps_nombre'], $r['fecha_ingreso'], $r['fecha_llamado_entrega'] ?? '', $r['fecha_salida'] ?? $r['fecha_finalizacion'],
                $r['tiempo_total_minutos'] ?? 0, $r['tiempo_tramite_farmacia_min'] ?? 0, $r['estado_tramite'], $r['usuario_salida_nombre'] ?? 'Sistema',
            ]);
        }
    }

    private function csvSalidas($out, $desde, $hasta, $sede): void
    {
        fputcsv($out, ['Tiquete', 'Sede', 'Tipo Doc', 'Documento', 'Paciente', 'EPS', 'Teléfono', 'Fecha Ingreso', 'Fecha Salida', 'Tiempo Total SLA (Minutos)', 'Estado', 'Cerrado Por', 'Observaciones']);
        foreach ($this->flujo->getReporteSalidas($desde, $hasta, $sede) as $r) {
            fputcsv($out, [
                $r['ticket_numero'], $r['nombre_sede'] ?? 'Sede Principal', $r['tipo_documento'], $r['numero_documento'], $r['nombres'].' '.$r['apellidos'],
                $r['eps_nombre'], $r['telefono'], $r['fecha_ingreso'], $r['fecha_salida'], $r['minutos_totales_sla'] ?? 0, $r['estado_tramite'],
                $r['usuario_salida_nombre'] ?? 'Sistema', $r['observacion_salida'] ?? '',
            ]);
        }
    }

    private function csvPendientes($out, $desde, $hasta, $sede): void
    {
        fputcsv($out, ['Tiquete', 'Sede', 'Tipo Doc', 'Documento', 'Paciente', 'EPS', 'Fecha Ingreso', 'Fecha Entrega/Actualización', 'Estado', 'Detalle Medicamentos Faltantes / Novedades']);
        foreach ($this->flujo->getReportePendientes($desde, $hasta, $sede) as $r) {
            fputcsv($out, [
                $r['ticket_numero'], $r['nombre_sede'] ?? 'Sede Principal', $r['tipo_documento'], $r['numero_documento'], $r['nombres'].' '.$r['apellidos'],
                $r['eps_nombre'], $r['fecha_ingreso'], $r['updated_at'] ?? '', $r['estado_tramite'],
                ! empty($r['faltantes_alistamiento']) ? $r['faltantes_alistamiento'] : ($r['observaciones_pendientes'] ?? ''),
            ]);
        }
    }

    /** Analítica de tiquetes por día y sede: creados vs cerrados vs pendientes y SLA. */
    public function ticketsSede(Request $request): View|JsonResponse|StreamedResponse
    {
        // Detalle de los tiquetes de un día y sede (modal)
        if ($request->query('ajax_detalle') === '1') {
            return response()->json($this->flujo->getDetalleTicketsDiaSede((string) $request->query('fecha', date('Y-m-d')), $request->query('sede_id')));
        }

        $desde = (string) $request->query('fecha_desde', date('Y-m-01'));
        $hasta = (string) $request->query('fecha_hasta', date('Y-m-d'));
        $sedeId = (string) $request->query('sede_id', '');

        $sedes = Sede::activas()->orderBy('nombre_sede')->get(['id', 'nombre_sede', 'codigo_sede'])->toArray();
        $reporteDiario = $this->flujo->getReporteTicketsPorDiaYSede($desde, $hasta, $sedeId);
        $consolidadoSedes = $this->flujo->getConsolidadoTicketsSedes($desde, $hasta, $sedeId);

        // Totales globales del rango; el SLA global se pondera por tiquetes cerrados
        $globalCreados = $globalCerrados = $globalPendientes = $globalCancelados = $sumaMinutos = $cerradosConSla = 0;
        foreach ($consolidadoSedes as $cs) {
            $globalCreados += (int) $cs['total_creados'];
            $globalCerrados += (int) $cs['total_cerrados'];
            $globalPendientes += (int) $cs['total_pendientes'];
            $globalCancelados += (int) $cs['total_cancelados'];
            if ($cs['avg_minutos_sla'] !== null && (int) $cs['total_cerrados'] > 0) {
                $sumaMinutos += (float) $cs['avg_minutos_sla'] * (int) $cs['total_cerrados'];
                $cerradosConSla += (int) $cs['total_cerrados'];
            }
        }
        $globalAvgSlaMin = $cerradosConSla > 0 ? round($sumaMinutos / $cerradosConSla, 1) : null;

        // Datos para gráficos: por sede y evolución diaria
        $labelsSedes = $dataCreadosSedes = $dataCerradosSedes = $dataPendientesSedes = $dataSlaSedesMin = $dataSlaSedesFmt = [];
        foreach ($consolidadoSedes as $cs) {
            $labelsSedes[] = $cs['nombre_sede'];
            $dataCreadosSedes[] = (int) $cs['total_creados'];
            $dataCerradosSedes[] = (int) $cs['total_cerrados'];
            $dataPendientesSedes[] = (int) $cs['total_pendientes'];
            $dataSlaSedesMin[] = $cs['avg_minutos_sla'] !== null ? (float) $cs['avg_minutos_sla'] : 0;
            $dataSlaSedesFmt[] = formatear_minutos_horas($cs['avg_minutos_sla']);
        }

        $diasUnicos = [];
        foreach ($reporteDiario as $rd) {
            $f = $rd['fecha'];
            $diasUnicos[$f] ??= ['creados' => 0, 'cerrados' => 0, 'pendientes' => 0];
            $diasUnicos[$f]['creados'] += (int) $rd['total_creados'];
            $diasUnicos[$f]['cerrados'] += (int) $rd['total_cerrados'];
            $diasUnicos[$f]['pendientes'] += (int) $rd['total_pendientes'];
        }
        ksort($diasUnicos);

        $datos = [
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'sede_id' => $sedeId,
            'sedes_disponibles' => $sedes,
            'reporteDiario' => $reporteDiario,
            'consolidadoSedes' => $consolidadoSedes,
            'globalCreados' => $globalCreados,
            'globalCerrados' => $globalCerrados,
            'globalPendientes' => $globalPendientes,
            'globalCancelados' => $globalCancelados,
            'globalTasaCierre' => $globalCreados > 0 ? round(($globalCerrados / $globalCreados) * 100, 1) : 0,
            'globalAvgSlaMin' => $globalAvgSlaMin,
            'globalAvgSlaFmt' => formatear_minutos_horas($globalAvgSlaMin),
            'labelsSedes' => $labelsSedes,
            'dataCreadosSedes' => $dataCreadosSedes,
            'dataCerradosSedes' => $dataCerradosSedes,
            'dataPendientesSedes' => $dataPendientesSedes,
            'dataSlaSedesMin' => $dataSlaSedesMin,
            'dataSlaSedesFmt' => $dataSlaSedesFmt,
            'chartFechasLabels' => array_keys($diasUnicos),
            'chartFechasCreados' => array_column($diasUnicos, 'creados'),
            'chartFechasCerrados' => array_column($diasUnicos, 'cerrados'),
            'chartFechasPendientes' => array_column($diasUnicos, 'pendientes'),
        ];

        if ($request->query('action') === 'export_csv') {
            return $this->csvTicketsSede($reporteDiario);
        }

        if ($request->query('action') === 'imprimir') {
            $nombreSede = 'Todas las Sedes';
            foreach ($sedes as $sd) {
                if ($sedeId !== '' && (string) $sd['id'] === $sedeId) {
                    $nombreSede = $sd['nombre_sede'].' ('.$sd['codigo_sede'].')';
                }
            }

            return view('reportes.tickets_sede_imprimir', $datos + ['nombreSedeFiltro' => $nombreSede]);
        }

        return view('reportes.tickets_sede', $datos);
    }

    private function csvTicketsSede(array $reporteDiario): StreamedResponse
    {
        $dias = ['Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'];

        return response()->streamDownload(function () use ($reporteDiario, $dias) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Fecha', 'Dia Semana / Tipo', 'Sede', 'Codigo Sede', 'Total Creados', 'Total Cerrados (Salida)', 'Total Pendientes', 'Total Cancelados', '% Conclusion / Cierre', 'Tiempo Promedio SLA (Horas y Min)', 'Tiempo Promedio SLA (Minutos)']);

            foreach ($reporteDiario as $row) {
                $nombreDia = date('l', strtotime($row['fecha']));
                fputcsv($out, [
                    $row['fecha'], ($dias[$nombreDia] ?? $nombreDia).' ('.($row['tipo_dia'] ?? 'HABIL').')', $row['nombre_sede'], $row['codigo_sede'],
                    $row['total_creados'], $row['total_cerrados'], $row['total_pendientes'], $row['total_cancelados'], $row['porcentaje_cierre'].'%',
                    formatear_minutos_horas($row['avg_minutos_sla']), $row['avg_minutos_sla'] !== null ? $row['avg_minutos_sla'].' min' : 'N/A',
                ]);
            }

            fclose($out);
        }, 'Reporte_Tiquetes_Dia_Sede_SLA_'.date('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
