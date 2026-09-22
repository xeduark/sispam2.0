<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de sala de espera. Documentos standalone, sin sidebar ni sesión:
 * corren en un televisor. La sede se elige con ?sede_id= (o la de la sesión, si la hay).
 */
class TurneroController extends Controller
{
    public function uno(Request $request): View
    {
        return view('turnero.turnero1', $this->datos($request, 0));
    }

    public function dos(Request $request): View
    {
        return view('turnero.turnero2', $this->datos($request, 1) + ['todasLasSedes' => Sede::activas()->orderBy('nombre_sede')->get()->toArray()]);
    }

    private function datos(Request $request, int $porDefecto): array
    {
        $sedeId = (int) $request->query('sede_id', session('active_sede_id', $porDefecto));
        $sedeId = $sedeId > 0 ? $sedeId : $porDefecto;

        return [
            'config' => EmpresaConfig::actual()->toArray(),
            'active_sede_id' => $sedeId,
            'nombre_sede_mostrar' => ($sedeId ? Sede::find($sedeId)?->nombre_sede : null) ?? 'Todas las Sedes',
        ];
    }
}
