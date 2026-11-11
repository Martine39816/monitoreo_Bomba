
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

    {{-- MENSAJE DE RESPUESTA --}}
    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- ENCABEZADO Y FILTROS --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-gray-100 pb-4 mb-2">

        <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
            <span class="text-orange-500">⚡</span>
            Control Rápido de Bombas

            <span class="text-sm font-normal text-gray-400">
                ({{ $bombas->total() }})
            </span>
        </h3>

        <form method="GET" action="{{ route('dashboard') }}"
              class="flex flex-wrap items-center gap-2">

            {{-- FILTRO POR CENTRO DE SALUD --}}
            @if ($centros->count() > 1)
                <select name="centro"
                        onchange="this.form.submit()"
                        class="border-gray-300 rounded-lg text-sm py-1.5">

                    <option value="">Todos los centros</option>

                    @foreach ($centros as $c)
                        <option value="{{ $c->id }}"
                            @selected(request('centro') == $c->id)>
                            {{ $c->nombre }}
                        </option>
                    @endforeach

                </select>
            @endif

            {{-- FILTRO POR FUNCIONAMIENTO REAL --}}
            <select name="estado"
                    onchange="this.form.submit()"
                    class="border-gray-300 rounded-lg text-sm py-1.5">

                <option value="">Todas las bombas</option>

                <option value="encendidas" @selected(request('estado') === 'encendidas')>
                    Funcionando
                </option>

                <option value="apagadas" @selected(request('estado') === 'apagadas')>
                    Apagadas
                </option>

                <option value="con_alertas" @selected(request('estado') === 'con_alertas')>
                    Con alertas pendientes
                </option>

            </select>

            {{-- BUSCADOR --}}
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Buscar bomba..."
                   class="border-gray-300 rounded-lg text-sm py-1.5 w-40">

            <button type="submit"
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">
                Buscar
            </button>

            {{-- LIMPIAR FILTROS --}}
            @if (request()->hasAny(['centro', 'estado', 'q']))
                <a href="{{ route('dashboard') }}"
                   class="text-sm text-gray-500 hover:underline">
                    Limpiar
                </a>
            @endif

        </form>
    </div>

    {{-- LISTADO DE BOMBAS --}}
    @forelse ($bombas as $b)

        @php
            // NIVEL ESTIMADO DEL TANQUE
            $nivelTanque = $b->nivel_tanque_estimado_datos ?? null;
            $nivelPct = $nivelTanque['porcentaje'] ?? null;
            $nivelLitros = $nivelTanque['litros'] ?? null;
            $nivelEstado = $nivelTanque['estado'] ?? 'Sin datos suficientes';

            // SALUD DE LA BOMBA
            $salud = $saludes->get($b->id);
            $pct = $salud?->porcentaje_salud_actual;

            // FUNCIONAMIENTO REAL
            $funcionando = (bool) $b->funcionando;

            // MODO DE OPERACIÓN
            $modoAutomatico = (bool) $b->modo_operacion;

            // ORDEN ACTUAL
            $bloqueada = (bool) $b->bloqueo_remoto;
            $ordenEncendido = (bool) $b->encendido && ! $bloqueada;

            // COLOR DE SALUD
            $pctColor = $pct === null
                ? 'bg-gray-100 text-gray-500'
                : ($pct >= 80
                    ? 'bg-green-100 text-green-700'
                    : ($pct >= 50
                        ? 'bg-yellow-100 text-yellow-700'
                        : 'bg-red-100 text-red-700'));

            // PERMISOS
            $puede = auth()->user()->can('controlar', $b);

            // ESTADO DE LOS BOTONES
            $bloquearEncender = $modoAutomatico
                ? ! $bloqueada
                : ($funcionando || $ordenEncendido);

            $bloquearApagar = $bloqueada;

            $tituloEncender = '';

            if ($bloquearEncender) {
                $tituloEncender = $modoAutomatico
                    ? 'No hay bloqueo que quitar'
                    : ($funcionando
                        ? 'La bomba ya está funcionando'
                        : 'La orden de encendido ya fue enviada');
            }

            $tituloApagar = $bloquearApagar
                ? 'La bomba ya está apagada y bloqueada'
                : '';

            // MENSAJES DE CONFIRMACIÓN
            $msgEncender = $modoAutomatico
                ? '¿Quitar el bloqueo y reanudar el control automático?'
                : '¿Enviar orden de encendido a '.$b->nombre.'?';

            $msgApagar = '¿Detener la bomba y activar el bloqueo remoto?';

            $msgModo = '¿Deseas cambiar el modo de operación de '.$b->nombre.'?';
        @endphp

        <div class="flex flex-wrap items-center gap-3 py-4 border-b border-gray-100 last:border-b-0">

            {{-- INDICADOR DE FUNCIONAMIENTO REAL --}}
            <span class="h-3 w-3 rounded-full flex-shrink-0
                {{ $funcionando ? 'bg-green-500' : 'bg-gray-300' }}"
                title="{{ $funcionando ? 'Funcionando realmente' : 'Apagada' }}">
            </span>

            {{-- INFORMACIÓN DE LA BOMBA --}}
            <div class="min-w-0 flex-1 basis-48">

                @if ($puede)
                    <a href="{{ route('bombas.show', $b->id) }}"
                       class="font-semibold text-gray-800 hover:text-blue-600">
                        {{ $b->nombre }}
                    </a>
                @else
                    <span class="font-semibold text-gray-800">
                        {{ $b->nombre }}
                    </span>
                @endif

                <p class="text-xs text-gray-500 truncate">
                    {{ $b->codigo }}
                    ·
                    {{ $b->centroSalud->nombre ?? 'Sin centro' }}
                </p>

                {{-- FUNCIONAMIENTO REAL --}}
                <p class="text-xs mt-1 font-medium
                    {{ $funcionando ? 'text-green-600' : 'text-gray-500' }}">
                    {{ $funcionando ? '● Funcionando' : '● Apagada' }}
                </p>

                {{-- MODO ACTUAL --}}
                <p class="text-xs mt-1 font-medium
                    {{ $modoAutomatico ? 'text-blue-600' : 'text-orange-600' }}">
                    Modo: {{ $modoAutomatico ? 'AUTOMÁTICO' : 'MANUAL' }}
                </p>

                {{-- ORDEN PENDIENTE / BLOQUEO --}}
                @if ($bloqueada)
                    <p class="text-xs mt-1 font-medium text-red-600">
                        🔒 Bloqueada remotamente
                    </p>
                @elseif (! $modoAutomatico && $b->encendido && ! $funcionando)
                    <p class="text-xs mt-1 font-medium text-blue-600">
                        Orden de encendido enviada, esperando a la bomba…
                    </p>
                @endif
            {{--
                
                <div class="mt-3 w-full max-w-sm">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-semibold text-gray-600">
                            Nivel del tanque
                        </span>
                

                        @if ($nivelPct !== null)
                            <span class="font-semibold text-gray-800">
                                {{ number_format($nivelPct, 1) }}%
                            </span>
                        @else
                            <span class="text-gray-400">
                                Sin datos
                            </span>
                        @endif
                    </div>

                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500
                                {{ $nivelPct === null
                                    ? 'bg-gray-300'
                                    : ($nivelPct >= 60
                                        ? 'bg-green-500'
                                        : ($nivelPct >= 30
                                            ? 'bg-yellow-500'
                                            : 'bg-red-500')) }}"
                            style="width: {{ $nivelPct !== null ? max(0, min(100, $nivelPct)) : 0 }}%">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-1 mt-1 text-xs text-gray-500">
                        <span>
                            {{ $nivelLitros !== null
                                ? number_format($nivelLitros, 1).' L estimados'
                                : 'Litros no disponibles' }}
                        </span>

                        <span>{{ $nivelEstado }}</span>
                    </div>
                </div> 
            --}}

            </div>

            {{-- ESTADO DE SALUD --}}
            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $pctColor }}"
                  title="Salud de la bomba">
                {{ $pct === null ? 'Salud —' : 'Salud '.number_format($pct, 0).'%' }}
            </span>

            {{-- ALERTAS PENDIENTES --}}
            @if ($b->alertas_pendientes > 0)
                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700">
                    🔔 {{ $b->alertas_pendientes }} pendiente(s)
                </span>
            @endif

            {{-- CONTROLES --}}
            @if ($puede)

                <div class="flex flex-wrap items-center gap-2">

                    {{-- SELECTOR MANUAL / AUTOMÁTICO --}}
                    <form action="{{ route('bombas.modo', $b->id) }}"
                          method="POST"
                          onsubmit="return confirm(@js($msgModo));">

                        @csrf

                        <select name="modo_operacion"
                                onchange="this.form.requestSubmit()"
                                class="border rounded-lg text-sm py-1.5 px-2
                                {{ $modoAutomatico
                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                    : 'bg-orange-50 text-orange-700 border-orange-200' }}">

                            <option value="0" @selected(!$modoAutomatico)>Manual</option>
                            <option value="1" @selected($modoAutomatico)>Automático</option>

                        </select>

                    </form>

                    {{-- ENCENDER / REANUDAR --}}
                    <form action="{{ route('bombas.encender', $b->id) }}"
                          method="POST"
                          onsubmit="if (!confirm(@js($msgEncender))) return false; this.querySelector('button').disabled = true; return true;">

                        @csrf

                        <button type="submit"
                                @disabled($bloquearEncender)
                                title="{{ $tituloEncender }}"
                                class="px-3 py-1.5 rounded-lg text-sm text-white transition
                                {{ $bloquearEncender
                                    ? 'bg-blue-300 cursor-not-allowed'
                                    : 'bg-blue-600 hover:bg-blue-700' }}">
                            {{ $modoAutomatico ? 'Reanudar' : 'Encender' }}
                        </button>

                    </form>

                    {{-- APAGAR / BLOQUEAR --}}
                    <form action="{{ route('bombas.apagar', $b->id) }}"
                          method="POST"
                          onsubmit="if (!confirm(@js($msgApagar))) return false; this.querySelector('button').disabled = true; return true;">

                        @csrf

                        <button type="submit"
                                @disabled($bloquearApagar)
                                title="{{ $tituloApagar }}"
                                class="px-3 py-1.5 rounded-lg text-sm text-white transition
                                {{ $bloquearApagar
                                    ? 'bg-red-300 cursor-not-allowed'
                                    : 'bg-red-600 hover:bg-red-700' }}">
                            Apagar
                        </button>

                    </form>

                    {{-- VER DETALLES --}}
                    <a href="{{ route('bombas.show', $b->id) }}"
                       class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                        Ver
                    </a>

                </div>

            @else

                {{-- USUARIOS SIN PERMISO DE CONTROL --}}
                <a href="{{ route('bombas.show', $b->id) }}"
                   class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                    Ver detalles
                </a>

            @endif

        </div>

    @empty

        <p class="py-8 text-center text-gray-500">
            No hay bombas que coincidan con el filtro.
        </p>

    @endforelse

    {{-- PAGINACIÓN --}}
    <div class="mt-4">
        {{ $bombas->links() }}
    </div>

</div>
