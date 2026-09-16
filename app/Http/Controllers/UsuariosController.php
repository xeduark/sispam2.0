<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    /** Módulos disponibles para asignación dinámica de permisos. */
    private const MODULOS_DISPONIBLES = [
        'ingreso' => 'Admisión / Ingreso de Pacientes',
        'expedientes' => 'Consulta de Expedientes',
        'transcripcion' => 'Transcripción & Verificación de Stock',
        'alistamiento' => 'Alistamiento de Medicamentos (Picking)',
        'entrega' => 'Factura & Entrega con Firma Digital',
        'reportes' => 'Reportes & Analítica SLA',
        'empresa' => 'Parametrización de la Empresa',
        'usuarios' => 'Gestión de Usuarios y Permisos',
    ];

    public function index(Request $request): View
    {
        return view('usuarios.index', [
            'subtab' => $request->query('subtab', 'usuarios'),
            'usuarios' => Usuario::with(['rol', 'empresa', 'sede'])->orderByDesc('id')->get(),
            'roles' => Rol::orderBy('id')->get(),
            'empresas' => Empresa::orderBy('id')->get(),
            'sedes' => Sede::with('empresa')->orderBy('empresa_id')->orderBy('nombre_sede')->get(),
            'modulosDisponibles' => self::MODULOS_DISPONIBLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'rol_id' => ['required', 'exists:roles,id'],
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre_completo' => ['required', 'string', 'max:120'],
            'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario'],
            'password' => ['required', 'string', 'min:4'],
        ], [], []);

        Usuario::create([
            'rol_id' => $datos['rol_id'],
            'empresa_id' => $datos['empresa_id'],
            'sede_id' => $datos['sede_id'],
            'nombre_completo' => $datos['nombre_completo'],
            'usuario' => $datos['usuario'],
            'password_hash' => Hash::make($datos['password']),
            'estado' => 'ACTIVO',
        ]);

        return back()->with('mensaje', 'Usuario registrado exitosamente con asignación de empresa y sede.');
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'rol_id' => ['required', 'exists:roles,id'],
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre_completo' => ['required', 'string', 'max:120'],
            'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario,'.$usuario->id],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
            'new_password' => ['nullable', 'string', 'min:4'],
        ]);

        if (! empty($datos['new_password'])) {
            $datos['password_hash'] = Hash::make($datos['new_password']);
        }
        unset($datos['new_password']);

        $usuario->update($datos);

        return back()->with('mensaje', 'Datos del usuario, empresa y sede actualizados correctamente.');
    }

    public function toggleEstado(Usuario $usuario): RedirectResponse
    {
        $usuario->update(['estado' => $usuario->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);

        return back()->with('mensaje', 'Estado de usuario actualizado.');
    }

    public function guardarMatrizPermisos(Request $request): RedirectResponse
    {
        $matriz = $request->input('permisos_matriz', []);

        foreach (Rol::all() as $rol) {
            $permisos = $matriz[$rol->id] ?? ['dashboard'];

            if (! in_array('dashboard', $permisos, true)) {
                $permisos[] = 'dashboard';
            }

            $rol->update(['permisos' => array_values($permisos)]);
        }

        return redirect()->route('usuarios.index', ['subtab' => 'permisos'])
            ->with('mensaje', 'Matriz de permisos de módulos actualizada correctamente para todos los roles.');
    }
}
