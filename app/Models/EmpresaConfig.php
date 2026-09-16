<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfig extends Model
{
    protected $table = 'empresa_config';

    protected $guarded = ['id'];

    public static function actual(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
