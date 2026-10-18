<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 p-2 rounded-xl">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5h10M11 9h7M11 13h4M3 17l2-6h6l2 6M5 17h8m-8 0v3m8-3v3" />
                </svg>
            </div>

            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Mantenimientos
                </h2>

                <p class="text-sm text-gray-500">
                    Historial de mantenimientos realizados y próximas fechas programadas
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'El periodo seleccionado filtra los mantenimientos por su fecha de realización. Las fechas programadas pueden quedar fuera del rango y se muestran igualmente en su columna.',
            ])

            @include('reportes.partials.filtros', ['accion' => 'reportes.mantenimientos'])

            @include('reportes.partials.descargas', ['clave' => 'mantenimientos'])

            {{-- ==========================================
                 INDICADORES DEL PERIODO
                 ========================================== --}}
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-3">
                    Indicadores del periodo
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($indicadores as [$etiqueta, $valor, $detalle])
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ $etiqueta }}
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-800">
                                {{ $valor }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                {{ $detalle }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($metricas['vencidos'] > 0)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm">
                    Hay <span class="font-semibold">{{ $metricas['vencidos'] }}</span>
                    {{ $metricas['vencidos'] === 1 ? 'mantenimiento programado cuya fecha ya venció' : 'mantenimientos programados cuya fecha ya venció' }}
                    dentro del alcance seleccionado.
                </div>
            @endif

            @php
                $hoy = \Illuminate\Support\Carbon::today();
            @endphp

            <div class="w-full bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800">
                        Mantenimientos del alcance seleccionado
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        {{ $mantenimientos->count() }}
                        {{ $mantenimientos->count() === 1 ? 'mantenimiento encontrado' : 'mantenimientos encontrados' }}
                    </p>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1200px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Centro de salud</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bomba</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Responsable</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Próximo mantenimiento</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                        @forelse ($mantenimientos as $mantenimiento)
                            @php
                                $proximoVencido = $mantenimiento->proximo_mantenimiento
                                    && $mantenimiento->proximo_mantenimiento->lt($hoy);
                            @endphp

                            <tr class="hover:bg-blue-50/40 transition-colors">

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $mantenimiento->fecha?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $mantenimiento->bomba->centroSalud->nombre ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $mantenimiento->bomba->centroSalud->codigo ?? '' }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $mantenimiento->bomba->nombre ?? 'Sin bomba' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ ucfirst(str_replace('_', ' ', (string) $mantenimiento->tipo)) }}
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600 max-w-md">
                                    {{ $mantenimiento->descripcion ?? '—' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $mantenimiento->usuario->name ?? 'No registrado' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($mantenimiento->proximo_mantenimiento)
                                        @if ($proximoVencido)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                                {{ $mantenimiento->proximo_mantenimiento->format('d/m/Y') }} · vencido
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 border border-gray-200 text-gray-600 text-xs font-semibold">
                                                {{ $mantenimiento->proximo_mantenimiento->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">No programado</span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <h3 class="text-lg font-bold text-gray-700">
                                        No hay mantenimientos para mostrar
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        No se encontraron mantenimientos con los filtros aplicados.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
