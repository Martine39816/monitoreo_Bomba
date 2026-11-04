<?php

namespace App\View\Components;

use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\VistaSaludBomba;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Bloque "Control Rápido de Bombas" del dashboard.
 * Pensado para muchos centros y muchas bombas: filtra, busca y pagina.
 * Uso en el dashboard:  <x-control-rapido-bombas />
 */
class ControlRapidoBombas extends Component
{
    private const POR_PAGINA = 8;

    public function render(): View
    {
        $request = request();
        $usuario = $request->user();

        $query = Bomba::with('centroSalud:id,nombre')
            ->withCount(['alertas as alertas_pendientes' => fn ($q) => $q->where('atendida', false)]);

        $centros = CentroSalud::orderBy('nombre');

        // Solo el Administrador ve todos los centros.
        if (! $usuario->esAdministrador()) {
            $query->where('centros_salud_id', $usuario->centros_salud_id);
            $centros->where('id', $usuario->centros_salud_id);
        }

        if ($request->filled('centro')) {
            $query->where('centros_salud_id', $request->input('centro'));
        }

        match ($request->input('estado')) {
            'encendidas' => $query->where('encendido', true),
            'apagadas' => $query->where('encendido', false),
            'con_alertas' => $query->having('alertas_pendientes', '>', 0),
            default => null,
        };

        if ($request->filled('q')) {
            $texto = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('nombre', 'like', $texto)->orWhere('codigo', 'like', $texto));
        }

        // Primero las que tienen alertas pendientes, luego por nombre.
        $bombas = $query
            ->orderByDesc('alertas_pendientes')
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA, ['*'], 'bombas_page')
            ->withQueryString();

        $saludes = VistaSaludBomba::whereIn('bomba_id', $bombas->pluck('id'))
            ->get()
            ->keyBy('bomba_id');

        return view('components.control-rapido-bombas', [
            'bombas' => $bombas,
            'saludes' => $saludes,
            'centros' => $centros->get(['id', 'nombre']),
        ]);
    }
}