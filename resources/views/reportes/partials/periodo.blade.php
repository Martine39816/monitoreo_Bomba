@php
    $usuarioPeriodo = Auth::user();
    $esAdminPeriodo = $usuarioPeriodo->esAdministrador();
    $maximos = \App\Services\ReporteService::MAXIMO_PERIODO;
@endphp

{{-- Selector de periodo del informe general: el usuario indica una cantidad y
     una unidad (por ejemplo, 2 semanas o 3 meses). Se envia por GET para que
     el informe sea enlazable y reproducible. --}}
<div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">
        Periodo del informe
    </h3>

    <form method="GET" action="{{ route('reportes.informe') }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <div>
                <label for="periodo_cantidad" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Cantidad
                </label>
                <input type="number" name="periodo_cantidad" id="periodo_cantidad"
                       min="1" step="1" value="{{ $periodo['cantidad'] }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label for="periodo_unidad" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Unidad
                </label>
                <select name="periodo_unidad" id="periodo_unidad"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="dias" @selected($periodo['unidad'] === 'dias')>Días</option>
                    <option value="semanas" @selected($periodo['unidad'] === 'semanas')>Semanas</option>
                    <option value="meses" @selected($periodo['unidad'] === 'meses')>Meses</option>
                </select>
            </div>

            <div>
                <label for="centro_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Centro de salud
                </label>

                @if ($esAdminPeriodo)
                    <select name="centro_id" id="centro_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Todos los centros</option>
                        @foreach ($centros as $opcion)
                            <option value="{{ $opcion->id }}" @selected((int) ($filtros['centro_id'] ?? 0) === $opcion->id)>
                                {{ $opcion->nombre }} ({{ $opcion->codigo }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <select disabled
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm text-sm text-gray-600">
                        <option>{{ $centro->nombre ?? 'Sin centro asignado' }}</option>
                    </select>
                @endif
            </div>

            <div>
                <label for="demo" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Datos de demostración
                </label>
                <select name="demo" id="demo"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="incluir" @selected($incluirDemo)>Incluir</option>
                    <option value="excluir" @selected(! $incluirDemo)>Excluir</option>
                </select>
            </div>

        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700 transition-colors">
                Generar informe
            </button>

            <p class="text-xs text-gray-400">
                El periodo se cuenta hacia atrás desde hoy, incluyendo el día en curso.
                Máximo: {{ $maximos['dias'] }} días, {{ $maximos['semanas'] }} semanas o {{ $maximos['meses'] }} meses.
            </p>
        </div>
    </form>
</div>
