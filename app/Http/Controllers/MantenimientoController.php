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
        $mantenimientos = Mantenimiento::with('bomba', 'usuario', 'alerta')
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->whereHas('bomba', function ($qb) use ($request) {
                    $qb->where('centros_salud_id', $request->user()->centros_salud_id);
                });
            })
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

    public function show(Request $request, Mantenimiento $mantenimiento): View
    {
        $this->verificarCentro($request, $mantenimiento);

        $mantenimiento->load('bomba', 'usuario', 'alerta');

        return view('mantenimientos.show', compact('mantenimiento'));
    }

    public function edit(Request $request, Mantenimiento $mantenimiento): View
    {
        $this->verificarCentro($request, $mantenimiento);

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
        $this->verificarCentro($request, $mantenimiento);

        $validated = $this->validarDatos($request);

        $mantenimiento->update($validated);

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento actualizado correctamente.');
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->verificarCentro($request, $mantenimiento);

        $mantenimiento->delete();

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento eliminado.');
    }

    /**
     * Verifica que el usuario (si no es administrador) pertenezca al mismo
     * centro de salud que la bomba asociada al mantenimiento.
     */
    private function verificarCentro(Request $request, Mantenimiento $mantenimiento): void
    {
        $usuario = $request->user();

        if ($usuario->esAdministrador()) {
            return;
        }

        abort_if($mantenimiento->bomba?->centros_salud_id !== $usuario->centros_salud_id, 403, 'No tienes permiso para acceder a este mantenimiento.');
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