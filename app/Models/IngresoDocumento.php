<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoDocumento extends Model
{
    protected $table = 'ingreso_documentos';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }
}
