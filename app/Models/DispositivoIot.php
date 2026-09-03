<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DispositivoIot extends Model
{
    use HasFactory;

    protected $table = 'dispositivos_iot';

    protected $fillable = [
        'codigo', 'nombre', 'modelo', 'tipo_dispositivo', 'puerto',
        'direccion_ip', 'mac_address', 'firmware', 'estado', 'centros_salud_id',
    ];

    protected $hidden = ['api_key'];

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class, 'centros_salud_id');
    }

    public function sensores(): HasMany
    {
        return $this->hasMany(Sensor::class, 'dispositivos_iot_id');
    }

    /**
     * Genera y guarda una nueva API key para el dispositivo (se muestra 1 sola vez).
     */
    public function generarApiKey(): string
    {
        $plain = Str::random(48);
        $this->api_key = hash('sha256', $plain);
        $this->save();

        return $plain; // el texto plano solo se entrega aqui, nunca se vuelve a mostrar
    }
}