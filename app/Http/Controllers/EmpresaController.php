<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaConfig;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function edit(Request $request): View
    {
        return view('empresa.config', [
            'tab' => $request->query('tab', 'general'),
            'config' => EmpresaConfig::actual(),
            'empresas' => Empresa::orderBy('id')->get(),
            'sedes' => Sede::with('empresa')->orderBy('empresa_id')->orderBy('nombre_sede')->get(),
        ]);
    }

    public function guardarGeneral(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'pie_tiquete' => ['nullable', 'string'],
            'hora_apertura_atencion' => ['required'],
            'video_turnero_url' => ['nullable', 'string', 'max:255'],
            'marquesina_turnero' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg'],
        ], [], ['nombre' => 'nombre']);

        if ($request->hasFile('logo')) {
            $ext = $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->move(public_path('assets/img'), 'logo_empresa.'.$ext);
            $datos['logo_url'] = 'assets/img/logo_empresa.'.$ext;
        }
        unset($datos['logo']);

        EmpresaConfig::actual()->update($datos);

        return redirect()->route('empresa.edit', ['tab' => 'general'])
            ->with('mensaje', 'Parámetros de la empresa actualizados correctamente.');
    }

    public function crearEmpresa(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
        ], [], []);

        Empresa::create($datos + ['estado' => 'Activo']);

        return redirect()->route('empresa.edit', ['tab' => 'empresas'])
            ->with('mensaje', 'Nueva empresa registrada exitosamente.');
    }

    public function actualizarEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'estado' => ['required', 'in:Activo,Inactivo'],
        ]);

        $empresa->update($datos);

        return redirect()->route('empresa.edit', ['tab' => 'empresas'])
            ->with('mensaje', 'Datos de la empresa actualizados correctamente.');
    }

    public function crearSede(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre_sede' => ['required', 'string', 'max:100'],
            'codigo_sede' => ['nullable', 'string', 'max:20'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'hora_apertura_atencion' => ['nullable'],
        ]);

        Sede::create($datos + [
            'ciudad' => $datos['ciudad'] ?: 'MEDELLIN',
            'hora_apertura_atencion' => $datos['hora_apertura_atencion'] ?: '07:20:00',
            'estado' => 'Activo',
        ]);

        return redirect()->route('empresa.edit', ['tab' => 'sedes'])
            ->with('mensaje', 'Sede de atención creada exitosamente con horario de inicio de atención SLA.');
    }

    public function actualizarSede(Request $request, Sede $sede): RedirectResponse
    {
        $datos = $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre_sede' => ['required', 'string', 'max:100'],
            'codigo_sede' => ['nullable', 'string', 'max:20'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'estado' => ['required', 'in:Activo,Inactivo'],
            'hora_apertura_atencion' => ['nullable'],
        ]);

        $sede->update($datos + [
            'ciudad' => $datos['ciudad'] ?: 'MEDELLIN',
            'hora_apertura_atencion' => $datos['hora_apertura_atencion'] ?: '07:20:00',
        ]);

        return redirect()->route('empresa.edit', ['tab' => 'sedes'])
            ->with('mensaje', 'Sede de atención actualizada correctamente.');
    }
}
