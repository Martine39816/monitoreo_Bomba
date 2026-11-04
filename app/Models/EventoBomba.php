<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoBomba extends Model
{
    use HasFactory;

    protected $table = 'eventos_bomba';

    public $timestamps = false; // usamos 'fecha_hora' propio, igual que Lectura

    protected $fillable = ['encendido', 'fecha_hora', 'origen', 'bombas_id'];

    protected $casts = [
        'encendido' => 'boolean',
        'fecha_hora' => 'datetime',
    ];

    public function bomba(): BelongsTo
    {
        return $this->belongsTo(Bomba::class, 'bombas_id');
    }
}