<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Bomba extends Model
{
    use HasFactory;

    protected $table = 'bombas';

    protected $fillable = [
        'codigo',
        'nombre',
        'marca',
        'modelo',
        'serie',
        'potencia_hp',
        'voltaje_nominal',
        'corriente_nominal',
        'caudal_min',
        'caudal_max',
        'altura_maxima',
        'temperatura_maxima',
        'presion_maxima',
        'fecha_instalacion',
        'modo_operacion',
        'encendido',
        'bloqueo_remoto',
        'funcionando',
        'estado',
        'tanque_codigo',
        'tanque_capacidad_litros',
        'tanque_altura_metros',
        'tanque_diametro_metros',
        'observaciones',
        'centros_salud_id',
        'nivel_tanque_estimado_pct',
        'nivel_tanque_ultimo_lleno_en',
        'nivel_tanque_ultimo_inicio_en',
        'nivel_tanque_consumo_lpm',
    ];

    protected $casts = [
        'modo_operacion' => 'boolean',
        'encendido' => 'boolean',
        'bloqueo_remoto' => 'boolean',
        'funcionando' => 'boolean',
        'fecha_instalacion' => 'date',
        'nivel_tanque_estimado_pct' => 'decimal:2',
        'nivel_tanque_ultimo_lleno_en' => 'datetime',
        'nivel_tanque_ultimo_inicio_en' => 'datetime',
        'nivel_tanque_consumo_lpm' => 'decimal:4',
    ];

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(
            CentroSalud::class,
            'centros_salud_id'
        );
    }

    public function sensores(): HasMany
    {
        return $this->hasMany(
            Sensor::class,
            'bombas_id'
        );
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(
            Alerta::class,
            'bombas_id'
        );
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(
            Mantenimiento::class,
            'bombas_id'
        );
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(
            EventoBomba::class,
            'bombas_id'
        );
    }

    /**
     * Minutos que la bomba lleva funcionando
     * de forma continua, segun la corriente medida.
     */
    public function minutosEncendidaContinuo(): ?int
    {
        if (! $this->funcionando) {
            return null;
        }

        $ultimoEvento = $this->eventos()
            ->orderBy('fecha_hora', 'desc')
            ->first();

        if (! $ultimoEvento || ! $ultimoEvento->encendido) {
            return null;
        }

        return (int) $ultimoEvento->fecha_hora
            ->diffInMinutes(now());
    }

    /**
     * Cantidad de arranques en los ultimos minutos indicados.
     */
    public function ciclosEncendidoEn(int $minutos): int
    {
        return $this->eventos()
            ->where('encendido', true)
            ->where(
                'fecha_hora',
                '>=',
                now()->subMinutes($minutos)
            )
            ->count();
    }

    /**
     * Marca de tiempo (unix) de la ultima senal de un sensor de corriente
     * de esta bomba. La escribe DispositivoApiController en cada lectura recibida.
     */
    public function ultimaSenalCorriente(): ?int
    {
        $ultima = null;

        foreach ($this->sensores()->where('tipo', 'corriente')->pluck('id') as $id) {
            $ts = Cache::store('file')->get('sensor_visto_'.$id);

            if ($ts && (! $ultima || $ts > $ultima)) {
                $ultima = (int) $ts;
            }
        }

        return $ultima;
    }

    /**
     * Si dice "funcionando" pero el sensor de corriente lleva demasiado tiempo
     * sin reportar, la bomba se marca como detenida.
     */
    public function sincronizarFuncionamiento(): void
    {
        if (! $this->funcionando) {
            return;
        }

        // Sin sensor de corriente no hay forma de contrastar: no se toca.
        if (! $this->sensores()->where('tipo', 'corriente')->exists()) {
            return;
        }

        $limite = now()
            ->subSeconds((int) config('monitoreo.corriente_sin_senal_segundos', 120))
            ->timestamp;

        $ultima = $this->ultimaSenalCorriente();

        if ($ultima === null || $ultima < $limite) {
            $this->update(['funcionando' => false]); // el Observer registra el evento
        }
    }

    /** Bombas visibles: todas (administrador/tecnico) o solo las de su centro (director). */
    public function scopeVisiblesPara($query, User $usuario)
    {
        if ($usuario->veTodosLosCentros()) {
            return $query;
        }

        return $usuario->centros_salud_id
            ? $query->where($this->qualifyColumn('centros_salud_id'), $usuario->centros_salud_id)
            : $query->whereRaw('1 = 0');
    }
}
