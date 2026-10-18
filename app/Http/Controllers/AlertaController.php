<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Sensor;
use App\Services\ReporteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertaController extends Controller
{
    private const TIPOS = [
        'nivel_bajo', 'nivel_alto', 'bomba_apagada', 'falla_electrica',
        'sobrecorriente', 'temperatura_alta', 'vibracion_alta', 'sensor_desconectado',
        'tiempo_excedido', 'ciclos_frecuentes', 'posible_rebalse',
    ];

    public function __construct(private readonly ReporteService $reporteService)
    {
    }

    public function index(Request $request): View
    {
        $query = Alerta::with('bomba', 'sensor', 'usuarioAtencion')->orderBy('fecha_hora', 'desc');

        if ($request->input('filtro') === 'pendientes') {
            $query->where('atendida', false);
        }

        if (! $request->user()->esAdministrador()) {
            $query->whereHas('bomba', function ($q) use ($request) {
                $q->where('centros_salud_id', $request->user()->centros_salud_id);
            });
        }

        $alertas = $query->paginate(20)->withQueryString();

        // El umbral de atencion se calcula con el servicio centralizado (no hay
        // consultas por fila: solo se leen atributos ya cargados de la alerta).
        $alertas->getCollection()->each(function (Alerta $alerta): void {
            $estado = $this->reporteService->evaluarTiempoAtencion($alerta);
            $alerta->setAttribute('estado_tiempo_atencion', $estado);
            $alerta->setAttribute('etiqueta_tiempo_atencion', $this->reporteService->etiquetaTiempoAtencion($estado));
        });

        return view('alertas.index', compact('alertas'));
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        // Un no-administrador solo puede reportar problemas sobre bombas y
        // sensores de su propio centro de salud.
        $bombas = Bomba::query()
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            })
            ->orderBy('nombre')
            ->get();

        $sensores = Sensor::query()
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->whereHas('bomba', function ($sub) use ($usuario) {
                    $sub->where('centros_salud_id', $usuario->centros_salud_id);
                });
            })
            ->orderBy('nombre')
            ->get();

        return view('alertas.create', [
            'bombas' => $bombas,
            'sensores' => $sensores,
            'tipos' => self::TIPOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $bombaRule = Rule::exists('bombas', 'id');
        $sensorRule = Rule::exists('sensores', 'id');

        if (! $usuario->esAdministrador()) {
            $bombaRule->where('centros_salud_id', $usuario->centros_salud_id);
            $sensorRule->whereIn('bombas_id', function ($q) use ($usuario) {
                $q->select('id')->from('bombas')
                    ->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'descripcion' => ['required', 'string', 'max:255'],
            'bombas_id' => ['required', $bombaRule],
            'sensores_id' => [
                'nullable',
                $sensorRule,
                // Si se indica un sensor, debe pertenecer a la misma bomba de
                // la alerta: validar solo la existencia (aunque sea dentro del
                // centro) permitiria asociar un sensor de otra bomba.
                function ($attribute, $value, $fail) use ($request): void {
                    if ($value === null || $request->input('bombas_id') === null) {
                        return;
                    }

                    $perteneceALaBomba = Sensor::whereKey($value)
                        ->where('bombas_id', $request->input('bombas_id'))
                        ->exists();

                    if (! $perteneceALaBomba) {
                        $fail('El sensor seleccionado no pertenece a la bomba indicada.');
                    }
                },
            ],
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

    public function show(Request $request, Alerta $alerta): View
    {
        $this->verificarCentro($request, $alerta);

        $alerta->load('bomba', 'sensor', 'usuarioAtencion');

        $estadoTiempoAtencion = $this->reporteService->evaluarTiempoAtencion($alerta);
        $etiquetaTiempoAtencion = $this->reporteService->etiquetaTiempoAtencion($estadoTiempoAtencion);

        return view('alertas.show', compact('alerta', 'estadoTiempoAtencion', 'etiquetaTiempoAtencion'));
    }

    public function atender(Request $request, Alerta $alerta): RedirectResponse
    {
        $this->verificarCentro($request, $alerta);

        // Transicion de estado idempotente: solo la primera atencion registra
        // la fecha y el usuario. El bloqueo de fila evita que dos solicitudes
        // simultaneas sobrescriban la evidencia original de quien atendio.
        $atendidaAhora = DB::transaction(function () use ($request, $alerta): bool {
            $registro = Alerta::whereKey($alerta->getKey())->lockForUpdate()->first();

            if ($registro === null || $registro->atendida) {
                return false;
            }

            $registro->update([
                'atendida' => true,
                'fecha_atencion' => now(),
                'usuario_atencion_id' => $request->user()->id,
            ]);

            return true;
        });

        if (! $atendidaAhora) {
            return redirect()->route('alertas.index')
                ->with('status', 'La alerta ya estaba marcada como solucionada.');
        }

        return redirect()->route('alertas.index')->with('status', 'Alerta marcada como solucionada.');
    }

    /**
     * Verifica que el usuario (si no es administrador) pertenezca al mismo
     * centro de salud que la bomba asociada a la alerta.
     */
    private function verificarCentro(Request $request, Alerta $alerta): void
    {
        $usuario = $request->user();

        if ($usuario->esAdministrador()) {
            return;
        }

        abort_if($alerta->bomba?->centros_salud_id !== $usuario->centros_salud_id, 403, 'No tienes permiso para acceder a esta alerta.');
    }
}