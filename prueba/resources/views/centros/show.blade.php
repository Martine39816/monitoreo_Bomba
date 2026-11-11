<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Centro de Salud
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos Generales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2">
                    <div><span class="font-semibold">Código:</span> {{ $centro->codigo }}</div>
                    <div><span class="font-semibold">Nombre:</span> {{ $centro->nombre }}</div>
                    <div><span class="font-semibold">Dirección:</span> {{ $centro->direccion }}</div>
                    <div><span class="font-semibold">Teléfono:</span> {{ $centro->telefono ?? '—' }}</div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">
                    Usuarios asignados ({{ $centro->usuarios->count() }})
                </h3>
                @forelse ($centro->usuarios as $usuario)
                    <div class="border-b py-2">
                        {{ $usuario->name }} — <span class="capitalize">{{ $usuario->rol }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">Sin usuarios asignados todavía.</p>
                @endforelse
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">
                    Bombas ({{ $centro->bombas->count() }})
                </h3>
                @forelse ($centro->bombas as $bomba)
                    <div class="border-b py-2">
                        {{ $bomba->codigo }} — {{ $bomba->nombre }}
                    </div>
                @empty
                    <p class="text-gray-500">Sin bombas registradas todavía.</p>
                @endforelse
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">
                    Dispositivos IoT ({{ $centro->dispositivosIot->count() }})
                </h3>
                @forelse ($centro->dispositivosIot as $dispositivo)
                    <div class="border-b py-2">
                        {{ $dispositivo->codigo }} — {{ $dispositivo->nombre }}
                    </div>
                @empty
                    <p class="text-gray-500">Sin dispositivos registrados todavía.</p>
                @endforelse
            </div>

            <a href="{{ route('centros.index') }}" class="text-blue-600">← Volver al listado</a>

        </div>
    </div>

</x-app-layout>