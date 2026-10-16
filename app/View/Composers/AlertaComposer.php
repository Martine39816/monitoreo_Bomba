<?php

namespace App\View\Composers;

use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertaComposer
{
    public function compose(View $view): void
    {
        $usuario = Auth::user();

        $pendientesQuery = Alerta::where('atendida', false)
            ->with('bomba')
            ->orderBy('fecha_hora', 'desc');

        if ($usuario && ! $usuario->esAdministrador()) {
            $pendientesQuery->whereHas('bomba', function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $pendientes = (clone $pendientesQuery)->limit(5)->get();

        $view->with('alertasPendientesCount', $pendientesQuery->count());
        $view->with('alertasPendientesRecientes', $pendientes);
    }
}