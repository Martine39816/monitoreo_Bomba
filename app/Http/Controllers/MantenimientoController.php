<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Mantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MantenimientoController extends Controller
{
    private const TIPOS = ['preventivo', 'correctivo', 'predictivo', 'emergencia'];

    public function index(Request $request): View
    {
        $mantenimientos = Mantenimiento::visiblesPara($request->user())
            ->with('bomba.centroSalud', 'usuario', 'alerta')
            ->orderBy('fecha', 'desc')
            ->get();

        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function create(Request $request): View
    {
        // El técnico (y el administrador) pueden elegir la bomba de CUALQUIER centro.
        $bombas = Bomba::visiblesPara($request->user())->with('centroSalud')->orderBy('nombre')->get();

        // Si se llega desde una alerta ("Generar mantenimiento"), se preselecciona
        $alertaId = $request->query('alerta_id');
        $alertasAbiertas = Alerta::visiblesPara($request->user())
            ->whereDoesntHave('mantenimientos')
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return view('mantenimientos.create', [
            'bombas' => $bombas,
            'tipos' => self::TIPOS,
            'alertasAbiertas' => $alertasAbiertas,
            'alertaPreseleccionada' => $alertaId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);
        $this->asegurarBombaVisible($request, (int) $validated['bombas_id']);
        $validated['usuarios_id'] = $request->user()->id;

        Mantenimiento::create($validated);

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento registrado correctamente.');
    }

    public function show(Request $request, Mantenimiento $mantenimiento): View
    {
        $this->asegurarAlcance($request, $mantenimiento);

        $mantenimiento->load('bomba', 'usuario', 'alerta');

        return view('mantenimientos.show', compact('mantenimiento'));
    }

    public function edit(Request $request, Mantenimiento $mantenimiento): View
    {
        $this->asegurarAlcance($request, $mantenimiento);

        $bombas = Bomba::visiblesPara($request->user())->with('centroSalud')->orderBy('nombre')->get();
        $alertasAbiertas = Alerta::visiblesPara($request->user())->where(function ($q) use ($mantenimiento) {
            $q->whereDoesntHave('mantenimientos')
                ->orWhere('id', $mantenimiento->alertas_id);
        })->orderBy('fecha_hora', 'desc')->get();

        return view('mantenimientos.edit', [
            'mantenimiento' => $mantenimiento,
            'bombas' => $bombas,
            'tipos' => self::TIPOS,
            'alertasAbiertas' => $alertasAbiertas,
        ]);
    }

    public function update(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->asegurarAlcance($request, $mantenimiento);

        $validated = $this->validarDatos($request);
        $this->asegurarBombaVisible($request, (int) $validated['bombas_id']);

        $mantenimiento->update($validated);

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento actualizado correctamente.');
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->asegurarAlcance($request, $mantenimiento);

        $mantenimiento->delete();

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento eliminado.');
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'proximo_mantenimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'bombas_id' => ['required', 'exists:bombas,id'],
            'alertas_id' => ['nullable', 'exists:alertas,id'],
        ]);
    }

    /** Evita acceder por URL a mantenimientos de otro centro (solo aplica al Director). */
    private function asegurarAlcance(Request $request, Mantenimiento $mantenimiento): void
    {
        $mantenimiento->loadMissing('bomba:id,centros_salud_id');

        abort_unless(
            $request->user()->puedeVerCentro($mantenimiento->bomba?->centros_salud_id),
            403,
            'No tienes permiso sobre este mantenimiento.'
        );
    }

    private function asegurarBombaVisible(Request $request, int $bombaId): void
    {
        $bomba = Bomba::findOrFail($bombaId);

        abort_unless($request->user()->puedeVerCentro($bomba->centros_salud_id), 403);
    }
}
