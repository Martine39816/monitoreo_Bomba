<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Mantenimiento
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-3">

                <div><span class="font-semibold">Tipo:</span> <span class="capitalize">{{ $mantenimiento->tipo }}</span></div>
                <div><span class="font-semibold">Bomba:</span> {{ $mantenimiento->bomba->nombre ?? '—' }}</div>
                <div><span class="font-semibold">Fecha:</span> {{ $mantenimiento->fecha->format('d/m/Y') }}</div>
                <div><span class="font-semibold">Responsable:</span> {{ $mantenimiento->usuario->name ?? '—' }}</div>

                @if ($mantenimiento->alerta)
                    <div>
                        <span class="font-semibold">Originado por alerta:</span>
                        <a href="{{ route('alertas.show', $mantenimiento->alerta->id) }}" class="text-blue-600">
                            #{{ $mantenimiento->alerta->id }} — {{ ucfirst(str_replace('_', ' ', $mantenimiento->alerta->tipo)) }}
                        </a>
                    </div>
                @else
                    <div><span class="font-semibold">Origen:</span> Mantenimiento programado</div>
                @endif

                @if ($mantenimiento->descripcion)
                    <div><span class="font-semibold">Descripción:</span> {{ $mantenimiento->descripcion }}</div>
                @endif

                @if ($mantenimiento->observaciones)
                    <div><span class="font-semibold">Observaciones:</span> {{ $mantenimiento->observaciones }}</div>
                @endif

                @if ($mantenimiento->proximo_mantenimiento)
                    <div><span class="font-semibold">Próximo mantenimiento:</span> {{ $mantenimiento->proximo_mantenimiento->format('d/m/Y') }}</div>
                @endif

                <div class="pt-4">
                    <a href="{{ route('mantenimientos.index') }}" class="text-blue-600">← Volver al listado</a>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>