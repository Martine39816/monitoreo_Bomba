<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Construye la representacion tabular de cada reporte del catalogo a partir de
 * los datos que ya resolvio ReporteService.
 *
 * Es el unico lugar donde se decide que columnas y en que orden se exportan,
 * de modo que el PDF y el Excel de un mismo reporte no puedan mostrar
 * informacion distinta. No aplica filtros ni aislamiento por centro: eso llega
 * ya resuelto en el contexto, para no duplicar esas reglas.
 */
class ReporteExportService
{
    /**
     * Reportes exportables. Coincide con las claves del catalogo de
     * ReporteService::catalogoReportes() que estan disponibles.
     */
    public const REPORTES = ['bombas', 'lecturas', 'alertas', 'mantenimientos'];

    public const FORMATOS = ['pdf', 'xlsx'];

    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function titulo(string $clave): string
    {
        return match ($clave) {
            'bombas' => 'Estado operativo de bombas',
            'lecturas' => 'Lecturas y calidad de datos',
            'alertas' => 'Alertas',
            'mantenimientos' => 'Mantenimientos',
            default => 'Reporte',
        };
    }

    public function nombreArchivo(string $clave, string $formato): string
    {
        return 'reporte-'.$clave.'-'.Carbon::now()->format('Y-m-d_His').'.'.$formato;
    }

    /**
     * Encabezado comun de alcance que acompana al reporte exportado
     * (centro, periodo, datos de demostracion y fecha de generacion).
     *
     * @param  array<string, mixed>  $contexto
     * @return array<int, array{etiqueta: string, valor: string}>
     */
    public function alcance(array $contexto): array
    {
        $centro = $contexto['centro'] ?? null;
        $rango = $contexto['rango'];

        $lineas = [
            [
                'etiqueta' => 'Centro de salud',
                'valor' => $centro
                    ? $centro->nombre.' ('.$centro->codigo.')'
                    : 'Todos los centros de salud',
            ],
            [
                'etiqueta' => 'Periodo',
                'valor' => $rango['desde']->format('d/m/Y').' al '.$rango['hasta']->format('d/m/Y'),
            ],
            [
                'etiqueta' => 'Datos de demostracion',
                'valor' => ($contexto['incluirDemo'] ?? true) ? 'Incluidos' : 'Excluidos',
            ],
            [
                'etiqueta' => 'Generado',
                'valor' => Carbon::now()->format('d/m/Y H:i'),
            ],
        ];

        if ($rango['recortado']) {
            $lineas[] = [
                'etiqueta' => 'Aviso',
                'valor' => 'El periodo solicitado superaba el maximo permitido y se recorto '
                    .'automaticamente al rango mostrado.',
            ];
        }

        return $lineas;
    }

    /**
     * Matriz completa del reporte: titulo, encabezados y filas. El reporte de
     * mantenimientos agrega ademas su resumen de indicadores bajo la clave
     * `resumen`.
     *
     * @param  array<string, mixed>  $contexto
     * @return array{titulo: string, encabezados: array<int, string>, filas: array<int, array<int, mixed>>, resumen?: array<int, array<int, string>>}
     */
    public function matriz(string $clave, array $contexto): array
    {
        if ($clave === 'mantenimientos') {
            return $this->matrizMantenimientos($contexto);
        }

        $matriz = match ($clave) {
            'bombas' => $this->tablaBombas($contexto),
            'lecturas' => $this->tablaLecturas($contexto),
            'alertas' => $this->tablaAlertas($contexto),
        };

        return ['titulo' => $this->titulo($clave)] + $matriz;
    }

    /**
     * Matriz del reporte de mantenimientos: el historial mas el resumen de
     * indicadores. Los dos se calculan sobre la misma coleccion para que la
     * descarga no pueda mostrar cifras distintas a las de la pantalla.
     *
     * @param  array<string, mixed>  $contexto
     * @return array<string, mixed>
     */
    private function matrizMantenimientos(array $contexto): array
    {
        $mantenimientos = $this->reportes->mantenimientosReporte(
            $contexto['centro'],
            $contexto['rango']['desde'],
            $contexto['rango']['hasta'],
            $contexto['incluirDemo']
        );

        return [
            'titulo' => $this->titulo('mantenimientos'),
            'resumen' => $this->reportes->indicadoresMantenimientos(
                $this->reportes->metricasMantenimientos($mantenimientos)
            ),
        ] + $this->tablaMantenimientos($contexto, $mantenimientos);
    }

    /**
     * Tabla del reporte de bombas. Es publica para que el informe general la
     * reutilice tal cual: asi la pantalla, el PDF y el Excel de una misma
     * seccion no pueden mostrar columnas distintas.
     *
     * @param  array<string, mixed>  $contexto
     * @return array{encabezados: array<int, string>, filas: array<int, array<int, mixed>>}
     */
    public function tablaBombas(array $contexto): array
    {
        $bombas = $this->reportes->estadoOperativoBombasCompleto(
            $contexto['centro'],
            $contexto['incluirDemo']
        );

        $etiquetasEstado = [
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
            'mantenimiento' => 'En mantenimiento',
            'fuera_servicio' => 'Fuera de servicio',
            'desconectado' => 'Desconectado',
        ];

        $filas = $bombas->map(function (Bomba $bomba) use ($etiquetasEstado) {
            return [
                $bomba->centroSalud->nombre ?? 'Sin centro',
                $bomba->nombre,
                (string) $bomba->codigo,
                $etiquetasEstado[$bomba->estado] ?? $this->textoLegible($bomba->estado),
                $bomba->encendido ? 'Encendido' : 'Apagado',
                $bomba->modo_operacion ? 'Automatico' : 'Manual',
                $bomba->fecha_instalacion ? $bomba->fecha_instalacion->format('d/m/Y') : 'No registrada',
                $bomba->ultima_lectura
                    ? Carbon::parse($bomba->ultima_lectura)->format('d/m/Y H:i')
                    : 'Sin datos disponibles',
                $this->entero($bomba->alertas_pendientes_count),
                $bomba->proximo_mantenimiento
                    ? Carbon::parse($bomba->proximo_mantenimiento)->format('d/m/Y')
                    : 'No programado',
                $this->flotante($bomba->porcentaje_salud),
            ];
        })->all();

        return [
            'encabezados' => [
                'Centro de salud', 'Bomba', 'Codigo', 'Estado registrado', 'Encendido',
                'Modo', 'Instalacion', 'Ultima lectura', 'Alertas pendientes',
                'Proximo mantenimiento', 'Indice de salud (estimado)',
            ],
            'filas' => $filas,
        ];
    }

    /**
     * Tabla del reporte de lecturas (ver tablaBombas para el porque de ser publica).
     *
     * @param  array<string, mixed>  $contexto
     * @return array{encabezados: array<int, string>, filas: array<int, array<int, mixed>>}
     */
    public function tablaLecturas(array $contexto): array
    {
        $sensores = $this->reportes->resumenLecturas(
            $contexto['centro'],
            $contexto['rango']['desde'],
            $contexto['rango']['hasta'],
            $contexto['incluirDemo']
        );

        $filas = $sensores->map(function (Sensor $sensor) {
            return [
                $sensor->bomba->centroSalud->nombre ?? 'Sin centro',
                $sensor->bomba->nombre ?? 'Sin bomba',
                (string) $sensor->codigo,
                $sensor->nombre,
                $this->textoLegible($sensor->tipo),
                $sensor->unidad_medida ?? '—',
                $this->entero($sensor->lecturas_count),
                $this->flotante($sensor->valor_minimo_medido),
                $this->flotante($sensor->valor_maximo_medido),
                $this->flotante($sensor->valor_promedio),
                $this->entero($sensor->lecturas_fuera_rango),
                $this->flotante($sensor->valor_minimo),
                $this->flotante($sensor->valor_maximo),
            ];
        })->all();

        return [
            'encabezados' => [
                'Centro de salud', 'Bomba', 'Codigo del sensor', 'Sensor', 'Tipo', 'Unidad',
                'Lecturas en el periodo', 'Valor minimo', 'Valor maximo', 'Valor promedio',
                'Lecturas fuera de rango', 'Umbral minimo configurado', 'Umbral maximo configurado',
            ],
            'filas' => $filas,
        ];
    }

    /**
     * Tabla del reporte de alertas (ver tablaBombas para el porque de ser publica).
     *
     * @param  array<string, mixed>  $contexto
     * @return array{encabezados: array<int, string>, filas: array<int, array<int, mixed>>}
     */
    public function tablaAlertas(array $contexto): array
    {
        $alertas = $this->reportes->alertasReporte(
            $contexto['centro'],
            $contexto['rango']['desde'],
            $contexto['rango']['hasta'],
            $contexto['incluirDemo']
        );

        $filas = $alertas->map(function (Alerta $alerta) {
            return [
                $alerta->fecha_hora ? $alerta->fecha_hora->format('d/m/Y H:i') : '—',
                $alerta->bomba->centroSalud->nombre ?? 'Sin centro',
                $alerta->bomba->nombre ?? 'Sin bomba',
                $alerta->sensor->nombre ?? 'Sin sensor',
                $this->textoLegible($alerta->tipo),
                (string) ($alerta->descripcion ?? ''),
                $this->flotante($alerta->valor_detectado),
                $this->flotante($alerta->valor_permitido),
                $alerta->atendida ? 'Atendida' : 'Pendiente',
                $alerta->usuarioAtencion->name ?? '—',
                $alerta->fecha_atencion ? $alerta->fecha_atencion->format('d/m/Y H:i') : '—',
                $this->reportes->etiquetaTiempoAtencion(
                    $this->reportes->evaluarTiempoAtencion($alerta)
                ),
            ];
        })->all();

        return [
            'encabezados' => [
                'Fecha y hora', 'Centro de salud', 'Bomba', 'Sensor', 'Tipo', 'Descripcion',
                'Valor detectado', 'Valor permitido', 'Estado', 'Atendida por',
                'Fecha de atencion', 'Tiempo de atencion (umbral 24 h)',
            ],
            'filas' => $filas,
        ];
    }

    /**
     * Tabla del reporte de mantenimientos (ver tablaBombas para el porque de ser publica).
     * Admite la coleccion ya resuelta para no repetir la consulta cuando quien
     * llama necesita ademas el resumen de indicadores.
     *
     * @param  array<string, mixed>  $contexto
     * @param  \Illuminate\Database\Eloquent\Collection<int, Mantenimiento>|null  $mantenimientos
     * @return array{encabezados: array<int, string>, filas: array<int, array<int, mixed>>}
     */
    public function tablaMantenimientos(array $contexto, ?Collection $mantenimientos = null): array
    {
        $mantenimientos ??= $this->reportes->mantenimientosReporte(
            $contexto['centro'],
            $contexto['rango']['desde'],
            $contexto['rango']['hasta'],
            $contexto['incluirDemo']
        );

        $filas = $mantenimientos->map(function (Mantenimiento $mantenimiento) {
            return [
                $mantenimiento->fecha ? $mantenimiento->fecha->format('d/m/Y') : '—',
                $mantenimiento->bomba->centroSalud->nombre ?? 'Sin centro',
                $mantenimiento->bomba->nombre ?? 'Sin bomba',
                $this->textoLegible($mantenimiento->tipo),
                (string) ($mantenimiento->descripcion ?? ''),
                $mantenimiento->usuario->name ?? 'No registrado',
                $mantenimiento->proximo_mantenimiento
                    ? $mantenimiento->proximo_mantenimiento->format('d/m/Y')
                    : 'No programado',
            ];
        })->all();

        return [
            'encabezados' => [
                'Fecha', 'Centro de salud', 'Bomba', 'Tipo', 'Descripcion',
                'Responsable', 'Proximo mantenimiento',
            ],
            'filas' => $filas,
        ];
    }

    /**
     * Deja un valor numerico como numero para que Excel pueda operar con el.
     * Los nulos se exportan como guion para no confundirlos con el valor 0.
     * Es publico para que el informe general formatee sus cifras igual.
     */
    public function flotante(mixed $valor): float|string
    {
        return $valor === null ? '—' : (float) $valor;
    }

    public function entero(mixed $valor): string
    {
        return $valor === null ? '—' : (string) (int) $valor;
    }

    private function textoLegible(?string $valor): string
    {
        return $valor === null || $valor === ''
            ? '—'
            : ucfirst(str_replace('_', ' ', $valor));
    }
}
