<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Bombas
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
                    <h3 class="text-xl font-bold">Lista de Bombas</h3>

                    <a href="{{ route('bombas.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Nueva Bomba
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Código</th>
                            <th class="border p-2">Nombre</th>
                            <th class="border p-2">Centro de Salud</th>
                            <th class="border p-2">Estado</th>
                            <th class="border p-2">Encendida</th>
                            <th class="border p-2">Modo</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($bombas as $bomba)
                        <tr>
                            <td class="border p-2">{{ $bomba->id }}</td>
                            <td class="border p-2">{{ $bomba->codigo }}</td>
                            <td class="border p-2">{{ $bomba->nombre }}</td>
                            <td class="border p-2">{{ $bomba->centroSalud->nombre ?? '—' }}</td>
                            <td class="border p-2 capitalize">{{ str_replace('_', ' ', $bomba->estado) }}</td>
                            <td class="border p-2">
                                @if ($bomba->encendido)
                                    <span class="text-green-600 font-semibold">Sí</span>
                                @else
                                    <span class="text-gray-500">No</span>
                                @endif
                            </td>
                            <td class="border p-2">
                                {{ $bomba->modo_operacion ? 'Automático' : 'Manual' }}
                            </td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('bombas.show', $bomba->id) }}" class="text-gray-600">Ver</a>
                                <a href="{{ route('bombas.edit', $bomba->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('bombas.destroy', $bomba->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar esta bomba?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4">
                                No existen bombas registradas.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</x-app-layout>