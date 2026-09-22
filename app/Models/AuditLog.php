<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bitácora de auditoría (tabla logs_auditoria, la misma del sistema nativo).
 */
class AuditLog extends Model
{
    protected $table = 'logs_auditoria';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Registra un evento con el usuario, rol e IP de la petición actual.
     * Nunca lanza excepción: una falla de auditoría no debe romper la operación.
     */
    public static function registrar(string $modulo, string $accion, ?int $registroId = null, array|string|null $detalles = null): bool
    {
        try {
            $u = auth()->user();
            $req = request();

            static::create([
                'usuario_id' => $u?->id,
                'usuario_nombre' => $u?->nombre_completo ?? $u?->usuario ?? 'Sistema / Invitado',
                'rol_nombre' => $u?->rol?->nombre ?? 'Invitado',
                'modulo' => strtoupper($modulo),
                'accion' => strtoupper($accion),
                'registro_id' => $registroId,
                'detalles' => is_array($detalles) ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : $detalles,
                'ip_address' => $req->ip() ?? '127.0.0.1',
                'user_agent' => mb_substr((string) $req->userAgent(), 0, 250),
                'created_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar el log de auditoría: '.$e->getMessage());

            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getLogs(array $filters = [], int $limit = 500): array
    {
        $q = DB::table('logs_auditoria')->orderByDesc('id')->limit($limit);

        if (! empty($filters['modulo'])) {
            $q->where('modulo', strtoupper($filters['modulo']));
        }
        if (! empty($filters['accion'])) {
            $q->where('accion', strtoupper($filters['accion']));
        }
        if (! empty($filters['usuario_id'])) {
            $q->where('usuario_id', (int) $filters['usuario_id']);
        }
        if (! empty($filters['fecha_desde'])) {
            $q->where('created_at', '>=', $filters['fecha_desde'].' 00:00:00');
        }
        if (! empty($filters['fecha_hasta'])) {
            $q->where('created_at', '<=', $filters['fecha_hasta'].' 23:59:59');
        }
        if (! empty($filters['q'])) {
            $like = '%'.trim($filters['q']).'%';
            $q->where(function ($w) use ($like) {
                $w->where('usuario_nombre', 'like', $like)
                    ->orWhere('detalles', 'like', $like)
                    ->orWhere('accion', 'like', $like)
                    ->orWhere('modulo', 'like', $like)
                    ->orWhereRaw('CAST(registro_id AS CHAR) LIKE ?', [$like]);
            });
        }

        return $q->get()->map(fn ($r) => (array) $r)->all();
    }

    public function getEstadisticas(): array
    {
        $hoy = now()->startOfDay();
        $hoyQ = fn () => DB::table('logs_auditoria')->where('created_at', '>=', $hoy);
        $top = $hoyQ()->select('modulo', DB::raw('COUNT(*) as total'))->groupBy('modulo')->orderByDesc('total')->first();

        return [
            'total_hoy' => $hoyQ()->count(),
            'usuarios_activos_hoy' => $hoyQ()->whereNotNull('usuario_id')->distinct()->count('usuario_id'),
            'modulo_mas_activo' => $top ? $top->modulo.' ('.$top->total.')' : 'N/A',
            'inicios_sesion_hoy' => $hoyQ()->where('accion', 'LOGIN_EXITOSO')->count(),
        ];
    }

    /** @return array<int, string> */
    public function getModulosDisponibles(): array
    {
        $base = ['AUTENTICACION', 'INGRESO', 'TRANSCRIPCION', 'MONITOREO', 'ALISTAMIENTO', 'ENTREGA', 'EXPEDIENTES', 'USUARIOS', 'EMPRESA'];
        $enBd = DB::table('logs_auditoria')->distinct()->orderBy('modulo')->pluck('modulo')->all();
        $todos = array_values(array_unique(array_merge($base, $enBd)));
        sort($todos);

        return $todos;
    }

    /** @return array<int, array{accion: string}> */
    public function getAccionesDisponibles(): array
    {
        return DB::table('logs_auditoria')->distinct()->orderBy('accion')->get(['accion'])->map(fn ($r) => (array) $r)->all();
    }
}
