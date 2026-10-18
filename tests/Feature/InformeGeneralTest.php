<?php

namespace Tests\Feature;

use App\Exports\InformeGeneralExport;
use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Lectura;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;
use App\Services\InformeGeneralService;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del informe general: un solo documento con indicadores y secciones
 * diferenciadas por rol, sobre un periodo expresado como cantidad + unidad.
 *
 * Se verifica el periodo, el contenido por rol, el aislamiento por centro de
 * salud (pantalla y descarga) y el formato de la exportacion.
 */
class InformeGeneralTest extends TestCase
{
    use RefreshDatabase;

    private function centro(string $codigo, string $nombre): CentroSalud
    {
        return CentroSalud::factory()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
        ]);
    }

    private function administrador(?CentroSalud $centro = null): User
    {
        return User::factory()->administrador()->create([
            'centros_salud_id' => $centro?->id ?? $this->centro('CS-ADM', 'Centro Admin')->id,
        ]);
    }

    // =====================================================================
    // PERIODO (CANTIDAD + UNIDAD)
    // =====================================================================

    public function test_el_periodo_por_defecto_son_los_ultimos_siete_dias(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([]);

        $this->assertSame('dias', $periodo['unidad']);
        $this->assertSame(7, $periodo['cantidad']);
        $this->assertSame(Carbon::today()->subDays(6)->toDateString(), $periodo['desde']->toDateString());
        $this->assertSame(Carbon::today()->toDateString(), $periodo['hasta']->toDateString());
        $this->assertSame('Últimos 7 días', $periodo['etiqueta']);
        $this->assertFalse($periodo['recortado']);
    }

    public function test_el_periodo_de_un_dia_incluye_solo_el_dia_en_curso(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([
            'periodo_cantidad' => 1,
            'periodo_unidad' => 'dias',
        ]);

        $this->assertSame(Carbon::today()->toDateString(), $periodo['desde']->toDateString());
        $this->assertSame('Último día', $periodo['etiqueta']);
    }

    public function test_el_periodo_en_semanas_cubre_siete_dias_por_semana(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([
            'periodo_cantidad' => 2,
            'periodo_unidad' => 'semanas',
        ]);

        $this->assertSame(Carbon::today()->subDays(13)->toDateString(), $periodo['desde']->toDateString());
        $this->assertSame('Últimas 2 semanas', $periodo['etiqueta']);
    }

    public function test_el_periodo_en_meses_se_cuenta_hacia_atras_desde_hoy(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([
            'periodo_cantidad' => 3,
            'periodo_unidad' => 'meses',
        ]);

        $this->assertSame(
            Carbon::today()->subMonthsNoOverflow(3)->addDay()->toDateString(),
            $periodo['desde']->toDateString()
        );
        $this->assertSame('Últimos 3 meses', $periodo['etiqueta']);
    }

    public function test_una_cantidad_excesiva_se_recorta_al_maximo_de_la_unidad(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([
            'periodo_cantidad' => 999,
            'periodo_unidad' => 'meses',
        ]);

        $this->assertTrue($periodo['recortado']);
        $this->assertSame(ReporteService::MAXIMO_PERIODO['meses'], $periodo['cantidad']);
        $this->assertSame('Últimos 12 meses', $periodo['etiqueta']);
    }

    public function test_una_unidad_no_admitida_cae_en_dias(): void
    {
        $periodo = app(ReporteService::class)->resolverPeriodo([
            'periodo_cantidad' => 5,
            'periodo_unidad' => 'anios',
        ]);

        $this->assertSame('dias', $periodo['unidad']);
        $this->assertSame(5, $periodo['cantidad']);
    }

    public function test_la_unidad_del_periodo_se_valida_en_la_pantalla(): void
    {
        $this->actingAs($this->administrador())
            ->get(route('reportes.informe', ['periodo_unidad' => 'anios']))
            ->assertSessionHasErrors('periodo_unidad');
    }

    // =====================================================================
    // CONTENIDO POR ROL
    // =====================================================================

    public function test_el_administrador_recibe_resumen_comparativo_y_todos_los_detalles(): void
    {
        $admin = $this->administrador();

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($admin), $admin);

        $this->assertSame(
            ['resumen', 'centros', 'bombas', 'lecturas', 'alertas', 'mantenimientos'],
            array_column($secciones, 'clave')
        );
    }

    public function test_el_tecnico_recibe_solo_los_bloques_operativos(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($tecnico), $tecnico);

        $this->assertSame(
            ['resumen', 'bombas', 'lecturas', 'alertas'],
            array_column($secciones, 'clave')
        );
    }

    public function test_el_director_recibe_indicadores_gerenciales_sin_el_detalle_de_lecturas(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($director), $director);
        $claves = array_column($secciones, 'clave');

        $this->assertSame(['resumen', 'bombas', 'alertas', 'mantenimientos'], $claves);
        $this->assertNotContains('lecturas', $claves);
        $this->assertNotContains('centros', $claves);
    }

    public function test_los_indicadores_reflejan_los_datos_del_periodo(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = $this->administrador($centro);

        $bombaActiva = $this->crearBomba($centro, 'BA-001');
        $this->crearBomba($centro, 'BA-002', 'fuera_servicio');

        $sensor = $this->crearSensorConLectura($bombaActiva, 40);
        $this->crearLectura($sensor, 150); // fuera del umbral maximo (100)
        $this->crearAlerta($bombaActiva, false);

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($admin), $admin);
        $resumen = collect($secciones)->firstWhere('clave', 'resumen');

        // Las filas del resumen son [etiqueta, valor, detalle].
        $valores = collect($resumen['filas'])->mapWithKeys(fn (array $fila) => [$fila[0] => $fila[1]]);

        $this->assertSame('2', $valores['Bombas registradas']);
        $this->assertSame('1', $valores['Bombas activas']);
        $this->assertSame('1', $valores['Bombas fuera de servicio']);
        $this->assertSame('1', $valores['Alertas del periodo']);
        $this->assertSame('1', $valores['Alertas pendientes']);
        $this->assertSame('2', $valores['Lecturas registradas']);
        $this->assertSame('1', $valores['Lecturas fuera de rango']);
    }

    public function test_los_mantenimientos_programados_vencidos_se_cuentan_en_el_resumen(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');

        // Los indicadores de mantenimiento programado son gerenciales: los ve el director.
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);
        $bomba = $this->crearBomba($centro, 'BA-001');

        $this->crearMantenimiento($bomba, Carbon::today()->subDays(3)->toDateString());

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($director), $director);
        $resumen = collect($secciones)->firstWhere('clave', 'resumen');

        $valores = collect($resumen['filas'])->mapWithKeys(fn (array $fila) => [$fila[0] => $fila[1]]);

        $this->assertSame('1', $valores['Mantenimientos del periodo']);
        $this->assertSame('1', $valores['Mantenimientos preventivos']);
        $this->assertSame('0', $valores['Mantenimientos correctivos']);
        $this->assertSame('1', $valores['Mantenimientos programados vencidos']);
    }

    public function test_el_comparativo_entre_centros_muestra_una_fila_por_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');
        $admin = $this->administrador($centroA);

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($admin), $admin);
        $comparativo = collect($secciones)->firstWhere('clave', 'centros');

        $this->assertCount(2, $comparativo['filas']);
        $this->assertStringContainsString('Centro Norte', $comparativo['filas'][0][0]);
        $this->assertStringContainsString('Centro Sur', $comparativo['filas'][1][0]);
    }

    // =====================================================================
    // PANTALLA Y DESCARGAS
    // =====================================================================

    public function test_los_tres_roles_acceden_a_la_pantalla_del_informe_general(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');

        $usuarios = [
            $this->administrador($centro),
            User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]),
            User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]),
        ];

        foreach ($usuarios as $usuario) {
            $this->actingAs($usuario)->get(route('reportes.informe'))
                ->assertSuccessful()
                ->assertSee('Generar informe')
                ->assertSee('Descargar el informe general');
        }
    }

    public function test_un_usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $this->get(route('reportes.informe'))->assertRedirect(route('login'));
    }

    public function test_el_catalogo_ofrece_el_informe_general(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertSee(route('reportes.informe'), false)
            ->assertSee('Informe general');
    }

    public function test_el_informe_general_se_descarga_en_pdf_y_excel(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = $this->administrador($centro);
        $this->crearBomba($centro, 'BA-001');

        $pdf = $this->actingAs($admin)
            ->get(route('reportes.informe.exportar', ['formato' => 'pdf']));

        $pdf->assertSuccessful();
        $pdf->assertHeader('content-type', 'application/pdf');
        $pdf->assertDownload();

        $this->actingAs($admin)
            ->get(route('reportes.informe.exportar', ['formato' => 'xlsx']))
            ->assertSuccessful()
            ->assertDownload();
    }

    public function test_un_formato_desconocido_devuelve_404(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->get(route('reportes.informe.exportar', ['formato' => 'csv']))
            ->assertNotFound();
    }

    public function test_la_exportacion_excel_lleva_una_hoja_por_seccion(): void
    {
        $admin = $this->administrador();
        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($admin), $admin);

        $hojas = (new InformeGeneralExport($secciones))->sheets();

        $this->assertCount(count($secciones), $hojas);
        $this->assertSame('Resumen del periodo', $hojas[0]->title());

        $titulos = array_map(fn ($hoja) => $hoja->title(), $hojas);

        // Excel limita el nombre de la hoja a 31 caracteres y no admite repetidos.
        foreach ($titulos as $titulo) {
            $this->assertLessThanOrEqual(31, mb_strlen($titulo));
        }

        $this->assertSame($titulos, array_values(array_unique($titulos)));
    }

    // =====================================================================
    // AISLAMIENTO POR CENTRO DE SALUD
    // =====================================================================

    public function test_un_tecnico_no_puede_generar_el_informe_de_otro_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        $this->actingAs($tecnico)
            ->get(route('reportes.informe', ['centro_id' => $centroB->id]))
            ->assertForbidden();

        $this->actingAs($tecnico)
            ->get(route('reportes.informe.exportar', ['formato' => 'pdf', 'centro_id' => $centroB->id]))
            ->assertForbidden();
    }

    public function test_el_alcance_del_tecnico_se_limita_a_su_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        $secciones = app(InformeGeneralService::class)->secciones($this->contexto($tecnico), $tecnico);
        $bombas = collect($secciones)->firstWhere('clave', 'bombas');

        $plano = json_encode($bombas['filas'], JSON_UNESCAPED_UNICODE);

        $this->assertCount(1, $bombas['filas']);
        $this->assertStringContainsString('BA-001', $plano);
        $this->assertStringNotContainsString('BB-001', $plano);
    }

    public function test_el_informe_excluye_los_datos_de_demostracion_cuando_se_pide(): void
    {
        $real = $this->centro('CS-001', 'Centro Real');
        $demo = $this->centro('DEMO-CS-01', 'Centro Demo');

        $this->crearBomba($real, 'BREAL-001');
        $this->crearBomba($demo, 'BDEMO-001');

        $admin = $this->administrador($real);

        $secciones = app(InformeGeneralService::class)
            ->secciones($this->contexto($admin, ['demo' => 'excluir']), $admin);

        $bombas = collect($secciones)->firstWhere('clave', 'bombas');
        $plano = json_encode($bombas['filas'], JSON_UNESCAPED_UNICODE);

        $this->assertCount(1, $bombas['filas']);
        $this->assertStringContainsString('BREAL-001', $plano);
        $this->assertStringNotContainsString('BDEMO-001', $plano);
    }

    // =====================================================================
    // AYUDANTES
    // =====================================================================

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function contexto(User $usuario, array $filtros = []): array
    {
        $reportes = app(ReporteService::class);
        $periodo = $reportes->resolverPeriodo($filtros);

        return [
            'filtros' => $filtros,
            'centro' => $reportes->resolverCentro($usuario, $filtros['centro_id'] ?? null),
            'rango' => $periodo,
            'periodo' => $periodo,
            'incluirDemo' => $reportes->incluirDemo($filtros),
            'centros' => $reportes->centrosDisponibles($usuario),
        ];
    }

    private function crearBomba(CentroSalud $centro, string $codigo, string $estado = 'activo'): Bomba
    {
        return Bomba::create([
            'codigo' => $codigo,
            'nombre' => 'Bomba '.$codigo,
            'serie' => 'S-'.Str::upper(Str::random(8)),
            'estado' => $estado,
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
}
