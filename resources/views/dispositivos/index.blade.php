<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Dispositivos IoT
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Lista de Dispositivos IoT</h3>

                    <a href="{{ route('dispositivos.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Nuevo Dispositivo
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Código</th>
                            <th class="border p-2">Nombre</th>
                            <th class="border p-2">Tipo</th>
                            <th class="border p-2">IP</th>
                            <th class="border p-2">Centro de Salud</th>
                            <th class="border p-2">Estado</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($dispositivos as $dispositivo)
                        <tr>
                            <td class="border p-2">{{ $dispositivo->id }}</td>
                            <td class="border p-2">{{ $dispositivo->codigo }}</td>
                            <td class="border p-2">{{ $dispositivo->nombre }}</td>
                            <td class="border p-2">{{ $dispositivo->tipo_dispositivo ?? '—' }}</td>
                            <td class="border p-2">{{ $dispositivo->direccion_ip ?? '—' }}</td>
                            <td class="border p-2">{{ $dispositivo->centroSalud->nombre ?? '—' }}</td>
                            <td class="border p-2 capitalize">{{ str_replace('_', ' ', $dispositivo->estado) }}</td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('dispositivos.show', $dispositivo->id) }}" class="text-gray-600">Ver</a>
                                <a href="{{ route('dispositivos.edit', $dispositivo->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('dispositivos.destroy', $dispositivo->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este dispositivo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4">
                                No existen dispositivos registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</x-app-layout>