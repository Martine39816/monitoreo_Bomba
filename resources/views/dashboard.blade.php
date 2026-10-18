<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sistema de Monitoreo de Bomba de Agua
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded-lg border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ==========================================
                 SALUDO
                 ========================================== --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-1">
                    Bienvenido {{ Auth::user()->name }}
                </h3>

                <p class="text-sm text-gray-500 mb-3 capitalize">
                    Rol: {{ Auth::user()->rol }}
                    @if (Auth::user()->esAdministrador())
                        · Vista global (todos los centros de salud)
                    @else
                        · Centro: {{ Auth::user()->centroSalud->nombre ?? '—' }}
                    @endif
                </p>

                <p class="text-gray-600">
                    @if (Auth::user()->esAdministrador())
                        Como Administrador, supervisas el sistema completo: usuarios, bombas, alertas y reportes de todos los centros de salud.
                    @elseif (Auth::user()->esTecnico())
                        Como Técnico, puedes monitorear las lecturas, controlar el encendido/apagado de la bomba y atender alertas.
                    @else
                        Como Director, puedes monitorear las lecturas, revisar alertas, reportar un problema y marcarlo como solucionado en tu centro de salud.
                    @endif
                </p>
            </div>

            {{-- ==========================================
                 TARJETAS DE MÉTRICAS
                 ========================================== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $metricas['centros_total'] !== null ? '3' : '2' }} gap-4">

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Bombas encendidas</p>
                    <p class="text-3xl font-bold text-gray-800">
                        {{ $metricas['bombas_encendidas'] }}
                        <span class="text-base font-normal text-gray-400">/ {{ $metricas['bombas_total'] }}</span>
                    </p>
                    <p class="text-xs mt-1 {{ $metricas['bombas_encendidas'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $metricas['bombas_encendidas'] > 0 ? 'En funcionamiento' : 'Todas apagadas' }}
                    </p>
                </div>

                <a href="{{ route('alertas.index') }}" class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition-shadow
                    {{ $metricas['alertas_pendientes'] > 0 ? 'border-red-200' : 'border-gray-100' }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Alertas pendientes</p>
                    <p class="text-3xl font-bold {{ $metricas['alertas_pendientes'] > 0 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $metricas['alertas_pendientes'] }}
                    </p>
                    <p class="text-xs mt-1 {{ $metricas['alertas_pendientes'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                        {{ $metricas['alertas_pendientes'] > 0 ? 'Requieren atención' : 'Todo en orden' }}
                    </p>
                </a>

                @if ($metricas['centros_total'] !== null)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Centros de salud</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $metricas['centros_total'] }}</p>
                        <p class="text-xs mt-1 text-gray-400">Registrados en el sistema</p>
                    </div>
                @endif

            </div>

            {{-- ==========================================
                 CONTROL RÁPIDO DE BOMBAS
                 (componente con filtros, búsqueda y paginación)
                 ========================================== --}}
            @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                <x-control-rapido-bombas />
            @endif

            {{-- ==========================================
                 ALERTAS RECIENTES
                 ========================================== --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span>🚨</span> Alertas Recientes
                    </h3>
                    <a href="{{ route('alertas.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Ver todas →
                    </a>
                </div>

                @if ($alertasPendientes->count() > 0)
                    <div class="space-y-2">
                        @foreach ($alertasPendientes as $alerta)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-red-50 border border-red-100">
                                <div>
                                    <p class="text-sm font-semibold text-red-700 capitalize">
                                        {{ str_replace('_', ' ', $alerta->tipo) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $alerta->bomba->nombre ?? '—' }} · {{ $alerta->fecha_hora->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="text-xs font-medium text-red-600 whitespace-nowrap ml-3">
                                    {{ $alerta->valor_detectado }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-400 py-6">No hay alertas pendientes. Todo funcionando con normalidad. ✅</p>
                @endif
            </div>

            {{-- ==========================================
                 ACCESOS RÁPIDOS (sección secundaria, más compacta)
                 ========================================== --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-400 mb-4">Accesos rápidos</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

                    @if (Auth::user()->esAdministrador())
                        <a href="{{ route('users.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            👥 <span>Usuarios</span>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('bombas.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            🔧 <span>Bombas</span>
                        </a>
                        <a href="{{ route('centros.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            🏥 <span>Centros</span>
                        </a>
                        <a href="{{ route('dispositivos.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            📡 <span>Dispositivos</span>
                        </a>
                        <a href="{{ route('sensores.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            🌡️ <span>Sensores</span>
                        </a>
                        <a href="{{ route('mantenimientos.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                            🔧 <span>Mantenimientos</span>
                        </a>
                    @endif

                    <a href="{{ route('lecturas.index') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                        💧 <span>Lecturas</span>
                    </a>

                    {{-- Reportar problema manual: disponible para todos los roles
                         (el Director reporta aunque no controle la bomba). --}}
                    <a href="{{ route('alertas.create') }}" class="flex items-center gap-2 border border-gray-100 rounded-lg p-3 text-sm hover:bg-gray-50 transition-colors">
                        🚨 <span>Reportar Problema</span>
                    </a>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>