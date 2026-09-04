<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">Detalle de bomba</h2>
            <a href="{{ route('bombas.index') }}" class="subtle-link">← Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="monitor-panel">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Estado de salud</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-800">{{ $bomba->nombre }}</h3>
                    </div>
                    <span class="status-badge {{ $bomba->encendido ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                        <span class="status-dot {{ $bomba->encendido ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $bomba->encendido ? 'Operativa' : 'Inactiva' }}
                    </span>
                </div>

                @if ($salud)
                    <div class="mt-6 grid gap-4 md:grid-cols-[180px_1fr] md:items-center">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Salud</p>
                            <div class="mt-3 text-4xl font-black {{ $salud->porcentaje_salud_actual >= 80 ? 'text-emerald-600' : ($salud->porcentaje_salud_actual >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                {{ number_format($salud->porcentaje_salud_actual, 0) }}%
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-sm text-slate-600">
                                <span>Evaluación general</span>
                                <span class="font-semibold text-slate-700">{{ $salud->total_sensores_evaluados }} sensores</span>
                            </div>
                            <div class="progress-track mt-3">
                                <div class="progress-fill {{ $salud->porcentaje_salud_actual >= 80 ? 'from-emerald-500 to-emerald-600' : ($salud->porcentaje_salud_actual >= 50 ? 'from-amber-500 to-amber-600' : 'from-red-500 to-red-600') }}" style="width: {{ min(max((float) $salud->porcentaje_salud_actual, 0), 100) }}%;"></div>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">Última lectura: {{ $salud->fecha_ultima_lectura?->diffForHumans() ?? '—' }}</p>
                        </div>
                    </div>
                @else
                    <p class="mt-6 text-sm leading-6 text-slate-500">
                        Aún no hay suficientes lecturas para calcular el estado de salud de esta bomba.
                        Asegúrate de que sus sensores tengan rango mínimo/máximo configurado y al menos una lectura registrada.
                    </p>
                @endif
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="monitor-panel">
                    <h3 class="text-lg font-bold text-slate-800">Datos generales</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Código</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->codigo }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Nombre</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->nombre }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Marca</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->marca ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Modelo</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->modelo ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Serie</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->serie }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Instalación</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->fecha_instalacion?->format('d/m/Y') ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Centro</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->centroSalud->nombre ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Estado</span><p class="mt-2 font-semibold capitalize text-slate-800">{{ str_replace('_', ' ', $bomba->estado) }}</p></div>
                    </div>
                </div>

                <div class="monitor-panel">
                    <h3 class="text-lg font-bold text-slate-800">Control operativo</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Modo</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->modo_operacion ? 'Automático' : 'Manual' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Encendida</span><p class="mt-2 font-semibold {{ $bomba->encendido ? 'text-emerald-700' : 'text-slate-600' }}">{{ $bomba->encendido ? 'Sí' : 'No' }}</p></div>
                    </div>

                    @if ($bomba->observaciones)
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Observaciones</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $bomba->observaciones }}</p>
                        </div>
                    @endif

                    @can('controlar', $bomba)
                        <div class="mt-5 flex flex-wrap gap-3">
                            <form action="{{ route('bombas.encender', $bomba->id) }}" method="POST" onsubmit="return confirm('¿Encender esta bomba?');">
                                @csrf
                                <button type="submit" {{ $bomba->encendido ? 'disabled' : '' }} class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ $bomba->encendido ? 'cursor-not-allowed bg-slate-300' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                    Encender
                                </button>
                            </form>

                            <form action="{{ route('bombas.apagar', $bomba->id) }}" method="POST" onsubmit="return confirm('¿Apagar esta bomba?');">
                                @csrf
                                <button type="submit" {{ !$bomba->encendido ? 'disabled' : '' }} class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ !$bomba->encendido ? 'cursor-not-allowed bg-slate-300' : 'bg-red-600 hover:bg-red-700' }}">
                                    Apagar
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="monitor-panel">
                    <h3 class="text-lg font-bold text-slate-800">Ficha técnica</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Potencia</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->potencia_hp ?? '—' }} HP</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Voltaje</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->voltaje_nominal ?? '—' }} V</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Corriente</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->corriente_nominal ?? '—' }} A</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Caudal mín</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->caudal_min ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Caudal máx</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->caudal_max ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Altura máxima</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->altura_maxima ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Temp. máxima</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->temperatura_maxima ?? '—' }} °C</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Presión máxima</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->presion_maxima ?? '—' }}</p></div>
                    </div>
                </div>

                <div class="monitor-panel">
                    <h3 class="text-lg font-bold text-slate-800">Tanque y sensores</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Código del tanque</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->tanque_codigo ?? '—' }}</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Capacidad</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->tanque_capacidad_litros ?? '—' }} litros</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Altura</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->tanque_altura_metros ?? '—' }} m</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3"><span class="text-xs uppercase tracking-[0.14em] text-slate-500">Diámetro</span><p class="mt-2 font-semibold text-slate-800">{{ $bomba->tanque_diametro_metros ?? '—' }} m</p></div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sensores asociados</p>
                        <div class="mt-3 space-y-2">
                            @forelse ($bomba->sensores as $sensor)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                    <span class="font-semibold capitalize">{{ $sensor->tipo }}</span>
                                    <span>{{ $sensor->codigo }} ({{ $sensor->nombre }})</span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Esta bomba aún no tiene sensores registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>