<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Services\ConsumoBomba;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumoController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $usuario = $request->user();

        [$periodo, $desde, $hasta, $titulo] = $this->resolverPeriodo($request);

        $centros = CentroSalud::orderBy('nombre');
        $bombas = Bomba::orderBy('nombre');

        // Solo el Administrador ve los centros y bombas de todo el sistema.
        if (! $usuario->esAdministrador()) {
            $centros->where('id', $usuario->centros_salud_id);
            $bombas->where('centros_salud_id', $usuario->centros_salud_id);
        }

        $centros = $centros->get(['id', 'nombre']);
        $todas = $bombas->get(['id', 'nombre', 'codigo', 'centros_salud_id']);

        $elegida = $request->filled('bomba_id')
            ? $todas->firstWhere('id', (int) $request->input('bomba_id'))
            : null;

        // Si cambió el centro, la bomba anterior ya no corresponde.
        if ($elegida && $request->filled('centro') && $elegida->centros_salud_id != (int) $request->input('centro')) {
            $elegida = null;
        }

        if (! $elegida) {
            $candidatas = $request->filled('centro')
                ? $todas->where('centros_salud_id', (int) $request->input('centro'))
                : $todas;

            $elegida = $candidatas->first();
        }

        $base = [
            'centros' => $centros,
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hasta,
            'titulo' => $titulo,
        ];

        if (! $elegida) {
            return view('consumo.index', $base + [
                'bomba' => null,
                'bombasCentro' => collect(),
                'centroSel' => null,
            ]);
        }

        $bomba = Bomba::with('centroSalud')->findOrFail($elegida->id);
        $this->authorize('view', $bomba);

        $consumo = new ConsumoBomba($bomba);

        return view('consumo.index', $base + [
            'bomba' => $bomba,
            'centroSel' => $bomba->centros_salud_id,
            'bombasCentro' => $todas->where('centros_salud_id', $bomba->centros_salud_id)->values(),
            'litrosPorCiclo' => $consumo->litrosPorCiclo(),
            'tiempo' => $consumo->tiempoLlenado(),
            'caudal' => $consumo->caudalLpm(),
            'resultado' => $consumo->periodo($desde, $hasta),
        ]);
    }

    /** Guarda (o borra) el tiempo de llenado que se usa para estimar el caudal. */
    public function guardarTiempo(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('update', $bomba);

        $validated = $request->validate([
            'tiempo_llenado_min' => ['nullable', 'numeric', 'gt:0', 'max:1440'],
        ], [
            'tiempo_llenado_min.gt' => 'El tiempo de llenado debe ser mayor que cero.',
            'tiempo_llenado_min.max' => 'El tiempo de llenado no puede superar 1440 minutos (24 horas).',
        ]);

        $bomba->tiempo_llenado_min = $validated['tiempo_llenado_min'] ?? null;
        $bomba->save();

        return redirect()
            ->route('consumo.index', ['bomba_id' => $bomba->id])
            ->with('status', $bomba->tiempo_llenado_min
                ? 'Tiempo de llenado guardado.'
                : 'Tiempo de llenado borrado: se estimará con los ciclos reales.');
    }

    /**
     * Período a mostrar: hoy (por defecto), últimos 7 días, últimos 30 días
     * o un rango de fechas elegido (máximo 92 días, sin fechas futuras).
     *
     * @return array{0: string, 1: Carbon, 2: Carbon, 3: string}
     */
    private function resolverPeriodo(Request $request): array
    {
        $hoy = now()->startOfDay();
        $periodo = $request->input('periodo', 'hoy');

        if ($periodo === 'rango') {
            $d = $this->fecha($request->input('desde'));
            $h = $this->fecha($request->input('hasta'));

            if ($d && $h && $d->lessThanOrEqualTo($h)) {
                if ($h->greaterThan($hoy)) {
                    $h = $hoy->copy();
                }
                if ($d->greaterThan($h)) {
                    $d = $h->copy();
                }
                if ($d->lessThan($h->copy()->subDays(91))) {
                    $d = $h->copy()->subDays(91);
                }

                return ['rango', $d, $h, $d->format('d/m/Y').' al '.$h->format('d/m/Y')];
            }

            $periodo = 'hoy';
        }

        return match ($periodo) {
            'semana' => ['semana', $hoy->copy()->subDays(6), $hoy->copy(), 'Últimos 7 días'],
            'mes' => ['mes', $hoy->copy()->subDays(29), $hoy->copy(), 'Últimos 30 días'],
            default => ['hoy', $hoy->copy(), $hoy->copy(), 'Hoy'],
        };
    }

    private function fecha(mixed $valor): ?Carbon
    {
        if (! is_string($valor) || $valor === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}