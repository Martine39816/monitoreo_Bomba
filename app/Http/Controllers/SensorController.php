<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\DispositivoIot;
use App\Models\Sensor;
use App\Support\TextPatterns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SensorController extends Controller
{
    private const TIPOS = ['temperatura', 'vibracion', 'corriente', 'presion', 'nivel'];
    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $query = Sensor::with(['bomba.centroSalud', 'dispositivoIot', 'ultimaLectura'])
            ->orderBy('id', 'desc');

        $bombas = Bomba::orderBy('nombre');

        // Solo el Administrador ve los sensores de todos los centros.
        if (! $usuario->esAdministrador()) {
            $query->whereHas('bomba', fn ($q) => $q->where('centros_salud_id', $usuario->centros_salud_id));
            $bombas->where('centros_salud_id', $usuario->centros_salud_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }
        if ($request->filled('bomba_id')) {
            $query->where('bombas_id', $request->input('bomba_id'));
        }
        if ($request->filled('q')) {
            $texto = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('nombre', 'like', $texto)->orWhere('codigo', 'like', $texto));
        }

        return view('sensores.index', [
            'sensores' => $query->paginate(15)->withQueryString(),
            'bombas' => $bombas->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('sensores.create', [
            'bombas' => $this->bombasDisponibles($request),
            'dispositivos' => $this->dispositivosDisponibles($request),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        Sensor::create($validated);

        return redirect()->route('sensores.index')->with('status', 'Sensor registrado correctamente.');
    }

    public function show(Request $request, Sensor $sensor): View
    {
        $this->verificarCentro($request, $sensor);

        $sensor->load('bomba', 'dispositivoIot', 'lecturas');

        return view('sensores.show', compact('sensor'));
    }

    public function edit(Request $request, Sensor $sensor): View
    {
        $this->verificarCentro($request, $sensor);

        return view('sensores.edit', [
            'sensor' => $sensor,
            'bombas' => $this->bombasDisponibles($request),
            'dispositivos' => $this->dispositivosDisponibles($request),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function update(Request $request, Sensor $sensor): RedirectResponse
    {
        $this->verificarCentro($request, $sensor);

        $validated = $this->validarDatos($request, $sensor->id);

        $sensor->update($validated);

        return redirect()->route('sensores.index')->with('status', 'Sensor actualizado correctamente.');
    }

    public function destroy(Request $request, Sensor $sensor): RedirectResponse
    {
        $this->verificarCentro($request, $sensor);

        $sensor->delete();

        return redirect()->route('sensores.index')->with('status', 'Sensor eliminado.');
    }

    /**
     * Verifica que el usuario (si no es administrador) pertenezca al mismo
     * centro de salud que la bomba asociada al sensor.
     */
    private function verificarCentro(Request $request, Sensor $sensor): void
    {
        $usuario = $request->user();

        if ($usuario->esAdministrador()) {
            return;
        }

        abort_if($sensor->bomba?->centros_salud_id !== $usuario->centros_salud_id, 403, 'No tienes permiso para acceder a este sensor.');
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
     * Dispositivos que el usuario puede seleccionar, con el mismo criterio.
     */
    private function dispositivosDisponibles(Request $request)
    {
        return DispositivoIot::query()
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->where('centros_salud_id', $request->user()->centros_salud_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        $usuario = $request->user();

        $reglaDispositivo = Rule::exists('dispositivos_iot', 'id');
        $reglaBomba = Rule::exists('bombas', 'id');

        // Un no-administrador solo puede asociar sensores a bombas y
        // dispositivos de su propio centro de salud.
        if (! $usuario->esAdministrador()) {
            $reglaDispositivo->where('centros_salud_id', $usuario->centros_salud_id);
            $reglaBomba->where('centros_salud_id', $usuario->centros_salud_id);
        }

        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'regex:'.TextPatterns::CODIGO, Rule::unique('sensores', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:120', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'marca' => ['nullable', 'string', 'max:80', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'modelo' => ['nullable', 'string', 'max:80', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            'valor_minimo' => ['nullable', 'numeric', 'lt:valor_maximo'],
            'valor_maximo' => ['nullable', 'numeric', 'gt:valor_minimo'],
            'precision_sensor' => ['nullable', 'numeric'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'fecha_instalacion' => ['nullable', 'date'],
            'ubicacion' => ['nullable', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'dispositivos_iot_id' => ['required', $reglaDispositivo],
            'bombas_id' => ['required', $reglaBomba],
        ], [
            'codigo.regex' => 'El código solo puede contener letras, números, guiones y puntos (sin espacios).',
            'nombre.regex' => 'El nombre no puede tener espacios dobles ni caracteres especiales.',
            'marca.regex' => 'La marca no puede tener espacios dobles ni caracteres especiales.',
            'modelo.regex' => 'El modelo no puede tener espacios dobles ni caracteres especiales.',
            'ubicacion.regex' => 'La ubicación no puede tener espacios dobles ni caracteres especiales.',
            'valor_minimo.lt' => 'El valor mínimo debe ser menor que el valor máximo.',
            'valor_maximo.gt' => 'El valor máximo debe ser mayor que el valor mínimo.',
        ]);
    }
}