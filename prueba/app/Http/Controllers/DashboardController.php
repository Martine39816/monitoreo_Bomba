<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\VistaSaludBomba;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NivelTanqueEstimado;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | 1. CENTROS DE SALUD VISIBLES
        |--------------------------------------------------------------------------
        */

        $centros = CentroSalud::visiblesPara($usuario)->orderBy('nombre')->get();

        /*
        |--------------------------------------------------------------------------
        | 2. CONSULTA BASE DE BOMBAS SEGÚN EL ROL
        |--------------------------------------------------------------------------
        */

        // Administrador y Técnico: todas las bombas. Director: solo las de su centro.
        $bombasBaseQuery = Bomba::visiblesPara($usuario);

        /*
        |--------------------------------------------------------------------------
        | 2.1 CORREGIR BOMBAS "FUNCIONANDO" SIN SEÑAL DEL DISPOSITIVO
        |--------------------------------------------------------------------------
        | Si una bomba quedó marcada como funcionando pero su sensor de corriente
        | dejó de reportar, se marca como detenida. Así el contador, los filtros
        | y el listado coinciden con la realidad.
        */

        (clone $bombasBaseQuery)
            ->where('funcionando', true)
            ->get()
            ->each(fn ($b) => $b->sincronizarFuncionamiento());

        /*
        |--------------------------------------------------------------------------
        | 3. MÉTRICAS GENERALES
        |--------------------------------------------------------------------------
        | Estos totales no cambian cuando se utilizan los filtros.
        */

        $bombasTotal = (clone $bombasBaseQuery)->count();

        $bombasFuncionando = (clone $bombasBaseQuery)
            ->where('funcionando', true)
            ->count();

        $alertasQuery = Alerta::visiblesPara($usuario)
            ->with(['sensor', 'bomba'])
            ->where('atendida', false);

        $totalAlertasPendientes = (clone $alertasQuery)->count();

        $alertasPendientes = $alertasQuery
            ->orderBy('fecha_hora', 'desc')
            ->limit(5)
            ->get();

        $metricas = [
            'bombas_total' => $bombasTotal,
            'bombas_encendidas' => $bombasFuncionando,
            'alertas_pendientes' => $totalAlertasPendientes,
            'centros_total' => $usuario->veTodosLosCentros()
                ? CentroSalud::count()
                : null,
        ];

        /*
        |--------------------------------------------------------------------------
        | 4. CONSULTA PARA EL LISTADO DEL DASHBOARD
        |--------------------------------------------------------------------------
        */

        $bombasQuery = (clone $bombasBaseQuery)
            ->with('centroSalud')
            ->withCount([
                'alertas as alertas_pendientes' => function ($q) {
                    $q->where('atendida', false);
                }
            ])
            ->orderBy('nombre');

        /*
        |--------------------------------------------------------------------------
        | 5. FILTRO POR CENTRO DE SALUD
        |--------------------------------------------------------------------------
        | Administrador y técnico pueden elegir cualquier centro.
        */

        if (
            $usuario->veTodosLosCentros() &&
            $request->filled('centro')
        ) {

            $bombasQuery->where(
                'centros_salud_id',
                $request->centro
            );

        }

        /*
        |--------------------------------------------------------------------------
        | 6. FILTRO POR FUNCIONAMIENTO REAL
        |--------------------------------------------------------------------------
        */

        if ($request->estado === 'encendidas') {

            $bombasQuery->where('funcionando', true);

        } elseif ($request->estado === 'apagadas') {

            $bombasQuery->where('funcionando', false);

        } elseif ($request->estado === 'con_alertas') {

            $bombasQuery->whereHas('alertas', function ($q) {
                $q->where('atendida', false);
            });

        }

        /*
        |--------------------------------------------------------------------------
        | 7. BUSCADOR POR NOMBRE O CÓDIGO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('q')) {

            $busqueda = trim($request->q);

            $bombasQuery->where(function ($q) use ($busqueda) {

                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('codigo', 'like', "%{$busqueda}%");

            });

        }

        /*
        |--------------------------------------------------------------------------
        | 8. PAGINACIÓN
        |--------------------------------------------------------------------------
        */

        $bombas = $bombasQuery
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | 9. SALUD DE LAS BOMBAS
        |--------------------------------------------------------------------------
        | Se obtiene la salud de las bombas que aparecen en esta página.
        */

        $idsBombas = $bombas->getCollection()->pluck('id');

        $saludes = VistaSaludBomba::whereIn(
            'bomba_id',
            $idsBombas
        )
            ->get()
            ->keyBy('bomba_id');

        /*
        |--------------------------------------------------------------------------
        | 10. ENVIAR DATOS A LA VISTA
        |--------------------------------------------------------------------------
        */

        // Actualizar el nivel estimado del tanque de las bombas visibles.
        // Se conserva el servicio existente y se ejecuta antes de renderizar la vista.
        $estimadorNivel = app(NivelTanqueEstimado::class);

        foreach ($bombas as $bomba) {
            $bomba->nivel_tanque_estimado_datos = $estimadorNivel->actualizar($bomba);
        }

        return view('dashboard', compact(
            'bombas',
            'centros',
            'saludes',
            'alertasPendientes',
            'metricas'
        ));
    }
}