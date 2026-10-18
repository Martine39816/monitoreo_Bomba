<?php

namespace Tests\Feature;

use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Lectura;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de aislamiento por centro de salud para el modulo de lecturas.
 *
 * Cubre los dos defectos de seguridad corregidos en la Fase 3:
 *   H-1: el grafico de lecturas no debe exponer sensores de otro centro.
 *   H-2: no se debe registrar una lectura con un sensor de otro centro.
 */
class LecturaSeguridadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Evidencia de aislamiento: la suite debe ejecutarse contra la base de
     * datos de pruebas y no contra la base de datos de desarrollo.
     */
    public function test_las_pruebas_se_ejecutan_contra_la_base_de_datos_aislada(): void
    {
        $this->assertSame('monitoreo_bomba_test', DB::connection()->getDatabaseName());
    }

    /**
     * H-1: un tecnico no puede consultar el grafico de un sensor de otro centro.
     */
    public function test_tecnico_no_puede_ver_el_grafico_de_un_sensor_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroB),
            $this->crearDispositivo($centroB),
            ['nombre' => 'Sensor Ajeno Grafico Tecnico']
        );
        $this->crearLectura($sensorAjeno, 42);

        $this->actingAs($tecnico)
            ->get(route('lecturas.index', ['sensor_id' => $sensorAjeno->id]))
            ->assertForbidden()
            ->assertDontSee('Sensor Ajeno Grafico Tecnico');
    }

    /**
     * H-1: un director no puede consultar el grafico de un sensor de otro centro.
     */
    public function test_director_no_puede_ver_el_grafico_de_un_sensor_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create([
            'rol' => User::ROL_DIRECTOR,
            'centros_salud_id' => $centroA->id,
        ]);

        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroB),
            $this->crearDispositivo($centroB),
            ['nombre' => 'Sensor Ajeno Grafico Director']
        );
        $this->crearLectura($sensorAjeno, 42);

        $this->actingAs($director)
            ->get(route('lecturas.index', ['sensor_id' => $sensorAjeno->id]))
            ->assertForbidden()
            ->assertDontSee('Sensor Ajeno Grafico Director');
    }

    /**
     * H-2: un tecnico no puede registrar una lectura para un sensor de otro centro.
     */
    public function test_tecnico_no_puede_registrar_lectura_de_un_sensor_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroB),
            $this->crearDispositivo($centroB)
        );

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensorAjeno->id,
            'valor_medido' => 250,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseMissing('lecturas', ['sensores_id' => $sensorAjeno->id]);
    }

    /**
     * H-2: un director no puede registrar una lectura para un sensor de otro
     * centro. La ruta de registro esta restringida a administrador y tecnico,
     * por lo que la peticion se rechaza antes de llegar al controlador.
     */
    public function test_director_no_puede_registrar_lectura_de_un_sensor_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create([
            'rol' => User::ROL_DIRECTOR,
            'centros_salud_id' => $centroA->id,
        ]);

        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroB),
            $this->crearDispositivo($centroB)
        );

        $this->actingAs($director)->post(route('lecturas.store'), [
            'sensores_id' => $sensorAjeno->id,
            'valor_medido' => 250,
        ])->assertForbidden();

        $this->assertDatabaseMissing('lecturas', ['sensores_id' => $sensorAjeno->id]);
    }

    /**
     * El intento rechazado (valor fuera de rango a proposito) no debe crear ni
     * la lectura ni la alerta asociada al sensor ajeno.
     */
    public function test_intento_rechazado_no_crea_lectura_ni_alerta_para_el_sensor_ajeno(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroB),
            $this->crearDispositivo($centroB)
        );

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensorAjeno->id,
            'valor_medido' => 250,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseCount('lecturas', 0);
        $this->assertDatabaseCount('alertas', 0);
    }

    /**
     * Operacion legitima: un tecnico si puede ver el grafico de un sensor de su
     * propio centro.
     */
    public function test_tecnico_puede_ver_el_grafico_de_un_sensor_de_su_centro(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor(
            $this->crearBomba($centro),
            $this->crearDispositivo($centro),
            ['nombre' => 'Sensor Propio Grafico']
        );
        $this->crearLectura($sensor, 55);

        $this->actingAs($tecnico)
            ->get(route('lecturas.index', ['sensor_id' => $sensor->id]))
            ->assertSuccessful()
            ->assertSee('Tendencia')
            ->assertSee('Sensor Propio Grafico');
    }

    /**
     * Operacion legitima: un tecnico si puede registrar una lectura de un
     * sensor de su propio centro, conservando la generacion de alertas.
     */
    public function test_tecnico_puede_registrar_lectura_de_un_sensor_de_su_centro(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor(
            $this->crearBomba($centro),
            $this->crearDispositivo($centro)
        );

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 250,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertDatabaseHas('lecturas', ['sensores_id' => $sensor->id]);
        $this->assertDatabaseHas('alertas', [
            'sensores_id' => $sensor->id,
            'tipo' => 'nivel_alto',
        ]);
    }

    /**
     * El administrador conserva el acceso a cualquier centro, tanto al grafico
     * como al registro de lecturas.
     */
    public function test_administrador_conserva_acceso_al_grafico_y_al_registro(): void
    {
        $admin = User::factory()->administrador()->create();
        $centroAjeno = $this->crearCentro();
        $sensor = $this->crearSensor(
            $this->crearBomba($centroAjeno),
            $this->crearDispositivo($centroAjeno),
            ['nombre' => 'Sensor Cualquier Centro Admin']
        );
        $this->crearLectura($sensor, 60);

        $this->actingAs($admin)
            ->get(route('lecturas.index', ['sensor_id' => $sensor->id]))
            ->assertSuccessful()
            ->assertSee('Tendencia')
            ->assertSee('Sensor Cualquier Centro Admin');

        $this->actingAs($admin)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 70,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertDatabaseHas('lecturas', ['sensores_id' => $sensor->id]);
    }

    /**
     * Los filtros y la presentacion del historial legitimo siguen funcionando:
     * al filtrar por un sensor propio se ve su nombre y no el de un sensor ajeno.
     */
    public function test_los_filtros_del_historial_legitimo_siguen_funcionando(): void
    {
        $centro = $this->crearCentro();
        $centroAjeno = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);

        $sensorPropio = $this->crearSensor(
            $this->crearBomba($centro),
            $this->crearDispositivo($centro),
            ['nombre' => 'Sensor Propio Filtro']
        );
        $sensorAjeno = $this->crearSensor(
            $this->crearBomba($centroAjeno),
            $this->crearDispositivo($centroAjeno),
            ['nombre' => 'Sensor Ajeno Filtro']
        );

        $this->crearLectura($sensorPropio, 33);
        $this->crearLectura($sensorAjeno, 44);

        $this->actingAs($tecnico)
            ->get(route('lecturas.index', ['sensor_id' => $sensorPropio->id]))
            ->assertSuccessful()
            ->assertSee('Sensor Propio Filtro')
            ->assertDontSee('Sensor Ajeno Filtro');
    }

    private function crearCentro(): CentroSalud
    {
        return CentroSalud::factory()->create([
            'codigo' => 'CS-'.Str::upper(Str::random(6)),
        ]);
    }

    private function crearBomba(CentroSalud $centro): Bomba
    {
        return Bomba::create([
            'codigo' => 'B-'.Str::upper(Str::random(8)),
            'nombre' => 'Bomba '.Str::random(5),
            'serie' => 'S-'.Str::upper(Str::random(8)),
            'centros_salud_id' => $centro->id,
        ]);
    }

    private function crearDispositivo(CentroSalud $centro): DispositivoIot
    {
        return DispositivoIot::create([
            'codigo' => 'D-'.Str::upper(Str::random(8)),
            'nombre' => 'Dispositivo '.Str::random(5),
            'centros_salud_id' => $centro->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function crearSensor(Bomba $bomba, DispositivoIot $dispositivo, array $atributos = []): Sensor
    {
        return Sensor::create(array_merge([
            'codigo' => 'SEN-'.Str::upper(Str::random(8)),
            'nombre' => 'Sensor '.Str::random(5),
            'tipo' => 'nivel',
            'valor_minimo' => 10,
            'valor_maximo' => 100,
            'dispositivos_iot_id' => $dispositivo->id,
            'bombas_id' => $bomba->id,
        ], $atributos));
    }

    private function crearLectura(Sensor $sensor, float $valor): Lectura
    {
        return Lectura::create([
            'sensores_id' => $sensor->id,
            'valor_medido' => $valor,
            'fecha_hora' => now(),
        ]);
    }
}
