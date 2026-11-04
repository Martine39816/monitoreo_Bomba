<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Bomba;
use App\Services\ConsumoBomba;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConsumoApiController extends Controller
{
    /**
     * Devuelve el consumo estimado de una bomba para Android.
     *
     * Periodos disponibles: hoy, semana, mes y rango.
     * El cálculo se realiza exclusivamente mediante ConsumoBomba.
     */
    public function show(Request $request, Bomba $bomba)
    {
        $this->authorize('view', $bomba);

        $validated = $request->validate([
            'periodo' => ['sometimes', 'in:hoy,semana,mes,rango'],
            'desde' => ['required_if:periodo,rango', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['required_if:periodo,rango', 'nullable', 'date_format:Y-m-d'],
        ]);

        $periodo = $validated['periodo'] ?? 'hoy';
        $hoy = now()->startOfDay();

        switch ($periodo) {
            case 'semana':
                $desde = $hoy->copy()->subDays(6);
                $hasta = $hoy->copy();
                break;

            case 'mes':
                $desde = $hoy->copy()->subDays(29);
                $hasta = $hoy->copy();
                break;

            case 'rango':
                $desde = Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['desde']
                )->startOfDay();

                $hasta = Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['hasta']
                )->startOfDay();

                if ($desde->greaterThan($hasta)) {
                    return response()->json([
                        'mensaje' => 'La fecha inicial no puede ser posterior a la fecha final.',
                    ], 422);
                }

                if ($hasta->greaterThan($hoy)) {
                    return response()->json([
                        'mensaje' => 'La fecha final no puede ser posterior a hoy.',
                    ], 422);
                }

                if ($desde->lessThan($hasta->copy()->subDays(91))) {
                    return response()->json([
                        'mensaje' => 'El rango máximo permitido es de 92 días.',
                    ], 422);
                }

                break;

            default:
                $desde = $hoy->copy();
                $hasta = $hoy->copy();
                break;
        }

        $consumo = new ConsumoBomba($bomba);

        $tiempo = $consumo->tiempoLlenado();
        $caudal = $consumo->caudalLpm();
        $litrosPorCiclo = $consumo->litrosPorCiclo();

        return response()->json([
            'bomba' => [
                'id' => $bomba->id,
                'codigo' => $bomba->codigo,
                'nombre' => $bomba->nombre,
                'funcionando' => $bomba->funcionando,
                'centros_salud_id' => $bomba->centros_salud_id,
            ],

            'periodo' => [
                'tipo' => $periodo,
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ],

            'calculo' => [
                'tipo' => 'estimado',
                'unidad' => 'litros',
                'litros_por_ciclo' => $litrosPorCiclo !== null
                    ? round($litrosPorCiclo, 2)
                    : null,
                'caudal_litros_minuto' => $caudal !== null
                    ? round($caudal, 2)
                    : null,
                'tiempo_llenado_minutos' => $tiempo['minutos'],
                'origen_tiempo_llenado' => $tiempo['origen'],
                'ciclos_para_calibracion' => $tiempo['ciclos'],
                'disponible' => $caudal !== null,
                'mensaje' => $caudal === null
                    ? 'No hay datos suficientes para estimar los litros. Revisa la capacidad del tanque y el tiempo de llenado.'
                    : 'Los litros son una estimación basada en el tiempo de funcionamiento.',
            ],

            'resultado' => $consumo->periodo($desde, $hasta),

            'series' => $consumo->series(),
        ]);
    }
}
