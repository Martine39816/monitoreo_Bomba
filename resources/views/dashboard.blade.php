<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sistema de Monitoreo de Bomba de Agua
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h3 class="text-2xl font-bold mb-1">
                    Bienvenido {{ Auth::user()->name }}
                </h3>

                <p class="text-sm text-gray-500 mb-4 capitalize">
                    Rol: {{ Auth::user()->rol }}
                    @if (Auth::user()->esAdministrador())
                        · Vista global (todos los centros de salud)
                    @else
                        · Centro: {{ Auth::user()->centroSalud->nombre ?? '—' }}
                    @endif
                </p>

                @if (Auth::user()->esAdministrador())
                    <p>Como Administrador, supervisas el sistema completo: usuarios, bombas, alertas y reportes de todos los centros de salud.</p>
                @elseif (Auth::user()->esTecnico())
                    <p>Como Técnico, puedes monitorear las lecturas, controlar el encendido/apagado de la bomba y atender alertas.</p>
                @else
                    <p>Como Director, puedes monitorear las lecturas, revisar alertas, reportar un problema y marcarlo como solucionado en tu centro de salud.</p>
                @endif

            </div>

            @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                @php
                    $bombasControl = Auth::user()->esAdministrador()
                        ? \App\Models\Bomba::with('centroSalud')->get()
                        : \App\Models\Bomba::where('centros_salud_id', Auth::user()->centros_salud_id)->get();
                @endphp

                @if ($bombasControl->count() > 0)
                    <div class="bg-white shadow rounded-lg p-6 mt-6">
                        <h3 class="text-lg font-bold border-b pb-2 mb-4">⚡ Control Rápido de Bombas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($bombasControl as $bomba)
                                <div class="border rounded p-4 flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $bomba->nombre }}</p>
                                        <p class="text-xs text-gray-500">{{ $bomba->centroSalud->nombre ?? '' }}</p>
                                        <p class="text-sm mt-1">
                                            @if ($bomba->encendido)
                                                <span class="text-green-600 font-semibold">🟢 Encendida</span>
                                            @else
                                                <span class="text-gray-500">⚪ Apagada</span>
                                            @endif
                                        </p>
                                    </div>
                                    @can('controlar', $bomba)
                                        <div class="flex gap-2">
                                            <form action="{{ route('bombas.encender', $bomba->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" {{ $bomba->encendido ? 'disabled' : '' }}
                                                    class="px-3 py-1 rounded text-sm font-semibold border {{ $bomba->encendido ? 'bg-gray-200 text-gray-500 border-gray-300 cursor-not-allowed' : 'bg-blue-600 text-white border-blue-700 hover:bg-blue-700' }}">
                                                    Encender
                                                </button>
                                            </form>
                                            <form action="{{ route('bombas.apagar', $bomba->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" {{ !$bomba->encendido ? 'disabled' : '' }}
                                                    class="px-3 py-1 rounded text-sm font-semibold border {{ !$bomba->encendido ? 'bg-gray-200 text-gray-500 border-gray-300 cursor-not-allowed' : 'bg-red-600 text-white border-red-700 hover:bg-red-700' }}">
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

            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                    @if (Auth::user()->esAdministrador())
                        <a href="{{ route('users.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">👥 Usuarios</h4>
                            <p>Administración de usuarios del sistema.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('bombas.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">🔧 Bombas</h4>
                            <p>Registro y ficha técnica de las bombas.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('centros.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">🏥 Centros de Salud</h4>
                            <p>Registro de centros de salud.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('dispositivos.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">📡 Dispositivos IoT</h4>
                            <p>Registro de dispositivos ESP32/PLC.</p>
                        </a>
                    @endif

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('sensores.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">🌡️ Sensores</h4>
                            <p>Registro de sensores y sus rangos.</p>
                        </a>
                    @endif

                    <a href="{{ route('lecturas.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                        <h4 class="font-bold">💧 Lecturas</h4>
                        <p>Monitoreo del consumo de agua.</p>
                    </a>

                    <a href="{{ route('alertas.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                        <h4 class="font-bold">🚨 Alertas</h4>
                        <p>Notificaciones de eventos críticos.</p>
                    </a>

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <a href="{{ route('mantenimientos.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">🔧 Mantenimientos</h4>
                            <p>Historial de mantenimientos de bombas.</p>
                        </a>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>