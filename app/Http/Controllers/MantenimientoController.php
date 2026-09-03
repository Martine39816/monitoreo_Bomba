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

    public function index(): View
    {
        $mantenimientos = Mantenimiento::with('bomba', 'usuario', 'alerta')
            ->orderBy('fecha', 'desc')
            ->get();

        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function create(Request $request): View
    {
        $bombas = Bomba::orderBy('nombre')->get();

        // Si se llega desde una alerta ("Generar mantenimiento"), se preselecciona
        $alertaId = $request->query('alerta_id');
        $alertasAbiertas = Alerta::whereDoesntHave('mantenimientos')
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
        $validated['usuarios_id'] = $request->user()->id;

        Mantenimiento::create($validated);

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento registrado correctamente.');
    }

    public function show(Mantenimiento $mantenimiento): View
    {
        $mantenimiento->load('bomba', 'usuario', 'alerta');

        return view('mantenimientos.show', compact('mantenimiento'));
    }

    public function edit(Mantenimiento $mantenimiento): View
    {
        $bombas = Bomba::orderBy('nombre')->get();
        $alertasAbiertas = Alerta::where(function ($q) use ($mantenimiento) {
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
        $validated = $this->validarDatos($request);

        $mantenimiento->update($validated);

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento actualizado correctamente.');
    }

    public function destroy(Mantenimiento $mantenimiento): RedirectResponse
    {
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
}