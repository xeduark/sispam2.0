<?php

namespace App\Http\Controllers;

use App\Models\ModuloEntrega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulosController extends Controller
{
    public function index(): View
    {
        return view('modulos.index', [
            'modulos' => ModuloEntrega::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nombre de la ventanilla o módulo es obligatorio.',
        ]);

        $sedeId = auth()->user()->sede_id ?? 1;

        $existe = ModuloEntrega::where('sede_id', $sedeId)
            ->where('nombre', $datos['nombre'])
            ->exists();

        if ($existe) {
            return back()->with('error', 'Error al registrar la ventanilla. Es posible que el nombre ya exista.');
        }

        ModuloEntrega::create([
            'sede_id' => $sedeId,
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'estado' => 'ACTIVO',
        ]);

        return back()->with('mensaje', 'Módulo / Ventanilla <strong>'.e($datos['nombre']).'</strong> registrada exitosamente.');
    }

    public function update(Request $request, ModuloEntrega $modulo): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
        ]);

        $modulo->update($datos);

        return back()->with('mensaje', 'Ventanilla / Módulo actualizado correctamente.');
    }

    public function toggleEstado(ModuloEntrega $modulo): RedirectResponse
    {
        $modulo->update(['estado' => $modulo->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);

        return back()->with('mensaje', 'Estado de la ventanilla actualizado.');
    }
}
