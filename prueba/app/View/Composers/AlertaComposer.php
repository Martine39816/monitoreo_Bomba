<?php

namespace App\View\Composers;

use App\Models\Alerta;
use Illuminate\View\View;

class AlertaComposer
{
    public function compose(View $view): void
    {
        $usuario = auth()->user();

        if (! $usuario) {
            $view->with('alertasPendientesCount', 0);
            $view->with('alertasPendientesRecientes', collect());

            return;
        }

        // Administrador y Técnico: todas las alertas. Director: solo las de su centro.
        $base = Alerta::visiblesPara($usuario)->where('atendida', false);

        $view->with('alertasPendientesCount', (clone $base)->count());
        $view->with('alertasPendientesRecientes', (clone $base)
            ->with('bomba')
            ->orderBy('fecha_hora', 'desc')
            ->limit(5)
            ->get());
    }
}
