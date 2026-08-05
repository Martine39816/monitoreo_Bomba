<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sensor extends Model
{
    use HasFactory;

    protected $table = 'sensores';

    public const TIPOS = ['temperatura', 'vibracion', 'corriente', 'presion', 'nivel'];

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'marca', 'modelo', 'unidad_medida',
        'valor_minimo', 'valor_maximo', 'precision_sensor', 'estado',
        'fecha_instalacion', 'ubicacion', 'dispositivos_iot_id', 'bombas_id',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
    ];

    public function dispositivoIot(): BelongsTo
    {
        return $this->belongsTo(DispositivoIot::class, 'dispositivos_iot_id');
    }

    public function bomba(): BelongsTo
    {
        return $this->belongsTo(Bomba::class, 'bombas_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'sensores_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'sensores_id');
    }
}
