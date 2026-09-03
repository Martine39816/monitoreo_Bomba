<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reportar Problema
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <p class="text-sm text-gray-500 mb-4">
                    Usa este formulario para reportar manualmente un problema detectado en una bomba
                    (por ejemplo, si un Director nota algo anómalo durante una visita).
                </p>

                <form method="POST" action="{{ route('alertas.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="tipo" value="Tipo de Problema" />
                        <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Selecciona --</option>
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>
                                    {{ ucfirst(str_replace('_', ' ', $tipo)) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bombas_id" value="Bomba" />
                        <select id="bombas_id" name="bombas_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Selecciona --</option>
                            @foreach ($bombas as $bomba)
                                <option value="{{ $bomba->id }}" @selected((string) old('bombas_id') === (string) $bomba->id)>
                                    {{ $bomba->nombre }} ({{ $bomba->codigo }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('bombas_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sensores_id" value="Sensor relacionado (opcional)" />
                        <select id="sensores_id" name="sensores_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Ninguno --</option>
                            @foreach ($sensores as $sensor)
                                <option value="{{ $sensor->id }}" @selected((string) old('sensores_id') === (string) $sensor->id)>
                                    {{ $sensor->nombre }} ({{ ucfirst($sensor->tipo) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sensores_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción del problema" />
                        <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full border-gray-300 rounded-md" required>{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Reportar</x-primary-button>
                        <a href="{{ route('alertas.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>