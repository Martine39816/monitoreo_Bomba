<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Monitoreo</p>
                <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">Alertas</h2>
            </div>
            <a href="{{ route('alertas.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Reportar problema
            </a>
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
                $totalAlertas = \App\Models\Alerta::count();
                $alertasPendientes = \App\Models\Alerta::where('atendida', false)->count();
                $alertasSolucionadas = \App\Models\Alerta::where('atendida', true)->count();
            @endphp

            <div class="grid gap-4 md:grid-cols-3">
                <div class="metric-card">
                    <p class="metric-label">Total</p>
                    <p class="metric-value">{{ $totalAlertas }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-blue-500"></span>
                        <span>Eventos registrados</span>
                    </div>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Pendientes</p>
                    <p class="metric-value">{{ $alertasPendientes }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-amber-500"></span>
                        <span>Requieren atención</span>
                    </div>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Solucionadas</p>
                    <p class="metric-value">{{ $alertasSolucionadas }}</p>
                    <div class="metric-meta">
                        <span class="status-dot bg-emerald-500"></span>
                        <span>Atendidas</span>
                    </div>
                </div>
            </div>

            <div class="monitor-panel">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('alertas.index') }}"
                       class="rounded-full px-3 py-1.5 text-sm font-medium {{ !request('filtro') ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600' }}">
                        Todas
                    </a>
                    <a href="{{ route('alertas.index', ['filtro' => 'pendientes']) }}"
                       class="rounded-full px-3 py-1.5 text-sm font-medium {{ request('filtro') === 'pendientes' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                        Solo pendientes
                    </a>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse($alertas as $alerta)
                        @php
                            $estadoClase = $alerta->atendida
                                ? 'border-emerald-200 bg-emerald-50'
                                : 'border-amber-200 bg-amber-50';
                            $estadoTexto = $alerta->atendida ? 'Solucionada' : 'Pendiente';
                            $estadoDot = $alerta->atendida ? 'bg-emerald-500' : 'bg-amber-500';
                            $tipoIcon = $alerta->atendida ? '✓' : '!';
                            $tipoClase = $alerta->atendida ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                        @endphp

                        <div class="rounded-2xl border {{ $estadoClase }} p-4 shadow-sm transition hover:border-blue-200 hover:bg-white">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tipoClase }} text-lg font-bold">
                                        {{ $tipoIcon }}
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-base font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $alerta->tipo) }}</p>
                                            <span class="status-badge {{ $alerta->atendida ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-amber-200 bg-amber-100 text-amber-700' }}">
                                                <span class="status-dot {{ $estadoDot }}"></span>
                                                {{ $estadoTexto }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500">{{ $alerta->fecha_hora->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1 text-sm text-slate-600">
                                    <span><span class="font-semibold">Bomba:</span> {{ $alerta->bomba->nombre ?? '—' }}</span>
                                    <span><span class="font-semibold">Atención:</span> {{ $alerta->usuarioAtencion->name ?? '—' }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('alertas.show', $alerta->id) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
                                        Ver detalle
                                    </a>
                                    @if (!$alerta->atendida)
                                        <form action="{{ route('alertas.atender', $alerta->id) }}" method="POST" onsubmit="return confirm('¿Marcar esta alerta como solucionada?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                                Marcar solucionada
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl bg-white/70 p-3 text-sm leading-6 text-slate-600">
                                {{ $alerta->descripcion }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">
                            No existen alertas registradas.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $alertas->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>