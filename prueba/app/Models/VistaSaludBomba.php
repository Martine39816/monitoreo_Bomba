<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VistaSaludBomba extends Model
{
    protected $table = 'vista_salud_bomba';

    public $timestamps = false;

    protected $primaryKey = 'bomba_id';

    public $incrementing = false;

    protected $casts = [
        'porcentaje_salud_actual' => 'float',
        'fecha_ultima_lectura' => 'datetime',
        'total_sensores_evaluados' => 'integer',
    ];
}