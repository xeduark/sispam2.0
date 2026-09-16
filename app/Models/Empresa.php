<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class, 'empresa_id');
    }
}
