<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\EventoBomba;
use App\Models\Lectura;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Services\ConsumoBomba;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private const TIPOS = ['alertas', 'consumo', 'mantenimientos', 'bombas', 'niveles'];

    public function index(Request $request): View|StreamedResponse
    {
        $usuario = $request->user();
        $tipo = in_array($request->input('tipo', 'alertas'), self::TIPOS, true)
            ? $request->input('tipo', 'alertas') : 'alertas';
        $desde = $this->fecha($request->input('desde'), now()->startOfMonth());
        $hasta = $this->fecha($request->input('hasta'), now())->endOfDay();
        abort_if($desde->gt($hasta), 422, 'La fecha inicial no puede ser posterior a la fecha final.');

        $centros = CentroSalud::visiblesPara($usuario)->orderBy('nombre')->get(['id', 'nombre']);
        $centroId = $request->filled('centro_id') ? $request->integer('centro_id') : null;
        if ($centroId && ! $centros->contains('id', $centroId)) abort(403);

        $filas = collect();
        $titulo = ucfirst($tipo);

        if ($tipo === 'alertas') {
            $q = Alerta::visiblesPara($usuario)->with(['bomba.centroSalud', 'sensor', 'usuarioAtencion'])
                ->whereBetween('fecha_hora', [$desde, $hasta]);
            if ($centroId) $q->whereHas('bomba', fn ($b) => $b->where('centros_salud_id', $centroId));
            $filas = $q->orderByDesc('fecha_hora')->get()->map(fn ($a) => [
                'Fecha' => optional($a->fecha_hora)->format('d/m/Y H:i'),
                'Centro' => $a->bomba?->centroSalud?->nombre,
                'Bomba' => $a->bomba?->nombre,
                'Sensor' => $a->sensor?->nombre ?? 'General',
                'Tipo' => $a->tipo,
                'Descripción' => $a->descripcion,
                'Valor detectado' => $a->valor_detectado,
                'Valor permitido' => $a->valor_permitido,
                'Atendida' => $a->atendida ? 'Sí' : 'No',
                'Atendida por' => $a->usuarioAtencion?->nombre,
            ]);
        }

        if ($tipo === 'mantenimientos') {
            $q = Mantenimiento::visiblesPara($usuario)->with(['bomba.centroSalud', 'usuario', 'alerta.sensor'])
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
            if ($centroId) $q->whereHas('bomba', fn ($b) => $b->where('centros_salud_id', $centroId));
            $filas = $q->orderByDesc('fecha')->get()->map(fn ($m) => [
                'Fecha' => optional($m->fecha)->format('d/m/Y'),
                'Centro' => $m->bomba?->centroSalud?->nombre,
                'Bomba' => $m->bomba?->nombre,
                'Sensor relacionado' => $m->alerta?->sensor?->nombre ?? 'No especificado',
                'Tipo' => $m->tipo,
                'Descripción' => $m->descripcion,
                'Observaciones' => $m->observaciones,
                'Técnico' => $m->usuario?->nombre,
                'Próximo mantenimiento' => optional($m->proximo_mantenimiento)->format('d/m/Y'),
            ]);
        }

        if ($tipo === 'bombas') {
            $q = Bomba::visiblesPara($usuario)->with('centroSalud')
                ->withCount(['eventos as arranques_periodo' => fn ($e) => $e->where('encendido', true)->whereBetween('fecha_hora', [$desde, $hasta])]);
            if ($centroId) $q->where('centros_salud_id', $centroId);
            $filas = $q->orderBy('nombre')->get()->map(fn ($b) => [
                'Centro' => $b->centroSalud?->nombre,
                'Código' => $b->codigo,
                'Bomba' => $b->nombre,
                'Estado equipo' => $b->estado,
                'Orden encendido' => $b->encendido ? 'Sí' : 'No',
                'Funcionamiento real' => $b->funcionando ? 'Funcionando' : 'Detenida',
                'Modo' => $b->modo_operacion ? 'Automático' : 'Manual',
                'Arranques en periodo' => $b->arranques_periodo,
            ]);
        }

        if ($tipo === 'niveles') {
            $q = Lectura::query()->with('sensor.bomba.centroSalud')
                ->whereBetween('fecha_hora', [$desde, $hasta])
                ->whereHas('sensor', fn ($s) => $s->where('tipo', 'nivel')->whereHas('bomba', fn ($b) => $b->visiblesPara($usuario)));
            if ($centroId) $q->whereHas('sensor.bomba', fn ($b) => $b->where('centros_salud_id', $centroId));
            $filas = $q->orderByDesc('fecha_hora')->limit(5000)->get()->map(fn ($l) => [
                'Fecha' => optional($l->fecha_hora)->format('d/m/Y H:i:s'),
                'Centro' => $l->sensor?->bomba?->centroSalud?->nombre,
                'Bomba' => $l->sensor?->bomba?->nombre,
                'Sensor' => $l->sensor?->nombre,
                'Lectura' => $l->valor_medido,
                'Unidad' => $l->sensor?->unidad_medida,
            ]);
        }

        if ($tipo === 'consumo') {
            $q = Bomba::visiblesPara($usuario)->with('centroSalud');
            if ($centroId) $q->where('centros_salud_id', $centroId);
            $filas = $q->get()->map(function ($b) use ($desde, $hasta) {
                $resumen = (new ConsumoBomba($b))->periodo($desde, $hasta);
                return [
                    'Centro' => $b->centroSalud?->nombre,
                    'Bomba' => $b->nombre,
                    'Capacidad tanque (L)' => $b->tanque_capacidad_litros,
                    'Consumo estimado (L)' => $resumen['total']['litros'] ?? null,
                    'Tiempo de funcionamiento (min)' => isset($resumen['total']['segundos'])
                        ? round($resumen['total']['segundos'] / 60, 1) : null,
                    'Periodo' => $desde->format('d/m/Y').' - '.$hasta->format('d/m/Y'),
                    'Método' => 'Estimación por ciclos y tiempo de funcionamiento; no es caudalímetro',
                ];
            });
        }

        if ($request->query('exportar') === 'csv') {
            return $this->csv($filas, 'reporte_'.$tipo.'_'.$desde->format('Ymd').'.csv');
        }

        return view('reportes.index', compact('tipo', 'titulo', 'desde', 'hasta', 'centros', 'centroId', 'filas'));
    }

    private function fecha(?string $valor, Carbon $porDefecto): Carbon
    {
        if (! $valor) return $porDefecto->copy();
        try { return Carbon::createFromFormat('Y-m-d', $valor)->startOfDay(); }
        catch (\Throwable) { abort(422, 'Fecha no válida.'); }
    }

    private function csv($filas, string $nombre): StreamedResponse
    {
        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            // BOM para que Excel reconozca correctamente tildes y ñ.
            fwrite($salida, "\xEF\xBB\xBF");
            if ($filas->isNotEmpty()) {
                fputcsv($salida, array_keys($filas->first()), ';');
                foreach ($filas as $fila) fputcsv($salida, array_values($fila), ';');
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
