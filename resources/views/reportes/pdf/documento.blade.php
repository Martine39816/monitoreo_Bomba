<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>

    <style>
        /* Dompdf no interpreta hojas de estilo externas ni JavaScript: todo el
           estilo va aqui, en linea, con tipografias y colores basicos. */
        @page {
            margin: 22px 24px 40px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #1f2937;
            margin: 0;
        }

        .hoja {
            width: 100%;
        }

        .cabecera {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .cabecera .entidad {
            font-size: 7.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cabecera h1 {
            font-size: 15px;
            color: #1e3a8a;
            margin: 2px 0 0 0;
        }

        .alcance {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .alcance td {
            padding: 3px 6px;
            font-size: 8px;
            vertical-align: top;
            border: 1px solid #e5e7eb;
        }

        .alcance .etiqueta {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            width: 22%;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
        }

        table.datos thead th {
            background-color: #1d4ed8;
            color: #ffffff;
            font-size: 7.5px;
            text-align: left;
            padding: 5px 4px;
            border: 1px solid #1e40af;
        }

        table.datos tbody td {
            padding: 4px;
            border: 1px solid #e5e7eb;
            font-size: 7.5px;
            vertical-align: top;
        }

        table.datos tbody tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .numerico {
            text-align: right;
            white-space: nowrap;
        }

        .vacio {
            padding: 18px;
            text-align: center;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            margin-top: 10px;
        }

        .resumen {
            margin-bottom: 14px;
        }

        .pie {
            margin-top: 12px;
            font-size: 6.8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="hoja">

        <div class="cabecera">
            <div class="entidad">
                Sistema de monitoreo del consumo de agua y del estado operativo de la bomba
            </div>
            <h1>{{ $titulo }}</h1>
        </div>

        <table class="alcance">
            <tbody>
                @foreach ($alcance as $linea)
                    <tr>
                        <td class="etiqueta">{{ $linea['etiqueta'] }}</td>
                        <td>{{ $linea['valor'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (! empty($resumen))

            <table class="datos resumen">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Valor</th>
                        <th>Detalle</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($resumen as $fila)
                        <tr>
                            <td>{{ $fila[0] }}</td>
                            <td>{{ $fila[1] }}</td>
                            <td>{{ $fila[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

        @if (count($filas) === 0)

            <div class="vacio">
                No hay informacion para mostrar con los filtros aplicados.
            </div>

        @else

            <table class="datos">
                <thead>
                    <tr>
                        @foreach ($encabezados as $encabezado)
                            <th>{{ $encabezado }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($filas as $fila)
                        <tr>
                            @foreach ($fila as $celda)
                                @if (is_float($celda))
                                    <td class="numerico">{{ number_format($celda, 2, ',', '.') }}</td>
                                @else
                                    <td>{{ $celda }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

        <p class="pie">
            Documento generado automaticamente por el sistema de monitoreo. Las cifras reflejan
            unicamente la informacion registrada por los dispositivos y usuarios del sistema;
            los indicadores marcados como estimados no constituyen una medicion fisica.
        </p>

    </div>
</body>
</html>
