<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'rol_id',
        'empresa_id',
        'sede_id',
        'nombre_completo',
        'usuario',
        'password_hash',
        'estado',
    ];

    protected $hidden = ['password_hash'];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // La columna de contraseña heredada no se llama 'password'; sin esto el
    // rehash automático de Laravel escribiría en una columna inexistente.
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // La tabla usuarios no tiene columna remember_token.
    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    /**
     * Sedes en las que puede trabajar: el administrador, todas las activas; los demás roles,
     * las asignadas en usuario_sedes o, si no tienen ninguna, la sede de su ficha.
     *
     * @return Collection<int, Sede>
     */
    public function sedesDisponibles(): Collection
    {
        if ($this->esAdministrador()) {
            return Sede::activas()->orderBy('nombre_sede')->get();
        }

        $ids = DB::table('usuario_sedes')->where('usuario_id', $this->id)->pluck('sede_id');
        $ids = $ids->isEmpty() ? collect([$this->sede_id]) : $ids;

        return Sede::activas()->whereIn('id', $ids->filter())->orderBy('nombre_sede')->get();
    }

    public function puedeTrabajarEnSede(int $sedeId): bool
    {
        return $this->sedesDisponibles()->contains('id', $sedeId);
    }

    public function esAdministrador(): bool
    {
        return $this->rol?->nombre === 'Administrador';
    }

    public function hasPermission(string $moduleKey): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        $permisos = $this->rol?->permisos ?? [];
        if (in_array($moduleKey, $permisos, true)) {
            return true;
        }

        if ($moduleKey === 'verificacion_ia' && (in_array('verificacion_ia', $permisos, true) || in_array('transcripcion', $permisos, true) || in_array('alistamiento', $permisos, true))) {
            return true;
        }

        if ($moduleKey === 'expedientes' && (in_array('expedientes', $permisos, true) || in_array('ingreso', $permisos, true) || in_array('ingreso_rapido', $permisos, true) || in_array('verificacion_ia', $permisos, true) || in_array('transcripcion', $permisos, true) || in_array('monitoreo', $permisos, true) || in_array('alistamiento', $permisos, true) || in_array('entrega', $permisos, true) || in_array('salida', $permisos, true) || in_array('reportes', $permisos, true) || in_array('pacientes', $permisos, true))) {
            return true;
        }

        if (str_starts_with($moduleKey, 'inventario') && in_array('inventario', $permisos, true)) {
            return true;
        }

        if (str_starts_with($moduleKey, 'factura') && in_array('facturas', $permisos, true)) {
            return true;
        }

        if ((str_starts_with($moduleKey, 'ia_') || $moduleKey === 'escaner_digitalizacion') && (in_array('ia_scanner', $permisos, true) || in_array('escaner_digitalizacion', $permisos, true))) {
            return true;
        }

        if (in_array($moduleKey, ['api_externo', 'configuracion_api'], true) && (in_array('api_externo', $permisos, true) || in_array('usuarios', $permisos, true) || in_array('empresa', $permisos, true))) {
            return true;
        }

        return false;
    }
}
