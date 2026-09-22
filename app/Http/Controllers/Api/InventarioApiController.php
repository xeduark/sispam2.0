<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioApiController extends Controller
{
    public function medicamentos(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['status' => 'ok', 'data' => []]);
        }

        $like = "%{$q}%";
        $filas = DB::select("
            SELECT id, codigo_sku, codigo_cums, codigo_ium, registro_invima, nombre_generico, nombre_comercial,
                   fabricante_laboratorio, laboratorio_nit, concentracion, forma_farmaceutica, presentacion_comercial,
                   requiere_cadena_frio, es_control_especial, es_alto_costo, precio_referencia, stock_minimo_alerta
            FROM productos_medicamentos
            WHERE estado_activo IN ('Activo', '1', 1)
              AND (codigo_sku LIKE ? OR codigo_cums LIKE ? OR nombre_generico LIKE ? OR nombre_comercial LIKE ?
                   OR fabricante_laboratorio LIKE ? OR principio_activo LIKE ?)
            ORDER BY (codigo_sku = ?) DESC, (codigo_cums = ?) DESC, (nombre_generico LIKE ?) DESC, nombre_generico
            LIMIT 50", [$like, $like, $like, $like, $like, $like, $q, $q, $q.'%']);

        return response()->json(['status' => 'ok', 'total' => count($filas), 'data' => $filas]);
    }

    public function lotesBodega(Request $request): JsonResponse
    {
        $bodegaId = (int) $request->query('bodega_id', 0);
        if ($bodegaId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Bodega no especificada', 'data' => []]);
        }

        return response()->json(['status' => 'ok', 'data' => (new Inventario())->getStockGeneralPorBodega($bodegaId)]);
    }
}
