<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del calculo del umbral de atencion de 24 horas (M-3) implementado
 * en ReporteService::evaluarTiempoAtencion.
 *
 * El limite de exactamente 24 horas queda dentro del umbral; solo una duracion
 * superior se considera fuera. Los datos faltantes o incoherentes se informan
 * como "indeterminable" en lugar de producir una conclusion enganosa.
 */
class ReporteUmbralAtencionTest extends TestCase
{
    use RefreshDatabase;

    private ReporteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(ReporteService::class);
    }

    public function test_las_pruebas_se_ejecutan_contra_la_base_de_datos_aislada(): void
    {
        $this->assertSame('monitoreo_bomba_test', DB::connection()->getDatabaseName());
    }

    public function test_alerta_atendida_dentro_del_umbral(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaAtendida($inicio, $inicio->copy()->addHours(5));

        $this->assertSame(
            ReporteService::ATENCION_ATENDIDA_DENTRO,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_alerta_atendida_exactamente_en_el_limite_de_24_horas_queda_dentro(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaAtendida($inicio, $inicio->copy()->addHours(24));

        $this->assertSame(
            ReporteService::ATENCION_ATENDIDA_DENTRO,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_alerta_atendida_despues_del_umbral(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaAtendida($inicio, $inicio->copy()->addHours(25));

        $this->assertSame(
            ReporteService::ATENCION_ATENDIDA_FUERA,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_alerta_pendiente_dentro_del_umbral(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaPendiente($inicio);

        $this->assertSame(
            ReporteService::ATENCION_PENDIENTE_DENTRO,
            $this->servicio->evaluarTiempoAtencion($alerta, $inicio->copy()->addHours(10))
        );
    }

    public function test_alerta_pendiente_exactamente_en_el_limite_de_24_horas_queda_dentro(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaPendiente($inicio);

        $this->assertSame(
            ReporteService::ATENCION_PENDIENTE_DENTRO,
            $this->servicio->evaluarTiempoAtencion($alerta, $inicio->copy()->addHours(24))
        );
    }

    public function test_alerta_pendiente_despues_del_umbral(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaPendiente($inicio);

        $this->assertSame(
            ReporteService::ATENCION_PENDIENTE_FUERA,
            $this->servicio->evaluarTiempoAtencion($alerta, $inicio->copy()->addHours(24)->addMinute())
        );
    }

    public function test_alerta_sin_fecha_hora_es_indeterminable(): void
    {
        $alerta = new Alerta(['atendida' => false]);

        $this->assertSame(
            ReporteService::ATENCION_INDETERMINABLE,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_alerta_atendida_sin_fecha_de_atencion_es_indeterminable(): void
    {
        $alerta = new Alerta([
            'atendida' => true,
            'fecha_hora' => Carbon::parse('2026-01-01 08:00:00'),
        ]);

        $this->assertSame(
            ReporteService::ATENCION_INDETERMINABLE,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_fecha_de_atencion_anterior_al_reporte_es_indeterminable(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = new Alerta([
            'atendida' => true,
            'fecha_hora' => $inicio,
            'fecha_atencion' => $inicio->copy()->subHours(2),
        ]);

        $this->assertSame(
            ReporteService::ATENCION_INDETERMINABLE,
            $this->servicio->evaluarTiempoAtencion($alerta)
        );
    }

    public function test_evaluar_no_modifica_el_estado_de_la_alerta(): void
    {
        $inicio = Carbon::parse('2026-01-01 08:00:00');
        $alerta = $this->alertaAtendida($inicio, $inicio->copy()->addHours(5));

        $atributosAntes = $alerta->getAttributes();
        $this->servicio->evaluarTiempoAtencion($alerta);

        $this->assertSame($atributosAntes, $alerta->getAttributes());
        $this->assertTrue($alerta->atendida);
        $this->assertTrue($inicio->copy()->addHours(5)->equalTo($alerta->fecha_atencion));
    }

    public function test_el_conteo_de_alertas_pendientes_no_cambia(): void
    {
        $centro = $this->crearCentro();
        $bomba = Bomba::create([
            'codigo' => 'B-'.Str::upper(Str::random(8)),
            'nombre' => 'Bomba '.Str::random(5),
            'serie' => 'S-'.Str::upper(Str::random(8)),
            'centros_salud_id' => $centro->id,
        ]);

        $this->crearAlerta($bomba, atendida: false);
        $this->crearAlerta($bomba, atendida: false);
        $this->crearAlerta($bomba, atendida: true);

        $paginador = $this->servicio->estadoOperativoBombas($centro, true);
        $fila = collect($paginador->items())->firstWhere('id', $bomba->id);

        $this->assertNotNull($fila);
        $this->assertSame(2, (int) $fila->alertas_pendientes_count);
    }

    private function alertaAtendida(Carbon $fechaHora, Carbon $fechaAtencion): Alerta
    {
        return new Alerta([
            'atendida' => true,
            'fecha_hora' => $fechaHora,
            'fecha_atencion' => $fechaAtencion,
        ]);
    }

    private function alertaPendiente(Carbon $fechaHora): Alerta
    {
        return new Alerta([
            'atendida' => false,
            'fecha_hora' => $fechaHora,
        ]);
    }

    private function crearCentro(): CentroSalud
    {
        return CentroSalud::factory()->create([
            'codigo' => 'CS-'.Str::upper(Str::random(6)),
        ]);
    }

    private function crearAlerta(Bomba $bomba, bool $atendida): Alerta
    {
        return Alerta::create([
            'tipo' => 'nivel_alto',
            'descripcion' => 'Alerta de prueba',
            'fecha_hora' => now(),
            'atendida' => $atendida,
            'fecha_atencion' => $atendida ? now() : null,
            'bombas_id' => $bomba->id,
        ]);
    }
}
