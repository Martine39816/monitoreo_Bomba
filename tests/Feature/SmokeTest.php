<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recorre las páginas principales autenticado como administrador
     * para verificar que ninguna devuelve error 500.
     */
    public function test_paginas_principales_renderizan(): void
    {
        $admin = User::factory()->administrador()->create();

        $rutas = [
            'dashboard',
            'bombas.index',
            'sensores.index',
            'dispositivos.index',
            'mantenimientos.index',
            'alertas.index',
            'lecturas.index',
            'lecturas.create',
            'centros.index',
            'users.index',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($admin)->get(route($ruta));
            $response->assertSuccessful();
        }
    }

    /**
     * Recorre las páginas principales como técnico (rol no administrador).
     */
    public function test_paginas_principales_como_tecnico(): void
    {
        $tecnico = User::factory()->create();

        $rutas = [
            'dashboard',
            'bombas.index',
            'sensores.index',
            'alertas.index',
            'lecturas.index',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($tecnico)->get(route($ruta));
            $response->assertSuccessful();
        }
    }

    /**
     * Un usuario desactivado (estado = false) no debe acceder a las rutas
     * protegidas por rol, aunque conserve su rol asignado.
     */
    public function test_usuario_desactivado_recibe_403_en_rutas_con_rol(): void
    {
        $admin = User::factory()->administrador()->create(['estado' => false]);

        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
    }

    /**
     * Las vistas de detalle (show) de cada recurso deben renderizar sin
     * errores cuando existen datos completos asociados.
     */
    public function test_vistas_show_renderizan(): void
    {
        $admin = User::factory()->administrador()->create();
        $centro = $this->crearCentro();
        $bomba = $this->crearBomba($centro);
        $dispositivo = $this->crearDispositivo($centro);
        $sensor = $this->crearSensor($bomba, $dispositivo);
        $alerta = $this->crearAlerta($bomba, $sensor);

        $mantenimiento = Mantenimiento::create([
            'tipo' => 'preventivo',
            'fecha' => now()->toDateString(),
            'bombas_id' => $bomba->id,
            'usuarios_id' => $admin->id,
            'alertas_id' => $alerta->id,
        ]);

        $rutas = [
            route('bombas.show', $bomba),
            route('sensores.show', $sensor),
            route('dispositivos.show', $dispositivo),
            route('centros.show', $centro),
            route('users.show', $admin),
            route('alertas.show', $alerta),
            route('mantenimientos.show', $mantenimiento),
        ];

        foreach ($rutas as $url) {
            $this->actingAs($admin)->get($url)->assertSuccessful();
        }
    }

    /**
     * El enlace de Usuarios depende del rol: el técnico no lo ve (no tiene
     * acceso), pero el director sí (ve los técnicos de su centro).
     */
    public function test_menu_muestra_usuarios_solo_segun_rol(): void
    {
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO]);
        $this->actingAs($tecnico)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertDontSee(route('users.index'));

        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR]);
        $this->actingAs($director)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSee(route('users.index'));
    }

    /**
     * El director solo ve los técnicos de su centro (no técnicos de otros
     * centros, ni administradores) y no dispone de acciones de gestión.
     */
    public function test_director_solo_ve_los_tecnicos_de_su_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();

        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id, 'correo' => 'propio@centro-a.local']);
        User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroB->id, 'correo' => 'ajeno@centro-b.local']);
        User::factory()->administrador()->create(['centros_salud_id' => $centroA->id, 'correo' => 'admin@centro-a.local']);

        $response = $this->actingAs($director)->get(route('users.index'));

        $response->assertSuccessful();
        $response->assertSee('propio@centro-a.local');
        $response->assertDontSee('ajeno@centro-b.local');
        $response->assertDontSee('admin@centro-a.local');
        $response->assertDontSee('Nuevo Usuario');
    }

    /**
     * El director solo puede abrir el detalle de técnicos de su centro.
     */
    public function test_director_no_puede_ver_detalle_de_usuario_ajeno(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        $tecnicoPropio = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroA->id]);
        $tecnicoAjeno = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centroB->id]);
        $adminMismoCentro = User::factory()->administrador()->create(['centros_salud_id' => $centroA->id]);

        $this->actingAs($director)->get(route('users.show', $tecnicoPropio))->assertSuccessful();
        $this->actingAs($director)->get(route('users.show', $tecnicoAjeno))->assertForbidden();
        $this->actingAs($director)->get(route('users.show', $adminMismoCentro))->assertForbidden();
    }

    /**
     * El director no puede acceder a las pantallas de gestión de usuarios.
     */
    public function test_director_no_puede_gestionar_usuarios(): void
    {
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR]);

        $this->actingAs($director)->get(route('users.create'))->assertForbidden();
        $this->actingAs($director)->post(route('users.store'), [])->assertForbidden();
    }

    /**
     * El director es un rol de solo consulta: no puede entrar a las pantallas
     * operativas protegidas (crear bombas).
     */
    public function test_director_no_accede_a_pantallas_operativas(): void
    {
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR]);

        $this->actingAs($director)->get(route('bombas.create'))->assertForbidden();
        $this->actingAs($director)->get(route('sensores.index'))->assertForbidden();
    }

    /**
     * Un técnico no puede abrir el detalle de una bomba de otro centro (403).
     */
    public function test_tecnico_no_puede_ver_bomba_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);
        $bombaB = $this->crearBomba($centroB);

        $this->actingAs($tecnico)->get(route('bombas.show', $bombaB))->assertForbidden();
    }

    /**
     * Aunque un técnico envíe el centro de otro centro en el formulario,
     * la bomba se guarda siempre en su propio centro.
     */
    public function test_tecnico_al_crear_bomba_se_fuerza_su_propio_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $this->actingAs($tecnico)->post(route('bombas.store'), [
            'codigo' => 'BT-FORCE',
            'nombre' => 'Bomba del tecnico',
            'serie' => 'ST-FORCE',
            'modo_operacion' => 0,
            'encendido' => 0,
            'estado' => 'activo',
            'centros_salud_id' => $centroB->id,
        ])->assertRedirect(route('bombas.index'));

        $this->assertDatabaseHas('bombas', [
            'codigo' => 'BT-FORCE',
            'centros_salud_id' => $centroA->id,
        ]);
        $this->assertDatabaseMissing('bombas', [
            'codigo' => 'BT-FORCE',
            'centros_salud_id' => $centroB->id,
        ]);
    }

    /**
     * Un técnico no puede asociar un sensor a bombas/dispositivos de otro centro.
     */
    public function test_tecnico_no_puede_asociar_sensor_a_recursos_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $bombaB = $this->crearBomba($centroB);
        $dispositivoB = $this->crearDispositivo($centroB);

        $this->actingAs($tecnico)->post(route('sensores.store'), [
            'codigo' => 'SEN-AJENO',
            'nombre' => 'Sensor de otro centro',
            'tipo' => 'nivel',
            'estado' => 'activo',
            'dispositivos_iot_id' => $dispositivoB->id,
            'bombas_id' => $bombaB->id,
        ])->assertSessionHasErrors(['bombas_id', 'dispositivos_iot_id']);

        $this->assertDatabaseMissing('sensores', ['codigo' => 'SEN-AJENO']);
    }

    /**
     * Un técnico no puede registrar un dispositivo IoT en otro centro.
     */
    public function test_tecnico_no_puede_registrar_dispositivo_en_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), [
            'codigo' => 'DIS-AJENO',
            'nombre' => 'Dispositivo de otro centro',
            'estado' => 'activo',
            'centros_salud_id' => $centroB->id,
        ])->assertSessionHasErrors('centros_salud_id');

        $this->assertDatabaseMissing('dispositivos_iot', ['codigo' => 'DIS-AJENO']);
    }

    /**
     * Un técnico no puede registrar mantenimientos sobre bombas de otro centro.
     */
    public function test_tecnico_no_puede_registrar_mantenimiento_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $tecnico = User::factory()->create(['centros_salud_id' => $centroA->id]);
        $bombaB = $this->crearBomba($centroB);

        $this->actingAs($tecnico)->post(route('mantenimientos.store'), [
            'tipo' => 'preventivo',
            'fecha' => now()->toDateString(),
            'bombas_id' => $bombaB->id,
        ])->assertSessionHasErrors('bombas_id');

        $this->assertDatabaseCount('mantenimientos', 0);
    }

    /**
     * Una lectura fuera de rango debe generar la lectura y su alerta
     * (ambas dentro de la misma transacción).
     */
    public function test_lectura_fuera_de_rango_genera_alerta(): void
    {
        $admin = User::factory()->administrador()->create();
        $centro = $this->crearCentro();
        $bomba = $this->crearBomba($centro);
        $dispositivo = $this->crearDispositivo($centro);
        $sensor = $this->crearSensor($bomba, $dispositivo); // rango 10..100, tipo nivel

        $this->actingAs($admin)->post(route('lecturas.store'), [
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
     * Una lectura dentro de rango no debe generar alertas.
     */
    public function test_lectura_dentro_de_rango_no_genera_alerta(): void
    {
        $admin = User::factory()->administrador()->create();
        $centro = $this->crearCentro();
        $bomba = $this->crearBomba($centro);
        $dispositivo = $this->crearDispositivo($centro);
        $sensor = $this->crearSensor($bomba, $dispositivo);

        $this->actingAs($admin)->post(route('lecturas.store'), [
            'sensores_id' => $sensor->id,
            'valor_medido' => 50,
        ])->assertRedirect(route('lecturas.index'));

        $this->assertDatabaseHas('lecturas', ['sensores_id' => $sensor->id]);
        $this->assertDatabaseCount('alertas', 0);
    }

    /**
     * Un administrador no puede eliminar su propia cuenta.
     */
    public function test_administrador_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('usuarios', ['id' => $admin->id]);
    }

    /**
     * El director puede consultar el listado de bombas, pero solo ve las de su
     * centro y sin botones de gestión (solo lectura).
     */
    public function test_director_ve_solo_las_bombas_de_su_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        $bombaA = $this->crearBomba($centroA);
        $bombaB = $this->crearBomba($centroB);

        $response = $this->actingAs($director)->get(route('bombas.index'));

        $response->assertSuccessful();
        $response->assertSee($bombaA->codigo);
        $response->assertDontSee($bombaB->codigo);
        $response->assertDontSee('Nueva Bomba');
    }

    /**
     * El director no puede abrir el detalle de una bomba de otro centro.
     */
    public function test_director_no_puede_ver_bomba_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);
        $bombaB = $this->crearBomba($centroB);

        $this->actingAs($director)->get(route('bombas.show', $bombaB))->assertForbidden();
    }

    /**
     * El director no puede crear, editar ni eliminar bombas (solo lectura).
     */
    public function test_director_no_puede_gestionar_bombas(): void
    {
        $centroA = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);
        $bombaA = $this->crearBomba($centroA);

        $this->actingAs($director)->post(route('bombas.store'), [])->assertForbidden();
        $this->actingAs($director)->get(route('bombas.edit', $bombaA))->assertForbidden();
        $this->actingAs($director)->delete(route('bombas.destroy', $bombaA))->assertForbidden();

        $this->assertDatabaseHas('bombas', ['id' => $bombaA->id]);
    }

    /**
     * El botón "Reportar Problema" debe estar visible para el director tanto en
     * el Dashboard (accesos rápidos) como en el listado de alertas.
     */
    public function test_director_ve_el_boton_para_reportar_problema(): void
    {
        $centro = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $this->actingAs($director)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSee('Reportar Problema')
            ->assertSee(route('alertas.create'));

        $this->actingAs($director)->get(route('alertas.index'))
            ->assertSuccessful()
            ->assertSee('Reportar Problema');
    }

    /**
     * El desplegable de bombas del formulario de alerta solo muestra las bombas
     * del centro del usuario no-administrador.
     */
    public function test_formulario_de_alerta_solo_muestra_bombas_del_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);

        $bombaA = $this->crearBomba($centroA);
        $bombaB = $this->crearBomba($centroB);

        $this->actingAs($director)->get(route('alertas.create'))
            ->assertSuccessful()
            ->assertSee($bombaA->codigo)
            ->assertDontSee($bombaB->codigo);
    }

    /**
     * Si el centro del usuario aún no tiene bombas, el formulario de alerta
     * muestra un aviso en lugar de un formulario que no se puede enviar.
     */
    public function test_formulario_de_alerta_muestra_aviso_si_el_centro_no_tiene_bombas(): void
    {
        $centro = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centro->id]);

        $this->actingAs($director)->get(route('alertas.create'))
            ->assertSuccessful()
            ->assertSee('no tiene bombas registradas')
            ->assertDontSee('name="descripcion"', false);
    }

    /**
     * Los formularios operativos del tecnico deben mostrar su accion principal
     * de forma visible para evitar pantallas sin boton de registro/guardado.
     */
    public function test_tecnico_ve_el_boton_principal_en_formulario_de_mantenimiento(): void
    {
        $centro = $this->crearCentro();
        $tecnico = User::factory()->create(['rol' => User::ROL_TECNICO, 'centros_salud_id' => $centro->id]);
        $this->crearBomba($centro);

        $this->actingAs($tecnico)->get(route('mantenimientos.create'))
            ->assertSuccessful()
            ->assertSee('Guardar Mantenimiento');
    }

    /**
     * Un no-administrador no puede reportar una alerta sobre una bomba de otro centro.
     */
    public function test_director_no_puede_reportar_alerta_de_otro_centro(): void
    {
        $centroA = $this->crearCentro();
        $centroB = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);
        $bombaB = $this->crearBomba($centroB);

        $this->actingAs($director)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Intento sobre bomba ajena',
            'bombas_id' => $bombaB->id,
        ])->assertSessionHasErrors('bombas_id');

        $this->assertDatabaseCount('alertas', 0);
    }

    /**
     * El director sí puede reportar una alerta sobre una bomba de su centro.
     */
    public function test_director_puede_reportar_alerta_de_su_centro(): void
    {
        $centroA = $this->crearCentro();
        $director = User::factory()->create(['rol' => User::ROL_DIRECTOR, 'centros_salud_id' => $centroA->id]);
        $bombaA = $this->crearBomba($centroA);

        $this->actingAs($director)->post(route('alertas.store'), [
            'tipo' => 'nivel_alto',
            'descripcion' => 'Reporte del director',
            'bombas_id' => $bombaA->id,
        ])->assertRedirect(route('alertas.index'));

        $this->assertDatabaseHas('alertas', [
            'bombas_id' => $bombaA->id,
            'tipo' => 'nivel_alto',
        ]);
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

    private function crearSensor(Bomba $bomba, DispositivoIot $dispositivo): Sensor
    {
        return Sensor::create([
            'codigo' => 'SEN-'.Str::upper(Str::random(8)),
            'nombre' => 'Sensor '.Str::random(5),
            'tipo' => 'nivel',
            'valor_minimo' => 10,
            'valor_maximo' => 100,
            'dispositivos_iot_id' => $dispositivo->id,
            'bombas_id' => $bomba->id,
        ]);
    }

    private function crearAlerta(Bomba $bomba, ?Sensor $sensor = null): Alerta
    {
        return Alerta::create([
            'tipo' => 'nivel_alto',
            'descripcion' => 'Alerta de prueba',
            'fecha_hora' => now(),
            'atendida' => false,
            'bombas_id' => $bomba->id,
            'sensores_id' => $sensor?->id,
        ]);
    }
}
