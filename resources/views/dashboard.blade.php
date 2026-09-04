<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Sistema operativo</p>
                <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">
                    Sistema de Monitoreo de Bomba de Agua
                </h2>
            </div>
            <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                Tiempo real
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="monitor-panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800">
                            Bienvenido {{ Auth::user()->name }}
                        </h3>
                        <p class="mt-2 text-sm uppercase tracking-[0.14em] text-slate-500">
                            Rol: {{ Auth::user()->rol }}
                            @if (Auth::user()->esAdministrador())
                                · Vista global
                            @else
                                · Centro: {{ Auth::user()->centroSalud->nombre ?? '—' }}
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        @if (Auth::user()->esAdministrador())
                            Supervisión global de bombas, alertas y reportes.
                        @elseif (Auth::user()->esTecnico())
                            Monitoreo operativo y atención de alertas.
                        @else
                            Revisión general del estado del sistema y la operación.
                        @endif
                    </div>
                </div>
            </div>

            @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                @php
                    $bombasControl = Auth::user()->esAdministrador()
                        ? \App\Models\Bomba::with('centroSalud')->get()
                        : \App\Models\Bomba::where('centros_salud_id', Auth::user()->centros_salud_id)->get();
                    $totalBombas = $bombasControl->count();
                    $bombasEncendidas = $bombasControl->where('encendido', true)->count();
                    $alertasPendientes = \App\Models\Alerta::where('atendida', false)->count();
                    $lecturasTotales = \App\Models\Lectura::count();
                @endphp

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card">
                        <p class="metric-label">Bombas</p>
                        <p class="metric-value">{{ $totalBombas }}</p>
                        <div class="metric-meta">
                            <span class="status-dot bg-blue-500"></span>
                            <span>Monitoreadas</span>
                        </div>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Encendidas</p>
                        <p class="metric-value">{{ $bombasEncendidas }}</p>
                        <div class="metric-meta">
                            <span class="status-dot bg-emerald-500"></span>
                            <span>Activas</span>
                        </div>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Alertas</p>
                        <p class="metric-value">{{ $alertasPendientes }}</p>
                        <div class="metric-meta">
                            <span class="status-dot bg-amber-500"></span>
                            <span>Pendientes</span>
                        </div>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Lecturas</p>
                        <p class="metric-value">{{ $lecturasTotales }}</p>
                        <div class="metric-meta">
                            <span class="status-dot bg-slate-500"></span>
                            <span>Registradas</span>
                        </div>
                    </div>
                </div>
            @endif

            @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                @if ($bombasControl->count() > 0)
                    <div class="monitor-panel">
                        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Control rápido de bombas</h3>
                                <p class="text-sm text-slate-500">Estado operativo del sistema en tiempo real</p>
                            </div>
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                {{ $totalBombas }} bombas monitorizadas
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($bombasControl as $bomba)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-white">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-bold text-slate-800">{{ $bomba->nombre }}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">{{ $bomba->centroSalud->nombre ?? 'Sin centro' }}</p>
                                        </div>
                                        <span class="status-badge {{ $bomba->encendido ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                            <span class="status-dot {{ $bomba->encendido ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $bomba->encendido ? 'Encendida' : 'Apagada' }}
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex items-center justify-between text-xs text-slate-500">
                                            <span>Estado</span>
                                            <span class="font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $bomba->estado) }}</span>
                                        </div>
                                        <div class="progress-track mt-2">
                                            <div class="progress-fill {{ $bomba->encendido ? 'from-emerald-500 to-emerald-600' : 'from-slate-400 to-slate-500' }}" style="width: {{ $bomba->encendido ? '100%' : '35%' }};"></div>
                                        </div>
                                    </div>

                                    @can('controlar', $bomba)
                                        <div class="mt-4 flex gap-2">
                                            <form action="{{ route('bombas.encender', $bomba->id) }}" method="POST" class="flex-1">
                                                @csrf
                                                <button type="submit" {{ $bomba->encendido ? 'disabled' : '' }}
                                                    class="w-full rounded-xl px-3 py-2 text-sm font-semibold transition {{ $bomba->encendido ? 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400' : 'border border-blue-600 bg-blue-600 text-white hover:bg-blue-700' }}">
                                                    Encender
                                                </button>
                                            </form>
                                            <form action="{{ route('bombas.apagar', $bomba->id) }}" method="POST" class="flex-1">
                                                @csrf
                                                <button type="submit" {{ !$bomba->encendido ? 'disabled' : '' }}
                                                    class="w-full rounded-xl px-3 py-2 text-sm font-semibold transition {{ !$bomba->encendido ? 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400' : 'border border-red-600 bg-red-600 text-white hover:bg-red-700' }}">
                                                    Apagar
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="monitor-panel">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Accesos rápidos</h3>
                        <p class="text-sm text-slate-500">Gestión del sistema</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @if (Auth::user()->esAdministrador())
                        <a href="{{ route('users.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">👥 Usuarios</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Administración de usuarios del sistema.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('bombas.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">🔧 Bombas</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Registro y ficha técnica de las bombas.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('centros.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">🏥 Centros</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Registro de centros de salud.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('dispositivos.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">📡 IoT</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Dispositivos ESP32/PLC conectados.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('sensores.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">🌡️ Sensores</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Registro de sensores y sus rangos.</p>
                        </a>
                    @endif

                    <a href="{{ route('lecturas.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-bold text-slate-800">💧 Lecturas</h4>
                            <span class="text-lg">→</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Monitoreo del consumo de agua.</p>
                    </a>

                    <a href="{{ route('alertas.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-bold text-slate-800">🚨 Alertas</h4>
                            <span class="text-lg">→</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Notificaciones de eventos críticos.</p>
                    </a>

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('mantenimientos.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-slate-800">🔧 Mantenimientos</h4>
                                <span class="text-lg">→</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Historial de mantenimientos de bombas.</p>
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>