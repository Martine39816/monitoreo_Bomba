<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de las reglas del modulo de alertas implementadas en la Fase 4:
 *   M-1: coherencia sensor-bomba en el alta manual.
 *   M-2: atencion idempotente y permisos por rol.
 *   M-4: generacion automatica centralizada (web e IoT) y deduplicacion.
 *
 * Todas las pruebas se ejecutan contra la base de datos aislada de pruebas.
 */
class AlertaReglasTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_pruebas_se_ejecutan_contra_la_base_de_datos_aislada(): void
    {
        $this->assertSame('monitoreo_bomba_test', DB::connection()->getDatabaseName());
    }

    // -----------------------------------------------------------------
    // M-1: coherencia sensor-bomba en el alta manual de alertas
    // -----------------------------------------------------------------

    /** Un sensor de la misma bomba indicada se acepta. */
    public function test_alta_manual_acepta_sensor_de_la_misma_bomba(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $bomba = $this->crearBomba($centro);
        $sensor = $this->crearSensor($bomba, $this->crearDispositivo($centro));

        $this->actingAs($tecnico)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Sensor coherente con la bomba',
            'bombas_id' => $bomba->id,
            'sensores_id' => $sensor->id,
        ])->assertRedirect(route('alertas.index'));

        $this->assertDatabaseHas('alertas', [
            'bombas_id' => $bomba->id,
            'sensores_id' => $sensor->id,
        ]);
    }

    /** Un sensor de otra bomba del mismo centro se rechaza. */
    public function test_alta_manual_rechaza_sensor_de_otra_bomba_del_mismo_centro(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $dispositivo = $this->crearDispositivo($centro);

        $bombaUno = $this->crearBomba($centro);
        $bombaDos = $this->crearBomba($centro);
        $sensorDeUno = $this->crearSensor($bombaUno, $dispositivo);

        $this->actingAs($tecnico)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Sensor de otra bomba del mismo centro',
            'bombas_id' => $bombaDos->id,
            'sensores_id' => $sensorDeUno->id,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseCount('alertas', 0);
    }

    /** Un sensor de otro centro se rechaza. */
    public function test_alta_manual_rechaza_sensor_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $bombaA = $this->crearBomba($centroA);
        $sensorB = $this->crearSensor($this->crearBomba($centroB), $this->crearDispositivo($centroB));

        $this->actingAs($tecnico)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Sensor de otro centro',
            'bombas_id' => $bombaA->id,
            'sensores_id' => $sensorB->id,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseCount('alertas', 0);
    }

    /** Un sensor inexistente se rechaza. */
    public function test_alta_manual_rechaza_sensor_inexistente(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $bomba = $this->crearBomba($centro);

        $this->actingAs($tecnico)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Sensor inexistente',
            'bombas_id' => $bomba->id,
            'sensores_id' => 999999,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseCount('alertas', 0);
    }

    /** Una alerta sin sensor sigue siendo valida. */
    public function test_alta_manual_permite_alerta_sin_sensor(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $bomba = $this->crearBomba($centro);

        $this->actingAs($tecnico)->post(route('alertas.store'), [
            'tipo' => 'bomba_apagada',
            'descripcion' => 'Reporte sin sensor asociado',
            'bombas_id' => $bomba->id,
        ])->assertRedirect(route('alertas.index'));

        $this->assertDatabaseHas('alertas', [
            'bombas_id' => $bomba->id,
            'sensores_id' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // M-2: atencion idempotente y permisos por rol
    // -----------------------------------------------------------------

    /** Un tecnico atiende una alerta pendiente de su centro. */
    public function test_tecnico_atiende_alerta_pendiente_de_su_centro(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $alerta = $this->crearAlerta($this->crearBomba($centro));

        $this->actingAs($tecnico)
            ->patch(route('alertas.atender', $alerta))
            ->assertRedirect(route('alertas.index'));

        $alerta->refresh();

        $this->assertTrue($alerta->atendida);
        $this->assertNotNull($alerta->fecha_atencion);
        $this->assertSame($tecnico->id, $alerta->usuario_atencion_id);
    }

    /** El director no puede atender: la restriccion se aplica en el servidor. */
    public function test_director_no_puede_atender_alertas(): void
    {
        $centro = $this->crearCentro();
        $director = User::factory()->create([
            'rol' => User::ROL_DIRECTOR,
            'centros_salud_id' => $centro->id,
        ]);
        $alerta = $this->crearAlerta($this->crearBomba($centro));

        $this->actingAs($director)
            ->patch(route('alertas.atender', $alerta))
            ->assertForbidden();

        $this->assertFalse($alerta->fresh()->atendida);
    }

    /** El administrador conserva el permiso de atender. */
    public function test_administrador_puede_atender_alertas(): void
    {
        $admin = User::factory()->administrador()->create();
        $alerta = $this->crearAlerta($this->crearBomba($this->crearCentro()));

        $this->actingAs($admin)
            ->patch(route('alertas.atender', $alerta))
            ->assertRedirect(route('alertas.index'));

        $this->assertTrue($alerta->fresh()->atendida);
    }

    /** Un tecnico no puede atender una alerta de otro centro. */
    public function test_tecnico_no_puede_atender_alerta_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);
        $alertaAjena = $this->crearAlerta($this->crearBomba($centroB));

        $this->actingAs($tecnico)
            ->patch(route('alertas.atender', $alertaAjena))
            ->assertForbidden();

        $this->assertFalse($alertaAjena->fresh()->atendida);
    }

    /** La segunda atencion no sobrescribe la evidencia original. */
    public function test_atencion_repetida_no_sobrescribe_la_evidencia_original(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $admin = User::factory()->administrador()->create();
        $alerta = $this->crearAlerta($this->crearBomba($centro));

        $this->actingAs($tecnico)->patch(route('alertas.atender', $alerta));
        $alerta->refresh();
        $fechaOriginal = $alerta->fecha_atencion;
        $usuarioOriginal = $alerta->usuario_atencion_id;

        $this->travel(30)->minutes();
        $this->actingAs($admin)->patch(route('alertas.atender', $alerta));

        $alerta->refresh();

        $this->assertSame($usuarioOriginal, $alerta->usuario_atencion_id);
        $this->assertTrue($fechaOriginal->equalTo($alerta->fecha_atencion));
        $this->assertSame($tecnico->id, $alerta->usuario_atencion_id);
    }

    // -----------------------------------------------------------------
    // M-4: generacion automatica centralizada y deduplicacion
    // -----------------------------------------------------------------

    /** La via web genera la alerta usando el servicio centralizado. */
    public function test_la_via_web_genera_alerta_fuera_de_rango(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro));

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 250,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertDatabaseHas('alertas', [
            'sensores_id' => $sensor->id,
            'tipo' => 'nivel_alto',
            'atendida' => false,
        ]);
    }

    /** La via IoT genera la alerta con la misma regla, marcando el origen. */
    public function test_la_via_iot_genera_alerta_fuera_de_rango(): void
    {
        $centro = $this->crearCentro();
        $dispositivo = $this->crearDispositivo($centro);
        $clavePlana = $dispositivo->generarApiKey();
        $sensor = $this->crearSensor($this->crearBomba($centro), $dispositivo);

        $this->withHeader('X-Device-Key', $clavePlana)
            ->postJson('/api/lecturas', [
                'sensores_id' => $sensor->id,
                'valor_medido' => 250,
            ])
            ->assertCreated()
            ->assertJson(['guardado' => true, 'alerta_generada' => true]);

        $alerta = Alerta::where('sensores_id', $sensor->id)->firstOrFail();

        $this->assertSame('nivel_alto', $alerta->tipo);
        $this->assertStringContainsString('automaticamente (IoT)', $alerta->descripcion);
    }

    /** Una lectura dentro de rango no genera alertas. */
    public function test_lectura_dentro_de_rango_no_genera_alerta(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro));

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 50,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertDatabaseCount('alertas', 0);
    }

    /** No se duplican alertas pendientes del mismo sensor y tipo (web). */
    public function test_deduplicacion_no_duplica_alerta_pendiente_web(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro));

        foreach ([250, 260, 270] as $valor) {
            $this->actingAs($tecnico)->post(route('lecturas.store'), [
                'sensores_id' => $sensor->id,
                'valor_medido' => $valor,
            ])->assertRedirect(route('lecturas.index'));
        }

        $this->assertSame(3, DB::table('lecturas')->where('sensores_id', $sensor->id)->count());
        $this->assertSame(1, Alerta::where('sensores_id', $sensor->id)->count());
    }

    /** No se duplican alertas pendientes del mismo sensor y tipo (IoT). */
    public function test_deduplicacion_no_duplica_alerta_pendiente_iot(): void
    {
        $centro = $this->crearCentro();
        $dispositivo = $this->crearDispositivo($centro);
        $clavePlana = $dispositivo->generarApiKey();
        $sensor = $this->crearSensor($this->crearBomba($centro), $dispositivo);

        $this->withHeader('X-Device-Key', $clavePlana)
            ->postJson('/api/lecturas', ['sensores_id' => $sensor->id, 'valor_medido' => 250])
            ->assertCreated()
            ->assertJson(['alerta_generada' => true]);

        $this->withHeader('X-Device-Key', $clavePlana)
            ->postJson('/api/lecturas', ['sensores_id' => $sensor->id, 'valor_medido' => 280])
            ->assertCreated()
            ->assertJson(['alerta_generada' => false]);

        $this->assertSame(1, Alerta::where('sensores_id', $sensor->id)->count());
    }

    /** Una lectura rechazada no crea lectura ni alerta. */
    public function test_lectura_rechazada_no_genera_alerta(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);
        $sensorAjeno = $this->crearSensor($this->crearBomba($centroB), $this->crearDispositivo($centroB));

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensorAjeno->id,
            'valor_medido' => 250,
        ])->assertSessionHasErrors('sensores_id');

        $this->assertDatabaseCount('lecturas', 0);
        $this->assertDatabaseCount('alertas', 0);
    }

    // -----------------------------------------------------------------
    // M-4 (observacion): umbrales de sensor incompletos
    // -----------------------------------------------------------------

    /** Un sensor sin valor minimo no genera alerta por umbral incompleto (web). */
    public function test_sensor_con_valor_minimo_nulo_no_genera_alerta(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro), [
            'valor_minimo' => null,
            'valor_maximo' => 100,
        ]);

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 250,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertSame(1, DB::table('lecturas')->where('sensores_id', $sensor->id)->count());
        $this->assertSame(0, Alerta::where('sensores_id', $sensor->id)->count());
    }

    /** Un sensor sin valor maximo no genera alerta por umbral incompleto (web). */
    public function test_sensor_con_valor_maximo_nulo_no_genera_alerta(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro), [
            'valor_minimo' => 10,
            'valor_maximo' => null,
        ]);

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 250,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertSame(1, DB::table('lecturas')->where('sensores_id', $sensor->id)->count());
        $this->assertSame(0, Alerta::where('sensores_id', $sensor->id)->count());
    }

    /** Un umbral incompleto tampoco genera alerta por la via IoT. */
    public function test_sensor_con_umbral_incompleto_no_genera_alerta_por_iot(): void
    {
        $centro = $this->crearCentro();
        $dispositivo = $this->crearDispositivo($centro);
        $clavePlana = $dispositivo->generarApiKey();
        $sensor = $this->crearSensor($this->crearBomba($centro), $dispositivo, [
            'valor_minimo' => 10,
            'valor_maximo' => null,
        ]);

        $this->withHeader('X-Device-Key', $clavePlana)
            ->postJson('/api/lecturas', [
                'sensores_id' => $sensor->id,
                'valor_medido' => 250,
            ])
            ->assertCreated()
            ->assertJson(['guardado' => true, 'alerta_generada' => false]);

        $this->assertSame(1, DB::table('lecturas')->where('sensores_id', $sensor->id)->count());
        $this->assertSame(0, Alerta::where('sensores_id', $sensor->id)->count());
    }

    /** Un sensor con umbrales completos sigue generando alerta con normalidad. */
    public function test_sensor_con_umbrales_completos_sigue_generando_alerta(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centro->id]);
        $sensor = $this->crearSensor($this->crearBomba($centro), $this->crearDispositivo($centro));

        $this->actingAs($tecnico)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 250,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertSame(1, Alerta::where('sensores_id', $sensor->id)->count());
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

    private function crearAlerta(Bomba $bomba): Alerta
    {
        return Alerta::create([
            'tipo' => 'nivel_alto',
            'descripcion' => 'Alerta de prueba',
            'fecha_hora' => now(),
            'atendida' => false,
            'bombas_id' => $bomba->id,
        ]);
    }
}
