<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroSalud extends Model
{
    use HasFactory;

    protected $table = 'centros_salud';

    protected $fillable = ['codigo', 'nombre', 'direccion', 'telefono'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'centros_salud_id');
    }

    public function bombas(): HasMany
    {
        return $this->hasMany(Bomba::class, 'centros_salud_id');
    }

    public function dispositivosIot(): HasMany
    {
        return $this->hasMany(DispositivoIot::class, 'centros_salud_id');
    }
}
