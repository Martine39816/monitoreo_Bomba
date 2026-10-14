<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-xl text-2xl leading-none">🌡️</div>
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Sensores</h2>
                <p class="text-sm text-gray-500">Instrumentos de medición asociados a cada bomba</p>
            </div>
        </div>
    </x-slot>

    @php
        // Formatea 26.00 -> 26 y 0.50 -> 0.5
        $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        $iconos = [
            'temperatura' => ['🌡️', 'bg-orange-50 border-orange-200 text-orange-700'],
            'vibracion' => ['📳', 'bg-purple-50 border-purple-200 text-purple-700'],
            'corriente' => ['⚡', 'bg-yellow-50 border-yellow-200 text-yellow-700'],
            'nivel' => ['💧', 'bg-blue-50 border-blue-200 text-blue-700'],
            'presion' => ['🧭', 'bg-teal-50 border-teal-200 text-teal-700'],
        ];
    @endphp

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl shadow-sm">
                    <span class="bg-green-100 p-1.5 rounded-full text-green-600">✓</span>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">

                {{-- ENCABEZADO + FILTROS --}}
                <div class="p-6 border-b border-gray-100 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">
                                Lista de Sensores
                                @if (method_exists($sensores, 'total'))
                                    <span class="text-sm font-normal text-gray-400">({{ $sensores->total() }})</span>
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">Rango normal, última lectura y estado de cada sensor</p>
                        </div>

                        <a href="{{ route('sensores.create') }}"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold
                                  px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                            <span class="text-lg leading-none">+</span> Nuevo Sensor
                        </a>
                    </div>

                    <form method="GET" action="{{ route('sensores.index') }}" class="flex flex-wrap items-center gap-2">
                        <select name="tipo" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm py-1.5">
                            <option value="">Todos los tipos</option>
                            @foreach (\App\Models\Sensor::TIPOS as $t)
                                <option value="{{ $t }}" @selected(request('tipo') === $t)>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>

                        @isset($bombas)
                            <select name="bomba_id" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm py-1.5">
                                <option value="">Todas las bombas</option>
                                @foreach ($bombas as $b)
                                    <option value="{{ $b->id }}" @selected(request('bomba_id') == $b->id)>{{ $b->nombre }}</option>
                                @endforeach
                            </select>
                        @endisset

                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o código…"
                               class="border-gray-300 rounded-lg text-sm py-1.5 w-56">

                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm">Buscar</button>

                        @if (request()->hasAny(['tipo', 'bomba_id', 'q']))
                            <a href="{{ route('sensores.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                        @endif
                    </form>
                </div>

                {{-- TABLA --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">Sensor</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Rango normal</th>
                                <th class="px-4 py-3 text-left">Última lectura</th>
                                <th class="px-4 py-3 text-left">Bomba</th>
                                <th class="px-4 py-3 text-left">Dispositivo IoT</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sensores as $sensor)
                                @php
                                    [$icono, $clasesTipo] = $iconos[$sensor->tipo] ?? ['📟', 'bg-gray-50 border-gray-200 text-gray-700'];

                                    $ultima = $sensor->ultimaLectura;
                                    $fuera = $ultima && (
                                        ($sensor->valor_minimo !== null && $ultima->valor_medido < $sensor->valor_minimo) ||
                                        ($sensor->valor_maximo !== null && $ultima->valor_medido > $sensor->valor_maximo)
                                    );

                                    $estado = strtolower($sensor->estado ?? '');
                                    $estadoClases = match ($estado) {
                                        'activo' => 'bg-green-50 border-green-200 text-green-700',
                                        'inactivo' => 'bg-gray-100 border-gray-200 text-gray-600',
                                        default => 'bg-amber-50 border-amber-200 text-amber-700',
                                    };
                                @endphp

                                <tr class="hover:bg-blue-50/40 transition-colors">

                                    {{-- SENSOR --}}
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl border flex items-center justify-center text-lg {{ $clasesTipo }}">
                                                {{ $icono }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-800">{{ $sensor->nombre }}</div>
                                                <div class="text-xs font-mono text-gray-500">#{{ $sensor->id }} · {{ $sensor->codigo }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- TIPO --}}
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold capitalize {{ $clasesTipo }}">
                                            {{ $sensor->tipo }}
                                        </span>
                                    </td>

                                    {{-- RANGO --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 font-mono text-xs">
                                            {{ $fmt($sensor->valor_minimo) }} – {{ $fmt($sensor->valor_maximo) }} {{ $sensor->unidad_medida }}
                                        </span>
                                    </td>

                                    {{-- ÚLTIMA LECTURA --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if ($ultima)
                                            <div class="font-bold {{ $fuera ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $fmt($ultima->valor_medido) }} {{ $sensor->unidad_medida }}
                                                @if ($fuera) <span title="Fuera de rango">⚠️</span> @endif
                                            </div>
                                            <div class="text-xs text-gray-400">{{ $ultima->fecha_hora->diffForHumans() }}</div>
                                        @else
                                            <span class="text-gray-400 text-xs">Sin datos</span>
                                        @endif
                                    </td>

                                    {{-- BOMBA --}}
                                    <td class="px-4 py-4">
                                        @if ($sensor->bomba)
                                            <a href="{{ route('bombas.show', $sensor->bomba->id) }}"
                                               class="font-medium text-gray-800 hover:text-blue-600">{{ $sensor->bomba->nombre }}</a>
                                            <div class="text-xs text-gray-500">{{ $sensor->bomba->centroSalud->nombre ?? '—' }}</div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- DISPOSITIVO --}}
                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $sensor->dispositivoIot->nombre ?? '—' }}
                                    </td>

                                    {{-- ESTADO --}}
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold capitalize {{ $estadoClases }}">
                                            <span class="h-2 w-2 rounded-full {{ $estado === 'activo' ? 'bg-green-500' : ($estado === 'inactivo' ? 'bg-gray-400' : 'bg-amber-500') }}"></span>
                                            {{ str_replace('_', ' ', $estado ?: '—') }}
                                        </span>
                                    </td>

                                    {{-- ACCIONES --}}
                                    <td class="px-4 py-4">
                                        <div class="flex justify-center items-center gap-1.5">
                                            <a href="{{ route('sensores.show', $sensor) }}" title="Ver"
                                               class="p-2 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            <a href="{{ route('sensores.edit', $sensor) }}" title="Editar"
                                               class="p-2 rounded-lg bg-gray-100 hover:bg-yellow-100 text-gray-600 hover:text-yellow-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </a>
                                            <form action="{{ route('sensores.destroy', $sensor) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar el sensor {{ addslashes($sensor->nombre) }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Eliminar"
                                                        class="p-2 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-14 text-center">
                                        <div class="text-4xl mb-2">📟</div>
                                        <p class="text-lg font-bold text-gray-700">No hay sensores que mostrar</p>
                                        <p class="text-sm text-gray-500">Registra un sensor nuevo o cambia el filtro.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($sensores, 'links'))
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $sensores->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-app-layout>