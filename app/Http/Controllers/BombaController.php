<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Support\TextPatterns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BombaController extends Controller
{
    use AuthorizesRequests;

    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    /**
     * Cuántas lecturas recientes se muestran por sensor en la ficha de la bomba.
     */
    private const LECTURAS_POR_SENSOR = 8;

    /**
     * Cuántas alertas se muestran en la ficha de la bomba.
     */
    private const ALERTAS_RECIENTES = 15;

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $query = Bomba::with('centroSalud')->orderBy('id', 'desc');

        // Solo el Administrador ve las bombas de todos los centros.
        if (! $usuario->esAdministrador()) {
            $query->where('centros_salud_id', $usuario->centros_salud_id);
        }

        $bombas = $query->paginate(15);

        return view('bombas.index', compact('bombas'));
    }

    public function create(Request $request): View
    {
        $centros = $this->centrosDisponibles($request);

        return view('bombas.create', ['centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        // Un no-administrador solo puede registrar bombas en su propio centro.
        if (! $request->user()->esAdministrador()) {
            $validated['centros_salud_id'] = $request->user()->centros_salud_id;
        }

        Bomba::create($validated);

        return redirect()->route('bombas.index')->with('status', 'Bomba registrada correctamente.');
    }

    public function show(Request $request, Bomba $bomba): View
    {
        $this->authorize('view', $bomba);

        $bomba->load('centroSalud', 'sensores');

        $salud = \App\Models\VistaSaludBomba::where('bomba_id', $bomba->id)->first();

        // Últimas N lecturas de cada sensor de esta bomba (no el historial completo).
        foreach ($bomba->sensores as $sensor) {
            $sensor->setRelation(
                'ultimasLecturas',
                $sensor->lecturas()
                    ->orderByDesc('fecha_hora')
                    ->limit(self::LECTURAS_POR_SENSOR)
                    ->get()
            );
        }

        // Pendientes primero; dentro de cada grupo, las más recientes arriba.
        $alertas = $bomba->alertas()
            ->with('sensor:id,tipo,codigo')
            ->orderBy('atendida')
            ->orderByDesc('fecha_hora')
            ->limit(self::ALERTAS_RECIENTES)
            ->get();

        // Datos para el selector Centro de Salud -> Bomba (respeta el rol).
        $usuario = $request->user();
        $visibles = Bomba::with('centroSalud:id,nombre')->orderBy('nombre');

        if (! $usuario->esAdministrador()) {
            $visibles->where('centros_salud_id', $usuario->centros_salud_id);
        }

        $selector = $visibles->get()->map(fn (Bomba $b) => [
            'id' => $b->id,
            'codigo' => $b->codigo,
            'nombre' => $b->nombre,
            'centro_id' => $b->centros_salud_id,
            'centro' => $b->centroSalud?->nombre ?? 'Sin centro',
        ])->values();

        $centrosSelector = $selector
            ->unique('centro_id')
            ->map(fn ($b) => ['id' => $b['centro_id'], 'nombre' => $b['centro']])
            ->sortBy('nombre')
            ->values();

        return view('bombas.show', compact('bomba', 'salud', 'alertas', 'selector', 'centrosSelector'));
    }

    public function edit(Request $request, Bomba $bomba): View
    {
        $this->authorize('update', $bomba);

        $centros = $this->centrosDisponibles($request);

        return view('bombas.edit', ['bomba' => $bomba, 'centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function update(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('update', $bomba);

        $validated = $this->validarDatos($request, $bomba->id);

        // Un no-administrador no puede mover la bomba a otro centro.
        if (! $request->user()->esAdministrador()) {
            $validated['centros_salud_id'] = $request->user()->centros_salud_id;
        }

        $bomba->update($validated);

        return redirect()->route('bombas.index')->with('status', 'Bomba actualizada correctamente.');
    }

    public function destroy(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('delete', $bomba);

        $bomba->delete();

        return redirect()->route('bombas.index')->with('status', 'Bomba eliminada.');
    }

    public function encender(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('controlar', $bomba);

        $bomba->update(['encendido' => true]);

        return redirect()->route('bombas.show', $bomba->id)
            ->with('status', 'Bomba encendida correctamente.');
    }

    public function apagar(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('controlar', $bomba);

        $bomba->update(['encendido' => false]);

        return redirect()->route('bombas.show', $bomba->id)
            ->with('status', 'Bomba apagada correctamente.');
    }

    /**
     * Centros que el usuario puede seleccionar: todos para administrador,
     * solo el propio para el resto de roles.
     */
    private function centrosDisponibles(Request $request)
    {
        return CentroSalud::query()
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->where('id', $request->user()->centros_salud_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'regex:'.TextPatterns::CODIGO, Rule::unique('bombas', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'marca' => ['nullable', 'string', 'max:100', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'modelo' => ['nullable', 'string', 'max:100', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'serie' => ['required', 'string', 'max:80', 'regex:'.TextPatterns::CODIGO, Rule::unique('bombas', 'serie')->ignore($ignorarId)],
            'potencia_hp' => ['nullable', 'numeric', 'gt:0'],
            'voltaje_nominal' => ['nullable', 'numeric', 'gt:0'],
            'corriente_nominal' => ['nullable', 'numeric', 'gte:0'],
            'caudal_min' => ['nullable', 'numeric', 'gte:0'],
            'caudal_max' => ['nullable', 'numeric', 'gte:0'],
            'altura_maxima' => ['nullable', 'numeric', 'gte:0'],
            'temperatura_maxima' => ['nullable', 'numeric'],
            'presion_maxima' => ['nullable', 'numeric', 'gte:0'],
            'fecha_instalacion' => ['nullable', 'date'],
            'modo_operacion' => ['required', 'boolean'],
            'encendido' => ['required', 'boolean'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'tanque_codigo' => ['nullable', 'string', 'max:30', 'regex:'.TextPatterns::CODIGO],
            'tanque_capacidad_litros' => ['nullable', 'numeric', 'gt:0'],
            'tanque_altura_metros' => ['nullable', 'numeric', 'gte:0'],
            'tanque_diametro_metros' => ['nullable', 'numeric', 'gte:0'],
            'observaciones' => ['nullable', 'string'],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
        ], [
            'codigo.regex' => 'El código solo puede contener letras, números, guiones y puntos (sin espacios).',
            'nombre.regex' => 'El nombre no puede tener espacios dobles ni caracteres especiales.',
            'marca.regex' => 'La marca no puede tener espacios dobles ni caracteres especiales.',
            'modelo.regex' => 'El modelo no puede tener espacios dobles ni caracteres especiales.',
            'serie.regex' => 'La serie solo puede contener letras, números, guiones y puntos (sin espacios).',
            'tanque_codigo.regex' => 'El código del tanque solo puede contener letras, números, guiones y puntos (sin espacios).',
        ]);
    }
}