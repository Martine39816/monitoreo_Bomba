<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bomba extends Model
{
    use HasFactory;

    protected $table = 'bombas';

    protected $fillable = [
        'codigo', 'nombre', 'marca', 'modelo', 'serie',
        'potencia_hp', 'voltaje_nominal', 'corriente_nominal',
        'caudal_min', 'caudal_max', 'altura_maxima',
        'temperatura_maxima', 'presion_maxima', 'fecha_instalacion',
        'modo_operacion', 'encendido', 'estado',
        'tanque_codigo', 'tanque_capacidad_litros', 'tanque_altura_metros', 'tanque_diametro_metros',
        'observaciones', 'centros_salud_id',
    ];

    protected $casts = [
        'modo_operacion' => 'boolean',
        'encendido' => 'boolean',
        'fecha_instalacion' => 'date',
    ];

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class, 'centros_salud_id');
    }

    public function sensores(): HasMany
    {
        return $this->hasMany(Sensor::class, 'bombas_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'bombas_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'bombas_id');
    }
}
