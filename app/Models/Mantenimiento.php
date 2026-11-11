<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';

    public const TIPOS = ['preventivo', 'correctivo', 'predictivo', 'emergencia'];

    protected $fillable = [
        'tipo', 'fecha', 'descripcion', 'observaciones', 'proximo_mantenimiento',
        'bombas_id', 'usuarios_id', 'alertas_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'proximo_mantenimiento' => 'date',
    ];

    public function bomba(): BelongsTo
    {
        return $this->belongsTo(Bomba::class, 'bombas_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(Alerta::class, 'alertas_id');
    }

    /** Registros visibles segun el centro de la bomba asociada. */
    public function scopeVisiblesPara($query, User $usuario)
    {
        if ($usuario->veTodosLosCentros()) {
            return $query;
        }

        return $query->whereHas('bomba', fn ($q) => $q->visiblesPara($usuario));
    }
}
