<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Sensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertaController extends Controller
{
    private const TIPOS = [
        'nivel_bajo', 'nivel_alto', 'bomba_apagada', 'falla_electrica',
        'sobrecorriente', 'temperatura_alta', 'vibracion_alta', 'sensor_desconectado',
        'tiempo_excedido', 'ciclos_frecuentes', 'posible_rebalse',
    ];

    public function index(Request $request): View
    {
        // Administrador y Técnico ven todas; el Director solo las de su centro.
        $query = Alerta::visiblesPara($request->user())
            ->with('bomba', 'sensor', 'usuarioAtencion')
            ->orderBy('fecha_hora', 'desc');

        if ($request->input('filtro') === 'pendientes') {
            $query->where('atendida', false);
        }

        $alertas = $query->paginate(20)->withQueryString();

        return view('alertas.index', compact('alertas'));
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('alertas.create', [
            'bombas' => Bomba::visiblesPara($usuario)->orderBy('nombre')->get(),
            'sensores' => Sensor::visiblesPara($usuario)->orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'descripcion' => ['required', 'string', 'max:255'],
            'bombas_id' => ['required', 'exists:bombas,id'],
            'sensores_id' => ['nullable', 'exists:sensores,id'],
        ]);

        // Un director solo puede reportar problemas de las bombas de su centro.
        $this->authorize('view', Bomba::findOrFail($validated['bombas_id']));

        Alerta::create([
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'bombas_id' => $validated['bombas_id'],
            'sensores_id' => $validated['sensores_id'] ?? null,
            'fecha_hora' => now(),
            'atendida' => false,
        ]);

        return redirect()->route('alertas.index')->with('status', 'Alerta reportada correctamente.');
    }

    public function show(Request $request, Alerta $alerta): View
    {
        $this->asegurarAlcance($request, $alerta);

        $alerta->load('bomba', 'sensor', 'usuarioAtencion');

        return view('alertas.show', compact('alerta'));
    }

    public function atender(Request $request, Alerta $alerta): RedirectResponse
    {
        $this->asegurarAlcance($request, $alerta);

        $alerta->update([
            'atendida' => true,
            'fecha_atencion' => now(),
            'usuario_atencion_id' => $request->user()->id,
        ]);

        return redirect()->route('alertas.index')->with('status', 'Alerta marcada como solucionada.');
    }

    /** Evita acceder por URL a alertas de otro centro (solo aplica al Director). */
    private function asegurarAlcance(Request $request, Alerta $alerta): void
    {
        $alerta->loadMissing('bomba:id,centros_salud_id');

        abort_unless(
            $request->user()->puedeVerCentro($alerta->bomba?->centros_salud_id),
            403,
            'No tienes permiso sobre esta alerta.'
        );
    }
}
