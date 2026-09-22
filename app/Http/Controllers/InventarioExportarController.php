<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Services\InventarioExportService;
use Illuminate\Http\Request;

class InventarioExportarController extends Controller
{
    public function index(Request $request)
    {
        $filtroBodega = $request->query('bodega_id', '') !== '' ? (int) $request->query('bodega_id') : null;
        $filtroTipo = trim((string) $request->query('tipo_movimiento', ''));
        $filtroDesde = trim((string) $request->query('fecha_desde', date('Y-m-d')));
        $filtroHasta = trim((string) $request->query('fecha_hasta', date('Y-m-d')));
        $filtroBuscar = trim((string) $request->query('buscar', ''));
        $f = ['bodega' => $filtroBodega, 'tipo' => $filtroTipo, 'desde' => $filtroDesde, 'hasta' => $filtroHasta, 'buscar' => $filtroBuscar];
        $svc = new InventarioExportService();

        if ($request->query('action_export') !== null && ($csv = $svc->csv((string) $request->query('action_export'), $f))) {
            [$name, $write] = $csv;

            return response()->streamDownload(function () use ($write) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                $write(fn ($row) => fputcsv($out, $row, ';'));
                fclose($out);
            }, $name, ['Content-Type' => 'text/csv; charset=utf-8', 'Cache-Control' => 'max-age=0, no-cache, must-revalidate']);
        }

        $bodegas = (new Inventario())->getBodegas();
        [$previewRows, $resumenFiltro] = $svc->preview($f);

        return view('inventario.exportar', compact('bodegas', 'filtroBodega', 'filtroBuscar', 'filtroDesde', 'filtroHasta', 'filtroTipo', 'previewRows', 'resumenFiltro'));
    }
}
