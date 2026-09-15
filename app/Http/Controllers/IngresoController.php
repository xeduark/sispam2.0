<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IngresoController extends Controller
{
    public function index(): View
    {
        // TODO: pendiente de portar desde el sistema legacy.
        return view('_pendiente', ['modulo' => 'Admisión / Ingreso']);
    }

    /**
     * Tiquete térmico de turno.
     *
     * La ruta no lleva middleware auth: con ?rawbt=1 la pide la app externa de
     * impresión térmica, que no manda cookie de sesión. Fuera de ese caso se
     * exige sesión, igual que el check_auth() del archivo legacy.
     */
    public function ticket(Request $request, int $id): View
    {
        $ingreso = Ingreso::with(['paciente', 'orientador'])->findOrFail($id);
        $config = EmpresaConfig::actual();

        if ($request->query('rawbt') === '1') {
            return view('ingreso.ticket_rawbt', compact('ingreso', 'config'));
        }

        abort_unless(auth()->check(), 403);

        return view('ingreso.ticket', [
            'ingreso' => $ingreso,
            'config' => $config,
            'rawbtUrl' => route('ingreso.ticket', ['ingreso' => $id, 'rawbt' => 1]),
        ]);
    }
}
