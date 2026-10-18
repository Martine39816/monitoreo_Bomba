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
                    Estado operativo de bombas
                </h2>

                <p class="text-sm text-gray-500">
                    Foto del estado actual de las bombas registradas
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'Este reporte refleja el estado actual de las bombas (no un histórico), por lo que no se filtra por el periodo seleccionado. El rango de fechas se aplicará a los reportes de lecturas, alertas y mantenimientos.',
            ])

            @include('reportes.partials.filtros', ['accion' => 'reportes.bombas'])

            @include('reportes.partials.descargas', ['clave' => 'bombas'])

            {{-- Aviso sobre el campo de encendido --}}
            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 text-sm">
                El campo <span class="font-semibold">Encendido</span> es el último valor registrado por el sistema,
                <span class="font-semibold">no una confirmación física en tiempo real</span>.
                El <span class="font-semibold">índice de salud</span> es un indicador
                <span class="font-semibold">estimado</span> a partir de las lecturas disponibles,
                no una medición física del estado de la bomba.
            </div>

            <div class="w-full bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800">
                        Bombas del alcance seleccionado
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        {{ $bombas->total() }}
                        {{ $bombas->total() === 1 ? 'bomba encontrada' : 'bombas encontradas' }}
                    </p>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1400px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Centro de salud
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Bomba
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Estado registrado
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Encendido
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Modo
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Instalación
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Última lectura
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Alertas pendientes
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Próximo mantenimiento
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Índice de salud
                                    <span class="block text-[10px] font-normal normal-case text-gray-400">(estimado)</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                        @forelse ($bombas as $bomba)
                            @php
                                $estadoEtiquetas = [
                                    'activo' => 'Activo',
                                    'inactivo' => 'Inactivo',
                                    'mantenimiento' => 'En mantenimiento',
                                    'fuera_servicio' => 'Fuera de servicio',
                                    'desconectado' => 'Desconectado',
                                ];
                                $estadoTexto = $estadoEtiquetas[$bomba->estado] ?? ucfirst(str_replace('_', ' ', (string) $bomba->estado));
                                $sinLecturas = $bomba->ultima_lectura === null;
                            @endphp

                            <tr data-bomba-id="{{ $bomba->id }}"
                                data-centro-id="{{ $bomba->centros_salud_id }}"
                                class="hover:bg-blue-50/40 transition-colors">

                                {{-- CENTRO DE SALUD --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $bomba->centroSalud->nombre ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $bomba->centroSalud->codigo ?? '' }}
                                    </div>
                                </td>

                                {{-- BOMBA --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-gray-800">
                                        {{ $bomba->nombre }}
                                    </div>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-mono text-xs">
                                        {{ $bomba->codigo }}
                                    </span>
                                </td>

                                {{-- ESTADO REGISTRADO --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ($bomba->estado === 'activo')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                            {{ $estadoTexto }}
                                        </span>
                                    @elseif ($bomba->estado === 'mantenimiento')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                            {{ $estadoTexto }}
                                        </span>
                                    @elseif (in_array($bomba->estado, ['fuera_servicio', 'desconectado'], true))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                            {{ $estadoTexto }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 border border-gray-200 text-gray-600 text-xs font-bold">
                                            <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                            {{ $estadoTexto }}
                                        </span>
                                    @endif
                                </td>

                                {{-- ENCENDIDO --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm font-semibold {{ $bomba->encendido ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ $bomba->encendido ? 'Encendido' : 'Apagado' }}
                                    </span>
                                </td>

                                {{-- MODO --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm text-gray-700">
                                        {{ $bomba->modo_operacion ? 'Automático' : 'Manual' }}
                                    </span>
                                </td>

                                {{-- INSTALACIÓN --}}
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    @if ($bomba->fecha_instalacion)
                                        {{ $bomba->fecha_instalacion->format('d/m/Y') }}
                                    @else
                                        <span class="text-xs text-gray-400">No registrada</span>
                                    @endif
                                </td>

                                {{-- ÚLTIMA LECTURA --}}
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    @if ($sinLecturas)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 border border-gray-200 text-gray-500 text-xs font-semibold">
                                            Sin datos disponibles
                                        </span>
                                    @else
                                        <span class="text-gray-700">
                                            {{ \Illuminate\Support\Carbon::parse($bomba->ultima_lectura)->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- ALERTAS PENDIENTES --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ($bomba->alertas_pendientes_count > 0)
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                            {{ $bomba->alertas_pendientes_count }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">0</span>
                                    @endif
                                </td>

                                {{-- PRÓXIMO MANTENIMIENTO --}}
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    @if ($bomba->proximo_mantenimiento)
                                        {{ \Illuminate\Support\Carbon::parse($bomba->proximo_mantenimiento)->format('d/m/Y') }}
                                    @else
                                        <span class="text-xs text-gray-400">No programado</span>
                                    @endif
                                </td>

                                {{-- ÍNDICE DE SALUD (ESTIMADO) --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if ($bomba->porcentaje_salud !== null)
                                        <span class="text-sm font-semibold text-gray-700">
                                            {{ rtrim(rtrim(number_format((float) $bomba->porcentaje_salud, 1), '0'), '.') }} %
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin datos disponibles</span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-gray-100 p-5 rounded-full mb-4">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 17v-6m3 6V7m3 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>

                                        <h3 class="text-lg font-bold text-gray-700">
                                            No hay bombas para mostrar
                                        </h3>

                                        <p class="text-sm text-gray-500 mt-1">
                                            No se encontraron bombas con los filtros aplicados.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($bombas->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $bombas->links() }}
                    </div>
                @endif

            </div>

            <p class="text-xs text-gray-400">
                Las bombas sin lecturas se incluyen igualmente en el listado y se señalan en la columna
                «Última lectura»; no se excluyen del inventario aunque no existan datos que mostrar.
            </p>

        </div>
    </div>

</x-app-layout>
