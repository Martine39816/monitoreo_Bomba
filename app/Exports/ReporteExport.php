<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportacion generica a Excel (.xlsx) de cualquier reporte del catalogo.
 *
 * Recibe ya resueltos el titulo, los encabezados y las filas para que el
 * archivo descargado use exactamente los mismos datos que la pantalla y no
 * tenga logica propia de filtros ni de aislamiento por centro de salud.
 *
 * Los valores numericos se envian como numeros (no como texto) para que el
 * usuario pueda operar con ellos en la hoja de calculo.
 */
class ReporteExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, array<int, mixed>>  $filas
     */
    public function __construct(
        private readonly string $titulo,
        private readonly array $encabezados,
        private readonly array $filas,
    ) {
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->filas;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->encabezados;
    }

    /**
     * Excel limita el nombre de la hoja a 31 caracteres.
     */
    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}
