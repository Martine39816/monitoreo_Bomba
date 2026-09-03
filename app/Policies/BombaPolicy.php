<?php

namespace App\Policies;

use App\Models\Bomba;
use App\Models\User;

class BombaPolicy
{
    public function view(User $user, Bomba $bomba): bool
    {
        return $user->esAdministrador() || $user->centros_salud_id === $bomba->centros_salud_id;
    }

    public function update(User $user, Bomba $bomba): bool
    {
        if ($user->esAdministrador()) {
            return true;
        }

        return $user->esTecnico() && $user->centros_salud_id === $bomba->centros_salud_id;
    }

    /**
     * Encender/apagar y cambiar modo manual/automatico: la accion mas critica
     * del sistema porque opera equipo fisico real.
     * Administrador: puede controlar cualquier bomba, de cualquier centro.
     * Tecnico: solo las bombas de su propio centro.
     * Director: sin acceso (no aparece en @can ni pasa esta validacion).
     */
    public function controlar(User $user, Bomba $bomba): bool
    {
        if ($user->esAdministrador()) {
            return true;
        }

        return $user->esTecnico() && $user->centros_salud_id === $bomba->centros_salud_id;
    }

    public function delete(User $user, Bomba $bomba): bool
    {
        return $user->esAdministrador();
    }
}