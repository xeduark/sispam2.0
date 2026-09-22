<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sede extends Model
{
    protected $table = 'sedes';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Todas las sedes como arreglos con el nombre de su empresa (formato que usan las vistas
     * heredadas del sistema nativo: Empresa::getTodasSedes()).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todasConEmpresa(): array
    {
        return static::query()
            ->leftJoin('empresas', 'sedes.empresa_id', '=', 'empresas.id')
            ->select('sedes.*', 'empresas.razon_social as empresa_nombre')
            ->orderBy('empresas.razon_social')->orderBy('sedes.nombre_sede')
            ->get()->map(fn ($s) => $s->getAttributes())->all();
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'Activo');
    }
}
