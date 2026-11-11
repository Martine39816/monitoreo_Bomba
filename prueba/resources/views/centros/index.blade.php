<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Centros de Salud
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
                    <h3 class="text-xl font-bold">Lista de Centros de Salud</h3>

                    <a href="{{ route('centros.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Nuevo Centro
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Código</th>
                            <th class="border p-2">Nombre</th>
                            <th class="border p-2">Dirección</th>
                            <th class="border p-2">Teléfono</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($centros as $centro)
                        <tr>
                            <td class="border p-2">{{ $centro->id }}</td>
                            <td class="border p-2">{{ $centro->codigo }}</td>
                            <td class="border p-2">{{ $centro->nombre }}</td>
                            <td class="border p-2">{{ $centro->direccion }}</td>
                            <td class="border p-2">{{ $centro->telefono ?? '—' }}</td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('centros.show', $centro->id) }}" class="text-gray-600">Ver</a>
                                <a href="{{ route('centros.edit', $centro->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('centros.destroy', $centro->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este centro de salud?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-4">
                                No existen centros de salud registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</x-app-layout>
