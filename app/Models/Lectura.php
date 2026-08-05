<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lectura extends Model
{
    use HasFactory;

    protected $table = 'lecturas';

    public $timestamps = false; // solo created_at, sin updated_at

    protected $fillable = ['valor_medido', 'fecha_hora', 'sensores_id', 'created_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class, 'sensores_id');
    }
}
