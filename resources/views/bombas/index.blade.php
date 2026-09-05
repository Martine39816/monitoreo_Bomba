<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-xl">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 3v18m0-18c-1.5 2-4 3-6 3m6-3c1.5 2 4 3 6 3M5 9h14M5 15h14" />
                </svg>
            </div>

            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Gestión de Bombas
                </h2>

                <p class="text-sm text-gray-500">
                    Monitoreo y administración de las bombas de agua
                </p>
            </div>
        </div>
    </x-slot>


    {{-- CONTENIDO PRINCIPAL --}}
    <div class="py-8 bg-gray-50 min-h-screen">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


            {{-- MENSAJE DE ÉXITO --}}
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 p-4
                            bg-green-50 border border-green-200
                            text-green-800 rounded-xl shadow-sm">

                    <div class="bg-green-100 p-2 rounded-full">
                        <svg class="w-5 h-5 text-green-600"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 13l4 4L19 7" />

                        </svg>
                    </div>

                    <span class="font-medium">
                        {{ session('status') }}
                    </span>
                </div>
            @endif


            {{-- TARJETA PRINCIPAL --}}
            <div class="bg-white rounded-2xl shadow-lg
                        border border-gray-100 overflow-hidden">


                {{-- ENCABEZADO --}}
                <div class="p-6 border-b border-gray-100">

                    <div class="flex flex-col sm:flex-row
                                justify-between items-start sm:items-center gap-4">

                        <div>

                            <div class="flex items-center gap-2">

                                <div class="bg-blue-600 p-2 rounded-lg">

                                    <svg class="w-5 h-5 text-white"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M13 10V3L4 14h7v7l9-11h-7z" />

                                    </svg>

                                </div>

                                <h3 class="text-xl font-bold text-gray-800">
                                    Lista de Bombas
                                </h3>

                            </div>

                            <p class="text-sm text-gray-500 mt-1">
                                Bombas registradas en el sistema de monitoreo
                            </p>

                        </div>


                        {{-- BOTÓN NUEVA BOMBA --}}
                        <a href="{{ route('bombas.create') }}"
                           class="inline-flex items-center gap-2
                                  bg-blue-600 hover:bg-blue-700
                                  text-white font-semibold
                                  px-5 py-2.5 rounded-xl
                                  shadow-md hover:shadow-lg
                                  transition-all duration-200
                                  hover:-translate-y-0.5">

                            <svg class="w-5 h-5"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M12 4v16m8-8H4" />

                            </svg>

                            Nueva Bomba

                        </a>

                    </div>

                </div>


                {{-- TABLA --}}
                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        {{-- CABECERA --}}
                        <thead>

                            <tr class="bg-gray-50 border-b border-gray-200">

                                <th class="px-6 py-4 text-left text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>

                                <th class="px-6 py-4 text-left text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Código
                                </th>

                                <th class="px-6 py-4 text-left text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Bomba
                                </th>

                                <th class="px-6 py-4 text-left text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Centro de Salud
                                </th>

                                <th class="px-6 py-4 text-center text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>

                                <th class="px-6 py-4 text-center text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Encendida
                                </th>

                                <th class="px-6 py-4 text-center text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Modo
                                </th>

                                <th class="px-6 py-4 text-center text-xs
                                           font-bold text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        {{-- CUERPO --}}
                        <tbody class="divide-y divide-gray-100">

                        @forelse($bombas as $bomba)

                            @php
                                $estado = strtolower($bomba->estado ?? '');

                                if (
                                    $estado === 'funcionando' ||
                                    $estado === 'encendida' ||
                                    $estado === 'activo' ||
                                    $estado === 'activa'
                                ) {
                                    $estadoColor = 'bg-green-100 text-green-700 border-green-200';
                                    $estadoIcon = '●';
                                    $estadoTexto = 'Funcionando';
                                }
                                elseif (
                                    $estado === 'apagada' ||
                                    $estado === 'inactiva' ||
                                    $estado === 'inactivo' ||
                                    $estado === 'detenida'
                                ) {
                                    $estadoColor = 'bg-gray-100 text-gray-600 border-gray-200';
                                    $estadoIcon = '●';
                                    $estadoTexto = 'Apagada';
                                }
                                elseif (
                                    $estado === 'falla' ||
                                    $estado === 'fallo' ||
                                    $estado === 'error'
                                ) {
                                    $estadoColor = 'bg-red-100 text-red-700 border-red-200';
                                    $estadoIcon = '●';
                                    $estadoTexto = 'Falla';
                                }
                                elseif (
                                    $estado === 'advertencia' ||
                                    $estado === 'alerta'
                                ) {
                                    $estadoColor = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                    $estadoIcon = '●';
                                    $estadoTexto = 'Advertencia';
                                }
                                else {
                                    $estadoColor = 'bg-blue-100 text-blue-700 border-blue-200';
                                    $estadoIcon = '●';
                                    $estadoTexto = ucfirst(str_replace('_', ' ', $estado));
                                }
                            @endphp


                            <tr class="hover:bg-blue-50/50
                                       transition-all duration-200
                                       group">


                                {{-- ID --}}
                                <td class="px-6 py-5 whitespace-nowrap">

                                    <span class="font-bold text-gray-700">
                                        #{{ $bomba->id }}
                                    </span>

                                </td>


                                {{-- CÓDIGO --}}
                                <td class="px-6 py-5 whitespace-nowrap">

                                    <span class="inline-flex items-center
                                                 px-3 py-1 rounded-lg
                                                 bg-gray-100
                                                 text-gray-700
                                                 font-mono text-sm font-semibold">

                                        {{ $bomba->codigo }}

                                    </span>

                                </td>


                                {{-- NOMBRE --}}
                                <td class="px-6 py-5 whitespace-nowrap">

                                    <div class="flex items-center gap-3">

                                        {{-- ICONO BOMBA --}}
                                        <div class="w-11 h-11
                                                    rounded-xl
                                                    bg-blue-100
                                                    flex items-center justify-center
                                                    group-hover:bg-blue-200
                                                    transition">

                                            <svg class="w-6 h-6 text-blue-600"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M12 3v18m0-18c-1.5 2-4 3-6 3m6-3c1.5 2 4 3 6 3M5 9h14M5 15h14" />

                                            </svg>

                                        </div>


                                        <div>

                                            <div class="font-bold text-gray-800">
                                                {{ $bomba->nombre }}
                                            </div>

                                            <div class="text-xs text-gray-400">
                                                Sistema de bombeo
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- CENTRO DE SALUD --}}
                                <td class="px-6 py-5">

                                    <div class="flex items-center gap-2">

                                        <svg class="w-5 h-5 text-gray-400"
                                             fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">

                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-6 0v-4a2 2 0 00-2-2h-2a2 2 0 00-2 2v4" />

                                        </svg>

                                        <span class="text-gray-700">
                                            {{ $bomba->centroSalud->nombre ?? '—' }}
                                        </span>

                                    </div>

                                </td>


                                {{-- ESTADO --}}
                                <td class="px-6 py-5 text-center">

                                    <span class="inline-flex items-center gap-2
                                                 px-3 py-1.5
                                                 rounded-full
                                                 border
                                                 text-xs font-bold
                                                 {{ $estadoColor }}">

                                        @if ($estado === 'funcionando' || $estado === 'encendida' || $estado === 'activo' || $estado === 'activa')

                                            <span class="relative flex h-2.5 w-2.5">

                                                <span class="animate-ping
                                                             absolute inline-flex
                                                             h-full w-full
                                                             rounded-full
                                                             bg-green-400 opacity-75">
                                                </span>

                                                <span class="relative inline-flex
                                                             rounded-full h-2.5 w-2.5
                                                             bg-green-500">
                                                </span>

                                            </span>

                                        @else

                                            <span class="text-xs">
                                                {{ $estadoIcon }}
                                            </span>

                                        @endif

                                        {{ $estadoTexto }}

                                    </span>

                                </td>


                                {{-- ENCENDIDA --}}
                                <td class="px-6 py-5 text-center">

                                    @if ($bomba->encendido)

                                        <div class="inline-flex items-center gap-2
                                                    bg-green-50
                                                    border border-green-200
                                                    text-green-700
                                                    px-3 py-1.5
                                                    rounded-full
                                                    font-semibold text-sm">

                                            <span class="relative flex h-2.5 w-2.5">

                                                <span class="animate-ping
                                                             absolute inline-flex
                                                             h-full w-full
                                                             rounded-full
                                                             bg-green-400 opacity-75">
                                                </span>

                                                <span class="relative inline-flex
                                                             rounded-full h-2.5 w-2.5
                                                             bg-green-500">
                                                </span>

                                            </span>

                                            ENCENDIDA

                                        </div>

                                    @else

                                        <div class="inline-flex items-center gap-2
                                                    bg-gray-100
                                                    border border-gray-200
                                                    text-gray-500
                                                    px-3 py-1.5
                                                    rounded-full
                                                    font-semibold text-sm">

                                            <span class="w-2.5 h-2.5
                                                         rounded-full
                                                         bg-gray-400">
                                            </span>

                                            APAGADA

                                        </div>

                                    @endif

                                </td>


                                {{-- MODO --}}
                                <td class="px-6 py-5 text-center">

                                    @if ($bomba->modo_operacion)

                                        <span class="inline-flex items-center
                                                     gap-2 px-3 py-1.5
                                                     rounded-lg
                                                     bg-purple-50
                                                     border border-purple-200
                                                     text-purple-700
                                                     text-xs font-bold">

                                            ⚙️ Automático

                                        </span>

                                    @else

                                        <span class="inline-flex items-center
                                                     gap-2 px-3 py-1.5
                                                     rounded-lg
                                                     bg-orange-50
                                                     border border-orange-200
                                                     text-orange-700
                                                     text-xs font-bold">

                                            ✋ Manual

                                        </span>

                                    @endif

                                </td>


                                {{-- ACCIONES --}}
                                <td class="px-6 py-5">

                                    <div class="flex justify-center items-center gap-2">


                                        {{-- VER --}}
                                        <a href="{{ route('bombas.show', $bomba->id) }}"
                                           title="Ver bomba"
                                           class="p-2.5
                                                  bg-gray-100
                                                  hover:bg-blue-100
                                                  text-gray-600
                                                  hover:text-blue-600
                                                  rounded-lg
                                                  transition-all duration-200">

                                            <svg class="w-5 h-5"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />

                                            </svg>

                                        </a>


                                        {{-- EDITAR --}}
                                        <a href="{{ route('bombas.edit', $bomba->id) }}"
                                           title="Editar bomba"
                                           class="p-2.5
                                                  bg-gray-100
                                                  hover:bg-yellow-100
                                                  text-gray-600
                                                  hover:text-yellow-600
                                                  rounded-lg
                                                  transition-all duration-200">

                                            <svg class="w-5 h-5"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />

                                            </svg>

                                        </a>


                                        {{-- ELIMINAR --}}
                                        <form action="{{ route('bombas.destroy', $bomba->id) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('¿Eliminar esta bomba?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    title="Eliminar bomba"
                                                    class="p-2.5
                                                           bg-gray-100
                                                           hover:bg-red-100
                                                           text-gray-600
                                                           hover:text-red-600
                                                           rounded-lg
                                                           transition-all duration-200">

                                                <svg class="w-5 h-5"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     viewBox="0 0 24 24">

                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />

                                                </svg>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="8" class="px-6 py-16 text-center">

                                    <div class="flex flex-col items-center">

                                        <div class="bg-gray-100 p-5 rounded-full mb-4">

                                            <svg class="w-10 h-10 text-gray-400"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M12 3v18m0-18c-1.5 2-4 3-6 3m6-3c1.5 2 4 3 6 3M5 9h14M5 15h14" />

                                            </svg>

                                        </div>

                                        <h3 class="text-lg font-bold text-gray-700">
                                            No existen bombas registradas
                                        </h3>

                                        <p class="text-sm text-gray-500 mt-1">
                                            Comienza registrando una nueva bomba.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>