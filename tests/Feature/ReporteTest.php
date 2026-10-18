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
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del modulo de reportes.
 *
 * En esta primera etapa se verifica el esqueleto: acceso por rol, aislamiento
 * por centro de salud aplicado en el servidor y validacion de los filtros
 * comunes. Los reportes en si se prueban en sus respectivas fases.
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private function centro(string $codigo, string $nombre): CentroSalud
    {
        return CentroSalud::factory()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
        ]);
    }

    public function test_los_tres_roles_acceden_al_modulo_de_reportes(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');

        $admin = User::factory()->administrador()->create();
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $this->actingAs($admin)->get(route('reportes.index'))->assertSuccessful();
        $this->actingAs($tecnico)->get(route('reportes.index'))->assertSuccessful();
        $this->actingAs($director)->get(route('reportes.index'))->assertSuccessful();
    }

    public function test_usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $this->get(route('reportes.index'))->assertRedirect(route('login'));
    }

    public function test_tecnico_no_puede_consultar_otro_centro_manipulando_el_filtro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        $this->actingAs($tecnico)
            ->get(route('reportes.index', ['centro_id' => $centroB->id]))
            ->assertForbidden();
    }

    public function test_director_no_puede_consultar_otro_centro_manipulando_el_filtro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        $this->actingAs($director)
            ->get(route('reportes.index', ['centro_id' => $centroB->id]))
            ->assertForbidden();
    }

    public function test_tecnico_ve_unicamente_su_propio_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        $this->actingAs($tecnico)
            ->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertSee('data-centro-id="'.$centroA->id.'"', false)
            ->assertSee('Centro Norte')
            ->assertDontSee('Centro Sur');
    }

    public function test_administrador_puede_filtrar_por_cualquier_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $this->centro('CS-B', 'Centro Sur');

        $admin = User::factory()->administrador()->create();

        // Sin filtro: alcance global.
        $this->actingAs($admin)
            ->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertSee('data-centro-id=""', false)
            ->assertSee('Centro Norte')
            ->assertSee('Centro Sur');

        // Con filtro: alcance al centro elegido.
        $this->actingAs($admin)
            ->get(route('reportes.index', ['centro_id' => $centroA->id]))
            ->assertSuccessful()
            ->assertSee('data-centro-id="'.$centroA->id.'"', false);
    }

    public function test_solo_el_administrador_ve_el_comparativo_entre_centros(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');

        $admin = User::factory()->administrador()->create();
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $this->actingAs($admin)->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertSee('Comparativo entre centros');

        $this->actingAs($tecnico)->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertDontSee('Comparativo entre centros');

        $this->actingAs($director)->get(route('reportes.index'))
            ->assertSuccessful()
            ->assertDontSee('Comparativo entre centros');
    }

    public function test_rechaza_rango_de_fechas_invertido(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.index', ['fecha_desde' => '2026-05-10', 'fecha_hasta' => '2026-05-01']))
            ->assertSessionHasErrors('fecha_hasta');
    }

    public function test_rechaza_fechas_invalidas(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.index', ['fecha_desde' => 'no-es-una-fecha']))
            ->assertSessionHasErrors('fecha_desde');
    }

    public function test_rechaza_centro_inexistente(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.index', ['centro_id' => 999999]))
            ->assertSessionHasErrors('centro_id');
    }

    public function test_rango_excesivo_se_recorta_a_los_366_dias(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.index', ['fecha_desde' => '2020-01-01', 'fecha_hasta' => '2026-01-01']))
            ->assertSuccessful()
            ->assertSee('366 días');
    }

    public function test_filtro_de_datos_demo_es_visible_y_acepta_solo_valores_validos(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.index', ['demo' => 'excluir']))
            ->assertSuccessful()
            ->assertSee('Excluidos');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['demo' => 'incluir']))
            ->assertSuccessful()
            ->assertSee('Incluidos');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['demo' => 'otro']))
            ->assertSessionHasErrors('demo');
    }

    // =====================================================================
    // FASE 2 — REPORTE DEL ESTADO OPERATIVO DE LAS BOMBAS
    // =====================================================================

    public function test_administrador_ve_bombas_de_varios_centros_en_el_reporte(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centroA->id]);

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $this->actingAs($admin)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('BA-001')
            ->assertSee('BB-001');
    }

    public function test_administrador_puede_filtrar_el_reporte_de_bombas_por_centro(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centroA->id]);

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $this->actingAs($admin)
            ->get(route('reportes.bombas', ['centro_id' => $centroA->id]))
            ->assertSuccessful()
            ->assertSee('BA-001')
            ->assertDontSee('BB-001');
    }

    public function test_tecnico_y_director_no_acceden_a_otro_centro_en_el_reporte_de_bombas(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        // Manipular el parametro centro_id no debe permitir salir del propio centro.
        $this->actingAs($tecnico)
            ->get(route('reportes.bombas', ['centro_id' => $centroB->id]))
            ->assertForbidden();

        $this->actingAs($director)
            ->get(route('reportes.bombas', ['centro_id' => $centroB->id]))
            ->assertForbidden();
    }

    public function test_tecnico_solo_ve_las_bombas_de_su_centro_en_el_reporte(): void
    {
        $centroA = $this->centro('CS-A', 'Centro Norte');
        $centroB = $this->centro('CS-B', 'Centro Sur');

        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);

        $this->crearBomba($centroA, 'BA-001');
        $this->crearBomba($centroB, 'BB-001');

        $this->actingAs($tecnico)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('BA-001')
            ->assertDontSee('BB-001');
    }

    public function test_bomba_sin_lecturas_se_muestra_con_sin_datos_disponibles(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);

        // Bomba sin sensores ni lecturas: el JOIN interno de vista_salud_bomba
        // la ocultaria, por lo que el reporte no debe depender de esa vista.
        $this->crearBomba($centro, 'BSIN-001');

        $this->actingAs($admin)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('BSIN-001')
            ->assertSee('Sin datos disponibles');
    }

    public function test_bomba_con_lecturas_muestra_ultima_lectura_e_indice_estimado(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);

        $bomba = $this->crearBomba($centro, 'BCON-001');
        $this->crearSensorConLectura($bomba, 50);

        $this->actingAs($admin)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('BCON-001')
            ->assertSee('100 %')
            ->assertDontSee('Sin datos disponibles');
    }

    public function test_alertas_pendientes_y_proximo_mantenimiento_se_relacionan_con_la_bomba_correcta(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);

        $bombaA = $this->crearBomba($centro, 'BA-REL');
        $bombaB = $this->crearBomba($centro, 'BB-REL');

        // Bomba A: dos alertas pendientes y una atendida (que no debe contarse).
        $this->crearAlerta($bombaA, false);
        $this->crearAlerta($bombaA, false);
        $this->crearAlerta($bombaA, true);

        // Bomba B: una alerta pendiente.
        $this->crearAlerta($bombaB, false);

        // Mantenimientos de la bomba A: uno futuro (cuenta) y uno pasado (no cuenta).
        $futuroA = now()->addDays(10)->toDateString();
        $this->crearMantenimiento($bombaA, $futuroA);
        $this->crearMantenimiento($bombaA, now()->subDays(5)->toDateString());

        // Mantenimiento de la bomba B: distinto, para comprobar que no se mezclan.
        $futuroB = now()->addDays(30)->toDateString();
        $this->crearMantenimiento($bombaB, $futuroB);

        $pagina = app(ReporteService::class)->estadoOperativoBombas($centro, true);
        $porId = collect($pagina->items())->keyBy('id');

        $this->assertSame(2, (int) $porId[$bombaA->id]->alertas_pendientes_count);
        $this->assertSame(1, (int) $porId[$bombaB->id]->alertas_pendientes_count);

        $this->assertSame(
            $futuroA,
            Carbon::parse($porId[$bombaA->id]->proximo_mantenimiento)->toDateString()
        );
        $this->assertSame(
            $futuroB,
            Carbon::parse($porId[$bombaB->id]->proximo_mantenimiento)->toDateString()
        );
    }

    public function test_filtro_demo_incluye_y_excluye_los_registros_correspondientes(): void
    {
        $real = $this->centro('CS-001', 'Centro Real');
        $demo = $this->centro('DEMO-CS-01', 'Centro Demo');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $real->id]);

        $this->crearBomba($real, 'BREAL-001');
        $this->crearBomba($demo, 'BDEMO-001');

        // Por defecto se incluyen los datos de demostracion.
        $this->actingAs($admin)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('BREAL-001')
            ->assertSee('BDEMO-001');

        // Excluidos: desaparecen solo los registros de centros DEMO-.
        $this->actingAs($admin)
            ->get(route('reportes.bombas', ['demo' => 'excluir']))
            ->assertSuccessful()
            ->assertSee('BREAL-001')
            ->assertDontSee('BDEMO-001');
    }

    public function test_reporte_de_bombas_sin_datos_no_produce_errores(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get(route('reportes.bombas'))
            ->assertSuccessful()
            ->assertSee('No hay bombas para mostrar');
    }

    public function test_el_reporte_de_bombas_esta_disponible_para_los_tres_roles(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $this->crearBomba($centro, 'BROLES-001');

        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        foreach ([$admin, $tecnico, $director] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('reportes.bombas'))
                ->assertSuccessful()
                ->assertSee('BROLES-001');
        }
    }

    public function test_una_bomba_con_varias_relaciones_aparece_una_sola_vez(): void
    {
        $centro = $this->centro('CS-A', 'Centro Norte');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $centro->id]);

        $bomba = $this->crearBomba($centro, 'BMULTI-001');

        // Varias relaciones que, con un JOIN mal planteado, duplicarian la fila.
        $this->crearAlerta($bomba, false);
        $this->crearAlerta($bomba, false);
        $this->crearAlerta($bomba, true);
        $this->crearSensorConLectura($bomba, 50);
        $this->crearSensorConLectura($bomba, 80);
        $this->crearMantenimiento($bomba, now()->addDays(5)->toDateString());
        $this->crearMantenimiento($bomba, now()->addDays(15)->toDateString());

        $pagina = app(ReporteService::class)->estadoOperativoBombas($centro, true);

        // La bomba se cuenta una sola vez y solo se cuentan las alertas pendientes.
        $this->assertSame(1, $pagina->total());
        $this->assertSame(2, (int) $pagina->items()[0]->alertas_pendientes_count);

        $response = $this->actingAs($admin)->get(route('reportes.bombas'));
        $response->assertSuccessful();
        $this->assertSame(1, substr_count($response->getContent(), 'data-bomba-id="'.$bomba->id.'"'));
    }

    public function test_la_paginacion_conserva_los_filtros_seleccionados(): void
    {
        $real = $this->centro('CS-001', 'Centro Real');
        $admin = User::factory()->administrador()->create(['centros_salud_id' => $real->id]);

        // 21 bombas superan el tamano de pagina (20): debe existir la pagina 2.
        for ($i = 1; $i <= 21; $i++) {
            $this->crearBomba($real, sprintf('BR-%03d', $i));
        }

        $response = $this->actingAs($admin)
            ->get(route('reportes.bombas', ['demo' => 'excluir']));

        $response->assertSuccessful();
        // Los enlaces de paginacion deben conservar el filtro demo.
        $response->assertSee('demo=excluir', false);
        $response->assertSee('page=2', false);
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

        Lectura::create([
            'valor_medido' => $valor,
            'fecha_hora' => now(),
            'sensores_id' => $sensor->id,
        ]);

        return $sensor;
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
