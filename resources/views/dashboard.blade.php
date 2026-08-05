<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sistema de Monitoreo de Bomba de Agua
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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

                <hr class="my-5">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                    @if (Auth::user()->esAdministrador())
                        <a href="{{ route('users.index') }}" class="border rounded p-4 shadow hover:bg-gray-50">
                            <h4 class="font-bold">👥 Usuarios</h4>
                            <p>Administración de usuarios del sistema.</p>
                        </a>
                    @endif

                    <div class="border rounded p-4 shadow opacity-60">
                        <h4 class="font-bold">💧 Lecturas</h4>
                        <p>Monitoreo del consumo de agua.</p>
                        <p class="text-xs text-gray-400 mt-1">(próximamente)</p>
                    </div>

                    @if (Auth::user()->tieneRol('administrador', 'tecnico'))
                        <div class="border rounded p-4 shadow opacity-60">
                            <h4 class="font-bold">⚙️ Control</h4>
                            <p>Encendido y apagado de la bomba.</p>
                            <p class="text-xs text-gray-400 mt-1">(próximamente)</p>
                        </div>
                    @endif

                    <div class="border rounded p-4 shadow opacity-60">
                        <h4 class="font-bold">🚨 Alertas</h4>
                        <p>Notificaciones de eventos críticos.</p>
                        <p class="text-xs text-gray-400 mt-1">(próximamente)</p>
                    </div>

                    @if (Auth::user()->esAdministrador())
                        <div class="border rounded p-4 shadow opacity-60">
                            <h4 class="font-bold">📊 Reportes</h4>
                            <p>Generación de reportes del sistema.</p>
                            <p class="text-xs text-gray-400 mt-1">(próximamente)</p>
                        </div>
                    @endif

                </div>

            </div>

        </div>
    </div>
</x-app-layout>