<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Port del check_role() legacy, incluidos sus alias de rol.
 *
 * Los parámetros pueden ser claves de módulo ('ingreso') o nombres de rol
 * ('Administrador'), mezclados, igual que en el sistema original. En caso de
 * denegar, redirige al dashboard con ?error=acceso_denegado en vez de un 403,
 * también como el original.
 */
class CheckRole
{
    /**
     * Alias heredados: clave de módulo/rol pedida => [permiso equivalente, nombres de rol equivalentes].
     */
    private const ALIAS = [
        'transcriptor' => ['transcripcion', ['Transcripcion', 'Transcriptor']],
        'transcripcion' => ['transcripcion', ['Transcripcion', 'Transcriptor']],
        'orientador' => ['ingreso', ['Orientador']],
        'ingreso' => ['ingreso', ['Orientador']],
        'alistamiento' => ['alistamiento', ['Alistamiento', 'Alistador']],
        'alistador' => ['alistamiento', ['Alistamiento', 'Alistador']],
        'entrega' => ['entrega', ['Entrega', 'Entregador']],
        'entregador' => ['entrega', ['Entrega', 'Entregador']],
        'regente' => ['reportes', ['Regente']],
        'reportes' => ['reportes', ['Regente']],
    ];

    public function handle(Request $request, Closure $next, string ...$requeridos): Response
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->esAdministrador() || $this->autorizado($usuario, $requeridos)) {
            return $next($request);
        }

        return redirect()->route('dashboard', ['error' => 'acceso_denegado']);
    }

    private function autorizado(Usuario $usuario, array $requeridos): bool
    {
        $rolUsuario = $usuario->rol?->nombre ?? '';
        $permisos = $usuario->rol?->permisos ?? [];

        // 1. Coincidencia directa por nombre de rol.
        if (in_array($rolUsuario, $requeridos, true)) {
            return true;
        }

        // 2. Coincidencia por permiso de módulo o por alias de rol.
        foreach ($requeridos as $item) {
            $item = strtolower($item);

            if (in_array($item, $permisos, true)) {
                return true;
            }

            [$permisoAlias, $rolesAlias] = self::ALIAS[$item] ?? [null, []];

            if ($permisoAlias && in_array($permisoAlias, $permisos, true)) {
                return true;
            }

            foreach ($rolesAlias as $rolAlias) {
                if (strcasecmp($rolUsuario, $rolAlias) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
