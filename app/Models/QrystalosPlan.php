<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrystalosPlan extends Model
{
    protected $table = 'qrystalos_planes';

    public $timestamps = false;

    protected $guarded = ['id'];
}
