<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reportes
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('reportes.partials.alcance', [
                'notaPeriodo' => 'El periodo seleccionado se aplicará a los reportes que analizan datos por rango de fechas.',
            ])

            @include('reportes.partials.filtros', ['accion' => 'reportes.index'])

            {{-- ==========================================
                 CATÁLOGO DE REPORTES
                 ========================================== --}}
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-3">
                    Reportes disponibles para su rol
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($reportes as $reporte)
                        @if ($reporte['disponible'])

                            <a href="{{ route('reportes.'.$reporte['clave'], request()->query()) }}"
                               class="bg-white rounded-xl shadow-sm border border-blue-100 p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="font-semibold text-gray-800">
                                        {{ $reporte['nombre'] }}
                                    </h4>

                                    <span class="flex-shrink-0 text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                        Disponible
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-gray-500">
                                    {{ $reporte['descripcion'] }}
                                </p>

                                <p class="mt-2 text-sm font-semibold text-blue-600">
                                    Ver reporte →
                                </p>
                            </a>

                        @else

                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="font-semibold text-gray-800">
                                        {{ $reporte['nombre'] }}
                                    </h4>

                                    <span class="flex-shrink-0 text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-500">
                                        Próximamente · {{ $reporte['fase'] }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-gray-500">
                                    {{ $reporte['descripcion'] }}
                                </p>
                            </div>

                        @endif
                    @endforeach
                </div>

                <p class="mt-3 text-xs text-gray-400">
                    Los reportes se habilitan por etapas. Mientras tanto, puede consultar la información
                    operativa desde los módulos de Bombas, Lecturas, Alertas y Mantenimientos.
                </p>
            </div>

            <p class="text-xs text-gray-400">
                Este módulo no presenta consumo de agua: el sistema no cuenta con un caudalímetro que
                permita medirlo. Las variaciones del nivel del tanque se muestran como lecturas de nivel,
                no como consumo.
            </p>

        </div>
    </div>

</x-app-layout>
