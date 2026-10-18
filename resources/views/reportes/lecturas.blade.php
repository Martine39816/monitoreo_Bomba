<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-xl">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12h4l3-7 4 14 3-7h4" />
                </svg>
            </div>

            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Lecturas y calidad de datos
                </h2>

                <p class="text-sm text-gray-500">
                    Cobertura y comportamiento de cada sensor dentro del periodo consultado
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'El periodo seleccionado delimita las lecturas analizadas. Los sensores sin lecturas en el rango aparecen con conteo 0 para evidenciar la falta de cobertura.',
            ])

            @include('reportes.partials.filtros', ['accion' => 'reportes.lecturas'])

            @include('reportes.partials.descargas', ['clave' => 'lecturas'])

            @php
                $numero = fn ($valor) => $valor === null
                    ? '—'
                    : rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
            @endphp

            <div class="w-full bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800">
                        Sensores del alcance seleccionado
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        {{ $sensores->count() }}
                        {{ $sensores->count() === 1 ? 'sensor encontrado' : 'sensores encontrados' }}
                    </p>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1300px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Centro de salud</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bomba</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sensor</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unidad</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Lecturas</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Mínimo</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Máximo</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Promedio</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Fuera de rango</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Umbral configurado
                                    <span class="block text-[10px] font-normal normal-case text-gray-400">(mín – máx)</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                        @forelse ($sensores as $sensor)
                            <tr class="hover:bg-blue-50/40 transition-colors">

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $sensor->bomba->centroSalud->nombre ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $sensor->bomba->centroSalud->codigo ?? '' }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $sensor->bomba->nombre ?? 'Sin bomba' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-gray-800">
                                        {{ $sensor->nombre }}
                                    </div>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-mono text-xs">
                                        {{ $sensor->codigo }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ ucfirst(str_replace('_', ' ', (string) $sensor->tipo)) }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $sensor->unidad_medida ?? '—' }}
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ($sensor->lecturas_count > 0)
                                        <span class="text-sm font-semibold text-gray-700">{{ (int) $sensor->lecturas_count }}</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold">
                                            Sin lecturas
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap text-sm text-gray-700">
                                    {{ $numero($sensor->valor_minimo_medido) }}
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap text-sm text-gray-700">
                                    {{ $numero($sensor->valor_maximo_medido) }}
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap text-sm text-gray-700">
                                    {{ $numero($sensor->valor_promedio) }}
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ((int) $sensor->lecturas_fuera_rango > 0)
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                            {{ (int) $sensor->lecturas_fuera_rango }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">0</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap text-sm text-gray-600">
                                    @if ($sensor->valor_minimo === null && $sensor->valor_maximo === null)
                                        <span class="text-xs text-gray-400">Sin umbrales configurados</span>
                                    @else
                                        {{ $numero($sensor->valor_minimo) }} – {{ $numero($sensor->valor_maximo) }}
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-16 text-center">
                                    <h3 class="text-lg font-bold text-gray-700">
                                        No hay sensores para mostrar
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        No se encontraron sensores con los filtros aplicados.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-xs text-gray-400">
                Las lecturas «fuera de rango» son aquellas cuyo valor medido queda por debajo del mínimo o por
                encima del máximo configurado en el sensor. Un sensor sin umbrales configurados no puede
                clasificar lecturas fuera de rango.
            </p>

        </div>
    </div>

</x-app-layout>
