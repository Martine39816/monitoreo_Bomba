<?php

namespace App\Policies;

use App\Models\Bomba;
use App\Models\User;

/**
 * Regla oficial de permisos por centro:
 *  - Administrador: todos los centros, control total.
 *  - Tecnico: todos los centros; puede editar y controlar bombas; no las elimina.
 *  - Director: solo su centro, solo consulta (no edita ni controla).
 */
class BombaPolicy
{
    public function view(User $user, Bomba $bomba): bool
    {
        return $user->puedeVerCentro($bomba->centros_salud_id);
    }

    public function update(User $user, Bomba $bomba): bool
    {
        return $user->tieneRol(User::ROL_ADMINISTRADOR, User::ROL_TECNICO);
    }

    /**
     * Encender/apagar y cambiar modo manual/automatico: la accion mas critica
     * del sistema porque opera equipo fisico real.
     * Administrador y Tecnico: cualquier bomba de cualquier centro.
     * Director: sin acceso.
     */
    public function controlar(User $user, Bomba $bomba): bool
    {
        return $user->tieneRol(User::ROL_ADMINISTRADOR, User::ROL_TECNICO);
    }

    public function delete(User $user, Bomba $bomba): bool
    {
        return $user->esAdministrador();
    }
}
