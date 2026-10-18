<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;

/**
 * Compone el informe general: un solo documento con los indicadores y las
 * secciones mas relevantes para el rol del usuario, sobre el periodo que este
 * elija (dia, dias, semanas o meses).
 *
 * No aplica filtros ni aislamiento por centro: eso llega ya resuelto en el
 * contexto. Las tablas de detalle se delegan en ReporteExportService para que
 * la pantalla, el PDF y el Excel no puedan mostrar columnas distintas.
 */
class InformeGeneralService
{
    public const CLAVE_RESUMEN = 'resumen';
    public const CLAVE_CENTROS = 'centros';

    public function __construct(
        private readonly ReporteService $reportes,
        private readonly ReporteExportService $exportador,
    ) {
    }

    public function titulo(): string
    {
        return 'Informe general';
    }

    /**
     * Secciones del informe, en el orden en que deben mostrarse, segun el rol.
     *
     * - Administrador: resumen global, comparativo entre centros y todos los detalles.
     * - Tecnico: resumen operativo (bombas, lecturas y alertas activas).
     * - Director: indicadores gerenciales (cumplimiento, disponibilidad y mantenimientos).
     *
     * @param  array<string, mixed>  $contexto
     * @return array<int, array{clave: string, titulo: string, encabezados: array<int, string>, filas: array<int, array<int, mixed>>}>
     */
    public function secciones(array $contexto, User $usuario): array
    {
        $datos = $this->datos($contexto);

        return array_map(
            fn (string $clave) => $this->seccion($clave, $datos, $contexto, $usuario),
            $this->clavesPorRol($usuario)
        );
    }

    /**
     * @return array<int, string>
     */
    private function clavesPorRol(User $usuario): array
    {
        return match (true) {
            $usuario->esAdministrador() => [
                self::CLAVE_RESUMEN, self::CLAVE_CENTROS, 'bombas', 'lecturas', 'alertas', 'mantenimientos',
            ],
            $usuario->esDirector() => [
                self::CLAVE_RESUMEN, 'bombas', 'alertas', 'mantenimientos',
            ],
            default => [
                self::CLAVE_RESUMEN, 'bombas', 'lecturas', 'alertas',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $contexto
     * @return array{clave: string, titulo: string, encabezados: array<int, string>, filas: array<int, array<int, mixed>>}
     */
    private function seccion(string $clave, array $datos, array $contexto, User $usuario): array
    {
        return match ($clave) {
            self::CLAVE_RESUMEN => [
                'clave' => self::CLAVE_RESUMEN,
                'titulo' => 'Resumen del periodo',
                'encabezados' => ['Indicador', 'Valor', 'Detalle'],
                'filas' => $this->indicadores($datos, $contexto, $usuario),
            ],
            self::CLAVE_CENTROS => [
                'clave' => self::CLAVE_CENTROS,
                'titulo' => 'Comparativo entre centros',
                'encabezados' => [
                    'Centro de salud', 'Bombas', 'Bombas activas', 'Fuera de servicio',
                    'Alertas del periodo', 'Alertas pendientes', 'Lecturas',
                    'Lecturas fuera de rango', 'Mantenimientos', 'Indice de salud (estimado)',
                ],
                'filas' => $this->comparativo($datos, $contexto),
            ],
            'bombas' => ['clave' => 'bombas', 'titulo' => $this->exportador->titulo('bombas')]
                + $this->exportador->tablaBombas($contexto),
            'lecturas' => ['clave' => 'lecturas', 'titulo' => $this->exportador->titulo('lecturas')]
                + $this->exportador->tablaLecturas($contexto),
            'alertas' => ['clave' => 'alertas', 'titulo' => $this->exportador->titulo('alertas')]
                + $this->exportador->tablaAlertas($contexto),
            'mantenimientos' => ['clave' => 'mantenimientos', 'titulo' => $this->exportador->titulo('mantenimientos')]
                + $this->exportador->tablaMantenimientos($contexto),
        };
    }

    /**
     * Datos de base del periodo, resueltos una sola vez y compartidos por
     * todas las secciones (evita repetir las mismas consultas).
     *
     * @param  array<string, mixed>  $contexto
     * @return array{bombas: \Illuminate\Database\Eloquent\Collection<int, Bomba>, sensores: \Illuminate\Database\Eloquent\Collection<int, Sensor>, alertas: \Illuminate\Database\Eloquent\Collection<int, Alerta>, mantenimientos: \Illuminate\Database\Eloquent\Collection<int, Mantenimiento>}
     */
    private function datos(array $contexto): array
    {
        $centro = $contexto['centro'];
        $incluirDemo = $contexto['incluirDemo'];
        $desde = $contexto['rango']['desde'];
        $hasta = $contexto['rango']['hasta'];

        return [
            'bombas' => $this->reportes->estadoOperativoBombasCompleto($centro, $incluirDemo),
            'sensores' => $this->reportes->resumenLecturas($centro, $desde, $hasta, $incluirDemo),
            'alertas' => $this->reportes->alertasReporte($centro, $desde, $hasta, $incluirDemo),
            'mantenimientos' => $this->reportes->mantenimientosReporte($centro, $desde, $hasta, $incluirDemo),
        ];
    }

    /**
     * Indicadores clave del periodo. Cada rol ve el subconjunto que le es util.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $contexto
     * @return array<int, array<int, string>>
     */
    private function indicadores(array $datos, array $contexto, User $usuario): array
    {
        $m = $this->metricas($datos);

        $todos = [
            'centros' => [
                'Centros de salud en el alcance',
                (string) $contexto['centros']->count(),
                'Centros registrados a los que su rol tiene acceso.',
            ],
            'bombas_total' => [
                'Bombas registradas',
                (string) $m['total_bombas'],
                'Inventario de bombas del alcance seleccionado.',
            ],
            'bombas_activas' => [
                'Bombas activas',
                (string) $m['bombas_activas'],
                'Bombas con estado registrado «activo».',
            ],
            'bombas_fuera' => [
                'Bombas fuera de servicio',
                (string) $m['bombas_fuera_servicio'],
                'Bombas con estado «fuera de servicio» o «desconectado».',
            ],
            'disponibilidad' => [
                'Disponibilidad de bombas (estimada)',
                $this->porcentaje($m['disponibilidad']),
                'Proporcion de bombas activas sobre el total del alcance.',
            ],
            'alertas_periodo' => [
                'Alertas del periodo',
                (string) $m['alertas_periodo'],
                'Alertas generadas dentro del periodo seleccionado.',
            ],
            'alertas_pendientes' => [
                'Alertas pendientes',
                (string) $m['alertas_pendientes'],
                'Alertas del periodo que aun no fueron atendidas.',
            ],
            'alertas_atendidas' => [
                'Alertas atendidas',
                (string) $m['alertas_atendidas'],
                'Alertas del periodo ya marcadas como atendidas.',
            ],
            'cumplimiento' => [
                'Cumplimiento del umbral de 24 h',
                $this->porcentaje($m['cumplimiento_umbral']),
                'Atendidas dentro de 24 h sobre las atendidas con tiempo determinable ('
                    .$m['alertas_dentro_umbral'].' de '.$m['alertas_evaluadas'].').',
            ],
            'lecturas_periodo' => [
                'Lecturas registradas',
                (string) $m['lecturas_periodo'],
                'Lecturas almacenadas por los sensores del alcance en el periodo.',
            ],
            'lecturas_fuera' => [
                'Lecturas fuera de rango',
                (string) $m['lecturas_fuera_rango'],
                'Lecturas por debajo del minimo o por encima del maximo configurado.',
            ],
            'sensores_sin_lecturas' => [
                'Sensores sin lecturas',
                (string) $m['sensores_sin_lecturas'],
                'Sensores del alcance ('.$m['sensores_total'].') sin ninguna lectura en el periodo.',
            ],
            'mantenimientos_periodo' => [
                'Mantenimientos del periodo',
                (string) $m['mantenimientos_periodo'],
                'Mantenimientos realizados dentro del periodo seleccionado.',
            ],
            'mantenimientos_preventivos' => [
                'Mantenimientos preventivos',
                (string) $m['mantenimientos_preventivos'],
                'Mantenimientos preventivos sobre el total del periodo.',
            ],
            'mantenimientos_correctivos' => [
                'Mantenimientos correctivos',
                (string) $m['mantenimientos_correctivos'],
                'Mantenimientos correctivos por falla o reparacion.',
            ],
            'mantenimientos_vencidos' => [
                'Mantenimientos programados vencidos',
                (string) $m['mantenimientos_vencidos'],
                'Mantenimientos del periodo con proxima fecha programada ya vencida.',
            ],
        ];

        $claves = match (true) {
            $usuario->esAdministrador() => [
                'centros', 'bombas_total', 'bombas_activas', 'bombas_fuera', 'alertas_periodo',
                'alertas_pendientes', 'cumplimiento', 'lecturas_periodo', 'lecturas_fuera',
                'mantenimientos_periodo', 'mantenimientos_preventivos', 'mantenimientos_correctivos',
            ],
            $usuario->esDirector() => [
                'cumplimiento', 'alertas_atendidas', 'alertas_pendientes', 'disponibilidad',
                'mantenimientos_periodo', 'mantenimientos_preventivos', 'mantenimientos_correctivos',
                'mantenimientos_vencidos',
            ],
            default => [
                'bombas_activas', 'bombas_fuera', 'sensores_sin_lecturas', 'lecturas_fuera',
                'alertas_periodo', 'alertas_pendientes',
            ],
        };

        return array_map(fn (string $clave) => $todos[$clave], $claves);
    }

    /**
     * Cifras del periodo, calculadas una sola vez a partir de los datos base.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, int|float|null>
     */
    private function metricas(array $datos): array
    {
        $bombas = $datos['bombas'];
        $sensores = $datos['sensores'];
        $alertas = $datos['alertas'];
        $mantenimientos = $datos['mantenimientos'];

        $totalBombas = $bombas->count();
        $activas = $bombas->where('estado', 'activo')->count();
        $alertasPendientes = $alertas->where('atendida', false)->count();

        // Solo se evalua el cumplimiento del umbral sobre las alertas atendidas
        // cuyo tiempo es determinable: incluir las indeterminables inflaria o
        // reduciria el porcentaje sin base real.
        $evaluadas = $alertas->filter(fn (Alerta $alerta) => in_array(
            $this->reportes->evaluarTiempoAtencion($alerta),
            [ReporteService::ATENCION_ATENDIDA_DENTRO, ReporteService::ATENCION_ATENDIDA_FUERA],
            true
        ));
        $dentroDelUmbral = $evaluadas->filter(
            fn (Alerta $alerta) => $this->reportes->evaluarTiempoAtencion($alerta) === ReporteService::ATENCION_ATENDIDA_DENTRO
        )->count();

        $cifrasMantenimiento = $this->reportes->metricasMantenimientos($mantenimientos);

        return [
            'total_bombas' => $totalBombas,
            'bombas_activas' => $activas,
            'bombas_fuera_servicio' => $bombas->whereIn('estado', ['fuera_servicio', 'desconectado'])->count(),
            'disponibilidad' => $totalBombas > 0 ? round($activas * 100 / $totalBombas, 1) : null,
            'alertas_periodo' => $alertas->count(),
            'alertas_pendientes' => $alertasPendientes,
            'alertas_atendidas' => $alertas->count() - $alertasPendientes,
            'alertas_evaluadas' => $evaluadas->count(),
            'alertas_dentro_umbral' => $dentroDelUmbral,
            'cumplimiento_umbral' => $evaluadas->count() > 0
                ? round($dentroDelUmbral * 100 / $evaluadas->count(), 1)
                : null,
            'lecturas_periodo' => (int) $sensores->sum('lecturas_count'),
            'lecturas_fuera_rango' => (int) $sensores->sum('lecturas_fuera_rango'),
            'sensores_total' => $sensores->count(),
            'sensores_sin_lecturas' => $sensores->filter(fn (Sensor $sensor) => (int) $sensor->lecturas_count === 0)->count(),
            // Las cifras de mantenimiento se delegan en ReporteService para que
            // el informe general y el reporte de mantenimientos coincidan.
            'mantenimientos_periodo' => $cifrasMantenimiento['total'],
            'mantenimientos_preventivos' => $cifrasMantenimiento['por_tipo']['preventivo'],
            'mantenimientos_correctivos' => $cifrasMantenimiento['por_tipo']['correctivo'],
            'mantenimientos_vencidos' => $cifrasMantenimiento['vencidos'],
        ];
    }

    /**
     * Comparativo por centro de salud. Se apoya en la lista de centros del
     * alcance para que un centro sin datos aparezca con ceros en lugar de
     * desaparecer del informe.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $contexto
     * @return array<int, array<int, mixed>>
     */
    private function comparativo(array $datos, array $contexto): array
    {
        $centros = $contexto['centro'] ? collect([$contexto['centro']]) : $contexto['centros'];

        return $centros->sortBy('nombre')->map(function ($centro) use ($datos) {
            $codigo = $centro->codigo;

            $bombas = $datos['bombas']->filter(fn (Bomba $bomba) => $bomba->centroSalud?->codigo === $codigo);
            $sensores = $datos['sensores']->filter(fn (Sensor $sensor) => $sensor->bomba?->centroSalud?->codigo === $codigo);
            $alertas = $datos['alertas']->filter(fn (Alerta $alerta) => $alerta->bomba?->centroSalud?->codigo === $codigo);
            $mantenimientos = $datos['mantenimientos']->filter(
                fn (Mantenimiento $mantenimiento) => $mantenimiento->bomba?->centroSalud?->codigo === $codigo
            );

            $salud = $bombas->pluck('porcentaje_salud')->filter(fn ($valor) => $valor !== null);
            $saludPromedio = $salud->isEmpty() ? null : round((float) $salud->avg(), 1);

            return [
                $centro->nombre.' ('.$centro->codigo.')',
                (string) $bombas->count(),
                (string) $bombas->where('estado', 'activo')->count(),
                (string) $bombas->whereIn('estado', ['fuera_servicio', 'desconectado'])->count(),
                (string) $alertas->count(),
                (string) $alertas->where('atendida', false)->count(),
                (string) (int) $sensores->sum('lecturas_count'),
                (string) (int) $sensores->sum('lecturas_fuera_rango'),
                (string) $mantenimientos->count(),
                $this->exportador->flotante($saludPromedio),
            ];
        })->values()->all();
    }

    /**
     * Porcentaje legible ("83,3 %") o "Sin datos" cuando no hay base de calculo.
     */
    private function porcentaje(?float $valor): string
    {
        if ($valor === null) {
            return 'Sin datos';
        }

        return rtrim(rtrim(number_format($valor, 1, ',', '.'), '0'), ',').' %';
    }
}
