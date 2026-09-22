<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $auditModel = new AuditLog();

        $filters = [
            'modulo'      => $request->input('modulo', ''),
            'accion'      => $request->input('accion', ''),
            'usuario_id'  => $request->input('usuario_id', ''),
            'fecha_desde' => $request->input('fecha_desde', date('Y-m-d', strtotime('-7 days'))),
            'fecha_hasta' => $request->input('fecha_hasta', date('Y-m-d')),
            'q'           => $request->input('q', ''),
        ];

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($auditModel, $filters);
        }

        $logs = $auditModel->getLogs($filters, 500);
        $stats = $auditModel->getEstadisticas();
        $modulos = $auditModel->getModulosDisponibles();
        $acciones = $auditModel->getAccionesDisponibles();
        $usuarios = Usuario::select('id', 'nombre_completo', 'usuario')->orderBy('nombre_completo')->get()->toArray();

        return view('auditoria.index', compact('logs', 'stats', 'modulos', 'acciones', 'usuarios', 'filters'));
    }

    private function exportCsv(AuditLog $auditModel, array $filters): StreamedResponse
    {
        $logsExport = $auditModel->getLogs($filters, 5000);
        $filename = 'log_auditoria_sispam_' . date('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($logsExport) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID Log', 'Fecha y Hora', 'ID Usuario', 'Usuario Nombre', 'Rol', 'Módulo', 'Acción', 'ID Registro Afectado', 'Detalles', 'Dirección IP', 'Navegador/Agente']);

            foreach ($logsExport as $l) {
                fputcsv($output, [
                    $l['id'],
                    $l['created_at'],
                    $l['usuario_id'] ?: 'N/A',
                    $l['usuario_nombre'],
                    $l['rol_nombre'],
                    $l['modulo'],
                    $l['accion'],
                    $l['registro_id'] ?: 'N/A',
                    $l['detalles'],
                    $l['ip_address'],
                    $l['user_agent'],
                ]);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}

