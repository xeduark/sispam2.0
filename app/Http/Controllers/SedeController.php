<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;

class SedeController extends Controller
{
    /** Cambia la sede de trabajo de la sesión (solo entre las que el usuario tiene permitidas). */
    public function cambiar(Sede $sede): RedirectResponse
    {
        abort_unless(auth()->user()->puedeTrabajarEnSede($sede->id), 403, 'No tiene acceso a esa sede.');

        session(['active_sede_id' => $sede->id]);
        AuditLog::registrar('AUTENTICACION', 'CAMBIO_SEDE', $sede->id, "Sede de trabajo cambiada a {$sede->nombre_sede}.");

        return back()->with('success', "Sede de trabajo: {$sede->nombre_sede}");
    }
}
