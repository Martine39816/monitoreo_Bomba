<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';

    public const TIPOS = [
        'nivel_bajo', 'nivel_alto', 'bomba_apagada', 'falla_electrica',
        'sobrecorriente', 'temperatura_alta', 'vibracion_alta', 'sensor_desconectado',
    ];

    protected $fillable = [
        'tipo', 'descripcion', 'valor_detectado', 'valor_permitido', 'fecha_hora',
        'atendida', 'fecha_atencion', 'sensores_id', 'usuario_atencion_id', 'bombas_id',
    ];

    protected $casts = [
        'atendida' => 'boolean',
        'fecha_hora' => 'datetime',
        'fecha_atencion' => 'datetime',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class, 'sensores_id');
    }

    public function usuarioAtencion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_atencion_id');
    }
    public function bomba(): BelongsTo
    {
        return $this->belongsTo(Bomba::class, 'bombas_id');
    }

    public function mantenimientos(): HasMany       
    {
        return $this->hasMany(Mantenimiento::class, 'alertas_id');
    }
}
