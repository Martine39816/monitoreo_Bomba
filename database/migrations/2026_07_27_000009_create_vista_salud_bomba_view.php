<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW vista_salud_bomba AS
            SELECT
                b.id AS bomba_id,
                b.codigo AS codigo_bomba,
                b.nombre AS nombre_bomba,
                b.encendido,
                b.modo_operacion,
                b.estado,
                COUNT(DISTINCT s.id) AS total_sensores_evaluados,
                ROUND(AVG(
                    CASE
                        WHEN ultima.valor_medido BETWEEN s.valor_minimo AND s.valor_maximo THEN 100
                        WHEN ultima.valor_medido BETWEEN (s.valor_minimo * 0.85) AND (s.valor_maximo * 1.15) THEN 60
                        ELSE 20
                    END
                ), 2) AS porcentaje_salud_actual,
                MAX(ultima.fecha_hora) AS fecha_ultima_lectura
            FROM bombas b
            JOIN sensores s ON s.bombas_id = b.id AND s.valor_minimo IS NOT NULL AND s.valor_maximo IS NOT NULL
            JOIN lecturas ultima ON ultima.sensores_id = s.id
                AND ultima.fecha_hora = (SELECT MAX(l2.fecha_hora) FROM lecturas l2 WHERE l2.sensores_id = s.id)
            GROUP BY b.id, b.codigo, b.nombre, b.encendido, b.modo_operacion, b.estado
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vista_salud_bomba");
    }
};
