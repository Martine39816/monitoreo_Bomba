<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">Detalle de alerta</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="monitor-panel overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Alerta</p>
                        <h3 class="mt-2 text-2xl font-bold capitalize text-slate-800">{{ str_replace('_', ' ', $alerta->tipo) }}</h3>
                    </div>
                    <span class="status-badge {{ $alerta->atendida ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-red-200 bg-red-100 text-red-700' }}">
                        <span class="status-dot {{ $alerta->atendida ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                        {{ $alerta->atendida ? 'Solucionada' : 'Pendiente' }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Bomba</p>
                        <p class="mt-2 text-lg font-semibold text-slate-800">{{ $alerta->bomba->nombre ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sensor</p>
                        <p class="mt-2 text-lg font-semibold text-slate-800">{{ $alerta->sensor->nombre ?? '—' }}</p>
                    </div>
                    @if ($alerta->valor_detectado)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Valor detectado</p>
                            <p class="mt-2 text-lg font-semibold text-slate-800">{{ $alerta->valor_detectado }}</p>
                        </div>
                    @endif
                    @if ($alerta->valor_permitido)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Valor permitido</p>
                            <p class="mt-2 text-lg font-semibold text-slate-800">{{ $alerta->valor_permitido }}</p>
                        </div>
                    @endif
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Descripción</p>
                        <p class="mt-2 text-base leading-7 text-slate-700">{{ $alerta->descripcion }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Fecha del reporte</p>
                        <p class="mt-2 text-base font-medium text-slate-800">{{ $alerta->fecha_hora->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Atendida por</p>
                        <p class="mt-2 text-base font-medium text-slate-800">{{ $alerta->usuarioAtencion->name ?? '—' }}</p>
                    </div>
                </div>

                @if ($alerta->atendida)
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <span class="font-semibold text-slate-700">Fecha de atención:</span>
                        {{ $alerta->fecha_atencion?->format('d/m/Y H:i:s') ?? '—' }}
                    </div>
                @else
                    <form action="{{ route('alertas.atender', $alerta->id) }}" method="POST" class="mt-6"
                          onsubmit="return confirm('¿Marcar esta alerta como solucionada?');">
                        @csrf
                        @method('PATCH')
                        <x-primary-button type="submit">Marcar como solucionada</x-primary-button>
                    </form>
                @endif

                @if ($alerta->atendida)
                    <div class="mt-6">
                        <a href="{{ route('mantenimientos.create', ['alerta_id' => $alerta->id]) }}" class="subtle-link">
                            + Generar mantenimiento desde esta alerta
                        </a>
                    </div>
                @endif

                <div class="mt-6">
                    <a href="{{ route('alertas.index') }}" class="subtle-link">← Volver al listado</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>