<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['leido' => 'boolean'];
    }

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }

    public function scopeSinLeerPara($query, int $usuarioId)
    {
        return $query->where('usuario_destino_id', $usuarioId)
            ->where('leido', 0)
            ->orderByDesc('id');
    }
}
