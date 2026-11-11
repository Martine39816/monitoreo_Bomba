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

    /** Centros visibles: todos (administrador/tecnico) o solo el suyo (director). */
    public function scopeVisiblesPara($query, User $usuario)
    {
        if ($usuario->veTodosLosCentros()) {
            return $query;
        }

        return $usuario->centros_salud_id
            ? $query->where($this->qualifyColumn('id'), $usuario->centros_salud_id)
            : $query->whereRaw('1 = 0');
    }
}
