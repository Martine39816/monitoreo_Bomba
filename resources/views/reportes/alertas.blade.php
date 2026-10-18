<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-red-100 p-2 rounded-xl">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v3m0 3h.01M4.5 19h15a1.5 1.5 0 001.3-2.25l-7.5-13a1.5 1.5 0 00-2.6 0l-7.5 13A1.5 1.5 0 004.5 19z" />
                </svg>
            </div>

            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Alertas
                </h2>

                <p class="text-sm text-gray-500">
                    Alertas generadas en el periodo y tiempo transcurrido hasta su atención
                </p>
            </div>
        </div>
    </x-slot>

    @inject('reportes', 'App\Services\ReporteService')

    @php
        $numero = fn ($valor) => $valor === null
            ? '—'
            : rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'El periodo seleccionado filtra las alertas por su fecha y hora de generación.',
            ])

            @include('reportes.partials.filtros', ['accion' => 'reportes.alertas'])

            @include('reportes.partials.descargas', ['clave' => 'alertas'])

            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 text-sm">
                El umbral de <span class="font-semibold">{{ $reportes::UMBRAL_ATENCION_HORAS }} horas</span> es un
                criterio interno de este proyecto para describir el tiempo de atención, no una norma del sector salud.
                Una duración de exactamente {{ $reportes::UMBRAL_ATENCION_HORAS }} horas se considera dentro del umbral.
            </div>

            <div class="w-full bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800">
                        Alertas del alcance seleccionado
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        {{ $alertas->count() }}
                        {{ $alertas->count() === 1 ? 'alerta encontrada' : 'alertas encontradas' }}
                    </p>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1400px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha y hora</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Centro de salud</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bomba</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sensor</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor detectado</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor permitido</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Atendida por</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tiempo de atención</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                        @forelse ($alertas as $alerta)
                            @php
                                $estadoAtencion = $reportes->evaluarTiempoAtencion($alerta);
                                $etiquetaAtencion = $reportes->etiquetaTiempoAtencion($estadoAtencion);
                                $fueraDeUmbral = str_contains($estadoAtencion, '_fuera');
                                $indeterminable = $estadoAtencion === $reportes::ATENCION_INDETERMINABLE;
                            @endphp

                            <tr class="hover:bg-blue-50/40 transition-colors">

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $alerta->fecha_hora?->format('d/m/Y H:i') ?? '—' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $alerta->bomba->centroSalud->nombre ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $alerta->bomba->centroSalud->codigo ?? '' }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $alerta->bomba->nombre ?? 'Sin bomba' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $alerta->sensor->nombre ?? 'Sin sensor' }}
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ ucfirst(str_replace('_', ' ', (string) $alerta->tipo)) }}
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap text-sm text-gray-700">
                                    {{ $numero($alerta->valor_detectado) }}
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap text-sm text-gray-700">
                                    {{ $numero($alerta->valor_permitido) }}
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ($alerta->atendida)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                            Atendida
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                            Pendiente
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $alerta->usuarioAtencion->name ?? '—' }}
                                    </div>
                                    @if ($alerta->fecha_atencion)
                                        <div class="text-xs text-gray-400">
                                            {{ $alerta->fecha_atencion->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($indeterminable)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 border border-gray-200 text-gray-600 text-xs font-semibold">
                                            {{ $etiquetaAtencion }}
                                        </span>
                                    @elseif ($fueraDeUmbral)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                            {{ $etiquetaAtencion }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                                            {{ $etiquetaAtencion }}
                                        </span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center">
                                    <h3 class="text-lg font-bold text-gray-700">
                                        No hay alertas para mostrar
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        No se encontraron alertas con los filtros aplicados.
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
