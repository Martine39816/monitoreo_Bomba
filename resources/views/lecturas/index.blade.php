<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lecturas de Sensores
        </h2>
    </x-slot>

    @php
        $coloresPorTipo = [
            'nivel' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
            'temperatura' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
            'corriente' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
            'vibracion' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
        ];
        $colorDefault = ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'dot' => 'bg-gray-500'];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded-lg border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ==========================================
                 TARJETAS RESUMEN: última lectura por sensor
                 ========================================== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($resumenSensores as $sensor)
                    @php $c = $coloresPorTipo[$sensor->tipo] ?? $colorDefault; @endphp
                    <a
                        href="{{ route('lecturas.index', ['sensor_id' => $sensor->id]) }}"
                        class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md hover:border-blue-200 transition-all
                            {{ request('sensor_id') == $sensor->id ? 'ring-2 ring-blue-500' : '' }}"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $c['dot'] }}"></span>
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $c['text'] }}">
                                {{ $sensor->tipo }}
                            </span>
                        </div>

                        <p class="text-sm font-medium text-gray-700 truncate">{{ $sensor->nombre }}</p>
                        <p class="text-xs text-gray-400 mb-2 truncate">{{ $sensor->bomba->nombre ?? 'Sin bomba' }}</p>

                        @if ($sensor->ultimaLectura)
                            <p class="text-2xl font-bold text-gray-800">
                                {{ number_format($sensor->ultimaLectura->valor_medido, 2) }}
                                <span class="text-sm font-normal text-gray-400">{{ $sensor->unidad_medida }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $sensor->ultimaLectura->fecha_hora->diffForHumans() }}
                            </p>
                        @else
                            <p class="text-sm text-gray-400 italic">Sin lecturas aún</p>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- ==========================================
                 GRÁFICO
                 ========================================== --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                @if ($sensorGrafico && count($datosGrafico) > 0)
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                Tendencia — {{ $sensorGrafico->nombre }}
                            </h3>
                            <p class="text-sm text-gray-400">Últimas {{ count($datosGrafico) }} lecturas</p>
                        </div>
                    </div>
                    <canvas
                        id="grafico-lecturas"
                        height="80"
                        data-labels="{{ json_encode($datosGrafico->pluck('hora')) }}"
                        data-valores="{{ json_encode($datosGrafico->pluck('valor')) }}"
                        data-unidad="{{ $sensorGrafico->unidad_medida }}"
                        data-sensor="{{ $sensorGrafico->nombre }}"
                    ></canvas>
                @elseif ($sensorGrafico)
                    <p class="text-center text-gray-400 py-8">Este sensor todavía no tiene lecturas registradas.</p>
                @else
                    <p class="text-center text-gray-400 py-8">
                        Elegí un sensor (arriba o en el filtro) para ver su gráfico de tendencia.
                    </p>
                @endif
            </div>

            {{-- ==========================================
                 TABLA
                 ========================================== --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">

                <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                    <h3 class="text-lg font-bold text-gray-800">Historial de Lecturas</h3>

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('lecturas.create') }}"
                           class="bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Registrar Lectura de Prueba
                        </a>
                    @endif
                </div>

                {{-- Filtro por sensor --}}
                <form method="GET" action="{{ route('lecturas.index') }}" class="mb-5 flex items-end gap-3 flex-wrap">
                    <div>
                        <x-input-label for="sensor_id" value="Filtrar por sensor" />
                        <select id="sensor_id" name="sensor_id"
                            class="mt-1 block rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="this.form.submit()">
                            <option value="">-- Todos los sensores --</option>
                            @foreach ($sensores as $sensor)
                                <option value="{{ $sensor->id }}" @selected(request('sensor_id') == $sensor->id)>
                                    {{ $sensor->nombre }} ({{ $sensor->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (request('sensor_id'))
                        <a href="{{ route('lecturas.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2">
                            Quitar filtro
                        </a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 tracking-wide">
                                <th class="py-3 px-3">Fecha y Hora</th>
                                <th class="py-3 px-3">Sensor</th>
                                <th class="py-3 px-3">Tipo</th>
                                <th class="py-3 px-3">Bomba</th>
                                <th class="py-3 px-3 text-right">Valor</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                        @forelse($lecturas as $lectura)
                            @php $c = $coloresPorTipo[$lectura->sensor->tipo ?? ''] ?? $colorDefault; @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-3 text-gray-500 whitespace-nowrap">
                                    {{ $lectura->fecha_hora->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-3 px-3 font-medium text-gray-700">
                                    {{ $lectura->sensor->nombre ?? '—' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize {{ $c['bg'] }} {{ $c['text'] }}">
                                        {{ $lectura->sensor->tipo ?? '—' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-gray-500">
                                    {{ $lectura->sensor->bomba->nombre ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-gray-800">
                                    {{ $lectura->valor_medido }} {{ $lectura->sensor->unidad_medida ?? '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-400">
                                    No existen lecturas registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $lecturas->links() }}
                </div>

            </div>
        </div>
    </div>

</x-app-layout>