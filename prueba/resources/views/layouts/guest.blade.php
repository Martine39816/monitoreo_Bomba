<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">

        {{-- Fondo de página: gris suave, para que la tarjeta central se note como una sola ventana --}}
        <div class="min-h-screen flex items-center justify-center p-4 sm:p-8" style="background-color: #eef1f6;">

            {{-- ==========================================
                 CONTENEDOR ÚNICO — una sola "ventana"
                 ========================================== --}}
            <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden grid lg:grid-cols-2">

                {{-- ==========================================
                     PANEL IZQUIERDO — marca / mensaje
                     Oculto en celular, visible desde lg
                     ========================================== --}}
                <div class="hidden lg:flex relative overflow-hidden flex-col justify-between p-12 text-white" style="background-color: #0f172a;">

                    {{-- Decoración: ondas de agua suaves de fondo --}}
                    <svg class="absolute inset-0 w-full h-full opacity-[0.07]" preserveAspectRatio="none" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#3b82f6" d="M0,300 C200,380 300,220 500,280 C650,320 700,240 800,300 L800,800 L0,800 Z"/>
                    </svg>
                    <svg class="absolute inset-0 w-full h-full opacity-[0.05]" preserveAspectRatio="none" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#60a5fa" d="M0,450 C220,500 320,400 500,450 C660,495 720,420 800,460 L800,800 L0,800 Z"/>
                    </svg>

                    <div class="relative z-10 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3.5C12 3.5 6 10.2 6 14.2a6 6 0 0012 0C18 10.2 12 3.5 12 3.5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-lg leading-tight">Monitoreo</p>
                            <p class="text-sm text-slate-400 leading-tight">Gestión de agua</p>
                        </div>
                    </div>

                    <div class="relative z-10 max-w-md">
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-400 mb-3">
                            Sistema de Monitoreo
                        </p>
                        <h1 class="text-2xl font-bold leading-snug mb-4">
                            Consumo de agua y estado operativo de la bomba
                        </h1>
                        <p class="text-slate-400 leading-relaxed text-sm">
                            Optimizamos la gestión de los recursos hídricos en los centros de salud
                            de Cercado, Cochabamba, mediante monitoreo en tiempo real y alertas
                            automáticas ante cualquier falla.
                        </p>
                    </div>

                    <p class="relative z-10 text-xs text-slate-500">
                        &copy; {{ date('Y') }} Sistema de Monitoreo de Agua. Todos los derechos reservados.
                    </p>

                </div>

                {{-- ==========================================
                     PANEL DERECHO — formulario
                     ========================================== --}}
                <div class="flex flex-col justify-center px-6 py-10 sm:px-12 sm:py-12">

                    {{-- Logo visible solo en celular, donde no se ve el panel izquierdo --}}
                    <div class="flex lg:hidden items-center gap-3 mb-8">
                        <div class="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3.5C12 3.5 6 10.2 6 14.2a6 6 0 0012 0C18 10.2 12 3.5 12 3.5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 leading-tight">Monitoreo</p>
                            <p class="text-xs text-gray-500 leading-tight">Gestión de agua</p>
                        </div>
                    </div>

                    {{ $slot }}

                </div>

            </div>

        </div>

    </body>
</html>