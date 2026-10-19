<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-xl text-2xl leading-none">🚰</div>
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Consumo de agua estimado</h2>
                <p class="text-sm text-gray-500">Agua bombeada al tanque alto, calculada con los ciclos de la bomba</p>
            </div>
        </div>
    </x-slot>

    @php
        $fmtLitros = fn ($v) => $v === null ? '—' : number_format($v, 0, ',', '.').' L';
        $fmtTiempo = fn ($seg) => $seg <= 0 ? '—' : sprintf('%dh %02dm', intdiv($seg, 3600), intdiv($seg % 3600, 60));
        $opciones = ['hoy' => 'Hoy', 'semana' => 'Últimos 7 días', 'mes' => 'Últimos 30 días'];
    @endphp

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded-lg border border-green-200">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- SELECTOR CENTRO -> BOMBA (conserva el período elegido) --}}
            <form method="GET" action="{{ route('consumo.index') }}"
                  class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="periodo" value="{{ $periodo }}">
                @if ($periodo === 'rango')
                    <input type="hidden" name="desde" value="{{ $desde->format('Y-m-d') }}">
                    <input type="hidden" name="hasta" value="{{ $hasta->format('Y-m-d') }}">
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Centro de Salud</label>
                    <select name="centro" onchange="this.form.submit()" class="w-full border-gray-300 rounded-lg text-sm">
                        @foreach ($centros as $c)
                            <option value="{{ $c->id }}" @selected($c->id == $centroSel)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Bomba</label>
                    <select name="bomba_id" onchange="this.form.submit()" class="w-full border-gray-300 rounded-lg text-sm">
                        @foreach ($bombasCentro as $b)
                            <option value="{{ $b->id }}" @selected($bomba && $b->id == $bomba->id)>{{ $b->codigo }} — {{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if (! $bomba)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-500">
                    No hay bombas disponibles para mostrar.
                </div>
            @else

                {{-- FILTRO DE PERÍODO --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap items-end gap-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($opciones as $clave => $texto)
                            <a href="{{ route('consumo.index', ['bomba_id' => $bomba->id, 'periodo' => $clave]) }}"
                               class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors
                                      {{ $periodo === $clave ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                {{ $texto }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('consumo.index') }}" class="flex flex-wrap items-end gap-2 md:ml-auto">
                        <input type="hidden" name="bomba_id" value="{{ $bomba->id }}">
                        <input type="hidden" name="periodo" value="rango">
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Desde</label>
                            <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   class="border-gray-300 rounded-lg text-sm py-1.5">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Hasta</label>
                            <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   class="border-gray-300 rounded-lg text-sm py-1.5">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors
                                       {{ $periodo === 'rango' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-800 border-gray-800 text-white hover:bg-gray-900' }}">
                            Ver rango
                        </button>
                    </form>
                </div>

                {{-- RESUMEN DEL PERÍODO ELEGIDO --}}
                @php
                    $total = $resultado['total'];
                    $nDias = $resultado['n_dias'];
                @endphp

                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-3">
                        {{ $titulo }} — {{ $bomba->nombre }}
                    </h3>

                    <div class="grid grid-cols-2 {{ $nDias > 1 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Agua bombeada</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $fmtLitros($total['litros']) }}</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Arranques</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $total['ciclos'] }}</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Funcionando</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $fmtTiempo($total['segundos']) }}</p>
                        </div>
                        @if ($nDias > 1)
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Promedio diario</p>
                                <p class="text-3xl font-bold text-gray-800">
                                    {{ $total['litros'] === null ? '—' : number_format($total['litros'] / $nDias, 0, ',', '.').' L' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- DETALLE: HOY = CICLOS; MÁS DÍAS = TABLA DIARIA --}}
                @if ($nDias === 1)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-3">Ciclos de la bomba</h3>

                        @if (count($resultado['detalle']) === 0)
                            <p class="text-sm text-gray-500 py-4">No hubo arranques de la bomba en este día.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wide text-gray-400 text-left">
                                        <th class="py-2 pr-3">Inicio</th>
                                        <th class="py-2 pr-3">Fin</th>
                                        <th class="py-2 pr-3 text-right">Duración</th>
                                        <th class="py-2 text-right">Litros estimados</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($resultado['detalle'] as $c)
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-700">{{ $c['inicio']->format('d/m H:i:s') }}</td>
                                            <td class="py-2 pr-3 text-gray-700">
                                                @if ($c['en_curso'])
                                                    <span class="inline-flex items-center gap-1.5 text-green-700 font-semibold">
                                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> En curso
                                                    </span>
                                                @else
                                                    {{ $c['fin']->format('d/m H:i:s') }}
                                                @endif
                                            </td>
                                            <td class="py-2 pr-3 text-right text-gray-600">{{ rtrim(rtrim(number_format($c['minutos'], 1, '.', ''), '0'), '.') }} min</td>
                                            <td class="py-2 text-right font-semibold text-gray-800">{{ $fmtLitros($c['litros']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @else
                    @php
                        $maximo = collect($resultado['dias'])
                            ->map(fn ($f) => $caudal !== null ? ($f['litros'] ?? 0) : $f['segundos'])
                            ->max() ?: 1;
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-3">Día por día</h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wide text-gray-400 text-left">
                                    <th class="py-2 pr-3">Día</th>
                                    <th class="py-2 pr-3 text-right">Arranques</th>
                                    <th class="py-2 pr-3 text-right">Funcionando</th>
                                    <th class="py-2 pr-3 text-right">Litros estimados</th>
                                    <th class="py-2 w-1/3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($resultado['dias'] as $f)
                                    @php
                                        $valor = $caudal !== null ? ($f['litros'] ?? 0) : $f['segundos'];
                                        $ancho = round($valor / $maximo * 100);
                                    @endphp
                                    <tr class="{{ $f['segundos'] === 0 ? 'text-gray-400' : '' }}">
                                        <td class="py-2 pr-3 font-medium {{ $f['segundos'] === 0 ? '' : 'text-gray-700' }}">{{ $f['etiqueta'] }}</td>
                                        <td class="py-2 pr-3 text-right">{{ $f['ciclos'] }}</td>
                                        <td class="py-2 pr-3 text-right">{{ $fmtTiempo($f['segundos']) }}</td>
                                        <td class="py-2 pr-3 text-right font-semibold {{ $f['segundos'] === 0 ? '' : 'text-gray-800' }}">{{ $fmtLitros($f['litros']) }}</td>
                                        <td class="py-2">
                                            <div class="h-2 rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-blue-500" style="width: {{ $ancho }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- CÓMO SE CALCULA (se abre solo si falta algún dato) --}}
                <details class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                         {{ ($litrosPorCiclo === null || ! $tiempo['minutos']) ? 'open' : '' }}>
                    <summary class="cursor-pointer text-lg font-bold text-gray-800">Cómo se calcula</summary>

                    <div class="mt-4">
                        @if ($litrosPorCiclo === null)
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                                Esta bomba no tiene la capacidad del tanque registrada, así que no se pueden estimar litros.
                                @can('update', $bomba)
                                    <a href="{{ route('bombas.edit', $bomba->id) }}" class="underline font-semibold">Registrar capacidad</a>
                                @endcan
                                Mientras tanto se muestran los arranques y el tiempo de funcionamiento.
                            </div>
                        @else
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Capacidad del tanque</p>
                                    <p class="text-lg font-bold text-gray-800">{{ number_format($bomba->tanque_capacidad_litros, 0, ',', '.') }} L</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Llenado por ciclo</p>
                                    <p class="text-lg font-bold text-gray-800">{{ \App\Services\ConsumoBomba::NIVEL_ENCENDIDO_PCT }}% → {{ \App\Services\ConsumoBomba::NIVEL_APAGADO_PCT }}%</p>
                                    <p class="text-xs text-gray-500">{{ $fmtLitros($litrosPorCiclo) }} por ciclo completo</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Tiempo de llenado</p>
                                    @if ($tiempo['minutos'])
                                        <p class="text-lg font-bold text-gray-800">{{ rtrim(rtrim(number_format($tiempo['minutos'], 1, '.', ''), '0'), '.') }} min</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $tiempo['origen'] === 'configurado' ? 'Configurado' : 'Estimado con '.$tiempo['ciclos'].' ciclos reales' }}
                                        </p>
                                    @else
                                        <p class="text-lg font-bold text-gray-400">Sin dato</p>
                                        <p class="text-xs text-gray-500">Hacen falta 3 ciclos completos o configurarlo</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Caudal estimado</p>
                                    <p class="text-lg font-bold text-gray-800">
                                        {{ $caudal ? number_format($caudal, 1, ',', '.').' L/min' : '—' }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @can('update', $bomba)
                            <form method="POST" action="{{ route('consumo.tiempo', $bomba->id) }}"
                                  class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Tiempo de llenado 30% → 90% (minutos)
                                    </label>
                                    <input type="number" name="tiempo_llenado_min" step="0.1" min="0.1" max="1440"
                                           value="{{ old('tiempo_llenado_min', $bomba->tiempo_llenado_min !== null ? rtrim(rtrim(number_format($bomba->tiempo_llenado_min, 1, '.', ''), '0'), '.') : '') }}"
                                           placeholder="Ej. 20"
                                           class="border-gray-300 rounded-lg text-sm w-40">
                                </div>
                                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                                    Guardar
                                </button>
                                <p class="text-xs text-gray-500 basis-full">
                                    Déjalo vacío para que el sistema lo estime con los ciclos reales de los últimos 30 días.
                                </p>
                            </form>
                        @endcan
                    </div>
                </details>

                <p class="text-xs text-gray-500">
                    Los litros son una estimación del agua bombeada al tanque alto (tiempo funcionando × caudal medio),
                    no una medición con caudalímetro. Equivalen al consumo del centro solo si el tanque empieza y
                    termina el período al mismo nivel. El tanque bajo no se monitorea.
                </p>
            @endif
        </div>
    </div>

</x-app-layout>