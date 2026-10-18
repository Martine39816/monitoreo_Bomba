@php
    $usuarioAlcance = Auth::user();
    $esAdminAlcance = $usuarioAlcance->esAdministrador();
    $sinCentro = ! $esAdminAlcance && $centro === null;
@endphp

{{-- Panel compartido por los reportes: muestra el alcance real autorizado
     (centro, periodo y datos de demostración) ya resuelto en el servidor. --}}
<div
    id="alcance-reportes"
    data-centro-id="{{ $centro->id ?? '' }}"
    class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6"
>
    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">
        Alcance del reporte
    </h3>

    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                Centro de salud
            </dt>
            <dd class="text-gray-800 font-semibold">
                @if ($centro)
                    {{ $centro->nombre }}
                    <span class="text-xs font-normal text-gray-400">({{ $centro->codigo }})</span>
                @elseif ($esAdminAlcance)
                    Todos los centros de salud
                    <span class="text-xs font-normal text-gray-400">({{ $centros->count() }} registrados)</span>
                @else
                    Sin centro asignado
                @endif
            </dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                Periodo consultado
            </dt>
            <dd class="text-gray-800 font-semibold">
                {{ $rango['desde']->format('d/m/Y') }} al {{ $rango['hasta']->format('d/m/Y') }}
            </dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                Datos de demostración
            </dt>
            <dd class="text-gray-800 font-semibold">
                {{ $incluirDemo ? 'Incluidos' : 'Excluidos' }}
            </dd>
        </div>
    </dl>

    @if (! empty($notaPeriodo))
        <p class="mt-3 text-xs text-gray-500">
            {{ $notaPeriodo }}
        </p>
    @endif

    @if ($rango['recortado'])
        <div class="mt-4 p-3 bg-amber-50 text-amber-800 rounded-lg border border-amber-200 text-sm">
            {{ $avisoRecorte ?? 'El periodo solicitado supera el máximo de '.\App\Services\ReporteService::MAX_DIAS_RANGO.' días.' }}
            Se recortó automáticamente a {{ $rango['desde']->format('Y-m-d') }} – {{ $rango['hasta']->format('Y-m-d') }}.
        </div>
    @endif

    @if ($sinCentro)
        <div class="mt-4 p-3 bg-amber-50 text-amber-800 rounded-lg border border-amber-200 text-sm">
            Su usuario no tiene un centro de salud asignado, por lo que no hay información que mostrar.
            Solicite al administrador que le asigne un centro.
        </div>
    @endif

    <p class="mt-4 text-xs text-gray-400">
        Los datos de demostración se identifican por el código del centro de salud con el prefijo
        <span class="font-semibold">{{ \App\Services\ReporteService::PREFIJO_DEMO }}</span>
        (DEMO-CS-01 a DEMO-CS-04). Los centros que no usan ese prefijo se consideran datos reales.
    </p>
</div>
