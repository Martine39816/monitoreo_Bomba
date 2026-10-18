<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\Lectura;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;
use App\Models\VistaSaludBomba;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Punto unico de verdad para los filtros y el aislamiento por centro de salud
 * del modulo de reportes. El controlador y las vistas no deben volver a
 * implementar estas reglas: asi se evita que una exportacion muestre datos
 * distintos a los de la pantalla.
 */
class ReporteService
{
    /**
     * Umbral operativo (en horas) para considerar que una alerta fue atendida
     * a tiempo. Es un criterio interno de este proyecto y no una norma del
     * sector salud. Se centraliza aqui para que la vista y la exportacion
     * usen siempre el mismo valor.
     */
    public const UMBRAL_ATENCION_HORAS = 24;

    /**
     * Resultados posibles de evaluar el tiempo de atencion de una alerta
     * frente al umbral interno. Son independientes del estado pendiente o
     * atendida que la alerta conserve.
     */
    public const ATENCION_PENDIENTE_DENTRO = 'pendiente_dentro';
    public const ATENCION_PENDIENTE_FUERA = 'pendiente_fuera';
    public const ATENCION_ATENDIDA_DENTRO = 'atendida_dentro';
    public const ATENCION_ATENDIDA_FUERA = 'atendida_fuera';
    public const ATENCION_INDETERMINABLE = 'indeterminable';

    /**
     * Maximo de dias que puede abarcar el rango de fechas de un reporte.
     */
    public const MAX_DIAS_RANGO = 366;

    /**
     * Unidades admitidas para elegir el periodo del informe general. El
     * usuario indica una cantidad y una unidad (por ejemplo, 2 semanas).
     */
    public const UNIDADES_PERIODO = ['dias', 'semanas', 'meses'];

    /**
     * Cantidad maxima admitida por unidad. Se limita de modo que el periodo
     * nunca supere MAX_DIAS_RANGO dias.
     */
    public const MAXIMO_PERIODO = ['dias' => 366, 'semanas' => 52, 'meses' => 12];

    /**
     * Prefijo verificado de los centros de salud de demostracion
     * (DEMO-CS-01 a DEMO-CS-04). El unico centro real, CS-001, no lo lleva.
     */
    public const PREFIJO_DEMO = 'DEMO-';

    /**
     * Centros de salud que el usuario autenticado puede consultar.
     * El administrador ve todos; el tecnico y el director solo el suyo.
     */
    public function centrosDisponibles(User $usuario): Collection
    {
        $query = CentroSalud::query()->orderBy('nombre');

        if (! $usuario->esAdministrador()) {
            $query->where('id', $usuario->centros_salud_id);
        }

        return $query->get();
    }

    /**
     * Centro efectivo del reporte. Para tecnico y director el alcance se fuerza
     * en el servidor: si solicitan otro centro se responde 403, sin importar lo
     * que envie el navegador.
     */
    public function resolverCentro(User $usuario, ?int $centroId): ?CentroSalud
    {
        if ($usuario->esAdministrador()) {
            return $centroId ? CentroSalud::find($centroId) : null;
        }

        abort_if(
            $centroId !== null && (int) $centroId !== (int) $usuario->centros_salud_id,
            403,
            'No tienes permiso para consultar informacion de otro centro de salud.'
        );

        return $usuario->centroSalud;
    }

    /**
     * Indica si el rango consultado incluye los datos de demostracion.
     * Por defecto se incluyen, para no ocultar informacion al usuario.
     */
    public function incluirDemo(array $filtros): bool
    {
        return ($filtros['demo'] ?? 'incluir') !== 'excluir';
    }

    /**
     * Rango de fechas efectivo del reporte (por defecto, los ultimos 30 dias).
     * Un rango mayor al maximo permitido se recorta y se informa al usuario.
     *
     * @return array{desde: Carbon, hasta: Carbon, recortado: bool}
     */
    public function resolverRangoFechas(array $filtros): array
    {
        $hasta = isset($filtros['fecha_hasta'])
            ? Carbon::parse($filtros['fecha_hasta'])->startOfDay()
            : Carbon::today();

        $desde = isset($filtros['fecha_desde'])
            ? Carbon::parse($filtros['fecha_desde'])->startOfDay()
            : $hasta->copy()->subDays(29);

        $recortado = false;

        if ($desde->diffInDays($hasta) > self::MAX_DIAS_RANGO) {
            $desde = $hasta->copy()->subDays(self::MAX_DIAS_RANGO);
            $recortado = true;
        }

        return ['desde' => $desde, 'hasta' => $hasta, 'recortado' => $recortado];
    }

    /**
     * Periodo del informe general elegido por el usuario como cantidad + unidad
     * (por ejemplo, 2 semanas o 3 meses). Se cuenta hacia atras desde hoy,
     * incluyendo el dia en curso.
     *
     * Devuelve la misma forma que resolverRangoFechas() (desde, hasta,
     * recortado) para que las vistas y la exportacion puedan tratarlos igual,
     * mas la cantidad, la unidad y la etiqueta legible del periodo.
     *
     * @return array{desde: Carbon, hasta: Carbon, recortado: bool, cantidad: int, unidad: string, etiqueta: string}
     */
    public function resolverPeriodo(array $filtros): array
    {
        $unidad = in_array($filtros['periodo_unidad'] ?? null, self::UNIDADES_PERIODO, true)
            ? $filtros['periodo_unidad']
            : 'dias';

        $cantidad = (int) ($filtros['periodo_cantidad'] ?? 7);

        if ($cantidad < 1) {
            $cantidad = 1;
        }

        // Un periodo mayor al maximo permitido se recorta en silencio hasta el
        // limite y se informa al usuario, en lugar de rechazar la consulta.
        $maximo = self::MAXIMO_PERIODO[$unidad];
        $recortado = $cantidad > $maximo;

        if ($recortado) {
            $cantidad = $maximo;
        }

        $hasta = Carbon::today();

        $desde = match ($unidad) {
            'semanas' => $hasta->copy()->subWeeks($cantidad)->addDay(),
            'meses' => $hasta->copy()->subMonthsNoOverflow($cantidad)->addDay(),
            default => $hasta->copy()->subDays($cantidad - 1),
        };

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'recortado' => $recortado,
            'cantidad' => $cantidad,
            'unidad' => $unidad,
            'etiqueta' => $this->etiquetaPeriodo($cantidad, $unidad),
        ];
    }

    /**
     * Descripcion legible del periodo elegido ("Ultimos 7 dias", "Ultima
     * semana", "Ultimos 3 meses"), usada en la pantalla y en la exportacion.
     */
    public function etiquetaPeriodo(int $cantidad, string $unidad): string
    {
        return match (true) {
            $unidad === 'semanas' && $cantidad === 1 => 'Última semana',
            $unidad === 'semanas' => "Últimas {$cantidad} semanas",
            $unidad === 'meses' && $cantidad === 1 => 'Último mes',
            $unidad === 'meses' => "Últimos {$cantidad} meses",
            $cantidad === 1 => 'Último día',
            default => "Últimos {$cantidad} días",
        };
    }

    /**
     * Catalogo de reportes que el rol del usuario puede consultar.
     * El comparativo entre centros solo se ofrece al administrador.
     *
     * @return array<int, array{clave: string, nombre: string, descripcion: string, fase: string, disponible: bool}>
     */
    public function catalogoReportes(User $usuario): array
    {
        $reportes = [
            [
                'clave' => 'informe',
                'nombre' => 'Informe general',
                'descripcion' => 'Un solo informe con los indicadores y bloques mas importantes para su rol, sobre el periodo que usted elija (dia, dias, semanas o meses).',
                'fase' => 'Informe general',
                'disponible' => true,
            ],
            [
                'clave' => 'bombas',
                'nombre' => 'Estado operativo de bombas',
                'descripcion' => 'Estado registrado, encendido/apagado, modo de operacion, ultima lectura, alertas pendientes e indice estimado de salud.',
                'fase' => 'Fase 2',
                'disponible' => true,
            ],
            [
                'clave' => 'lecturas',
                'nombre' => 'Lecturas y calidad de datos',
                'descripcion' => 'Cantidad, minimos, maximos, promedios, cobertura y cumplimiento de umbrales por sensor.',
                'fase' => 'Fase 3',
                'disponible' => true,
            ],
            [
                'clave' => 'alertas',
                'nombre' => 'Alertas',
                'descripcion' => 'Pendientes y atendidas, distribucion por tipo, tiempos de atencion y cumplimiento del umbral de 24 horas.',
                'fase' => 'Fase 4',
                'disponible' => true,
            ],
            [
                'clave' => 'mantenimientos',
                'nombre' => 'Mantenimientos',
                'descripcion' => 'Historial por tipo, proximos mantenimientos y fechas programadas vencidas.',
                'fase' => 'Fase 5',
                'disponible' => true,
            ],
        ];

        if ($usuario->esAdministrador()) {
            $reportes[] = [
                'clave' => 'comparativo',
                'nombre' => 'Comparativo entre centros',
                'descripcion' => 'Indicadores normalizados por centro de salud. Reservado para administradores.',
                'fase' => 'Fase 6',
                'disponible' => false,
            ];
        }

        return $reportes;
    }

    /**
     * Indica si el centro de salud es un centro de demostracion, segun el
     * criterio verificado: el codigo empieza con el prefijo DEMO-.
     */
    public function esCentroDemo(?CentroSalud $centro): bool
    {
        return $centro !== null && str_starts_with($centro->codigo, self::PREFIJO_DEMO);
    }

    /**
     * Excluye de la consulta los registros que pertenecen a centros de
     * demostracion cuando el usuario pide no verlos. No elimina ni modifica
     * datos: solo deja de listarlos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    public function aplicarFiltroDemo($query, bool $incluirDemo, string $relacionCentro = 'centroSalud')
    {
        if ($incluirDemo) {
            return $query;
        }

        return $query->whereHas($relacionCentro, function ($consulta) {
            $consulta->where('codigo', 'not like', self::PREFIJO_DEMO.'%');
        });
    }

    /**
     * Reporte del estado operativo de las bombas: una fila por bomba, con su
     * estado registrado, la ultima lectura disponible, las alertas pendientes,
     * el proximo mantenimiento y el indice estimado de salud.
     *
     * La consulta parte siempre de la tabla `bombas` (no de
     * `vista_salud_bomba`, cuyo JOIN interno ocultaria las bombas sin lecturas),
     * de modo que las bombas sin datos aparecen igualmente y se marcan como
     * "Sin datos disponibles" en la vista.
     */
    public function estadoOperativoBombas(?CentroSalud $centro, bool $incluirDemo): LengthAwarePaginator
    {
        return $this->consultaEstadoOperativoBombas($centro, $incluirDemo)
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Version sin paginar del reporte de bombas, para la exportacion. Usa la
     * misma consulta que la pantalla para que el archivo descargado no pueda
     * mostrar datos distintos a los del informe.
     */
    public function estadoOperativoBombasCompleto(?CentroSalud $centro, bool $incluirDemo): Collection
    {
        return $this->consultaEstadoOperativoBombas($centro, $incluirDemo)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Bomba>
     */
    private function consultaEstadoOperativoBombas(?CentroSalud $centro, bool $incluirDemo)
    {
        $query = Bomba::query()
            ->with('centroSalud:id,nombre,codigo')
            ->withCount([
                'alertas as alertas_pendientes_count' => fn ($consulta) => $consulta->where('atendida', false),
            ])
            ->addSelect([
                'ultima_lectura' => Lectura::query()
                    ->selectRaw('MAX(lecturas.fecha_hora)')
                    ->join('sensores', 'sensores.id', '=', 'lecturas.sensores_id')
                    ->whereColumn('sensores.bombas_id', 'bombas.id'),
                'porcentaje_salud' => VistaSaludBomba::query()
                    ->select('porcentaje_salud_actual')
                    ->whereColumn('vista_salud_bomba.bomba_id', 'bombas.id')
                    ->limit(1),
                'proximo_mantenimiento' => Mantenimiento::query()
                    ->selectRaw('MIN(mantenimientos.proximo_mantenimiento)')
                    ->whereColumn('mantenimientos.bombas_id', 'bombas.id')
                    ->whereNotNull('mantenimientos.proximo_mantenimiento')
                    ->where('mantenimientos.proximo_mantenimiento', '>=', Carbon::today()->toDateString()),
            ])
            ->orderBy('centros_salud_id')
            ->orderBy('nombre');

        if ($centro) {
            $query->where('centros_salud_id', $centro->id);
        }

        $this->aplicarFiltroDemo($query, $incluirDemo);

        return $query;
    }

    /**
     * Reporte de lecturas y calidad de datos: una fila por sensor del alcance,
     * con la cantidad de lecturas del periodo, minimo, maximo, promedio y el
     * numero de lecturas fuera de los umbrales configurados del sensor.
     *
     * Se parte de `sensores` (no de `lecturas`) para que los sensores sin
     * lecturas en el periodo aparezcan con conteo 0 y evidencien la falta de
     * cobertura, en lugar de desaparecer del informe.
     */
    public function resumenLecturas(?CentroSalud $centro, Carbon $desde, Carbon $hasta, bool $incluirDemo): Collection
    {
        // El rango se resuelve por dias completos: la fecha final debe incluir
        // los registros de todo ese dia, aunque el periodo se muestre como una
        // sola fecha. Sin esto, las lecturas de hoy quedarian fuera del informe.
        $fin = $hasta->copy()->endOfDay();

        $query = Sensor::query()
            ->with('bomba.centroSalud:id,nombre,codigo')
            ->select('sensores.*')
            ->addSelect([
                'lecturas_count' => Lectura::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('lecturas.sensores_id', 'sensores.id')
                    ->whereBetween('lecturas.fecha_hora', [$desde, $fin]),
                'valor_minimo_medido' => Lectura::query()
                    ->selectRaw('MIN(lecturas.valor_medido)')
                    ->whereColumn('lecturas.sensores_id', 'sensores.id')
                    ->whereBetween('lecturas.fecha_hora', [$desde, $fin]),
                'valor_maximo_medido' => Lectura::query()
                    ->selectRaw('MAX(lecturas.valor_medido)')
                    ->whereColumn('lecturas.sensores_id', 'sensores.id')
                    ->whereBetween('lecturas.fecha_hora', [$desde, $fin]),
                'valor_promedio' => Lectura::query()
                    ->selectRaw('AVG(lecturas.valor_medido)')
                    ->whereColumn('lecturas.sensores_id', 'sensores.id')
                    ->whereBetween('lecturas.fecha_hora', [$desde, $fin]),
                // Cuenta las lecturas que caen fuera de los umbrales del sensor.
                // Si el sensor no tiene umbrales configurados, no cuenta ninguna
                // (mismo criterio que la generacion de alertas).
                'lecturas_fuera_rango' => Lectura::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('lecturas.sensores_id', 'sensores.id')
                    ->whereBetween('lecturas.fecha_hora', [$desde, $fin])
                    ->whereRaw(
                        '(sensores.valor_minimo IS NOT NULL AND lecturas.valor_medido < sensores.valor_minimo)'
                        .' OR (sensores.valor_maximo IS NOT NULL AND lecturas.valor_medido > sensores.valor_maximo)'
                    ),
            ]);

        if ($centro) {
            $query->whereHas('bomba', fn ($consulta) => $consulta->where('centros_salud_id', $centro->id));
        }

        $this->aplicarFiltroDemo($query, $incluirDemo, 'bomba.centroSalud');

        return $query->orderBy('sensores.bombas_id')->orderBy('sensores.nombre')->get();
    }

    /**
     * Reporte de alertas del periodo: una fila por alerta, con su tipo, valor
     * detectado, estado y el resultado de comparar su tiempo de atencion con el
     * umbral interno de 24 horas (evaluarTiempoAtencion).
     */
    public function alertasReporte(?CentroSalud $centro, Carbon $desde, Carbon $hasta, bool $incluirDemo): Collection
    {
        // Igual que en lecturas, el periodo cubre dias completos para no dejar
        // fuera las alertas generadas durante la fecha final.
        $fin = $hasta->copy()->endOfDay();

        $query = Alerta::query()
            ->with([
                'bomba.centroSalud:id,nombre,codigo',
                'sensor:id,nombre,codigo',
                'usuarioAtencion',
            ])
            ->whereBetween('fecha_hora', [$desde, $fin])
            ->orderBy('fecha_hora', 'desc');

        if ($centro) {
            $query->whereHas('bomba', fn ($consulta) => $consulta->where('centros_salud_id', $centro->id));
        }

        $this->aplicarFiltroDemo($query, $incluirDemo, 'bomba.centroSalud');

        return $query->get();
    }

    /**
     * Reporte de mantenimientos del periodo: una fila por mantenimiento, con su
     * tipo, la fecha realizada y la proxima fecha programada. El historial se
     * filtra por el rango de fechas sobre la fecha del mantenimiento.
     */
    public function mantenimientosReporte(?CentroSalud $centro, Carbon $desde, Carbon $hasta, bool $incluirDemo): Collection
    {
        $query = Mantenimiento::query()
            ->with(['bomba.centroSalud:id,nombre,codigo', 'usuario'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha', 'desc');

        if ($centro) {
            $query->whereHas('bomba', fn ($consulta) => $consulta->where('centros_salud_id', $centro->id));
        }

        $this->aplicarFiltroDemo($query, $incluirDemo, 'bomba.centroSalud');

        return $query->get();
    }

    /**
     * Cifras del reporte de mantenimientos, calculadas sobre la coleccion que
     * ya resolvio mantenimientosReporte(). Es el unico lugar donde se define
     * que se considera un mantenimiento programado vencido, de modo que la
     * pantalla, la exportacion y el informe general no puedan informar cifras
     * distintas.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Mantenimiento>  $mantenimientos
     * @return array{total: int, por_tipo: array<string, int>, programados: int, vencidos: int, sin_programar: int, intervalo_promedio: float|null}
     */
    public function metricasMantenimientos(Collection $mantenimientos, ?Carbon $hoy = null): array
    {
        $referencia = ($hoy ?? Carbon::today())->copy()->startOfDay();

        $porTipo = [];

        foreach (Mantenimiento::TIPOS as $tipo) {
            $porTipo[$tipo] = $mantenimientos->where('tipo', $tipo)->count();
        }

        // Solo los mantenimientos con proxima fecha programada pueden estar
        // vencidos o pendientes; los que no la tienen se cuentan aparte.
        $conProgramada = $mantenimientos->filter(
            fn (Mantenimiento $mantenimiento) => $mantenimiento->proximo_mantenimiento !== null
        );

        // Tiempo entre mantenimientos: promedio de dias entre las fechas
        // consecutivas del periodo. Se necesitan al menos dos fechas.
        $fechas = $mantenimientos
            ->map(fn (Mantenimiento $mantenimiento) => $mantenimiento->fecha)
            ->filter()
            ->map(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->sort()
            ->values();

        $intervalos = [];

        for ($indice = 1; $indice < $fechas->count(); $indice++) {
            $intervalos[] = ($fechas[$indice] - $fechas[$indice - 1]) / 86400;
        }

        return [
            'total' => $mantenimientos->count(),
            'por_tipo' => $porTipo,
            'programados' => $conProgramada->filter(
                fn (Mantenimiento $mantenimiento) => $mantenimiento->proximo_mantenimiento->gte($referencia)
            )->count(),
            'vencidos' => $conProgramada->filter(
                fn (Mantenimiento $mantenimiento) => $mantenimiento->proximo_mantenimiento->lt($referencia)
            )->count(),
            'sin_programar' => $mantenimientos->count() - $conProgramada->count(),
            'intervalo_promedio' => $intervalos === []
                ? null
                : round(array_sum($intervalos) / count($intervalos), 1),
        ];
    }

    /**
     * Resumen de mantenimientos como filas [etiqueta, valor, detalle], con la
     * misma forma que el resumen del informe general, para que la pantalla, el
     * PDF y el Excel muestren exactamente los mismos textos.
     *
     * @param  array<string, mixed>  $metricas  Resultado de metricasMantenimientos().
     * @return array<int, array<int, string>>
     */
    public function indicadoresMantenimientos(array $metricas): array
    {
        $promedio = $metricas['intervalo_promedio'];

        return [
            [
                'Mantenimientos del periodo',
                (string) $metricas['total'],
                'Mantenimientos realizados dentro del periodo seleccionado.',
            ],
            [
                'Preventivos',
                (string) $metricas['por_tipo']['preventivo'],
                'Mantenimientos preventivos programados y realizados.',
            ],
            [
                'Correctivos',
                (string) $metricas['por_tipo']['correctivo'],
                'Mantenimientos correctivos por falla o reparacion.',
            ],
            [
                'Predictivos',
                (string) $metricas['por_tipo']['predictivo'],
                'Mantenimientos predictivos segun la condicion del equipo.',
            ],
            [
                'Emergencias',
                (string) $metricas['por_tipo']['emergencia'],
                'Intervenciones realizadas por emergencia.',
            ],
            [
                'Programados vencidos',
                (string) $metricas['vencidos'],
                'Proximas fechas programadas anteriores a hoy.',
            ],
            [
                'Proximos programados',
                (string) $metricas['programados'],
                'Proximas fechas programadas iguales o posteriores a hoy.',
            ],
            [
                'Tiempo entre mantenimientos',
                $promedio === null ? 'Sin datos' : number_format($promedio, 1, ',', '.').' días',
                $promedio === null
                    ? 'Se necesitan al menos dos mantenimientos fechados en el periodo.'
                    : 'Promedio de días entre mantenimientos consecutivos del periodo.',
            ],
        ];
    }

    /**
     * Evalua el tiempo de atencion de una alerta frente al umbral interno de
     * 24 horas. Es un criterio interno del proyecto y no una norma del sector
     * salud: solo se informa el resultado de la comparacion, sin afirmar
     * cumplimiento normativo.
     *
     * - Alerta pendiente: se mide desde fecha_hora hasta el momento de
     *   referencia (por defecto, ahora).
     * - Alerta atendida: se mide desde fecha_hora hasta fecha_atencion.
     * - Una duracion de exactamente 24 horas queda dentro del umbral; solo una
     *   duracion superior se considera fuera.
     * - Si falta alguna fecha necesaria, o la final es anterior a la inicial
     *   (dato incoherente), el resultado es "indeterminable" para no producir
     *   conclusiones enganosas.
     *
     * @return string  Uno de los valores ATENCION_* definidos en esta clase.
     */
    public function evaluarTiempoAtencion(Alerta $alerta, ?Carbon $referencia = null): string
    {
        $inicio = $alerta->fecha_hora;

        if ($inicio === null) {
            return self::ATENCION_INDETERMINABLE;
        }

        if ($alerta->atendida) {
            $fin = $alerta->fecha_atencion;

            if ($fin === null) {
                return self::ATENCION_INDETERMINABLE;
            }
        } else {
            $fin = $referencia ?? Carbon::now();
        }

        if ($fin->lessThan($inicio)) {
            return self::ATENCION_INDETERMINABLE;
        }

        // Se comparan segundos exactos (sin redondear) para que el limite de
        // 24 horas exactas quede siempre del lado "dentro".
        $horas = $inicio->diffInSeconds($fin) / 3600;
        $dentroDelUmbral = $horas <= self::UMBRAL_ATENCION_HORAS;

        if ($alerta->atendida) {
            return $dentroDelUmbral ? self::ATENCION_ATENDIDA_DENTRO : self::ATENCION_ATENDIDA_FUERA;
        }

        return $dentroDelUmbral ? self::ATENCION_PENDIENTE_DENTRO : self::ATENCION_PENDIENTE_FUERA;
    }

    /**
     * Traduccion legible del resultado de evaluarTiempoAtencion. Se centraliza
     * aqui para que la tabla y el detalle de alertas muestren siempre el mismo
     * texto. No altera la logica de negocio: solo etiqueta el estado interno.
     */
    public function etiquetaTiempoAtencion(string $estado): string
    {
        return match ($estado) {
            self::ATENCION_PENDIENTE_DENTRO => 'Pendiente dentro de 24 horas',
            self::ATENCION_PENDIENTE_FUERA => 'Pendiente fuera de 24 horas',
            self::ATENCION_ATENDIDA_DENTRO => 'Atendida dentro de 24 horas',
            self::ATENCION_ATENDIDA_FUERA => 'Atendida fuera de 24 horas',
            default => 'Tiempo indeterminable',
        };
    }
}
