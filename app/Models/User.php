<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de autenticacion. Vive sobre la tabla 'usuarios' (no 'users'),
 * que es la tabla real del modelo de datos del proyecto.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    public const ROL_ADMINISTRADOR = 'administrador';
    public const ROL_TECNICO = 'tecnico';
    public const ROL_DIRECTOR = 'director';

    public const ROLES = [self::ROL_ADMINISTRADOR, self::ROL_TECNICO, self::ROL_DIRECTOR];

    protected $fillable = [
        'nombre', 'apellido', 'correo', 'telefono', 'password',
        'rol', 'estado', 'centros_salud_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Laravel usa este campo como "username" en vez de 'email'.
     * Se mantiene el nombre 'email' aqui solo por compatibilidad con
     * el sistema de recuperacion de contrasena de Laravel.
     */
    public function getEmailAttribute(): string
    {
        return $this->correo;
    }

    public function getNameAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }

    // ---- Relaciones ----

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class, 'centros_salud_id');
    }

    public function alertasAtendidas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'usuario_atencion_id');
    }

    public function mantenimientosRealizados(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'usuarios_id');
    }

    // ---- Helpers de rol (usados por Gates/Policies) ----

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esTecnico(): bool
    {
        return $this->rol === self::ROL_TECNICO;
    }

    public function esDirector(): bool
    {
        return $this->rol === self::ROL_DIRECTOR;
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }
}