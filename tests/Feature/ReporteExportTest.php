<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Lectura;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;
use App\Services\ReporteExportService;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de la descarga de reportes en PDF (dompdf) y Excel (.xlsx).
 *
 * Se comprueba que la exportacion:
 *  - esta disponible para los tres roles y para todos los reportes del catalogo;
 *  - respeta el aislamiento por centro de salud igual que la pantalla;
 *  - no depende de los parametros del navegador para decidir el alcance.
 *
 * El contenido de un archivo binario no se inspecciona: la fidelidad de los
 * datos se verifica sobre la matriz que alimenta a ambos formatos.
 */
class ReporteExportTest extends TestCase
{
    use RefreshDatabase;

    private function centro(string $codigo, string $nombre): CentroSalud
    {
        return CentroSalud::factory()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
        ]);
    }

    // =====================================================================
    // PANTALLAS DE LOS REPORTES HABILITADOS
    // =====================================================================

    public function test_los_tres_roles_acceden_a_las_pantallas_de_los_reportes_habilitados(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');

        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $rutas = ['reportes.lecturas', 'reportes.alertas', 'reportes.mantenimientos'];

        foreach ([$admin, $tecnico, $director] as $usuario) {
            foreach ($rutas as $ruta) {
                $this->actingAs($usuario)->get(route($ruta))->assertSuccessful();
            }
        }
    }

    public function test_las_pantallas_de_los_reportes_habilitados_se_muestran_sin_datos(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->get(route('reportes.lecturas'))
            ->assertSuccessful()
            ->assertSee('No hay sensores para mostrar');

        $this->actingAs($admin)->get(route('reportes.alertas'))
            ->assertSuccessful()
            ->assertSee('No hay alertas para mostrar');

        $this->actingAs($admin)->get(route('reportes.mantenimientos'))
            ->assertSuccessful()
            ->assertSee('No hay mantenimientos para mostrar');
    }

    public function test_el_catalogo_enlaza_los_reportes_habilitados(): void
    {
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)->get(route('reportes.index'));

        $response->assertSuccessful();
        $response->assertSee(route('reportes.lecturas'), false);
        $response->assertSee(route('reportes.alertas'), false);
        $response->assertSee(route('reportes.mantenimientos'), false);
    }

    // =====================================================================
    // DESCARGAS
    // =====================================================================

    public function test_se_descarga_el_reporte_de_bombas_en_pdf(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);
        $this->crearBomba($centro, 'BA-001');

        $response = $this->actingAs($admin)
            ->get(route('reportes.exportar', ['reporte' => 'bombas', 'formato' => 'pdf']));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload();
    }

    public function test_se_descarga_el_reporte_de_bombas_en_excel(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);
        $this->crearBomba($centro, 'BA-001');

        $response = $this->actingAs($admin)
            ->get(route('reportes.exportar', ['reporte' => 'bombas', 'formato' => 'xlsx']));

        $response->assertSuccessful();
        $response->assertDownload();
    }

    public function test_todos_los_reportes_se_descargan_en_ambos_formatos(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);

        foreach (ReporteExportService::REPORTES as $reporte) {
            foreach (ReporteExportService::FORMATOS as $formato) {
                $response = $this->actingAs($admin)->get(route('reportes.exportar', [
                    'reporte' => $reporte,
                    'formato' => $formato,
                ]));

                $response->assertSuccessful();
                $response->assertDownload();
            }
        }
    }

    public function test_las_pantallas_ofrecen_los_enlaces_de_descarga(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('Descargar este reporte');

        $this->actingAs($admin)->get(route('reportes.lecturas'))
            ->assertSuccessful()
            ->assertSee('Descargar este reporte');

        // Los enlaces conservan los filtros aplicados.
        $this->actingAs($admin)->get(route('reportes.alertas', ['demo' => 'excluir']))
            ->assertSuccessful()
            ->assertSee('demo=excluir', false);
    }

    public function test_la_pantalla_de_mantenimientos_muestra_los_indicadores_del_periodo(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);
        $bomba = $this->crearBomba($centro, 'BA-001');

        // Un preventivo cuya proxima fecha ya vencio.
        $this->crearMantenimientoConTipo($bomba, 'preventivo', now()->subDays(10), now()->subDays(3));

        $this->actingAs($admin)->get(route('reportes.mantenimientos'))
            ->assertSuccessful()
            ->assertSee('Indicadores del periodo')
            ->assertSee('Mantenimientos del periodo')
            ->assertSee('Programados vencidos')
            ->assertSee('Tiempo entre mantenimientos')
            ->assertSee('mantenimiento programado cuya fecha ya venció');
    }

    // =====================================================================
    // SEGURIDAD Y VALIDACION
    // =====================================================================

    public function test_un_usuario_no_autenticado_no_puede_descargar_reportes(): void
    {
        $this->get(route('reportes.exportar', ['reporte' => 'bombas', 'formato' => 'pdf']))
            ->assertRedirect(route('login'));
    }

    public function test_un_tecnico_no_puede_exportar_el_centro_ajeno(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        foreach (ReporteExportService::FORMATOS as $formato) {
            $this->actingAs($tecnico)
                ->get(route('reportes.exportar', [
                    'reporte' => 'bombas',
                    'formato' => $formato,
                    'centro_id' => $centroB->id,
                ]))
                ->assertForbidden();
        }
    }

    public function test_un_reporte_o_un_formato_desconocido_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.exportar', ['reporte' => 'inexistente', 'formato' => 'pdf']))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('reportes.exportar', ['reporte' => 'bombas', 'formato' => 'csv']))
            ->assertNotFound();

        // El comparativo existe en el catalogo pero todavia no es exportable.
        $this->actingAs($admin)
            ->get(route('reportes.exportar', ['reporte' => 'comparativo', 'formato' => 'pdf']))
            ->assertNotFound();
    }

    public function test_la_exportacion_rechaza_un_rango_de_fechas_invertido(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.exportar', [
                'reporte' => 'alertas',
                'formato' => 'pdf',
                'fecha_desde' => '2026-05-10',
                'fecha_hasta' => '2026-05-01',
            ]))
            ->assertSessionHasErrors('fecha_hasta');
    }

    // =====================================================================
    // FIDELIDAD DE LOS DATOS EXPORTADOS
    // =====================================================================

    public function test_la_matriz_de_bombas_respeta_el_aislamiento_por_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $contexto = [
            'centro' => $centroA,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => true,
        ];

        $matriz = app(ReporteExportService::class)->matriz('bombas', $contexto);
        $plano = json_encode($matriz['filas'], JSON_UNESCAPED_UNICODE);

        $this->assertCount(1, $matriz['filas']);
        $this->assertStringContainsString('BA-001', $plano);
        $this->assertStringNotContainsString('BB-001', $plano);
    }

    public function test_la_matriz_de_lecturas_resume_los_valores_del_periodo(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $bomba = $this->crearBomba($centro, 'BA-001');
        $sensor = $this->crearSensorConLectura($bomba, 40);
        $this->crearLectura($sensor, 60);

        $contexto = [
            'centro' => $centro,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => true,
        ];

        $matriz = app(ReporteExportService::class)->matriz('lecturas', $contexto);

        $this->assertCount(1, $matriz['filas']);

        $fila = $matriz['filas'][0];

        // Se exporta como numero (no como texto) para poder operar en Excel.
        $this->assertSame(2.0, (float) $fila[6]); // lecturas en el periodo
        $this->assertSame(40.0, (float) $fila[7]); // minimo medido
        $this->assertSame(60.0, (float) $fila[8]); // maximo medido
        $this->assertSame(50.0, (float) $fila[9]); // promedio
        $this->assertSame('0', $fila[10]);         // lecturas fuera de rango
    }

    public function test_la_matriz_de_alertas_incluye_el_tiempo_de_atencion(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $bomba = $this->crearBomba($centro, 'BA-001');
        $this->crearAlerta($bomba, false);

        $contexto = [
            'centro' => $centro,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => true,
        ];

        $matriz = app(ReporteExportService::class)->matriz('alertas', $contexto);

        $this->assertCount(1, $matriz['filas']);
        // La ultima columna informa el resultado frente al umbral de 24 horas.
        $this->assertSame(
            'Pendiente dentro de 24 horas',
            $matriz['filas'][0][11]
        );
    }

    public function test_la_matriz_de_mantenimientos_lista_el_historial(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $bomba = $this->crearBomba($centro, 'BA-001');
        $this->crearMantenimiento($bomba, now()->addDays(10)->toDateString());

        $contexto = [
            'centro' => $centro,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => true,
        ];

        $matriz = app(ReporteExportService::class)->matriz('mantenimientos', $contexto);

        $this->assertCount(1, $matriz['filas']);
        $this->assertSame('Preventivo', $matriz['filas'][0][3]);
    }

    public function test_la_matriz_de_mantenimientos_incluye_el_resumen_de_indicadores(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $bomba = $this->crearBomba($centro, 'BA-001');

        // Un preventivo con la proxima fecha ya vencida y un correctivo futuro,
        // separados por 10 dias para poder promediar el intervalo.
        $this->crearMantenimientoConTipo($bomba, 'preventivo', now()->subDays(10), now()->subDays(3));
        $this->crearMantenimientoConTipo($bomba, 'correctivo', now(), now()->addDays(20));

        $contexto = [
            'centro' => $centro,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => true,
        ];

        $matriz = app(ReporteExportService::class)->matriz('mantenimientos', $contexto);
        $resumen = collect($matriz['resumen'])->mapWithKeys(fn (array $fila) => [$fila[0] => $fila[1]]);

        // La tabla del historial no cambia: dos filas, la mas reciente primero y
        // el tipo en la columna 3.
        $this->assertCount(2, $matriz['filas']);
        $this->assertSame(['Correctivo', 'Preventivo'], array_column($matriz['filas'], 3));

        $this->assertSame('2', $resumen['Mantenimientos del periodo']);
        $this->assertSame('1', $resumen['Preventivos']);
        $this->assertSame('1', $resumen['Correctivos']);
        $this->assertSame('0', $resumen['Predictivos']);
        $this->assertSame('0', $resumen['Emergencias']);
        $this->assertSame('1', $resumen['Programados vencidos']);
        $this->assertSame('1', $resumen['Proximos programados']);
        $this->assertSame('10,0 días', $resumen['Tiempo entre mantenimientos']);
    }

    public function test_la_matriz_de_lecturas_excluye_los_datos_de_demostracion(): void
    {
        $real = $this->centro('CS-001', 'Centro Real');
        $demo = $this->centro('DEMO-CS-01', 'Centro Demo');

        $bombaReal = $this->crearBomba($real, 'BREAL-001');
        $this->crearSensorConLectura($bombaReal, 40);

        $bombaDemo = $this->crearBomba($demo, 'BDEMO-001');
        $this->crearSensorConLectura($bombaDemo, 40);

        $contexto = [
            'centro' => null,
            'rango' => app(ReporteService::class)->resolverRangoFechas([]),
            'incluirDemo' => false,
        ];

        $matriz = app(ReporteExportService::class)->matriz('lecturas', $contexto);
        $plano = json_encode($matriz['filas'], JSON_UNESCAPED_UNICODE);

        // Solo queda el sensor del centro real.
        $this->assertCount(1, $matriz['filas']);
        $this->assertStringContainsString('Centro Real', $plano);
        $this->assertStringNotContainsString('Centro Demo', $plano);
        $this->assertStringNotContainsString('BDEMO-001', $plano);
    }

    // =====================================================================
    // AYUDANTES
    // =====================================================================

    private function crearBomba(CentroSalud $centro, string $codigo): Bomba
    {
        return Bomba::create([
            'codigo' => $codigo,
            'nombre' => 'Bomba '.$codigo,
            'serie' => 'S-'.Str::upper(Str::random(8)),
            'centros_salud_id' => $centro->id,
        ]);
    }

    private function crearSensorConLectura(Bomba $bomba, float $valor): Sensor
    {
        $dispositivo = DispositivoIot::create([
            'codigo' => 'D-'.Str::upper(Str::random(8)),
            'nombre' => 'Dispositivo '.Str::random(5),
            'centros_salud_id' => $bomba->centros_salud_id,
        ]);

        $sensor = Sensor::create([
            'codigo' => 'SEN-'.Str::upper(Str::random(8)),
            'nombre' => 'Sensor '.Str::random(5),
            'tipo' => 'nivel',
            'valor_minimo' => 10,
            'valor_maximo' => 100,
            'dispositivos_iot_id' => $dispositivo->id,
            'bombas_id' => $bomba->id,
        ]);

        $this->crearLectura($sensor, $valor);

        return $sensor;
    }

    private function crearLectura(Sensor $sensor, float $valor): Lectura
    {
        return Lectura::create([
            'valor_medido' => $valor,
            'fecha_hora' => now(),
            'sensores_id' => $sensor->id,
        ]);
    }

    private function crearAlerta(Bomba $bomba, bool $atendida): Alerta
    {
        return Alerta::create([
            'tipo' => 'nivel_alto',
            'descripcion' => 'Alerta de prueba',
            'fecha_hora' => now(),
            'atendida' => $atendida,
            'bombas_id' => $bomba->id,
        ]);
    }

    private function crearMantenimiento(Bomba $bomba, string $proximo): Mantenimiento
    {
        return Mantenimiento::create([
            'tipo' => 'preventivo',
            'fecha' => now()->toDateString(),
            'proximo_mantenimiento' => $proximo,
            'bombas_id' => $bomba->id,
        ]);
    }

    private function crearMantenimientoConTipo(Bomba $bomba, string $tipo, Carbon $fecha, ?Carbon $proximo): Mantenimiento
    {
        return Mantenimiento::create([
            'tipo' => $tipo,
            'fecha' => $fecha->toDateString(),
            'proximo_mantenimiento' => $proximo?->toDateString(),
            'bombas_id' => $bomba->id,
        ]);
    }
}
