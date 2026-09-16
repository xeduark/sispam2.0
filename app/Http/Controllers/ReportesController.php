<?php

namespace App\Http\Controllers;

use App\Services\IngresoService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesController extends Controller
{
    public function index(Request $request, IngresoService $ingresos): View|StreamedResponse
    {
        $tab = $request->query('tab', 'pacientes');
        $fecha_desde = $request->query('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fecha_hasta = $request->query('fecha_hasta', now()->format('Y-m-d'));
        $eps_filtro = $request->query('eps', '');
        $estado_filtro = $request->query('estado', '');

        if ($request->query('export') === 'csv') {
            return $this->exportarCsv($tab, $fecha_desde, $fecha_hasta, $eps_filtro, $ingresos);
        }

        $datos = match ($tab) {
            'tiempos' => ['listaTiempos' => $ingresos->reporteTiemposSLA($fecha_desde, $fecha_hasta)],
            'pendientes' => ['listaPendientes' => $ingresos->reportePendientes($fecha_desde, $fecha_hasta)],
            'productividad' => ['listaProd' => $ingresos->reporteProductividad($fecha_desde, $fecha_hasta)],
            'eps' => ['listaEPS' => $ingresos->reportePorEPS($fecha_desde, $fecha_hasta)],
            default => ['listaPacientes' => $ingresos->reportePacientes($fecha_desde, $fecha_hasta, $eps_filtro, $estado_filtro)],
        };

        return view('reportes.index', array_merge(
            compact('tab', 'fecha_desde', 'fecha_hasta', 'eps_filtro', 'estado_filtro'),
            $datos
        ));
    }

    private function exportarCsv(string $tab, string $fechaDesde, string $fechaHasta, string $eps, IngresoService $ingresos): StreamedResponse
    {
        $nombreArchivo = "reporte_{$tab}_".now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($tab, $fechaDesde, $fechaHasta, $eps, $ingresos) {
            $output = fopen('php://output', 'w');
            fwrite($output, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($tab === 'pacientes') {
                fputcsv($output, ['Tiquete', 'Tipo Doc', 'Documento', 'Nombres', 'Apellidos', 'EPS', 'Teléfono', 'Email', 'Fecha Ingreso', 'Estado', 'Orientador']);
                foreach ($ingresos->reportePacientes($fechaDesde, $fechaHasta, $eps, null) as $r) {
                    fputcsv($output, [$r->ticket_numero, $r->tipo_documento, $r->numero_documento, $r->nombres, $r->apellidos, $r->eps_nombre, $r->telefono, $r->email, $r->fecha_ingreso, $r->estado_tramite, $r->orientador_nombre]);
                }
            } elseif ($tab === 'tiempos') {
                fputcsv($output, ['Tiquete', 'Tipo Doc', 'Documento', 'Paciente', 'EPS', 'Fecha/Hora Ingreso', 'Fecha/Hora Finalización', 'Tiempo Total (Minutos)', 'Estado Tramite']);
                foreach ($ingresos->reporteTiemposSLA($fechaDesde, $fechaHasta) as $r) {
                    fputcsv($output, [$r->ticket_numero, $r->tipo_documento, $r->numero_documento, $r->nombres.' '.$r->apellidos, $r->eps_nombre, $r->fecha_ingreso, $r->fecha_finalizacion, $r->tiempo_total_minutos ?? 0, $r->estado_tramite]);
                }
            } elseif ($tab === 'pendientes') {
                fputcsv($output, ['Tiquete', 'Documento', 'Paciente', 'EPS', 'Fecha Ingreso', 'Estado', 'Detalle Medicamentos Faltantes']);
                foreach ($ingresos->reportePendientes($fechaDesde, $fechaHasta) as $r) {
                    fputcsv($output, [$r->ticket_numero, $r->numero_documento, $r->nombres.' '.$r->apellidos, $r->eps_nombre, $r->fecha_ingreso, $r->estado_tramite, $r->observaciones_pendientes]);
                }
            }

            fclose($output);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
