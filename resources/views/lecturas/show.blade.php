<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Alerta
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-3">

                <div>
                    <span class="font-semibold">Tipo:</span>
                    <span class="capitalize">{{ str_replace('_', ' ', $alerta->tipo) }}</span>
                </div>
                <div><span class="font-semibold">Descripción:</span> {{ $alerta->descripcion }}</div>
                <div><span class="font-semibold">Bomba:</span> {{ $alerta->bomba->nombre ?? '—' }}</div>
                <div><span class="font-semibold">Sensor:</span> {{ $alerta->sensor->nombre ?? '—' }}</div>
                @if ($alerta->valor_detectado)
                    <div><span class="font-semibold">Valor detectado:</span> {{ $alerta->valor_detectado }}</div>
                @endif
                @if ($alerta->valor_permitido)
                    <div><span class="font-semibold">Valor permitido:</span> {{ $alerta->valor_permitido }}</div>
                @endif
                <div><span class="font-semibold">Fecha del reporte:</span> {{ $alerta->fecha_hora->format('d/m/Y H:i:s') }}</div>

                <div>
                    <span class="font-semibold">Estado:</span>
                    @if ($alerta->atendida)
                        <span class="text-green-600 font-semibold">Solucionada</span>
                    @else
                        <span class="text-red-600 font-semibold">Pendiente</span>
                    @endif
                </div>

                @if ($alerta->atendida)
                    <div><span class="font-semibold">Atendida por:</span> {{ $alerta->usuarioAtencion->name ?? '—' }}</div>
                    <div><span class="font-semibold">Fecha de atención:</span> {{ $alerta->fecha_atencion?->format('d/m/Y H:i:s') }}</div>
                @else
                    <form action="{{ route('alertas.atender', $alerta->id) }}" method="POST" class="mt-4"
                          onsubmit="return confirm('¿Marcar esta alerta como solucionada?');">
                        @csrf
                        @method('PATCH')
                        <x-primary-button type="submit">Marcar como Solucionada</x-primary-button>
                    </form>
                @endif

                <div class="pt-4">
                    <a href="{{ route('alertas.index') }}" class="text-blue-600">← Volver al listado</a>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>