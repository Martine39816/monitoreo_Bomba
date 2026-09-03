<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Bomba
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

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
                    <p class="text-gray-500">
                        Aún no hay suficientes lecturas para calcular el estado de salud de esta bomba.
                        Asegúrate de que sus sensores tengan rango mínimo/máximo configurado y al menos una lectura registrada.
                    </p>
                @endif
            </div>
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

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Ficha Técnica</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-2">
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
                    <div><span class="font-semibold">Código:</span> {{ $bomba->tanque_codigo ?? '—' }}</div>
                    <div><span class="font-semibold">Capacidad:</span> {{ $bomba->tanque_capacidad_litros ?? '—' }} litros</div>
                    <div><span class="font-semibold">Altura:</span> {{ $bomba->tanque_altura_metros ?? '—' }} m</div>
                    <div><span class="font-semibold">Diámetro:</span> {{ $bomba->tanque_diametro_metros ?? '—' }} m</div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Control Operativo</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
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
                    <div class="mt-2"><span class="font-semibold">Observaciones:</span> {{ $bomba->observaciones }}</div>
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
                                    {{ !$bomba->encendido ? 'disabled' : '' }}
                                    class="px-4 py-2 rounded text-white {{ !$bomba->encendido ? 'bg-gray-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }}">
                                Apagar
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Sensores Asociados</h3>
                @forelse ($bomba->sensores as $sensor)
                    <div class="border-b py-2">
                        <span class="font-semibold capitalize">{{ $sensor->tipo }}</span> —
                        {{ $sensor->codigo }} ({{ $sensor->nombre }})
                    </div>
                @empty
                    <p class="text-gray-500">Esta bomba aún no tiene sensores registrados.</p>
                @endforelse
            </div>

            <a href="{{ route('bombas.index') }}" class="text-blue-600">← Volver al listado</a>

        </div>
    </div>

</x-app-layout>