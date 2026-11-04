<nav
    x-data="{
        sidebarOpen: false,
        monitoringOpen: true,
        administrationOpen: true
    }"
    class="relative lg:w-64 lg:flex-shrink-0"
>

    {{-- ==========================================
         BARRA SUPERIOR PARA CELULAR
         ========================================== --}}
    <div
        class="fixed top-0 left-0 right-0 z-[60] h-16 bg-[#0f172a] shadow-lg flex items-center justify-between px-4 lg:hidden"
    >

        <button
            @click="sidebarOpen = true"
            type="button"
            class="p-2 text-white rounded-lg hover:bg-slate-800"
        >
            <svg
                class="w-7 h-7"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>

        <div class="flex items-center gap-2 text-white">

            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center">

                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 3.5C12 3.5 6 10.2 6 14.2a6 6 0 0012 0C18 10.2 12 3.5 12 3.5z"
                    />
                </svg>

            </div>

            <div>
                <p class="text-sm font-bold">
                    Monitoreo
                </p>

                <p class="text-xs text-slate-400">
                    Gestión de agua
                </p>
            </div>

        </div>

        <div class="w-10"></div>

    </div>


    {{-- ==========================================
         FONDO OSCURO CUANDO SE ABRE EN CELULAR
         ========================================== --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-[55] bg-black/70 lg:hidden"
        x-cloak
    ></div>


    {{-- ==========================================
         MENÚ LATERAL
         ========================================== --}}
    <aside
        style="background-color: #0f172a !important; opacity: 1 !important;"
        class="
            sidebar-menu
            fixed
            top-0
            left-0
            z-[70]
            h-screen
            w-64
            text-white
            shadow-2xl
            flex
            flex-col
            transform
            transition-transform
            duration-300
            -translate-x-full
            lg:translate-x-0
        "
    >

        {{-- ==========================================
             ENCABEZADO
             ========================================== --}}
        <div
            class="h-20 min-h-[80px] flex items-center px-5 border-b border-slate-700"
            style="background-color: #0f172a;"
        >

            <div class="flex items-center gap-3">

                <div
                    class="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg"
                >

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
                            d="M12 3.5C12 3.5 6 10.2 6 14.2a6 6 0 0012 0C18 10.2 12 3.5 12 3.5z"
                        />
                    </svg>

                </div>

                <div>

                    <p class="font-bold text-base text-white">
                        Monitoreo
                    </p>

                    <p class="text-xs text-slate-400">
                        Gestión de agua
                    </p>

                </div>

            </div>

            {{-- Cerrar menú en celular --}}
            <button
                @click="sidebarOpen = false"
                type="button"
                class="ml-auto lg:hidden text-slate-400 hover:text-white"
            >

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
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>

            </button>

        </div>


        {{-- ==========================================
             CONTENIDO DEL MENÚ
             ========================================== --}}
        <div class="flex-1 overflow-y-auto px-3 py-5">


            {{-- ==========================================
                 DASHBOARD
                 ========================================== --}}
            <a
                href="{{ route('dashboard') }}"
                @click="sidebarOpen = false"
                class="
                    flex
                    items-center
                    gap-3
                    px-4
                    py-3
                    rounded-xl
                    text-sm
                    font-medium
                    mb-2
                    transition-all
                    {{ request()->routeIs('dashboard')
                        ? 'bg-blue-600 text-white shadow-lg'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                "
            >

                <svg
                    class="w-5 h-5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10.5L12 3l9 7.5M5 9.5V21h14V9.5M9 21v-6h6v6"
                    />
                </svg>

                <span>
                    Dashboard
                </span>

            </a>


            {{-- ==========================================
                 SECCIÓN MONITOREO
                 ========================================== --}}
            <div class="mt-5">

                <button
                    @click="monitoringOpen = !monitoringOpen"
                    type="button"
                    class="w-full flex items-center justify-between px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white"
                >

                    <span>
                        Monitoreo
                    </span>

                    <svg
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': monitoringOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>

                </button>


                <div
                    x-show="monitoringOpen"
                    x-transition
                    class="mt-1 space-y-1"
                >

                    {{-- BOMBAS --}}
                    <a
                        href="{{ route('bombas.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('bombas.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 12h4l2-6 4 12 2-6h4"
                            />
                        </svg>

                        <span>
                            Bombas
                        </span>

                    </a>


                    {{-- SENSORES --}}
                    <a
                        href="{{ route('sensores.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('sensores.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="8"
                                stroke-width="2"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-width="2"
                                d="M12 8v4l2 2"
                            />
                        </svg>

                        <span>
                            Sensores
                        </span>

                    </a>


                    {{-- LECTURAS --}}
                    <a
                        href="{{ route('lecturas.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('lecturas.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 19V5M4 19h16M7 15l3-3 3 2 5-6"
                            />
                        </svg>

                        <span>
                            Lecturas
                        </span>

                    </a>


                    {{-- DISPOSITIVOS --}}
                    <a
                        href="{{ route('dispositivos.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('dispositivos.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <rect
                                x="5"
                                y="4"
                                width="14"
                                height="16"
                                rx="2"
                                stroke-width="2"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-width="2"
                                d="M9 8h6M9 12h6M9 16h3"
                            />
                        </svg>

                        <span>
                            Dispositivos
                        </span>

                    </a>

                </div>

            </div>


            {{-- ==========================================
                 ALERTAS
                 ========================================== --}}
            @php
                $cantidadAlertas = $alertasPendientesCount ?? 0;
            @endphp

            <div class="mt-5">

                <a
                    href="{{ route('alertas.index') }}"
                    @click="sidebarOpen = false"
                    class="
                        flex
                        items-center
                        justify-between
                        px-4
                        py-3
                        rounded-xl
                        text-sm
                        font-medium
                        transition-all

                        {{ request()->routeIs('alertas.*')
                            ? 'bg-red-600 text-white shadow-lg'
                            : ($cantidadAlertas > 0
                                ? 'bg-red-600/20 text-red-400 border border-red-500/30'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white') }}
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 01-6 0"
                            />
                        </svg>

                        <span>
                            Alertas
                        </span>

                    </div>

                    @if($cantidadAlertas > 0)

                        <span
                            class="min-w-[25px] h-6 px-1.5 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center"
                        >
                            {{ $cantidadAlertas > 99 ? '99+' : $cantidadAlertas }}
                        </span>

                    @endif

                </a>

            </div>


            {{-- ==========================================
                 ADMINISTRACIÓN
                 ========================================== --}}
            <div class="mt-6">

                <button
                    @click="administrationOpen = !administrationOpen"
                    type="button"
                    class="w-full flex items-center justify-between px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white"
                >

                    <span>
                        Administración
                    </span>

                    <svg
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': administrationOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>

                </button>


                <div
                    x-show="administrationOpen"
                    x-transition
                    class="mt-1 space-y-1"
                >

                    {{-- CENTROS --}}
                    <a
                        href="{{ route('centros.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('centros.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 21V6a2 2 0 012-2h12a2 2 0 012 2v15M8 8h2M14 8h2M8 12h2M14 12h2M8 16h2M14 16h2M10 21v-5h4v5"
                            />
                        </svg>

                        <span>
                            Centros de Salud
                        </span>

                    </a>


                    {{-- MANTENIMIENTOS --}}
                    <a
                        href="{{ route('mantenimientos.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('mantenimientos.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14.7 6.3a4 4 0 00-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 005.4-5.4l-2 2-2-2 2-2z"
                            />
                        </svg>

                        <span>
                            Mantenimientos
                        </span>

                    </a>


                    {{-- USUARIOS --}}
                    <a
                        href="{{ route('users.index') }}"
                        @click="sidebarOpen = false"
                        class="
                            flex items-center gap-3 px-4 py-3 ml-1 rounded-xl text-sm transition-all
                            {{ request()->routeIs('users.*')
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                        "
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                            />
                        </svg>

                        <span>
                            Usuarios
                        </span>

                    </a>

                </div>

            </div>

        </div>


        {{-- ==========================================
             USUARIO / CERRAR SESIÓN
             ========================================== --}}
        <div
            class="border-t border-slate-700 p-3"
            style="background-color: #0f172a;"
        >

            <div
                class="flex items-center gap-3 px-3 py-3 mb-2 rounded-xl"
                style="background-color: #1e293b;"
            >

                <div
                    class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-bold text-white"
                >
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>

                <div class="min-w-0">

                    <p class="text-sm font-semibold text-white truncate">
                        {{ auth()->user()->name ?? 'Usuario' }}
                    </p>

                    <p class="text-xs text-slate-400 truncate">
                        {{ auth()->user()->email ?? '' }}
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('logout') }}"
            >

                @csrf

                <button
                    type="submit"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-300 hover:bg-red-600/10 hover:text-red-400 transition-all"
                >

                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"
                        />
                    </svg>

                    <span>
                        Cerrar sesión
                    </span>

                </button>

            </form>

        </div>

    </aside>

</nav>