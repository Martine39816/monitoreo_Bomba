@php
    $usuarioFiltros = Auth::user();
    $esAdminFiltros = $usuarioFiltros->esAdministrador();
@endphp

{{-- Filtros comunes a todos los reportes. Se envian por GET para que el
     resultado sea enlazable y reproducible. --}}
<div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">
        Filtros
    </h3>

    <form method="GET" action="{{ route($accion) }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <div>
                <label for="centro_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Centro de salud
                </label>

                @if ($esAdminFiltros)
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
                    <p class="mt-1 text-xs text-gray-400">
                        El alcance se fija automáticamente a su centro de salud.
                    </p>
                @endif
            </div>

            <div>
                <label for="fecha_desde" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Desde
                </label>
                <input type="date" name="fecha_desde" id="fecha_desde"
                       value="{{ $rango['desde']->format('Y-m-d') }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label for="fecha_hasta" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Hasta
                </label>
                <input type="date" name="fecha_hasta" id="fecha_hasta"
                       value="{{ $rango['hasta']->format('Y-m-d') }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
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

        <div class="mt-4 flex items-center gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700 transition-colors">
                Aplicar filtros
            </button>

            <a href="{{ route($accion) }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                Limpiar
            </a>
        </div>
    </form>
</div>
