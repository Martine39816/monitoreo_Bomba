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
        'modo_operacion', 'encendido', 'funcionando', 'estado',
        'tanque_codigo', 'tanque_capacidad_litros', 'tanque_altura_metros', 'tanque_diametro_metros',
        'observaciones', 'centros_salud_id',
    ];

    protected $casts = [
        'modo_operacion' => 'boolean',
        'encendido' => 'boolean',
        'funcionando' => 'boolean',
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

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoBomba::class, 'bombas_id');
    }

    /**
     * Minutos que la bomba lleva FUNCIONANDO sin interrupcion (segun la
     * corriente medida). Devuelve null si esta detenida o no hay historial.
     */
    public function minutosEncendidaContinuo(): ?int
    {
        if (! $this->funcionando) {
            return null;
        }

        $ultimoEvento = $this->eventos()->orderBy('fecha_hora', 'desc')->first();

        if (! $ultimoEvento || ! $ultimoEvento->encendido) {
            return null; // sin historial confiable todavia
        }

        return (int) $ultimoEvento->fecha_hora->diffInMinutes(now());
    }

    /**
     * Cuantas veces arranco la bomba dentro de los ultimos $minutos.
     */
    public function ciclosEncendidoEn(int $minutos): int
    {
        return $this->eventos()
            ->where('encendido', true)
            ->where('fecha_hora', '>=', now()->subMinutes($minutos))
            ->count();
    }
}