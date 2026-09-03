<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lecturas de Sensores
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
                    <h3 class="text-xl font-bold">Historial de Lecturas</h3>

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('lecturas.create') }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded">
                            Registrar Lectura de Prueba
                        </a>
                    @endif
                </div>

                {{-- Filtro por sensor --}}
                <form method="GET" action="{{ route('lecturas.index') }}" class="mb-4 flex items-end gap-3">
                    <div>
                        <x-input-label for="sensor_id" value="Filtrar por sensor" />
                        <select id="sensor_id" name="sensor_id" class="mt-1 block border-gray-300 rounded-md" onchange="this.form.submit()">
                            <option value="">-- Todos los sensores --</option>
                            @foreach ($sensores as $sensor)
                                <option value="{{ $sensor->id }}" @selected(request('sensor_id') == $sensor->id)>
                                    {{ $sensor->nombre }} ({{ $sensor->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (request('sensor_id'))
                        <a href="{{ route('lecturas.index') }}" class="text-sm text-gray-600 mb-2">Quitar filtro</a>
                    @endif
                </form>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">Fecha y Hora</th>
                            <th class="border p-2">Sensor</th>
                            <th class="border p-2">Tipo</th>
                            <th class="border p-2">Bomba</th>
                            <th class="border p-2">Valor</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($lecturas as $lectura)
                        <tr>
                            <td class="border p-2">{{ $lectura->fecha_hora->format('d/m/Y H:i:s') }}</td>
                            <td class="border p-2">{{ $lectura->sensor->nombre ?? '—' }}</td>
                            <td class="border p-2 capitalize">{{ $lectura->sensor->tipo ?? '—' }}</td>
                            <td class="border p-2">{{ $lectura->sensor->bomba->nombre ?? '—' }}</td>
                            <td class="border p-2">
                                {{ $lectura->valor_medido }} {{ $lectura->sensor->unidad_medida ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4">
                                No existen lecturas registradas todavía.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $lecturas->links() }}
                </div>

            </div>
        </div>
    </div>

</x-app-layout>