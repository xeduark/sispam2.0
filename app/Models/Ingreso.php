<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingreso extends Model
{
    protected $table = 'ingresos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'locked_at' => 'datetime',
            'fecha_alistado' => 'datetime',
            'fecha_verificacion' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'orientador_id');
    }

    public function bloqueadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'locked_by_user_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(IngresoDocumento::class, 'ingreso_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'ingreso_id');
    }

    /**
     * Filtro multi-sede del sistema legacy: todo rol distinto de Administrador
     * ve solo su sede activa (más los ingresos sin sede asignada).
     */
    public function scopeDeSedeActiva($query, ?int $sedeId, bool $esAdministrador)
    {
        if ($sedeId && ! $esAdministrador) {
            $query->where(fn ($q) => $q->whereNull('sede_id')->orWhere('sede_id', $sedeId));
        }

        return $query;
    }

    /** Preferenciales primero, luego orden de llegada. */
    public function scopeOrdenAtencion($query, string $columnaFecha = 'fecha_ingreso')
    {
        return $query->orderByRaw("IF(prioridad = 'NORMAL', 1, 0) ASC")
            ->orderBy($columnaFecha, 'asc');
    }
}
