<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrystalosBarrio;
use App\Models\QrystalosPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de catálogo para los selectores dependientes del formulario de
 * Ingreso (Aseguradora → Plan, Ciudad → Barrio). Ver database/example/LISTADO.xlsx.
 */
class QrystalosApiController extends Controller
{
    public function aseguradoras(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = QrystalosPlan::query()
            ->where('razonsocial', 'like', "%{$q}%")
            ->select('idtercero', 'razonsocial')
            ->distinct()
            ->orderBy('razonsocial')
            ->limit(30)
            ->get();

        return response()->json($resultados);
    }

    public function planes(Request $request): JsonResponse
    {
        $idtercero = $request->query('idtercero', '');

        $planes = QrystalosPlan::where('idtercero', $idtercero)
            ->select('idplan', 'descplan')
            ->orderBy('descplan')
            ->get();

        return response()->json($planes);
    }

    public function ciudades(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = QrystalosBarrio::query()
            ->where('nombre_ciudad', 'like', "%{$q}%")
            ->select('idciudad', 'nombre_ciudad')
            ->distinct()
            ->orderBy('nombre_ciudad')
            ->limit(30)
            ->get();

        return response()->json($resultados);
    }

    public function barrios(Request $request): JsonResponse
    {
        $idciudad = $request->query('idciudad', '');

        $barrios = QrystalosBarrio::where('idciudad', $idciudad)
            ->whereNotNull('idbarrio')
            ->select('idbarrio', 'nombre_barrio')
            ->orderBy('nombre_barrio')
            ->get();

        return response()->json($barrios);
    }

    public function sedes(): JsonResponse
    {
        $sedes = Sede::whereNotNull('qrystalos_id_sede')
            ->orderBy('nombre_sede')
            ->get(['qrystalos_id_sede', 'nombre_sede']);

        return response()->json($sedes);
    }
}
