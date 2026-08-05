<?php

namespace App\Policies;

use App\Models\Bomba;
use App\Models\User;

class BombaPolicy
{
    /** Cualquier usuario autenticado del mismo centro puede ver. */
    public function view(User $user, Bomba $bomba): bool
    {
        return $user->centros_salud_id === $bomba->centros_salud_id;
    }

    /** Solo Administrador y Tecnico pueden crear/editar fichas tecnicas. */
    public function update(User $user, Bomba $bomba): bool
    {
        return $user->tieneRol(User::ROL_ADMINISTRADOR, User::ROL_TECNICO)
            && $user->centros_salud_id === $bomba->centros_salud_id;
    }

    /**
     * Encender/apagar y cambiar modo manual/automatico: la accion mas critica
     * del sistema porque opera equipo fisico real. Se restringe a
     * Administrador y Tecnico (no Operador).
     */
    public function controlar(User $user, Bomba $bomba): bool
    {
        return $user->tieneRol(User::ROL_ADMINISTRADOR, User::ROL_TECNICO)
            && $user->centros_salud_id === $bomba->centros_salud_id;
    }

    /** Solo Administrador puede eliminar una bomba del sistema. */
    public function delete(User $user, Bomba $bomba): bool
    {
        return $user->esAdministrador();
    }
}
