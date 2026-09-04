<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Resultados</p>
                <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">Lecturas de sensores</h2>
            </div>
            @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                <a href="{{ route('lecturas.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Registrar lectura
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @php
                $totalLecturas = \App\Models\Lectura::count();
                $promedioLectura = \App\Models\Lectura::avg('valor_medido');
                $totalSensores = \App\Models\Sensor::count();
            @endphp

            <div class="grid gap-4 md:grid-cols-3">
                <div class="metric-card">
                    <p class="metric-label">Total</p>
                    <p class="metric-value">{{ $totalLecturas }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-blue-500"></span>
                        <span>Registros</span>
                    </div>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Promedio</p>
                    <p class="metric-value">{{ number_format((float) $promedioLectura, 2) }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-emerald-500"></span>
                        <span>Valor medio</span>
                    </div>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Sensores</p>
                    <p class="metric-value">{{ $totalSensores }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-slate-500"></span>
                        <span>Disponibles</span>
                    </div>
                </div>
            </div>

            <div class="monitor-panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Historial de lecturas</h3>
                        <p class="text-sm text-slate-500">Detalle por sensor y bomba</p>
                    </div>

                    <form method="GET" action="{{ route('lecturas.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div>
                            <x-input-label for="sensor_id" value="Filtrar por sensor" />
                            <select id="sensor_id" name="sensor_id" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="this.form.submit()">
                                <option value="">-- Todos los sensores --</option>
                                @foreach ($sensores as $sensor)
                                    <option value="{{ $sensor->id }}" @selected(request('sensor_id') == $sensor->id)>
                                        {{ $sensor->nombre }} ({{ $sensor->codigo }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (request('sensor_id'))
                            <a href="{{ route('lecturas.index') }}" class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-4">Quitar filtro</a>
                        @endif
                    </form>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Fecha y hora</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sensor</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tipo</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Bomba</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($lecturas as $lectura)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $lectura->fecha_hora->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700">{{ $lectura->sensor->nombre ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm capitalize text-slate-700">{{ $lectura->sensor->tipo ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $lectura->sensor->bomba->nombre ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-800">
                                        {{ $lectura->valor_medido }} {{ $lectura->sensor->unidad_medida ?? '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                        No existen lecturas registradas todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $lecturas->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>