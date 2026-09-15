<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', [
            'sedes' => Sede::activas()->orderBy('nombre_sede')->get(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
            'sede_id' => ['nullable', 'exists:sedes,id'],
        ]);

        $ok = Auth::attempt([
            'usuario' => $credenciales['usuario'],
            'password' => $credenciales['password'],
            'estado' => 'ACTIVO',
        ], $request->boolean('recordar'));

        if (! $ok) {
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos, o usuario inactivo.',
            ]);
        }

        $request->session()->regenerate();

        // Sede activa opcional para esta sesión: activa el filtro multi-sede de Ingreso
        // (App\Services\IngresoService::sedeActiva()), que hasta ahora nunca se llenaba.
        if (! empty($credenciales['sede_id'])) {
            $request->session()->put('active_sede_id', $credenciales['sede_id']);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
