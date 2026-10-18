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
            ->paginate(15);

        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function create(Request $request): View
    {
        $bombas = $this->bombasDisponibles($request);

        // Si se llega desde una alerta ("Generar mantenimiento"), se preselecciona
        $alertaId = $request->query('alerta_id');
        $alertasAbiertas = $this->alertasDisponibles($request)
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

        $bombas = $this->bombasDisponibles($request);
        $alertasAbiertas = $this->alertasDisponibles($request)
            ->where(function ($q) use ($mantenimiento) {
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

    /**
     * Bombas que el usuario puede seleccionar: todas para administrador,
     * solo las de su propio centro para el resto de roles.
     */
    private function bombasDisponibles(Request $request)
    {
        return Bomba::query()
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->where('centros_salud_id', $request->user()->centros_salud_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Alertas visibles para el usuario, acotadas a su centro (excepto administrador).
     */
    private function alertasDisponibles(Request $request)
    {
        return Alerta::query()
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->whereHas('bomba', function ($qb) use ($request) {
                    $qb->where('centros_salud_id', $request->user()->centros_salud_id);
                });
            });
    }

    private function validarDatos(Request $request): array
    {
        $usuario = $request->user();

        $reglaBomba = Rule::exists('bombas', 'id');
        $reglaAlerta = Rule::exists('alertas', 'id');

        // Un no-administrador solo puede registrar mantenimientos sobre
        // bombas (y alertas) de su propio centro de salud.
        if (! $usuario->esAdministrador()) {
            $reglaBomba->where('centros_salud_id', $usuario->centros_salud_id);
            $reglaAlerta->whereIn(
                'bombas_id',
                Bomba::where('centros_salud_id', $usuario->centros_salud_id)->pluck('id')
            );
        }

        return $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'proximo_mantenimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'bombas_id' => ['required', $reglaBomba],
            'alertas_id' => ['nullable', $reglaAlerta],
        ]);
    }
}