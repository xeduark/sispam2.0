<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuloEntrega extends Model
{
    protected $table = 'modulos_entrega';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO')->orderBy('id');
    }
}
