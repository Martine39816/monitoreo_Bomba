<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Sensor
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos Generales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
                    <div><span class="font-semibold">Código:</span> {{ $sensor->codigo }}</div>
                    <div><span class="font-semibold">Nombre:</span> {{ $sensor->nombre }}</div>
                    <div><span class="font-semibold">Tipo:</span> <span class="capitalize">{{ $sensor->tipo }}</span></div>
                    <div><span class="font-semibold">Marca / Modelo:</span> {{ $sensor->marca ?? '—' }} / {{ $sensor->modelo ?? '—' }}</div>
                    <div><span class="font-semibold">Rango Normal:</span> {{ $sensor->valor_minimo ?? '—' }} a {{ $sensor->valor_maximo ?? '—' }} {{ $sensor->unidad_medida }}</div>
                    <div><span class="font-semibold">Precisión:</span> {{ $sensor->precision_sensor ?? '—' }}</div>
                    <div><span class="font-semibold">Bomba:</span> {{ $sensor->bomba->nombre ?? '—' }}</div>
                    <div><span class="font-semibold">Dispositivo IoT:</span> {{ $sensor->dispositivoIot->nombre ?? '—' }}</div>
                    <div><span class="font-semibold">Ubicación:</span> {{ $sensor->ubicacion ?? '—' }}</div>
                    <div><span class="font-semibold">Estado:</span> <span class="capitalize">{{ str_replace('_', ' ', $sensor->estado) }}</span></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">
                    Últimas Lecturas ({{ $sensor->lecturas->count() }} en total)
                </h3>
                @forelse ($sensor->lecturas->sortByDesc('fecha_hora')->take(10) as $lectura)
                    <div class="border-b py-2">
                        {{ $lectura->valor_medido }} {{ $sensor->unidad_medida }}
                        — {{ $lectura->fecha_hora->format('d/m/Y H:i:s') }}
                    </div>
                @empty
                    <p class="text-gray-500">Este sensor aún no tiene lecturas registradas.</p>
                @endforelse
            </div>

            <a href="{{ route('sensores.index') }}" class="text-blue-600">← Volver al listado</a>

        </div>
    </div>

</x-app-layout>