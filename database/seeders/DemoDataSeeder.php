<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Models\Lectura;
use App\Models\Mantenimiento;
use App\Models\Sensor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga un conjunto de datos FICTICIOS y relacionados para probar el
 * monitoreo y los futuros reportes (periodos diarios, semanales, mensuales
 * y filtros por fecha y centro de salud).
 *
 * Caracteristicas de seguridad:
 *  - Es IDEMPOTENTE: se puede ejecutar varias veces sin duplicar registros.
 *  - NO borra, trunca ni sobrescribe datos existentes.
 *  - NO modifica el rol, la contrasena ni el correo de usuarios existentes
 *    (solo rellena 'ultimo_acceso' de las cuentas DEMO si esta vacio).
 *  - Rellena campos vacios de los registros DEMO (api_key del dispositivo,
 *    ultimo_acceso del usuario y proximo_mantenimiento) sin tocar otros datos.
 *  - Todo lo creado lleva el prefijo "DEMO-" o la marca "[DEMO]" para
 *    distinguirlo de datos reales.
 *  - Se niega a ejecutarse en el entorno de produccion.
 *
 * Uso:  php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /** Fin del historial simulado. Fijo para que el seeder sea reproducible. */
    private const FECHA_FIN = '2026-10-15 20:00:00';

    /** Dias de historial de lecturas (centros originales). */
    private const DIAS_HISTORIAL = 90;

    /**
     * Centros agregados despues usan una ventana mas corta para mantener el
     * volumen de datos al minimo. centro => dias de historial.
     */
    private const DIAS_HISTORIAL_CENTRO = [
        'DEMO-CS-04' => 30,
        'CS-001' => 30,
    ];

    /** Horas entre lecturas consecutivas de un mismo sensor. */
    private const INTERVALO_HORAS = 4;

    /** Contrasena de las cuentas DEMO (documentada en el informe). */
    public const PASSWORD_DEMO = 'Demo#2026';

    /** Limite de filas por INSERT masivo. */
    private const TAMANO_LOTE = 500;

    /**
     * Prefijo de la clave en texto plano de los dispositivos DEMO. El valor
     * que se guarda en 'api_key' es su SHA-256 (igual que AuthenticateDevice).
     * Sirve para probar la API IoT:  X-Device-Key: DEMO-DEVICE-KEY-DEMO-IOT-01
     */
    private const PREFIJO_API_KEY = 'DEMO-DEVICE-KEY-';

    /**
     * Especificacion de cada tipo de sensor (rangos = umbrales configurados
     * por el seeder, ya que la tabla 'sensores' estaba vacia):
     * tipo => [unidad, min, max, precision, valor_base, amplitud, fase_horaria].
     */
    private const SPEC_SENSORES = [
        'nivel' => ['%', 30, 85, 0.10, 60, 12, 0],
        'temperatura' => ['°C', 20, 75, 0.10, 45, 8, 9],
        'corriente' => ['A', 2, 12, 0.01, 7, 2, 8],
        'presion' => ['bar', 1, 6, 0.01, 3.2, 1, 8],
        'vibracion' => ['mm/s', 0.5, 7, 0.01, 3, 1.2, 8],
    ];

    /** Codigo corto por tipo de sensor, usado en el codigo del sensor. */
    private const CODIGO_SENSOR = [
        'nivel' => 'NIV',
        'temperatura' => 'TEMP',
        'corriente' => 'CORR',
        'presion' => 'PRES',
        'vibracion' => 'VIB',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DemoDataSeeder no se ejecuta en produccion. Cancelado.');

            return;
        }

        // Semilla fija de mt_rand para que los valores sean reproducibles.
        mt_srand(20261016);

        DB::transaction(function () {
            $centros = $this->crearCentros();
            $usuarios = $this->crearUsuariosDemo($centros);
            $dispositivos = $this->crearDispositivos($centros);
            $bombas = $this->crearBombas($centros);
            $sensores = $this->crearSensores($bombas, $dispositivos);
            $this->generarLecturasYAlertas($sensores, $usuarios);
            $this->crearAlertasManuales($bombas, $sensores);
            $this->crearMantenimientos($bombas, $sensores, $usuarios);
        });

        $this->mostrarResumen();
    }

    // ------------------------------------------------------------------
    // Centros, usuarios y dispositivos
    // ------------------------------------------------------------------

    /**
     * @return array<string, CentroSalud>
     */
    private function crearCentros(): array
    {
        $definicion = [
            'DEMO-CS-01' => ['nombre' => 'Centro de Salud Demo Norte', 'direccion' => 'Av. Demo Norte #100, Cercado', 'telefono' => '4400001'],
            'DEMO-CS-02' => ['nombre' => 'Centro de Salud Demo Sur', 'direccion' => 'Calle Demo Sur #200, Cercado', 'telefono' => '4400002'],
            'DEMO-CS-03' => ['nombre' => 'Centro de Salud Demo Valle', 'direccion' => 'Av. Demo Valle #300, Cercado', 'telefono' => '4400003'],
            'DEMO-CS-04' => ['nombre' => 'Centro de Salud Demo Este', 'direccion' => 'Av. Demo Este #400, Cercado', 'telefono' => '4400004'],
        ];

        $centros = [];
        foreach ($definicion as $codigo => $datos) {
            $centros[$codigo] = CentroSalud::firstOrCreate(['codigo' => $codigo], $datos);
        }

        // El centro real CS-001 tambien recibe datos DEMO, para poder probar el
        // rol Director (hasta ahora su centro no tenia bombas y no podia
        // reportar problemas). NO se crea ni se modifica: solo se reutiliza si
        // ya existe.
        $principal = CentroSalud::where('codigo', 'CS-001')->first();
        if ($principal !== null) {
            $centros['CS-001'] = $principal;
        }

        return $centros;
    }

    /**
     * Crea un tecnico y un director DEMO por cada centro DEMO (no toca usuarios
     * reales; el centro CS-001 conserva sus usuarios propios).
     *
     * @param  array<string, CentroSalud>  $centros
     * @return array<string, array{tecnico: User, director: User}>
     */
    private function crearUsuariosDemo(array $centros): array
    {
        $usuarios = [];
        $n = 0;

        foreach ($centros as $codigo => $centro) {
            // Solo los centros DEMO llevan cuentas DEMO.
            if (! str_starts_with($codigo, 'DEMO-')) {
                continue;
            }

            $n++;

            $tecnico = User::firstOrCreate(
                ['correo' => "demo.tecnico{$n}@demo.local"],
                [
                    'nombre' => 'Tecnico Demo',
                    'apellido' => "Centro {$n}",
                    'telefono' => "700000{$n}",
                    'password' => self::PASSWORD_DEMO,
                    'rol' => User::ROL_TECNICO,
                    'estado' => true,
                    'centros_salud_id' => $centro->id,
                ]
            );

            $director = User::firstOrCreate(
                ['correo' => "demo.director{$n}@demo.local"],
                [
                    'nombre' => 'Director Demo',
                    'apellido' => "Centro {$n}",
                    'telefono' => "710000{$n}",
                    'password' => self::PASSWORD_DEMO,
                    'rol' => User::ROL_DIRECTOR,
                    'estado' => true,
                    'centros_salud_id' => $centro->id,
                ]
            );

            // Rellenar 'ultimo_acceso' si esta vacio (idempotente) con una fecha
            // dentro de la ventana del historial, distinta por cuenta.
            foreach (['tecnico' => $tecnico, 'director' => $director] as $rol => $usuario) {
                if ($usuario->ultimo_acceso === null) {
                    $usuario->ultimo_acceso = Carbon::parse(self::FECHA_FIN)
                        ->subHours(($n * 4) + ($rol === 'director' ? 2 : 0) + 1);
                    $usuario->save();
                }
            }

            $usuarios[$codigo] = ['tecnico' => $tecnico, 'director' => $director];
        }

        return $usuarios;
    }

    /**
     * @param  array<string, CentroSalud>  $centros
     * @return array<string, DispositivoIot>
     */
    private function crearDispositivos(array $centros): array
    {
        $dispositivos = [];
        $n = 0;

        foreach ($centros as $codigo => $centro) {
            $n++;
            $dispositivo = DispositivoIot::firstOrCreate(
                ['codigo' => sprintf('DEMO-IOT-%02d', $n)],
                [
                    'nombre' => "Controlador Demo {$n}",
                    'modelo' => 'ESP32-DEV',
                    'tipo_dispositivo' => 'controlador',
                    'puerto' => 8080,
                    'direccion_ip' => "192.168.100.{$n}",
                    'mac_address' => sprintf('DE:MO:0%d:00:00:%02d', 1, $n),
                    'firmware' => '1.0.0-demo',
                    'estado' => 'activo',
                    'centros_salud_id' => $centro->id,
                ]
            );

            // Rellenar 'api_key' si esta vacia (idempotente). El valor guardado
            // es el SHA-256 de la clave en texto plano, igual que hace
            // AuthenticateDevice al comparar la cabecera X-Device-Key.
            if ($dispositivo->api_key === null) {
                $dispositivo->api_key = hash('sha256', self::PREFIJO_API_KEY.$dispositivo->codigo);
                $dispositivo->save();
            }

            $dispositivos[$codigo] = $dispositivo;
        }

        return $dispositivos;
    }

    // ------------------------------------------------------------------
    // Bombas y sensores
    // ------------------------------------------------------------------

    /**
     * @param  array<string, CentroSalud>  $centros
     * @return array<string, Bomba>
     */
    private function crearBombas(array $centros): array
    {
        $definicion = [
            ['codigo' => 'DEMO-BOM-01', 'nombre' => 'Bomba Demo Norte 1', 'centro' => 'DEMO-CS-01', 'numero' => 1, 'encendido' => true, 'estado' => 'activo'],
            ['codigo' => 'DEMO-BOM-02', 'nombre' => 'Bomba Demo Norte 2 (mantenimiento)', 'centro' => 'DEMO-CS-01', 'numero' => 2, 'encendido' => false, 'estado' => 'mantenimiento'],
            ['codigo' => 'DEMO-BOM-03', 'nombre' => 'Bomba Demo Sur 1', 'centro' => 'DEMO-CS-02', 'numero' => 3, 'encendido' => true, 'estado' => 'activo'],
            ['codigo' => 'DEMO-BOM-04', 'nombre' => 'Bomba Demo Valle 1', 'centro' => 'DEMO-CS-03', 'numero' => 4, 'encendido' => true, 'estado' => 'activo'],
            ['codigo' => 'DEMO-BOM-05', 'nombre' => 'Bomba Demo Este 1', 'centro' => 'DEMO-CS-04', 'numero' => 5, 'encendido' => true, 'estado' => 'activo'],
            ['codigo' => 'DEMO-BOM-06', 'nombre' => 'Bomba Demo Este 2', 'centro' => 'DEMO-CS-04', 'numero' => 6, 'encendido' => true, 'estado' => 'activo'],
            // Bomba DEMO dentro del centro real CS-001: da al rol Director algo
            // sobre lo que reportar problemas en su propio centro.
            ['codigo' => 'DEMO-BOM-07', 'nombre' => 'Bomba Demo Centro Principal', 'centro' => 'CS-001', 'numero' => 7, 'encendido' => true, 'estado' => 'activo'],
        ];

        $bombas = [];
        foreach ($definicion as $def) {
            $numero = $def['numero'];
            $bombas[$def['codigo']] = Bomba::firstOrCreate(
                ['codigo' => $def['codigo']],
                [
                    'nombre' => $def['nombre'],
                    'marca' => 'DemoPumps',
                    'modelo' => 'DP-100',
                    'serie' => sprintf('DEMO-SER-%02d', $numero),
                    'potencia_hp' => 5.50,
                    'voltaje_nominal' => 380.00,
                    'corriente_nominal' => 11.50,
                    'caudal_min' => 40.00,
                    'caudal_max' => 120.00,
                    'altura_maxima' => 60.00,
                    'temperatura_maxima' => 80.00,
                    'presion_maxima' => 6.00,
                    'fecha_instalacion' => '2026-04-01',
                    'modo_operacion' => $def['encendido'], // 1 automatico, 0 manual
                    'encendido' => $def['encendido'],
                    'estado' => $def['estado'],
                    'tanque_codigo' => sprintf('DEMO-TK-%02d', $numero),
                    'tanque_capacidad_litros' => 5000.00,
                    'tanque_altura_metros' => 3.50,
                    'tanque_diametro_metros' => 2.20,
                    'observaciones' => '[DEMO] Bomba ficticia creada para pruebas de reportes.',
                    'centros_salud_id' => $centros[$def['centro']]->id,
                ]
            );
        }

        return $bombas;
    }

    /**
     * Crea los sensores de cada bomba. Los tipos 'corriente' y 'vibracion'
     * solo se asignan a bombas encendidas (regla del sistema).
     *
     * @param  array<string, Bomba>  $bombas
     * @param  array<string, DispositivoIot>  $dispositivos
     * @return array<int, array<string, mixed>>
     */
    private function crearSensores(array $bombas, array $dispositivos): array
    {
        $definicion = [
            ['bomba' => 'DEMO-BOM-01', 'dispositivo' => 'DEMO-CS-01', 'tipos' => ['nivel', 'temperatura', 'corriente', 'presion', 'vibracion']],
            ['bomba' => 'DEMO-BOM-02', 'dispositivo' => 'DEMO-CS-01', 'tipos' => ['nivel', 'presion']],
            ['bomba' => 'DEMO-BOM-03', 'dispositivo' => 'DEMO-CS-02', 'tipos' => ['nivel', 'temperatura', 'corriente', 'presion', 'vibracion']],
            ['bomba' => 'DEMO-BOM-04', 'dispositivo' => 'DEMO-CS-03', 'tipos' => ['nivel', 'temperatura', 'corriente']],
            // Centro agregado despues: sin eventos fuera de rango automaticos
            // (sus alertas se crean manualmente y son pocas).
            ['bomba' => 'DEMO-BOM-05', 'dispositivo' => 'DEMO-CS-04', 'tipos' => ['nivel', 'temperatura', 'presion'], 'eventos' => false],
            ['bomba' => 'DEMO-BOM-06', 'dispositivo' => 'DEMO-CS-04', 'tipos' => ['nivel', 'corriente', 'vibracion', 'temperatura'], 'eventos' => false],
            // Centro real CS-001: bombas de prueba para el rol Director.
            ['bomba' => 'DEMO-BOM-07', 'dispositivo' => 'CS-001', 'tipos' => ['nivel', 'temperatura', 'corriente', 'presion', 'vibracion'], 'eventos' => false],
        ];

        $sensores = [];
        $n = 0;

        foreach ($definicion as $def) {
            $bomba = $bombas[$def['bomba']];
            $dispositivo = $dispositivos[$def['dispositivo']];

            foreach ($def['tipos'] as $tipo) {
                $n++;
                [$unidad, $min, $max, $precision, $base, $amp, $fase] = self::SPEC_SENSORES[$tipo];

                $sensor = Sensor::firstOrCreate(
                    ['codigo' => sprintf('DEMO-S%02d-%s', $n, self::CODIGO_SENSOR[$tipo])],
                    [
                        'nombre' => sprintf('Sensor Demo %s - %s', $bomba->nombre, $tipo),
                        'tipo' => $tipo,
                        'marca' => 'DemoSense',
                        'modelo' => 'DS-'.strtoupper(substr($tipo, 0, 3)),
                        'unidad_medida' => $unidad,
                        'valor_minimo' => $min,
                        'valor_maximo' => $max,
                        'precision_sensor' => $precision,
                        'estado' => 'activo',
                        'fecha_instalacion' => '2026-04-01',
                        'ubicacion' => 'Sala de maquinas (demo)',
                        'dispositivos_iot_id' => $dispositivo->id,
                        'bombas_id' => $bomba->id,
                    ]
                );

                $sensores[] = [
                    'model' => $sensor,
                    'tipo' => $tipo,
                    'min' => (float) $min,
                    'max' => (float) $max,
                    'base' => (float) $base,
                    'amp' => (float) $amp,
                    'fase' => (float) $fase,
                    'unidad' => $unidad,
                    'centro' => $def['dispositivo'],
                    'dias' => self::DIAS_HISTORIAL_CENTRO[$def['dispositivo']] ?? self::DIAS_HISTORIAL,
                    'eventos' => $def['eventos'] ?? true,
                ];
            }
        }

        return $sensores;
    }

    // ------------------------------------------------------------------
    // Lecturas y alertas automaticas
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $sensores
     * @param  array<string, array{tecnico: User, director: User}>  $usuarios
     */
    private function generarLecturasYAlertas(array $sensores, array $usuarios): void
    {
        $fin = Carbon::parse(self::FECHA_FIN);

        foreach ($sensores as $sensor) {
            $modelo = $sensor['model'];

            // Idempotencia: si el sensor ya tiene lecturas, no se vuelve a cargar nada.
            if (Lectura::where('sensores_id', $modelo->id)->exists()) {
                continue;
            }

            // Cada centro puede tener una ventana de historial distinta.
            $inicio = $fin->copy()->subDays($sensor['dias']);

            [$valoresForzados, $eventos] = $sensor['eventos']
                ? $this->planificarEventos($sensor, $inicio)
                : [[], []];

            $filas = [];
            $cursor = $inicio->copy();
            while ($cursor->lte($fin)) {
                $clave = $cursor->format('Y-m-d H:i:s');
                $valor = $valoresForzados[$clave] ?? $this->valorNormal($sensor, $cursor);

                $filas[] = [
                    'sensores_id' => $modelo->id,
                    'valor_medido' => $valor,
                    'fecha_hora' => $clave,
                    'created_at' => $clave,
                ];

                $cursor->addHours(self::INTERVALO_HORAS);
            }

            foreach (array_chunk($filas, self::TAMANO_LOTE) as $lote) {
                DB::table('lecturas')->insert($lote);
            }

            $this->crearAlertasDeEventos($sensor, $eventos, $usuarios[$sensor['centro']]['tecnico'] ?? null);
        }
    }

    /**
     * Determina los valores fuera de rango (que generaran alertas) por sensor.
     *
     * @param  array<string, mixed>  $sensor
     * @return array{0: array<string, float>, 1: array<int, array<string, mixed>>}
     */
    private function planificarEventos(array $sensor, Carbon $inicio): array
    {
        // 'presion' se omite a proposito: su fuera de rango caeria en la
        // alerta generica 'falla_electrica' y no es coherente. Solo se generan
        // eventos para los tipos con una alerta propia definida en el sistema.
        if (! in_array($sensor['tipo'], ['nivel', 'temperatura', 'corriente', 'vibracion'], true)) {
            return [[], []];
        }

        $valoresForzados = [];
        $eventos = [];

        // Los eventos se ubican al 24% y 74% de la ventana del sensor, de modo
        // que funcionan tanto para historiales de 90 dias como de 30.
        $dias = $sensor['dias'] ?? self::DIAS_HISTORIAL;

        foreach ([0.24, 0.74] as $indice => $fraccion) {
            $ts = $inicio->copy()->addDays((int) round($dias * $fraccion))->setTime(12, 0, 0);

            $direccion = ($sensor['tipo'] === 'nivel' && $indice === 1) ? 'bajo' : 'alto';
            $valor = $direccion === 'alto'
                ? round($sensor['max'] * 1.08, 3)
                : round($sensor['min'] * 0.75, 3);

            $clave = $ts->format('Y-m-d H:i:s');
            $valoresForzados[$clave] = $valor;
            $eventos[] = ['ts' => $ts, 'valor' => $valor, 'direccion' => $direccion];
        }

        return [$valoresForzados, $eventos];
    }

    /**
     * Valor normal (dentro de rango) para una lectura: base + onda diaria + ruido,
     * recortado para no salir del rango permitido.
     *
     * @param  array<string, mixed>  $sensor
     */
    private function valorNormal(array $sensor, Carbon $ts): float
    {
        $hora = (int) $ts->format('G') + ((int) $ts->format('i')) / 60;
        $valor = $sensor['base']
            + $sensor['amp'] * sin(2 * M_PI * ($hora - $sensor['fase']) / 24)
            + (mt_rand(-100, 100) / 1000) * ($sensor['max'] - $sensor['min']) * 0.03;

        // Margen del 5% para garantizar que la lectura normal quede dentro de rango.
        $margen = ($sensor['max'] - $sensor['min']) * 0.05;
        $valor = max($sensor['min'] + $margen, min($sensor['max'] - $margen, $valor));

        return round($valor, 3);
    }

    /**
     * Crea las alertas generadas por los eventos fuera de rango, respetando la
     * regla del sistema de no dejar mas de una alerta PENDIENTE del mismo tipo
     * por sensor.
     *
     * @param  array<string, mixed>  $sensor
     * @param  array<int, array<string, mixed>>  $eventos
     */
    private function crearAlertasDeEventos(array $sensor, array $eventos, ?User $tecnico = null): void
    {
        if ($eventos === []) {
            return;
        }

        // Agrupar por tipo de alerta resultante.
        $porTipo = [];
        foreach ($eventos as $evento) {
            $tipoAlerta = $this->mapearTipoAlerta($sensor['tipo'], $evento['direccion']);
            $porTipo[$tipoAlerta][] = $evento;
        }

        foreach ($porTipo as $tipoAlerta => $lista) {
            usort($lista, fn ($a, $b) => $a['ts']->timestamp <=> $b['ts']->timestamp);

            foreach ($lista as $i => $evento) {
                $esUltimo = ($i === count($lista) - 1);
                // Los eventos anteriores ya se atendieron; el ultimo queda
                // pendiente el 60% de las veces (nunca mas de uno pendiente).
                $atendida = ! $esUltimo || (mt_rand(0, 99) < 40);

                Alerta::create([
                    'tipo' => $tipoAlerta,
                    'descripcion' => sprintf(
                        '[DEMO] Valor %s detectado: %s %s (rango normal: %s a %s %s)',
                        $evento['direccion'] === 'alto' ? 'alto' : 'bajo',
                        $evento['valor'],
                        $sensor['unidad'],
                        $sensor['min'],
                        $sensor['max'],
                        $sensor['unidad']
                    ),
                    'valor_detectado' => $evento['valor'],
                    'valor_permitido' => $evento['direccion'] === 'alto' ? $sensor['max'] : $sensor['min'],
                    'fecha_hora' => $evento['ts'],
                    'atendida' => $atendida,
                    'fecha_atencion' => $atendida ? $evento['ts']->copy()->addHours(2) : null,
                    'usuario_atencion_id' => $atendida ? $tecnico->id : null,
                    'sensores_id' => $sensor['model']->id,
                    'bombas_id' => $sensor['model']->bombas_id,
                ]);
            }
        }
    }

    /**
     * Misma logica de mapeo que usan LecturaController y DispositivoApiController.
     */
    private function mapearTipoAlerta(string $tipoSensor, string $direccion): string
    {
        return match (true) {
            $tipoSensor === 'nivel' && $direccion === 'alto' => 'nivel_alto',
            $tipoSensor === 'nivel' && $direccion === 'bajo' => 'nivel_bajo',
            $tipoSensor === 'temperatura' && $direccion === 'alto' => 'temperatura_alta',
            $tipoSensor === 'corriente' && $direccion === 'alto' => 'sobrecorriente',
            $tipoSensor === 'vibracion' && $direccion === 'alto' => 'vibracion_alta',
            default => 'falla_electrica',
        };
    }

    // ------------------------------------------------------------------
    // Alertas manuales y mantenimientos
    // ------------------------------------------------------------------

    /**
     * Alertas de tipos que en el sistema se reportan manualmente
     * (no se derivan de un valor fuera de rango).
     *
     * @param  array<string, Bomba>  $bombas
     * @param  array<int, array<string, mixed>>  $sensores
     */
    private function crearAlertasManuales(array $bombas, array $sensores): void
    {
        $tecnicoCentro1 = User::where('correo', 'demo.tecnico1@demo.local')->first();

        // Bomba apagada (esta en mantenimiento y con encendido = false).
        Alerta::firstOrCreate(
            [
                'tipo' => 'bomba_apagada',
                'bombas_id' => $bombas['DEMO-BOM-02']->id,
                'descripcion' => '[DEMO] Bomba apagada por mantenimiento programado.',
            ],
            [
                'fecha_hora' => Carbon::parse(self::FECHA_FIN)->subDays(30),
                'atendida' => true,
                'fecha_atencion' => Carbon::parse(self::FECHA_FIN)->subDays(30)->addHours(3),
                'usuario_atencion_id' => $tecnicoCentro1?->id,
            ]
        );

        // Sensor desconectado (simulado) sobre un sensor de vibracion.
        $sensorVibracion = collect($sensores)->firstWhere('tipo', 'vibracion');
        if ($sensorVibracion !== null) {
            Alerta::firstOrCreate(
                [
                    'tipo' => 'sensor_desconectado',
                    'sensores_id' => $sensorVibracion['model']->id,
                    'descripcion' => '[DEMO] Sensor desconectado (simulado).',
                ],
                [
                    'fecha_hora' => Carbon::parse(self::FECHA_FIN)->subDays(10),
                    'atendida' => false,
                    'bombas_id' => $sensorVibracion['model']->bombas_id,
                ]
            );
        }

        $this->crearAlertasManualesCentroExtra($bombas);
    }

    /**
     * Alertas manuales del centro agregado (DEMO-CS-04): pocas, con variedad
     * de tipos y de estados (pendiente / atendida).
     *
     * @param  array<string, Bomba>  $bombas
     */
    private function crearAlertasManualesCentroExtra(array $bombas): void
    {
        $tecnico = User::where('correo', 'demo.tecnico4@demo.local')->first();
        $sensorDe = fn (Bomba $bomba, string $tipo) => Sensor::where('bombas_id', $bomba->id)
            ->where('tipo', $tipo)
            ->first();

        $definicion = [
            [
                'tipo' => 'nivel_bajo', 'bomba' => 'DEMO-BOM-05', 'sensor_tipo' => 'nivel',
                'valor' => 22.500, 'permitido' => 30.000, 'dias_atras' => 25, 'atendida' => true,
                'descripcion' => '[DEMO] Nivel bajo de tanque reportado por el director (revisado).',
            ],
            [
                'tipo' => 'temperatura_alta', 'bomba' => 'DEMO-BOM-06', 'sensor_tipo' => 'temperatura',
                'valor' => 78.000, 'permitido' => 75.000, 'dias_atras' => 18, 'atendida' => false,
                'descripcion' => '[DEMO] Temperatura alta detectada en la bomba (pendiente).',
            ],
            [
                'tipo' => 'sobrecorriente', 'bomba' => 'DEMO-BOM-06', 'sensor_tipo' => 'corriente',
                'valor' => 12.800, 'permitido' => 12.000, 'dias_atras' => 11, 'atendida' => true,
                'descripcion' => '[DEMO] Sobrecorriente corregida tras ajuste del tablero electrico.',
            ],
            [
                'tipo' => 'falla_electrica', 'bomba' => 'DEMO-BOM-05', 'sensor_tipo' => null,
                'valor' => 0.000, 'permitido' => 0.000, 'dias_atras' => 4, 'atendida' => false,
                'descripcion' => '[DEMO] Falla electrica reportada manualmente (pendiente).',
            ],
        ];

        foreach ($definicion as $def) {
            $bomba = $bombas[$def['bomba']];
            $sensor = $def['sensor_tipo'] !== null ? $sensorDe($bomba, $def['sensor_tipo']) : null;
            $fechaHora = Carbon::parse(self::FECHA_FIN)->subDays($def['dias_atras']);

            $alerta = Alerta::firstOrCreate(
                [
                    'tipo' => $def['tipo'],
                    'bombas_id' => $bomba->id,
                    'descripcion' => $def['descripcion'],
                ],
                [
                    'valor_detectado' => $def['valor'],
                    'valor_permitido' => $def['permitido'],
                    'fecha_hora' => $fechaHora,
                    'atendida' => $def['atendida'],
                    'fecha_atencion' => $def['atendida'] ? $fechaHora->copy()->addHours(3) : null,
                    'usuario_atencion_id' => $def['atendida'] ? $tecnico?->id : null,
                    'sensores_id' => $sensor?->id,
                ]
            );

            // Rellenar 'sensores_id' en alertas que ya existian sin sensor
            // (idempotente), por ejemplo si el sensor se agrego despues.
            if ($alerta->sensores_id === null && $sensor !== null) {
                $alerta->sensores_id = $sensor->id;
                $alerta->save();
            }
        }
    }

    /**
     * @param  array<string, Bomba>  $bombas
     * @param  array<int, array<string, mixed>>  $sensores
     * @param  array<string, array{tecnico: User, director: User}>  $usuarios
     */
    private function crearMantenimientos(array $bombas, array $sensores, array $usuarios): void
    {
        $inicio = Carbon::parse(self::FECHA_FIN)->subDays(self::DIAS_HISTORIAL);

        $definicion = [
            ['bomba' => 'DEMO-BOM-01', 'centro' => 'DEMO-CS-01', 'tipo' => 'preventivo', 'dia' => 20, 'descripcion' => '[DEMO] Mantenimiento preventivo trimestral.'],
            ['bomba' => 'DEMO-BOM-02', 'centro' => 'DEMO-CS-01', 'tipo' => 'correctivo', 'dia' => 30, 'descripcion' => '[DEMO] Reparacion de bomba en mantenimiento.', 'alerta' => ['bomba_apagada', 'DEMO-BOM-02']],
            ['bomba' => 'DEMO-BOM-03', 'centro' => 'DEMO-CS-02', 'tipo' => 'preventivo', 'dia' => 25, 'descripcion' => '[DEMO] Mantenimiento preventivo semestral.'],
            ['bomba' => 'DEMO-BOM-03', 'centro' => 'DEMO-CS-02', 'tipo' => 'predictivo', 'dia' => 60, 'descripcion' => '[DEMO] Analisis predictivo por vibracion fuera de rango.', 'alerta' => ['vibracion_alta', 'DEMO-BOM-03']],
            ['bomba' => 'DEMO-BOM-04', 'centro' => 'DEMO-CS-03', 'tipo' => 'preventivo', 'dia' => 35, 'descripcion' => '[DEMO] Mantenimiento preventivo programado.'],
            ['bomba' => 'DEMO-BOM-04', 'centro' => 'DEMO-CS-03', 'tipo' => 'emergencia', 'dia' => 68, 'descripcion' => '[DEMO] Atencion de emergencia por temperatura alta.', 'alerta' => ['temperatura_alta', 'DEMO-BOM-04']],
            ['bomba' => 'DEMO-BOM-05', 'centro' => 'DEMO-CS-04', 'tipo' => 'preventivo', 'dia' => 80, 'descripcion' => '[DEMO] Mantenimiento preventivo tras reporte de nivel bajo.', 'alerta' => ['nivel_bajo', 'DEMO-BOM-05']],
            ['bomba' => 'DEMO-BOM-06', 'centro' => 'DEMO-CS-04', 'tipo' => 'correctivo', 'dia' => 87, 'descripcion' => '[DEMO] Correccion por temperatura alta en la bomba.', 'alerta' => ['temperatura_alta', 'DEMO-BOM-06']],
        ];

        foreach ($definicion as $def) {
            $bomba = $bombas[$def['bomba']];
            $alertaId = null;

            if (isset($def['alerta'])) {
                [$tipoAlerta, $codigoBomba] = $def['alerta'];
                $alerta = Alerta::where('tipo', $tipoAlerta)
                    ->where('bombas_id', $bombas[$codigoBomba]->id)
                    ->orderBy('fecha_hora')
                    ->first();
                $alertaId = $alerta?->id;
            }

            $mantenimiento = Mantenimiento::firstOrCreate(
                ['bombas_id' => $bomba->id, 'descripcion' => $def['descripcion']],
                [
                    'tipo' => $def['tipo'],
                    'fecha' => $inicio->copy()->addDays($def['dia'])->toDateString(),
                    'observaciones' => '[DEMO] Registro ficticio para pruebas.',
                    'proximo_mantenimiento' => $inicio->copy()->addDays($def['dia'] + 90)->toDateString(),
                    'usuarios_id' => $usuarios[$def['centro']]['tecnico']->id,
                    'alertas_id' => $alertaId,
                ]
            );

            // Rellenar 'proximo_mantenimiento' en registros ya existentes que
            // quedaron sin ese dato (idempotente).
            if ($mantenimiento->proximo_mantenimiento === null) {
                $mantenimiento->proximo_mantenimiento = $inicio->copy()
                    ->addDays($def['dia'] + 90)->toDateString();
                $mantenimiento->save();
            }
        }
    }

    // ------------------------------------------------------------------
    // Resumen
    // ------------------------------------------------------------------

    private function mostrarResumen(): void
    {
        $this->command->info('Datos DEMO cargados (idempotente). Totales en la base de datos:');

        foreach ([
            'centros_salud' => CentroSalud::class,
            'usuarios' => User::class,
            'dispositivos_iot' => DispositivoIot::class,
            'bombas' => Bomba::class,
            'sensores' => Sensor::class,
            'lecturas' => Lectura::class,
            'alertas' => Alerta::class,
            'mantenimientos' => Mantenimiento::class,
        ] as $tabla => $modelo) {
            $this->command->line(sprintf('  %-18s %d', $tabla, $modelo::count()));
        }

        $this->command->newLine();
        $this->command->info('Claves de API de los dispositivos DEMO (cabecera X-Device-Key):');
        DispositivoIot::where('codigo', 'like', 'DEMO-IOT-%')->orderBy('codigo')->get()
            ->each(function (DispositivoIot $dispositivo) {
                $this->command->line(sprintf('  %-12s %s', $dispositivo->codigo, self::PREFIJO_API_KEY.$dispositivo->codigo));
            });
    }
}
