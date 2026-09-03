<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mantenimientos
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
                    <h3 class="text-xl font-bold">Historial de Mantenimientos</h3>

                    <a href="{{ route('mantenimientos.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Nuevo Mantenimiento
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">Fecha</th>
                            <th class="border p-2">Tipo</th>
                            <th class="border p-2">Bomba</th>
                            <th class="border p-2">Responsable</th>
                            <th class="border p-2">Origen</th>
                            <th class="border p-2">Próximo</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($mantenimientos as $mantenimiento)
                        <tr>
                            <td class="border p-2">{{ $mantenimiento->fecha->format('d/m/Y') }}</td>
                            <td class="border p-2 capitalize">{{ $mantenimiento->tipo }}</td>
                            <td class="border p-2">{{ $mantenimiento->bomba->nombre ?? '—' }}</td>
                            <td class="border p-2">{{ $mantenimiento->usuario->name ?? '—' }}</td>
                            <td class="border p-2">
                                @if ($mantenimiento->alertas_id)
                                    <span class="text-amber-600">Alerta #{{ $mantenimiento->alertas_id }}</span>
                                @else
                                    <span class="text-gray-500">Programado</span>
                                @endif
                            </td>
                            <td class="border p-2">{{ $mantenimiento->proximo_mantenimiento?->format('d/m/Y') ?? '—' }}</td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('mantenimientos.show', $mantenimiento->id) }}" class="text-gray-600">Ver</a>
                                <a href="{{ route('mantenimientos.edit', $mantenimiento->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('mantenimientos.destroy', $mantenimiento->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este mantenimiento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4">
                                No existen mantenimientos registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</x-app-layout>