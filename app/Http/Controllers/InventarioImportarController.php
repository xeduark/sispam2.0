<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Services\InventarioImportService;
use Illuminate\Http\Request;

class InventarioImportarController extends Controller
{
    public function index(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);

        if ($request->query('download_template') !== null) {
            $resp = $this->plantilla((string) $request->query('download_template'));
            if ($resp) return $resp;
        }

        $invModel = new Inventario();
        $active_sede_id = session('active_sede_id') ?? sesion('sede_id');
        $bodegas = $invModel->getBodegas();
        $mensaje = '';
        $error = '';
        $resumenImportacion = null;

        if ($request->isMethod('post') && $request->has('action_importar')) {
            $modo = $request->input('modo_importacion', 'catalogo');
            $bodegaDestinoId = (int) $request->input('bodega_id', 0);
            $file = $request->file('archivo_importar');
            $ext = $file ? strtolower($file->getClientOriginalExtension()) : '';

            if (!$file || !$file->isValid()) {
                $error = 'Por favor seleccione un archivo válido para importar (.xlsx o .csv).';
            } elseif (!in_array($ext, ['xlsx', 'csv', 'txt'])) {
                $error = 'Formato de archivo no soportado. Debe ser .xlsx o .csv';
            } elseif ($modo === 'saldos' && $bodegaDestinoId <= 0) {
                $error = 'Debe seleccionar la Bodega de destino para cargar los lotes y saldos.';
            } else {
                try {
                    $svc = new InventarioImportService(
                        $modo, $bodegaDestinoId,
                        (string) $request->input('tipo_movimiento', 'ENTRADA_COMPRA'),
                        $request->input('sobrescribir_existentes') === '1',
                        (int) sesion('user_id', 1)
                    );
                    $resumenImportacion = $svc->procesar($file->getRealPath(), $ext);
                    $mensaje = "Proceso de importación completado exitosamente en {$resumenImportacion['duracion']} segundos.";
                    registrar_log_auditoria('INVENTARIO', 'IMPORTACION_EXCEL', $bodegaDestinoId ?: 1,
                        "Importación masiva ({$modo}): Total: {$resumenImportacion['total_filas']}, Insertados: {$resumenImportacion['insertados']}, Actualizados: {$resumenImportacion['actualizados']}");
                } catch (\Throwable $e) {
                    $error = 'Error durante el procesamiento: ' . $e->getMessage();
                }
            }
        }

        return view('inventario.importar', compact('active_sede_id', 'bodegas', 'mensaje', 'error', 'resumenImportacion'));
    }

    private function plantilla(string $tpl)
    {
        if ($tpl === 'catalogo_csv') {
            $headers = ['IDARTICULO', 'DESCRIPCION', 'MANEJA_IVA', 'ARTICULO_PRINCIPAL', 'IDITAR',
            'DESCTIPOARTICULO', 'ESTADO', 'NIT', 'RAZONSOCIAL', 'IDCLASE',
            'DESCLASE', 'IDSUBCLASE', 'DESCSUBCLASE', 'IDGRUPO', 'DESCGRUPO',
            'IDPRINACTIVO', 'DESCPRINCIPIO', 'IDFORFARM', 'DESCFORMAFAR', 'IDUNIDAD',
            'DESCUNIDAD', 'PCOSTO', 'PRECIO_REGULADO', 'IDGENERICO', 'DESCGENERICO',
            'CODCUM', 'REGINVIMA', 'UNID_VENTA', 'ALTO_COSTO', 'CONTROLADO',
            'REGULADO', 'NOPOS', 'USO_INSTITUCIONAL', 'BIOLOGICO', 'SKU',
            'REQUIERE_CADENA_FRIO', 'FECHA_VIGENCIA_INVIMA', 'CODIGO_IUM', 'DE_MARCA', 'OBSERVACION'];
            $rows = [
                ['MED-00101', 'ACETAMINOFEN 500 MG TABLETA', 'No', 'Si', '01',
            'MEDICAMENTOS', 'Activo', '890900123-1', 'GENFAR S.A.', '',
            'ANALGESICOS Y ANTIPIRÉTICOS', '', '', '', '',
            '', 'ACETAMINOFEN', '', 'TABLETA', '',
            '500 MG', '120.00', '0', 'N02BE01', 'ACETAMINOFEN',
            '19934521-01', 'INVIMA 2018M-0001234-R2', 'UNIDAD', '0', '0',
            '0', '0', '0', '0', 'MED-00101',
            '0', '2028-12-31', '', 'No', 'Uso general analgésico'],
                ['MED-00102', 'LOSARTAN POTASICO 50 MG TABLETA RECUBIERTA', 'No', 'Si', '01',
            'MEDICAMENTOS', 'Activo', '890900555-2', 'MK / TECNOQUIMICAS', '',
            'ANTIHIPERTENSIVOS', '', '', '', '',
            '', 'LOSARTAN POTASICO', '', 'TABLETA RECUBIERTA', '',
            '50 MG', '350.00', '0', 'C09CA01', 'LOSARTAN',
            '20014589-02', 'INVIMA 2020M-0005678-R1', 'CAJA X 30', '0', '0',
            '0', '0', '0', '0', 'MED-00102',
            '0', '2029-06-30', '', 'Si', 'Hipertensión arterial'],
            ];
            $name = 'plantilla_maestro_articulos_40col.csv';
        } elseif ($tpl === 'saldos_csv') {
            $headers = ['TIPO', 'IDARTICULO', 'ARTICULO', 'U_MEDIDA', 'ID_BODEGA', 'BODEGA',
            'EXISTENCIA', 'COSTO_UNI', 'COSTO_TOTAL', 'CODCUM', 'RINVIMA',
            'LOTE_INTERNO', 'N_LOTE', 'FECHAVENCE', 'STOCKMINIMO', 'STOCKMAXIMO', 'IUM'];
            $rows = [
                ['MEDICAMENTOS', 'MX0004-1', 'DIENOGEST 2 MG TABLETA COMPRIMIDOS', 'MILIGRAMOS', '078', 'BODEGA SF LA 30',
            '5842', '922.00', '5386324.00', '20080146-8', '2020M-0016006-R1',
            '01L00009639', 'LF45763A', '30/03/2029', '0', '0', '1D1030551002100'],
                ['MEDICAMENTOS', 'MX03-2', 'ACETAMINOFEN 500 MG TABLETA', 'MILIGRAMOS', '078', 'BODEGA SF LA 30',
            '4140', '51.00', '211140.00', '19935303-4', '2023M-0002317-R3',
            '01L00009732', '26A820', '30/04/2028', '0', '0', ''],
            ];
            $name = 'plantilla_saldos_lotes_bodega.csv';
        } else {
            return null;
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $r) fputcsv($out, $r, ';');
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=utf-8', 'Cache-Control' => 'max-age=0, no-cache, must-revalidate']);
    }
}
