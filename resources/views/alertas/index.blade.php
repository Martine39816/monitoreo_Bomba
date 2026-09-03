<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Alertas
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

                <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                    <h3 class="text-xl font-bold">Lista de Alertas</h3>

                    <a href="{{ route('alertas.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        Reportar Problema
                    </a>
                </div>

                <div class="mb-4 space-x-3">
                    <a href="{{ route('alertas.index') }}"
                       class="{{ !request('filtro') ? 'font-bold text-blue-600' : 'text-gray-500' }}">
                        Todas
                    </a>
                    <a href="{{ route('alertas.index', ['filtro' => 'pendientes']) }}"
                       class="{{ request('filtro') === 'pendientes' ? 'font-bold text-blue-600' : 'text-gray-500' }}">
                        Solo Pendientes
                    </a>
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">Fecha</th>
                            <th class="border p-2">Tipo</th>
                            <th class="border p-2">Descripción</th>
                            <th class="border p-2">Bomba</th>
                            <th class="border p-2">Estado</th>
                            <th class="border p-2">Atendida por</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($alertas as $alerta)
                        <tr class="{{ !$alerta->atendida ? 'bg-red-50' : '' }}">
                            <td class="border p-2">{{ $alerta->fecha_hora->format('d/m/Y H:i') }}</td>
                            <td class="border p-2 capitalize">{{ str_replace('_', ' ', $alerta->tipo) }}</td>
                            <td class="border p-2">{{ $alerta->descripcion }}</td>
                            <td class="border p-2">{{ $alerta->bomba->nombre ?? '—' }}</td>
                            <td class="border p-2">
                                @if ($alerta->atendida)
                                    <span class="text-green-600 font-semibold">Solucionada</span>
                                @else
                                    <span class="text-red-600 font-semibold">Pendiente</span>
                                @endif
                            </td>
                            <td class="border p-2">{{ $alerta->usuarioAtencion->name ?? '—' }}</td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('alertas.show', $alerta->id) }}" class="text-gray-600">Ver</a>
                                @if (!$alerta->atendida)
                                    <form action="{{ route('alertas.atender', $alerta->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Marcar esta alerta como solucionada?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-600">Marcar Solucionada</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4">
                                No existen alertas registradas.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $alertas->links() }}
                </div>

            </div>
        </div>
    </div>

</x-app-layout>