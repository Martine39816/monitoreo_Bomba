<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();

        // Bombas visibles según el rol
        $bombasControl = $usuario->esAdministrador()
            ? Bomba::with('centroSalud')->orderBy('nombre')->get()
            : Bomba::where('centros_salud_id', $usuario->centros_salud_id)
                ->orderBy('nombre')
                ->get();

        // Alertas pendientes visibles según el rol
        $alertasQuery = Alerta::with(['sensor', 'bomba'])
            ->where('atendida', false)
            ->orderBy('fecha_hora', 'desc');

        if (! $usuario->esAdministrador()) {
            $alertasQuery->whereHas('bomba', function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $totalAlertasPendientes = (clone $alertasQuery)->count();
        $alertasPendientes = $alertasQuery->limit(5)->get();

        $metricas = [
            'bombas_total' => $bombasControl->count(),
            'bombas_encendidas' => $bombasControl->where('encendido', true)->count(),
            'alertas_pendientes' => $totalAlertasPendientes,
            'centros_total' => $usuario->esAdministrador() ? CentroSalud::count() : null,
        ];

        return view('dashboard', compact('bombasControl', 'alertasPendientes', 'metricas'));
    }
}