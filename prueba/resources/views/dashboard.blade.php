<x-app-layout>
    <x-slot name="header">
        <h2>Panel general de monitoreo</h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100">✓</span>
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-800">
                        <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                        Supervisión del sistema
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-800 sm:text-3xl">
                        Bienvenido, {{ Auth::user()->name }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Rol: <strong class="capitalize text-slate-700">{{ Auth::user()->rol }}</strong>
                        @if (Auth::user()->esAdministrador())
                            · Vista global de todos los centros de salud
                        @else
                            · Centro: {{ Auth::user()->centroSalud->nombre ?? 'Sin centro asignado' }}
                        @endif
                    </p>
                    <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-600">
                        @if (Auth::user()->esAdministrador())
                            Desde aquí puedes supervisar el estado operativo de las bombas, revisar alertas y administrar los recursos registrados en los centros de salud.
                        @elseif (Auth::user()->esTecnico())
                            Desde aquí puedes revisar el funcionamiento de las bombas, consultar lecturas, gestionar dispositivos y atender alertas de mantenimiento.
                        @else
                            Desde aquí puedes supervisar el estado de tu centro de salud, consultar lecturas, revisar alertas y reportar cualquier problema.
                        @endif
                    </p>
                </div>
                <div class="hidden h-28 w-28 items-center justify-center rounded-2xl bg-teal-50 text-5xl text-teal-700 lg:flex" aria-hidden="true">💧</div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $metricas['centros_total'] !== null ? 'xl:grid-cols-3' : '' }}">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Bombas funcionando</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-800">
                            {{ $metricas['bombas_encendidas'] }}<span class="ml-1 text-base font-semibold text-slate-400">/ {{ $metricas['bombas_total'] }}</span>
                        </p>
                        <p class="mt-1 text-xs font-semibold {{ $metricas['bombas_encendidas'] > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $metricas['bombas_encendidas'] > 0 ? 'Funcionamiento detectado' : 'No hay bombas funcionando' }}
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl">⚙️</span>
                </div>
            </article>

            <a href="{{ route('alertas.index') }}" class="rounded-2xl border {{ $metricas['alertas_pendientes'] > 0 ? 'border-rose-200 bg-rose-50/60' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Alertas pendientes</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight {{ $metricas['alertas_pendientes'] > 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ $metricas['alertas_pendientes'] }}</p>
                        <p class="mt-1 text-xs font-semibold {{ $metricas['alertas_pendientes'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $metricas['alertas_pendientes'] > 0 ? 'Requieren revisión' : 'Sin alertas pendientes' }}
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-xl">!</span>
                </div>
            </a>

            @if ($metricas['centros_total'] !== null)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Centros de salud</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-800">{{ $metricas['centros_total'] }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-400">Registrados en el sistema</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-xl">🏥</span>
                    </div>
                </article>
            @endif
        </section>

        @if ($metricas['bombas_total'] > 0)
            <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,.65fr)]">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-extrabold text-slate-800">Resumen operativo</h2>
                        <p class="mt-1 text-sm text-slate-500">Distribución actual según el estado de funcionamiento reportado</p>
                    </div>
                    <div class="relative h-64 sm:h-72">
                        <canvas id="grafico-estado-bombas"
                            data-valores="{{ json_encode([$metricas['bombas_encendidas'], max(0, $metricas['bombas_total'] - $metricas['bombas_encendidas'])]) }}"></canvas>
                    </div>
                </div>
                <div class="flex flex-col justify-center rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Equipos registrados</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-slate-800">{{ $metricas['bombas_total'] }}</p>
                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>Funcionando</span><strong class="text-slate-800">{{ $metricas['bombas_encendidas'] }}</strong></div>
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>Sin funcionamiento detectado</span><strong class="text-slate-800">{{ max(0, $metricas['bombas_total'] - $metricas['bombas_encendidas']) }}</strong></div>
                    </div>
                    <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-400">Resumen del estado reportado por el sistema; no representa un historial de actividad.</p>
                </div>
            </section>
        @endif

        @if (Auth::user()->tieneRol('administrador', 'tecnico', 'director'))
            <section class="space-y-3">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800">Estado de las bombas</h2>
                        <p class="mt-1 text-sm text-slate-500">Supervisión operativa y acceso a las acciones disponibles según tu rol.</p>
                    </div>
                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('bombas.index') }}" class="text-sm font-bold text-teal-700 hover:text-teal-900">Ver gestión de bombas →</a>
                    @endif
                </div>
                <x-control-rapido-bombas />
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-800">Alertas recientes</h2>
                    <p class="mt-1 text-xs text-slate-500">Incidencias pendientes de atención</p>
                </div>
                <a href="{{ route('alertas.index') }}" class="whitespace-nowrap text-sm font-bold text-teal-700 hover:text-teal-900">Ver todas →</a>
            </div>
            @if ($alertasPendientes->count() > 0)
                <div class="divide-y divide-slate-100">
                    @foreach ($alertasPendientes as $alerta)
                        <a href="{{ route('alertas.show', $alerta) }}" class="flex flex-col gap-2 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between sm:px-2">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">!</span>
                                <div>
                                    <p class="text-sm font-extrabold capitalize text-slate-800">{{ str_replace('_', ' ', $alerta->tipo) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $alerta->bomba->nombre ?? 'Bomba sin nombre' }} · {{ $alerta->fecha_hora?->diffForHumans() ?? 'Fecha no disponible' }}</p>
                                </div>
                            </div>
                            <span class="pl-12 text-xs font-bold text-rose-600 sm:pl-0">{{ $alerta->valor_detectado ?? 'Revisar' }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl bg-emerald-50 px-5 py-8 text-center">
                    <div class="text-2xl">✓</div>
                    <p class="mt-2 text-sm font-bold text-emerald-800">No hay alertas pendientes</p>
                    <p class="mt-1 text-xs text-emerald-700">No se registran incidencias que requieran atención en este momento.</p>
                </div>
            @endif
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-extrabold text-slate-800">Accesos directos</h2>
                <p class="mt-1 text-sm text-slate-500">Ingresa rápidamente a los módulos disponibles para tu perfil.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @if (Auth::user()->esAdministrador())
                    <a href="{{ route('users.index') }}" class="app-shortcut"><span>👥</span><strong>Usuarios</strong><small>Administración de cuentas</small></a>
                @endif
                @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                    <a href="{{ route('bombas.index') }}" class="app-shortcut"><span>⚙️</span><strong>Bombas</strong><small>Equipos registrados</small></a>
                    <a href="{{ route('sensores.index') }}" class="app-shortcut"><span>🌡️</span><strong>Sensores</strong><small>Instrumentos de medición</small></a>
                    <a href="{{ route('dispositivos.index') }}" class="app-shortcut"><span>📡</span><strong>Dispositivos</strong><small>Conexión IoT</small></a>
                    <a href="{{ route('mantenimientos.index') }}" class="app-shortcut"><span>🧰</span><strong>Mantenimiento</strong><small>Historial y programación</small></a>
                    <a href="{{ route('centros.index') }}" class="app-shortcut"><span>🏥</span><strong>Centros de salud</strong><small>Instalaciones registradas</small></a>
                @endif
                <a href="{{ route('lecturas.index') }}" class="app-shortcut"><span>📈</span><strong>Lecturas</strong><small>Historial de sensores</small></a>
                <a href="{{ route('alertas.index') }}" class="app-shortcut"><span>🔔</span><strong>Alertas</strong><small>Incidencias y seguimiento</small></a>
                <a href="{{ route('consumo.index') }}" class="app-shortcut"><span>💧</span><strong>Consumo estimado</strong><small>Resumen de consumo</small></a>
            </div>
        </section>
    </div>
</x-app-layout>
