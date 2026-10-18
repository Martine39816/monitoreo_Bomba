<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Sensor;
use App\Services\AlertaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturaController extends Controller
{
    public function __construct(private readonly AlertaService $alertaService)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $query = Lectura::with('sensor.bomba')->orderBy('fecha_hora', 'desc');

        if ($request->filled('sensor_id')) {
            $query->where('sensores_id', $request->input('sensor_id'));
        }

        if (! $usuario->esAdministrador()) {
            $query->whereHas('sensor.bomba', function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $lecturas = $query->paginate(30)->withQueryString();
        $sensores = Sensor::query()
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->whereHas('bomba', function ($qb) use ($usuario) {
                    $qb->where('centros_salud_id', $usuario->centros_salud_id);
                });
            })
            ->orderBy('nombre')
            ->get();

        // Tarjetas resumen: última lectura conocida de cada sensor
        $resumenSensores = Sensor::with(['ultimaLectura', 'bomba'])
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->whereHas('bomba', function ($qb) use ($usuario) {
                    $qb->where('centros_salud_id', $usuario->centros_salud_id);
                });
            })
            ->orderBy('nombre')
            ->get();

        // Datos para el gráfico: últimas 50 lecturas del sensor filtrado, en orden cronológico
        $sensorGrafico = null;
        $datosGrafico = [];

        if ($request->filled('sensor_id')) {
            $sensorGrafico = Sensor::with('bomba')->find($request->input('sensor_id'));

            if ($sensorGrafico) {
                // El alcance se resuelve en el servidor antes de consultar las
                // lecturas y antes de entregar el sensor a la vista: un
                // no-administrador no puede ver el grafico de un sensor de otro centro.
                $this->verificarCentro($request, $sensorGrafico);

                $datosGrafico = Lectura::where('sensores_id', $sensorGrafico->id)
                    ->orderBy('fecha_hora', 'desc')
                    ->limit(50)
                    ->get()
                    ->sortBy('fecha_hora')
                    ->map(fn ($l) => [
                        'hora' => $l->fecha_hora->format('d/m H:i'),
                        'valor' => (float) $l->valor_medido,
                    ])
                ->values();
            }
        }

        return view('lecturas.index', compact(
            'lecturas', 'sensores', 'resumenSensores', 'datosGrafico', 'sensorGrafico'
        ));
    }

    /**
     * Formulario para registrar una lectura de prueba (simula lo que haría el ESP32).
     */
    public function create(Request $request): View
    {
        $usuario = $request->user();

        $sensores = Sensor::with('bomba')
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->whereHas('bomba', function ($qb) use ($usuario) {
                    $qb->where('centros_salud_id', $usuario->centros_salud_id);
                });
            })
            ->orderBy('nombre')
            ->get();

        return view('lecturas.create', compact('sensores'));
    }

    /**
     * Guarda una lectura de prueba y, si el valor está fuera de rango, genera una alerta.
     */
    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $reglaSensor = Rule::exists('sensores', 'id');

        // Un no-administrador solo puede registrar lecturas de sensores
        // asociados a bombas de su propio centro de salud. La pertenencia se
        // valida en el servidor y no depende del desplegable de la vista.
        if (! $usuario->esAdministrador()) {
            $reglaSensor->whereIn('bombas_id', function ($sub) use ($usuario) {
                $sub->select('id')->from('bombas')
                    ->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $validated = $request->validate([
            'sensores_id' => ['required', $reglaSensor],
            'valor_medido' => ['required', 'numeric', 'gte:0'],
            'fecha_hora' => ['nullable', 'date'],
        ]);

        $sensor = Sensor::with('bomba')->findOrFail($validated['sensores_id']);

        // Sensores que solo tienen sentido con la bomba encendida.
        $tiposDependientesDeBomba = ['corriente', 'vibracion'];

        if (in_array($sensor->tipo, $tiposDependientesDeBomba, true) && ! ($sensor->bomba?->encendido ?? false)) {
            return back()->withErrors([
                'valor_medido' => 'La bomba está apagada. No se pueden registrar lecturas de ' . $sensor->tipo . '.',
            ])->withInput();
        }

        // Lectura y alerta se guardan dentro de una misma transaccion para
        // evitar que quede una lectura sin su alerta si algo falla a mitad.
        $alertaGenerada = DB::transaction(function () use ($validated, $sensor) {
            $lectura = Lectura::create([
                'sensores_id' => $validated['sensores_id'],
                'valor_medido' => $validated['valor_medido'],
                'fecha_hora' => $validated['fecha_hora'] ?? now(),
            ]);

            return $this->alertaService->evaluarYGenerarAlerta($lectura, $sensor);
        });

        $mensaje = 'Lectura registrada correctamente.';
        if ($alertaGenerada) {
            $mensaje .= ' Se generó una alerta porque el valor está fuera del rango normal.';
        }

        return redirect()->route('lecturas.index')->with('status', $mensaje);
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
}
