<nav x-data="{ open: false, notificationsOpen: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Panel Principal') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Parte derecha -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">

                <!-- NOTIFICACIONES -->
                <div class="relative mr-3">

                    <button
                        @click="notificationsOpen = !notificationsOpen"
                        class="relative inline-flex items-center justify-center p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition"
                        title="Notificaciones"
                    >

                        <!-- Icono de campana -->
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>

                        <!-- Número de alertas pendientes -->
                        @if($alertasPendientesCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full">
                                {{ $alertasPendientesCount > 99 ? '99+' : $alertasPendientesCount }}
                            </span>
                        @endif

                    </button>

                    <!-- PANEL DE NOTIFICACIONES -->
                    <div
                        x-show="notificationsOpen"
                        @click.outside="notificationsOpen = false"
                        x-transition
                        class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-50"
                        style="display: none;"
                    >

                        <!-- Encabezado -->
                        <div class="px-4 py-3 border-b border-gray-200">

                            <h3 class="font-bold text-gray-800">
                                Notificaciones
                            </h3>

                            <p class="text-xs text-gray-500">
                                {{ $alertasPendientesCount }} alerta(s) pendiente(s)
                            </p>

                        </div>

                        <!-- Lista -->
                        <div class="max-h-80 overflow-y-auto">

                            @forelse($alertasPendientesRecientes as $alerta)

                                <a
                                    href="{{ route('alertas.show', $alerta->id) }}"
                                    class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition"
                                >

                                    <div class="flex items-start gap-3">

                                        <!-- Icono según tipo -->
                                        <div class="text-lg">

                                            @if($alerta->tipo === 'temperatura_alta')
                                                🌡️
                                            @elseif($alerta->tipo === 'vibracion_alta')
                                                ⚠️
                                            @elseif($alerta->tipo === 'sobrecorriente')
                                                ⚡
                                            @elseif($alerta->tipo === 'nivel_bajo')
                                                💧
                                            @elseif($alerta->tipo === 'nivel_alto')
                                                💧
                                            @elseif($alerta->tipo === 'bomba_apagada')
                                                🔴
                                            @elseif($alerta->tipo === 'sensor_desconectado')
                                                📡
                                            @else
                                                🚨
                                            @endif

                                        </div>

                                        <!-- Información -->
                                        <div class="flex-1 min-w-0">

                                            <p class="font-semibold text-sm text-gray-800">
                                                {{ ucfirst(str_replace('_', ' ', $alerta->tipo)) }}
                                            </p>

                                            <p class="text-xs text-gray-600 mt-1">
                                                {{ $alerta->descripcion }}
                                            </p>

                                            @if($alerta->bomba)
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Bomba: {{ $alerta->bomba->nombre }}
                                                </p>
                                            @endif

                                            @if($alerta->valor_detectado !== null)
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Valor detectado:
                                                    <strong>{{ $alerta->valor_detectado }}</strong>

                                                    @if($alerta->sensor)
                                                        {{ $alerta->sensor->unidad_medida }}
                                                    @endif
                                                </p>
                                            @endif

                                            <p class="text-xs text-gray-400 mt-1">
                                                {{ $alerta->fecha_hora?->format('d/m/Y H:i') }}
                                            </p>

                                        </div>

                                    </div>

                                </a>

                            @empty

                                <div class="px-4 py-8 text-center">

                                    <div class="text-3xl mb-2">
                                        ✅
                                    </div>

                                    <p class="text-sm font-medium text-gray-600">
                                        No hay alertas pendientes
                                    </p>

                                    <p class="text-xs text-gray-400 mt-1">
                                        Todo está en orden.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                        <!-- Ver todas -->
                        <div class="border-t border-gray-200 px-4 py-3 text-center">

                            <a
                                href="{{ route('alertas.index') }}"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                            >
                                Ver todas las alertas →
                            </a>

                        </div>

                    </div>

                </div>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">

                            <div>
                                {{ Auth::user()->name }}
                            </div>

                            <div class="ms-1">

                                <svg
                                    class="fill-current h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>

                            </div>

                        </button>

                    </x-slot>

                    <x-slot name="content">

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();"
                            >
                                {{ __('Log Out') }}
                            </x-dropdown-link>

                        </form>

                    </x-slot>

                </x-dropdown>

            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">

                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                >

                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >

                        <path
                            :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />

                        <path
                            :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />

                    </svg>

                </button>

            </div>

        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div
        :class="{'block': open, 'hidden': ! open}"
        class="hidden sm:hidden"
    >

        <div class="pt-2 pb-3 space-y-1">

            <x-responsive-nav-link
                :href="route('dashboard')"
                :active="request()->routeIs('dashboard')"
            >
                {{ __('Panel Principal') }}
            </x-responsive-nav-link>

            <!-- Alertas en móvil -->
            <x-responsive-nav-link :href="route('alertas.index')">

                🚨 Alertas

                @if($alertasPendientesCount > 0)

                    <span class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1 bg-red-600 text-white text-xs font-bold rounded-full">
                        {{ $alertasPendientesCount }}
                    </span>

                @endif

            </x-responsive-nav-link>

        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">

            <div class="px-4">

                <div class="font-medium text-base text-gray-800">
                    {{ Auth::user()->name }}
                </div>

                <div class="font-medium text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </div>

            </div>

            <div class="mt-3 space-y-1">

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();"
                    >
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>

                </form>

            </div>

        </div>

    </div>
</nav>
