<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-xl">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-6m3 6V7m3 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>

            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Informe general
                </h2>

                <p class="text-sm text-gray-500">
                    {{ $periodo['etiqueta'] }} · Perfil: {{ ucfirst(Auth::user()->rol) }}
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $resumen = collect($secciones)->firstWhere('clave', 'resumen');
        $seccionesDetalle = collect($secciones)->where('clave', '!=', 'resumen');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'El informe general reúne los indicadores y bloques más relevantes para su rol. El periodo elegido se aplica a las secciones que analizan datos por fecha.',
                'avisoRecorte' => 'La cantidad solicitada supera el máximo permitido para la unidad elegida.',
            ])

            @include('reportes.partials.periodo')

            @include('reportes.partials.descargas', [
                'tituloDescarga' => 'Descargar el informe general',
                'enlaces' => [
                    'pdf' => route('reportes.informe.exportar', ['formato' => 'pdf'] + request()->query()),
                    'xlsx' => route('reportes.informe.exportar', ['formato' => 'xlsx'] + request()->query()),
                ],
            ])

            {{-- ==========================================
                 INDICADORES CLAVE DEL PERIODO
                 ========================================== --}}
            @if ($resumen)
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-3">
                        Indicadores clave
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($resumen['filas'] as [$etiqueta, $valor, $descripcion])
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    {{ $etiqueta }}
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-800">
                                    {{ $valor }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $descripcion }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ==========================================
                 SECCIONES DE DETALLE
                 ========================================== --}}
            @foreach ($seccionesDetalle as $seccion)
                <div class="w-full bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800">
                            {{ $seccion['titulo'] }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            {{ count($seccion['filas']) }}
                            {{ count($seccion['filas']) === 1 ? 'registro' : 'registros' }}
                        </p>
                    </div>

                    @if (count($seccion['filas']) === 0)
                        <p class="px-6 py-10 text-center text-sm text-gray-500">
                            No hay información para mostrar en esta sección con los filtros aplicados.
                        </p>
                    @else
                        <div class="w-full overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        @foreach ($seccion['encabezados'] as $encabezado)
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                                {{ $encabezado }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($seccion['filas'] as $fila)
                                        <tr class="hover:bg-blue-50/40 transition-colors">
                                            @foreach ($fila as $celda)
                                                <td class="px-5 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                    {{ is_float($celda) ? number_format($celda, 2, ',', '.') : $celda }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach

            <p class="text-xs text-gray-400">
                Este informe no presenta consumo de agua: el sistema no cuenta con un caudalímetro que permita medirlo.
                Los indicadores marcados como estimados no constituyen una medición física. El cumplimiento del umbral
                de {{ \App\Services\ReporteService::UMBRAL_ATENCION_HORAS }} horas es un criterio interno de gestión.
            </p>

        </div>
    </div>

</x-app-layout>
