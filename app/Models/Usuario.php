<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function esAdministrador(): bool
    {
        return $this->rol?->nombre === 'Administrador';
    }

    public function hasPermission(string $moduleKey): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        return in_array($moduleKey, $this->rol?->permisos ?? [], true);
    }
}
