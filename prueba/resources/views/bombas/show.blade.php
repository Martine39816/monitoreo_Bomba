<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Bomba — {{ $bomba->codigo }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            {{-- SELECTOR CENTRO DE SALUD -> BOMBA --}}
            <div class="bg-white shadow rounded-lg p-4"
                 x-data="{
                    bombas: @js($selector),
                    centro: '{{ $bomba->centros_salud_id }}',
                    actual: {{ $bomba->id }},
                    ir(id) { if (id) window.location = '{{ url('/bombas') }}/' + id; }
                 }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Centro de Salud</label>
                        <select x-model="centro"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            @foreach ($centrosSelector as $c)
                                <option value="{{ $c['id'] }}" @selected($c['id'] == $bomba->centros_salud_id)>
                                    {{ $c['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Bomba</label>
                        <select @change="ir($event.target.value)"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Seleccione una bomba…</option>
                            <template x-for="b in bombas.filter(b => b.centro_id == centro)" :key="b.id">
                                <option :value="b.id" :selected="b.id == actual"
                                        x-text="b.codigo + ' — ' + b.nombre"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- SALUD + CONTROL OPERATIVO, LADO A LADO --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Estado de Salud (Tiempo Real)</h3>

                    @if ($salud)
                        <div class="flex items-center gap-6">
                            <div class="text-4xl font-bold
                                {{ $salud->porcentaje_salud_actual >= 80 ? 'text-green-600' : ($salud->porcentaje_salud_actual >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($salud->porcentaje_salud_actual, 0) }}%
                            </div>
                            <div class="text-sm text-gray-600">
                                <p>Basado en <span class="font-semibold">{{ $salud->total_sensores_evaluados }}</span> sensor(es) evaluado(s)</p>
                                <p>Última lectura: {{ $salud->fecha_ultima_lectura?->diffForHumans() ?? '—' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">
                            Aún no hay suficientes lecturas para calcular el estado de salud de esta bomba.
                            Asegúrate de que sus sensores tengan rango mínimo/máximo configurado y al menos una lectura registrada.
                        </p>
                    @endif
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Control Operativo</h3>
                    <div class="grid grid-cols-1 gap-y-2">
                        <div><span class="font-semibold">Modo:</span> {{ $bomba->modo_operacion ? 'Automático' : 'Manual' }}</div>
                        <div>
                            <span class="font-semibold">Encendida:</span>
                            @if ($bomba->encendido)
                                <span class="text-green-600 font-semibold">Sí</span>
                            @else
                                <span class="text-gray-500">No</span>
                            @endif
                        </div>
                    </div>
                    @if ($bomba->observaciones)
                        <div class="mt-2 text-sm"><span class="font-semibold">Observaciones:</span> {{ $bomba->observaciones }}</div>
                    @endif

                    @can('controlar', $bomba)
                        <div class="mt-4 flex gap-3">
                            <form action="{{ route('bombas.encender', $bomba->id) }}" method="POST"
                                  onsubmit="return confirm('¿Encender esta bomba?');">
                                @csrf
                                <button type="submit"
                                        {{ $bomba->encendido ? 'disabled' : '' }}
                                        class="px-4 py-2 rounded text-white {{ $bomba->encendido ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }}">
                                    Encender
                                </button>
                            </form>

                            <form action="{{ route('bombas.apagar', $bomba->id) }}" method="POST"
                                  onsubmit="return confirm('¿Apagar esta bomba?');">
                                @csrf
                                <button type="submit"
                                        {{ ! $bomba->encendido ? 'disabled' : '' }}
                                        class="px-4 py-2 rounded text-white {{ ! $bomba->encendido ? 'bg-gray-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }}">
                                    Apagar
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            {{-- ÚLTIMAS LECTURAS POR SENSOR --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Últimas lecturas por sensor</h3>

                @forelse ($bomba->sensores as $sensor)
                    @php
                        $fueraRango = fn ($v) => ($sensor->valor_minimo !== null && $v < $sensor->valor_minimo)
                                              || ($sensor->valor_maximo !== null && $v > $sensor->valor_maximo);
                        $ultima = $sensor->ultimasLecturas->first();
                    @endphp

                    <div class="py-4 border-b last:border-b-0">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="font-semibold capitalize">{{ $sensor->tipo }}</span>
                                <span class="text-sm text-gray-500">— {{ $sensor->codigo }} ({{ $sensor->nombre }})</span>
                            </div>
                            @if ($ultima)
                                <span class="text-xl font-bold {{ $fueraRango($ultima->valor_medido) ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $ultima->valor_medido }} {{ $sensor->unidad_medida }}
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500">
                            Rango permitido: {{ $sensor->valor_minimo ?? '—' }} a {{ $sensor->valor_maximo ?? '—' }}
                            {{ $sensor->unidad_medida }}
                        </p>

                        @if ($sensor->ultimasLecturas->isNotEmpty())
                            <table class="w-full text-sm mt-2">
                                <tbody>
                                    @foreach ($sensor->ultimasLecturas as $l)
                                        <tr class="border-t border-gray-100">
                                            <td class="py-1 text-gray-500">{{ $l->fecha_hora->format('d/m/Y H:i:s') }}</td>
                                            <td class="py-1 text-right font-mono {{ $fueraRango($l->valor_medido) ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                                                {{ $l->valor_medido }} {{ $sensor->unidad_medida }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <a href="{{ route('lecturas.index', ['sensor_id' => $sensor->id]) }}"
                               class="text-xs text-blue-600 hover:underline">Ver historial completo →</a>
                        @else
                            <p class="text-sm text-gray-500 mt-2">Sin lecturas registradas.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">Esta bomba aún no tiene sensores registrados.</p>
                @endforelse
            </div>

            {{-- ALERTAS DE ESTA BOMBA --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between border-b pb-2 mb-4">
                    <h3 class="text-lg font-bold">Alertas de esta bomba</h3>
                    <a href="{{ route('alertas.index') }}" class="text-xs text-blue-600 hover:underline">Ver todas →</a>
                </div>

                @forelse ($alertas as $alerta)
                    <div class="py-3 border-b last:border-b-0">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                @if ($alerta->atendida)
                                    <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">Atendida</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">Pendiente</span>
                                @endif
                                <span class="ml-2 font-semibold capitalize">{{ str_replace('_', ' ', $alerta->tipo) }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ $alerta->fecha_hora->format('d/m/Y H:i') }}</span>
                        </div>

                        <p class="text-sm text-gray-700 mt-1">{{ $alerta->descripcion }}</p>
                        <p class="text-xs text-gray-500">
                            @if ($alerta->sensor) Sensor: {{ $alerta->sensor->codigo }} · @endif
                            Detectado: {{ $alerta->valor_detectado ?? '—' }} · Permitido: {{ $alerta->valor_permitido ?? '—' }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                            <a href="{{ route('alertas.show', $alerta->id) }}" class="text-blue-600 hover:underline">Ver detalle</a>

                            @unless ($alerta->atendida)
                                <form action="{{ route('alertas.atender', $alerta->id) }}" method="POST"
                                      onsubmit="return confirm('¿Marcar esta alerta como solucionada?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-green-700 hover:underline">Marcar atendida</button>
                                </form>

                                <a href="{{ route('mantenimientos.create', ['alerta_id' => $alerta->id]) }}"
                                   class="text-yellow-700 hover:underline">Generar mantenimiento</a>
                            @endunless
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">Esta bomba no tiene alertas registradas.</p>
                @endforelse
            </div>

            {{-- DATOS ESTÁTICOS DE LA BOMBA --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos Generales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
                    <div><span class="font-semibold">Código:</span> {{ $bomba->codigo }}</div>
                    <div><span class="font-semibold">Nombre:</span> {{ $bomba->nombre }}</div>
                    <div><span class="font-semibold">Marca:</span> {{ $bomba->marca ?? '—' }}</div>
                    <div><span class="font-semibold">Modelo:</span> {{ $bomba->modelo ?? '—' }}</div>
                    <div><span class="font-semibold">Serie:</span> {{ $bomba->serie }}</div>
                    <div><span class="font-semibold">Fecha instalación:</span> {{ $bomba->fecha_instalacion?->format('d/m/Y') ?? '—' }}</div>
                    <div><span class="font-semibold">Centro de Salud:</span> {{ $bomba->centroSalud->nombre ?? '—' }}</div>
                    <div><span class="font-semibold">Estado:</span> <span class="capitalize">{{ str_replace('_', ' ', $bomba->estado) }}</span></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Ficha Técnica</h3>
                    <div class="grid grid-cols-1 gap-y-2">
                        <div><span class="font-semibold">Potencia:</span> {{ $bomba->potencia_hp ?? '—' }} HP</div>
                        <div><span class="font-semibold">Voltaje:</span> {{ $bomba->voltaje_nominal ?? '—' }} V</div>
                        <div><span class="font-semibold">Corriente:</span> {{ $bomba->corriente_nominal ?? '—' }} A</div>
                        <div><span class="font-semibold">Caudal mín:</span> {{ $bomba->caudal_min ?? '—' }}</div>
                        <div><span class="font-semibold">Caudal máx:</span> {{ $bomba->caudal_max ?? '—' }}</div>
                        <div><span class="font-semibold">Altura máxima:</span> {{ $bomba->altura_maxima ?? '—' }}</div>
                        <div><span class="font-semibold">Temp. máxima:</span> {{ $bomba->temperatura_maxima ?? '—' }} °C</div>
                        <div><span class="font-semibold">Presión máxima:</span> {{ $bomba->presion_maxima ?? '—' }}</div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos del Tanque</h3>
                    <div class="grid grid-cols-1 gap-y-2">
                        <div><span class="font-semibold">Código:</span> {{ $bomba->tanque_codigo ?? '—' }}</div>
                        <div><span class="font-semibold">Capacidad:</span> {{ $bomba->tanque_capacidad_litros ?? '—' }} litros</div>
                        <div><span class="font-semibold">Altura:</span> {{ $bomba->tanque_altura_metros ?? '—' }} m</div>
                        <div><span class="font-semibold">Diámetro:</span> {{ $bomba->tanque_diametro_metros ?? '—' }} m</div>
                    </div>
                </div>
            </div>

            <a href="{{ route('bombas.index') }}" class="text-blue-600">← Volver al listado</a>

        </div>
    </div>

</x-app-layout>