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
    ];

    public function index(Request $request): View
    {
        $query = Alerta::with('bomba', 'sensor', 'usuarioAtencion')->orderBy('fecha_hora', 'desc');

        if ($request->input('filtro') === 'pendientes') {
            $query->where('atendida', false);
        }

        $alertas = $query->paginate(20)->withQueryString();

        return view('alertas.index', compact('alertas'));
    }

    public function create(): View
    {
        return view('alertas.create', [
            'bombas' => Bomba::orderBy('nombre')->get(),
            'sensores' => Sensor::orderBy('nombre')->get(),
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

    public function show(Alerta $alerta): View
    {
        $alerta->load('bomba', 'sensor', 'usuarioAtencion');

        return view('alertas.show', compact('alerta'));
    }

    public function atender(Request $request, Alerta $alerta): RedirectResponse
    {
        $alerta->update([
            'atendida' => true,
            'fecha_atencion' => now(),
            'usuario_atencion_id' => $request->user()->id,
        ]);

        return redirect()->route('alertas.index')->with('status', 'Alerta marcada como solucionada.');
    }
}