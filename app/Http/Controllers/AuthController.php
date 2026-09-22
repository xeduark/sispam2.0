<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
            AuditLog::registrar('AUTENTICACION', 'LOGIN_FALLIDO', null, "Intento fallido de inicio de sesión con el usuario: {$credenciales['usuario']}");

            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos, o usuario inactivo.',
            ]);
        }

        $request->session()->regenerate();

        // Sede de trabajo: la elegida al entrar (si le está permitida) o la de su ficha.
        $usuario = $request->user();
        $sedeElegida = (int) ($credenciales['sede_id'] ?? 0);
        $sedeActiva = $sedeElegida && $usuario->puedeTrabajarEnSede($sedeElegida) ? $sedeElegida : $usuario->sede_id;
        $request->session()->put('active_sede_id', $sedeActiva);

        AuditLog::registrar('AUTENTICACION', 'LOGIN_EXITOSO', $usuario->id, "Inicio de sesión exitoso usuario: {$usuario->usuario} ({$usuario->nombre_completo})");

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
