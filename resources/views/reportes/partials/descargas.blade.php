{{-- Botones de descarga compartidos por los reportes. Conservan los filtros
     aplicados (centro, periodo y datos de demostracion) en el enlace.
     El informe general puede pasar sus propios enlaces mediante $enlaces. --}}
@php
    $enlaces = $enlaces ?? [
        'pdf' => route('reportes.exportar', ['reporte' => $clave, 'formato' => 'pdf'] + request()->query()),
        'xlsx' => route('reportes.exportar', ['reporte' => $clave, 'formato' => 'xlsx'] + request()->query()),
    ];

    $tituloDescarga = $tituloDescarga ?? 'Descargar este reporte';
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
    <div>
        <p class="text-sm font-semibold text-gray-700">
            {{ $tituloDescarga }}
        </p>
        <p class="text-xs text-gray-500">
            El archivo conserva el centro, el periodo y los datos de demostracion seleccionados.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ $enlaces['pdf'] }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            PDF
        </a>

        <a href="{{ $enlaces['xlsx'] }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Excel
        </a>
    </div>
</div>
