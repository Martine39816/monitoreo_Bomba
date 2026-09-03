<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Sensores
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Lista de Sensores</h3>

                    <a href="{{ route('sensores.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Nuevo Sensor
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Código</th>
                            <th class="border p-2">Nombre</th>
                            <th class="border p-2">Tipo</th>
                            <th class="border p-2">Rango Normal</th>
                            <th class="border p-2">Bomba</th>
                            <th class="border p-2">Dispositivo IoT</th>
                            <th class="border p-2">Estado</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($sensores as $sensor)
                        <tr>
                            <td class="border p-2">{{ $sensor->id }}</td>
                            <td class="border p-2">{{ $sensor->codigo }}</td>
                            <td class="border p-2">{{ $sensor->nombre }}</td>
                            <td class="border p-2 capitalize">{{ $sensor->tipo }}</td>
                            <td class="border p-2">
                                {{ $sensor->valor_minimo ?? '—' }} a {{ $sensor->valor_maximo ?? '—' }}
                                {{ $sensor->unidad_medida }}
                            </td>
                            <td class="border p-2">{{ $sensor->bomba->nombre ?? '—' }}</td>
                            <td class="border p-2">{{ $sensor->dispositivoIot->nombre ?? '—' }}</td>
                            <td class="border p-2 capitalize">{{ str_replace('_', ' ', $sensor->estado) }}</td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('sensores.show', $sensor->id) }}" class="text-gray-600">Ver</a>
                                <a href="{{ route('sensores.edit', $sensor->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('sensores.destroy', $sensor->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este sensor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-4">
                                No existen sensores registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</x-app-layout>