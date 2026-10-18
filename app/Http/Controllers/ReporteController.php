<?php

namespace App\Http\Controllers;

use App\Exports\InformeGeneralExport;
use App\Exports\ReporteExport;
use App\Services\InformeGeneralService;
use App\Services\ReporteExportService;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reportes,
        private readonly ReporteExportService $exportador,
        private readonly InformeGeneralService $informe,
    ) {
    }

    /**
     * Pantalla inicial del modulo de reportes: alcance autorizado, filtros
     * comunes y catalogo de reportes disponibles para el rol.
     */
    public function index(Request $request): View
    {
        return view('reportes.index', $this->contexto($request) + [
            'reportes' => $this->reportes->catalogoReportes($request->user()),
        ]);
    }

    /**
     * Informe general: un solo documento con los indicadores y las secciones
     * mas relevantes para el rol del usuario, sobre el periodo elegido.
     */
    public function informe(Request $request): View
    {
        $contexto = $this->contextoPeriodo($request);

        return view('reportes.informe', $contexto + [
            'titulo' => $this->informe->titulo(),
            'secciones' => $this->informe->secciones($contexto, $request->user()),
        ]);
    }

    /**
     * Descarga del informe general en PDF (un documento con todas las
     * secciones) o Excel (un libro con una hoja por seccion).
     */
    public function exportarInforme(Request $request, string $formato): Response|BinaryFileResponse
    {
        abort_unless(in_array($formato, ReporteExportService::FORMATOS, true), 404);

        $contexto = $this->contextoPeriodo($request);
        $secciones = $this->informe->secciones($contexto, $request->user());
        $nombre = 'informe-general-'.Carbon::now()->format('Y-m-d_His').'.'.$formato;

        if ($formato === 'xlsx') {
            return Excel::download(new InformeGeneralExport($secciones), $nombre);
        }

        return Pdf::loadView('reportes.pdf.informe', [
            'titulo' => $this->informe->titulo(),
            'secciones' => $secciones,
            'alcance' => $this->exportador->alcance($contexto),
        ])->download($nombre);
    }

    /**
     * Reporte del estado operativo de las bombas.
     */
    public function bombas(Request $request): View
    {
        $contexto = $this->contexto($request);

        return view('reportes.bombas', $contexto + [
            'bombas' => $this->reportes->estadoOperativoBombas(
                $contexto['centro'],
                $contexto['incluirDemo']
            ),
        ]);
    }

    /**
     * Reporte de lecturas y calidad de datos por sensor.
     */
    public function lecturas(Request $request): View
    {
        $contexto = $this->contexto($request);

        return view('reportes.lecturas', $contexto + [
            'sensores' => $this->reportes->resumenLecturas(
                $contexto['centro'],
                $contexto['rango']['desde'],
                $contexto['rango']['hasta'],
                $contexto['incluirDemo']
            ),
        ]);
    }

    /**
     * Reporte de alertas del periodo.
     */
    public function alertas(Request $request): View
    {
        $contexto = $this->contexto($request);

        return view('reportes.alertas', $contexto + [
            'alertas' => $this->reportes->alertasReporte(
                $contexto['centro'],
                $contexto['rango']['desde'],
                $contexto['rango']['hasta'],
                $contexto['incluirDemo']
            ),
        ]);
    }

    /**
     * Reporte de mantenimientos del periodo, con su resumen de indicadores.
     */
    public function mantenimientos(Request $request): View
    {
        $contexto = $this->contexto($request);

        $mantenimientos = $this->reportes->mantenimientosReporte(
            $contexto['centro'],
            $contexto['rango']['desde'],
            $contexto['rango']['hasta'],
            $contexto['incluirDemo']
        );

        $metricas = $this->reportes->metricasMantenimientos($mantenimientos);

        return view('reportes.mantenimientos', $contexto + [
            'mantenimientos' => $mantenimientos,
            'metricas' => $metricas,
            'indicadores' => $this->reportes->indicadoresMantenimientos($metricas),
        ]);
    }

    /**
     * Descarga de un reporte en PDF o Excel.
     *
     * Reutiliza exactamente el mismo contexto y las mismas consultas que la
     * pantalla, por lo que el archivo respeta el rol y el aislamiento por
     * centro de salud sin agregar reglas propias.
     */
    public function exportar(Request $request, string $reporte, string $formato): Response|BinaryFileResponse
    {
        abort_unless(in_array($reporte, ReporteExportService::REPORTES, true), 404);
        abort_unless(in_array($formato, ReporteExportService::FORMATOS, true), 404);

        // Se valida el alcance incluso al exportar: si un tecnico pide otro
        // centro, ReporteService responde 403 igual que en la pantalla.
        $contexto = $this->contexto($request);

        $matriz = $this->exportador->matriz($reporte, $contexto);
        $nombre = $this->exportador->nombreArchivo($reporte, $formato);

        // Los reportes que traen un resumen de indicadores (mantenimientos)
        // viajan como libro de dos hojas: resumen e historial.
        if ($formato === 'xlsx') {
            $resumen = $matriz['resumen'] ?? [];

            return Excel::download(
                $resumen === []
                    ? new ReporteExport($matriz['titulo'], $matriz['encabezados'], $matriz['filas'])
                    : new InformeGeneralExport($this->seccionesDe($matriz)),
                $nombre
            );
        }

        return Pdf::loadView('reportes.pdf.documento', [
            'titulo' => $matriz['titulo'],
            'encabezados' => $matriz['encabezados'],
            'filas' => $matriz['filas'],
            'resumen' => $matriz['resumen'] ?? [],
            'alcance' => $this->exportador->alcance($contexto),
        ])->download($nombre);
    }

    /**
     * Secciones de un reporte exportable: el resumen de indicadores como
     * primera hoja (si el reporte lo trae) y la tabla del detalle despues.
     *
     * @param  array<string, mixed>  $matriz
     * @return array<int, array{clave: string, titulo: string, encabezados: array<int, string>, filas: array<int, array<int, mixed>>}>
     */
    private function seccionesDe(array $matriz): array
    {
        $secciones = [];

        if (! empty($matriz['resumen'])) {
            $secciones[] = [
                'clave' => 'resumen',
                'titulo' => 'Resumen',
                'encabezados' => ['Indicador', 'Valor', 'Detalle'],
                'filas' => $matriz['resumen'],
            ];
        }

        $secciones[] = [
            'clave' => $matriz['titulo'],
            'titulo' => $matriz['titulo'],
            'encabezados' => $matriz['encabezados'],
            'filas' => $matriz['filas'],
        ];

        return $secciones;
    }

    /**
     * Valida los filtros comunes y resuelve el alcance autorizado del usuario.
     * El centro se resuelve en el servicio, que responde 403 si un tecnico o
     * un director intentan consultar otro centro de salud.
     *
     * @return array<string, mixed>
     */
    private function contexto(Request $request): array
    {
        $usuario = $request->user();

        $filtros = $request->validate([
            'centro_id' => ['nullable', 'integer', 'exists:centros_salud,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'demo' => ['nullable', 'in:incluir,excluir'],
        ]);

        // Un rango invertido no se corrige en silencio: se rechaza.
        if (isset($filtros['fecha_desde'], $filtros['fecha_hasta'])
            && Carbon::parse($filtros['fecha_desde'])->gt(Carbon::parse($filtros['fecha_hasta']))) {
            throw ValidationException::withMessages([
                'fecha_hasta' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]);
        }

        return [
            'filtros' => $filtros,
            'centro' => $this->reportes->resolverCentro($usuario, $filtros['centro_id'] ?? null),
            'rango' => $this->reportes->resolverRangoFechas($filtros),
            'incluirDemo' => $this->reportes->incluirDemo($filtros),
            'centros' => $this->reportes->centrosDisponibles($usuario),
        ];
    }

    /**
     * Variante de contexto() para el informe general: en lugar de un rango de
     * fechas libre, el periodo se expresa como cantidad + unidad y se cuenta
     * hacia atras desde hoy (resolverPeriodo). El resto de reglas (centro,
     * datos de demostracion) son las mismas que en los demas reportes.
     *
     * @return array<string, mixed>
     */
    private function contextoPeriodo(Request $request): array
    {
        $usuario = $request->user();

        $filtros = $request->validate([
            'centro_id' => ['nullable', 'integer', 'exists:centros_salud,id'],
            'periodo_cantidad' => ['nullable', 'integer', 'min:1'],
            'periodo_unidad' => ['nullable', Rule::in(ReporteService::UNIDADES_PERIODO)],
            'demo' => ['nullable', 'in:incluir,excluir'],
        ]);

        $periodo = $this->reportes->resolverPeriodo($filtros);

        return [
            'filtros' => $filtros,
            'centro' => $this->reportes->resolverCentro($usuario, $filtros['centro_id'] ?? null),
            // Se expone tambien como rango para reutilizar el panel de alcance,
            // que trabaja con desde/hasta/recortado.
            'rango' => $periodo,
            'periodo' => $periodo,
            'incluirDemo' => $this->reportes->incluirDemo($filtros),
            'centros' => $this->reportes->centrosDisponibles($usuario),
        ];
    }
}
