<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

    {{-- ENCABEZADO + FILTROS --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-gray-100 pb-4 mb-2">
        <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
            <span class="text-orange-500">⚡</span>
            Control Rápido de Bombas
            <span class="text-sm font-normal text-gray-400">({{ $bombas->total() }})</span>
        </h3>

        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2">
            @if ($centros->count() > 1)
                <select name="centro" onchange="this.form.submit()"
                        class="border-gray-300 rounded-lg text-sm py-1.5">
                    <option value="">Todos los centros</option>
                    @foreach ($centros as $c)
                        <option value="{{ $c->id }}" @selected(request('centro') == $c->id)>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            @endif

            <select name="estado" onchange="this.form.submit()"
                    class="border-gray-300 rounded-lg text-sm py-1.5">
                <option value="">Todas</option>
                <option value="encendidas" @selected(request('estado') === 'encendidas')>Encendidas</option>
                <option value="apagadas" @selected(request('estado') === 'apagadas')>Apagadas</option>
                <option value="con_alertas" @selected(request('estado') === 'con_alertas')>Con alertas pendientes</option>
            </select>

            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar bomba…"
                   class="border-gray-300 rounded-lg text-sm py-1.5 w-40">

            <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">
                Buscar
            </button>

            @if (request()->hasAny(['centro', 'estado', 'q']))
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
            @endif
        </form>
    </div>

    {{-- LISTA COMPACTA: UNA FILA POR BOMBA --}}
    @forelse ($bombas as $b)
        @php
            $salud = $saludes->get($b->id);
            $pct = $salud?->porcentaje_salud_actual;
            $pctColor = $pct === null ? 'bg-gray-100 text-gray-500'
                : ($pct >= 80 ? 'bg-green-100 text-green-700'
                : ($pct >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'));
            // Solo Administrador/Técnico del centro pueden controlar (el Director no).
            $puede = auth()->user()->can('controlar', $b);
        @endphp

        <div class="flex flex-wrap items-center gap-3 py-3 border-b border-gray-100 last:border-b-0">

            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0 {{ $b->encendido ? 'bg-green-500' : 'bg-gray-300' }}"
                  title="{{ $b->encendido ? 'Encendida' : 'Apagada' }}"></span>

            <div class="min-w-0 flex-1 basis-48">
                @if ($puede)
                    <a href="{{ route('bombas.show', $b->id) }}"
                       class="font-semibold text-gray-800 hover:text-blue-600">{{ $b->nombre }}</a>
                @else
                    <span class="font-semibold text-gray-800">{{ $b->nombre }}</span>
                @endif
                <p class="text-xs text-gray-500 truncate">
                    {{ $b->codigo }} · {{ $b->centroSalud->nombre ?? 'Sin centro' }}
                </p>
            </div>

            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $pctColor }}" title="Salud">
                {{ $pct === null ? 'Salud —' : 'Salud '.number_format($pct, 0).'%' }}
            </span>

            @if ($b->alertas_pendientes > 0)
                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700">
                    🔔 {{ $b->alertas_pendientes }} pendiente(s)
                </span>
            @endif

            @if ($puede)
                <div class="flex items-center gap-2">
                    <form action="{{ route('bombas.encender', $b->id) }}" method="POST"
                          onsubmit="return confirm('¿Encender {{ addslashes($b->nombre) }}?');">
                        @csrf
                        <button type="submit" {{ $b->encendido ? 'disabled' : '' }}
                                class="px-3 py-1.5 rounded-lg text-sm text-white {{ $b->encendido ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }}">
                            Encender
                        </button>
                    </form>
                    <form action="{{ route('bombas.apagar', $b->id) }}" method="POST"
                          onsubmit="return confirm('¿Apagar {{ addslashes($b->nombre) }}?');">
                        @csrf
                        <button type="submit" {{ ! $b->encendido ? 'disabled' : '' }}
                                class="px-3 py-1.5 rounded-lg text-sm text-white {{ ! $b->encendido ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }}">
                            Apagar
                        </button>
                    </form>
                    <a href="{{ route('bombas.show', $b->id) }}"
                       class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                        Ver
                    </a>
                </div>
            @endif
        </div>
    @empty
        <p class="py-8 text-center text-gray-500">No hay bombas que coincidan con el filtro.</p>
    @endforelse

    <div class="mt-4">
        {{ $bombas->links() }}
    </div>
</div>