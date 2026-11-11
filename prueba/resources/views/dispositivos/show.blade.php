<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Dispositivo IoT
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos Generales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
                    <div><span class="font-semibold">Código:</span> {{ $dispositivo->codigo }}</div>
                    <div><span class="font-semibold">Nombre:</span> {{ $dispositivo->nombre }}</div>
                    <div><span class="font-semibold">Tipo:</span> {{ $dispositivo->tipo_dispositivo ?? '—' }}</div>
                    <div><span class="font-semibold">Modelo:</span> {{ $dispositivo->modelo ?? '—' }}</div>
                    <div><span class="font-semibold">IP:</span> {{ $dispositivo->direccion_ip ?? '—' }}</div>
                    <div><span class="font-semibold">Puerto:</span> {{ $dispositivo->puerto ?? '—' }}</div>
                    <div><span class="font-semibold">MAC:</span> {{ $dispositivo->mac_address ?? '—' }}</div>
                    <div><span class="font-semibold">Firmware:</span> {{ $dispositivo->firmware ?? '—' }}</div>
                    <div><span class="font-semibold">Centro de Salud:</span> {{ $dispositivo->centroSalud->nombre ?? '—' }}</div>
                    <div><span class="font-semibold">Estado:</span> <span class="capitalize">{{ str_replace('_', ' ', $dispositivo->estado) }}</span></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">
                    Sensores conectados ({{ $dispositivo->sensores->count() }})
                </h3>
                @forelse ($dispositivo->sensores as $sensor)
                    <div class="border-b py-2">
                        <span class="font-semibold capitalize">{{ $sensor->tipo }}</span> — {{ $sensor->codigo }} ({{ $sensor->nombre }})
                    </div>
                @empty
                    <p class="text-gray-500">Este dispositivo aún no tiene sensores conectados.</p>
                @endforelse
            </div>

            @if (session('api_key_generada'))
                <div class="bg-yellow-50 border border-yellow-400 rounded-lg p-4">
                    <p class="font-semibold text-yellow-800">⚠️ Copia esta clave ahora, no se volverá a mostrar:</p>
                    <code class="block bg-white p-2 mt-2 rounded border text-sm break-all">{{ session('api_key_generada') }}</code>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">API Key del Dispositivo</h3>
                <p class="text-sm text-gray-500 mb-3">
                    Esta clave se usa en el código del ESP32 para autenticar sus peticiones (cabecera X-Device-Key).
                </p>
                <form action="{{ route('dispositivos.generar-api-key', $dispositivo->id) }}" method="POST"
                        onsubmit="return confirm('¿Generar una nueva API key? La anterior dejará de funcionar.');">
                    @csrf
                    <x-primary-button type="submit">Generar Nueva API Key</x-primary-button>
                </form>
            </div>

            <a href="{{ route('dispositivos.index') }}" class="text-blue-600">← Volver al listado</a>

        </div>
    </div>

</x-app-layout>