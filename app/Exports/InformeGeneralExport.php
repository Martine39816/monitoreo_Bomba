<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportacion del informe general a un unico libro .xlsx con una hoja por
 * seccion (Resumen, Comparativo, Bombas, Lecturas, Alertas, Mantenimientos).
 *
 * Cada hoja reutiliza ReporteExport, de modo que sus encabezados, orden de
 * columnas y formato son exactamente los mismos que los de la pantalla y el PDF.
 */
class InformeGeneralExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{clave: string, titulo: string, encabezados: array<int, string>, filas: array<int, array<int, mixed>>}>  $secciones
     */
    public function __construct(private readonly array $secciones)
    {
    }

    /**
     * @return array<int, ReporteExport>
     */
    public function sheets(): array
    {
        return array_map(
            fn (array $seccion) => new ReporteExport(
                $seccion['titulo'],
                $seccion['encabezados'],
                $seccion['filas']
            ),
            $this->secciones
        );
    }
}
