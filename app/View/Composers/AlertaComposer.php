<?php

namespace App\View\Composers;

use App\Models\Alerta;
use Illuminate\View\View;

class AlertaComposer
{
    public function compose(View $view): void
    {
        $pendientes = Alerta::where('atendida', false)
            ->with('bomba')
            ->orderBy('fecha_hora', 'desc')
            ->limit(5)
            ->get();

        $view->with('alertasPendientesCount', Alerta::where('atendida', false)->count());
        $view->with('alertasPendientesRecientes', $pendientes);
    }
}