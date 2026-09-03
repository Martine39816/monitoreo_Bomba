<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Mantenimiento
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <form method="POST" action="{{ route('mantenimientos.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="tipo" value="Tipo de Mantenimiento" />
                        <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Selecciona --</option>
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>
                                    {{ ucfirst($tipo) }}
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
                        <x-input-label for="alertas_id" value="Alerta que lo origina (opcional)" />
                        <select id="alertas_id" name="alertas_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Ninguna (mantenimiento programado) --</option>
                            @foreach ($alertasAbiertas as $alerta)
                                <option value="{{ $alerta->id }}"
                                    @selected((string) old('alertas_id', $alertaPreseleccionada) === (string) $alerta->id)>
                                    #{{ $alerta->id }} — {{ ucfirst(str_replace('_', ' ', $alerta->tipo)) }} ({{ $alerta->fecha_hora->format('d/m/Y H:i') }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('alertas_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fecha" value="Fecha del Mantenimiento" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" :value="old('fecha', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción del trabajo realizado" />
                        <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <textarea id="observaciones" name="observaciones" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('observaciones') }}</textarea>
                        <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="proximo_mantenimiento" value="Próximo Mantenimiento (opcional)" />
                        <x-text-input id="proximo_mantenimiento" name="proximo_mantenimiento" type="date" class="mt-1 block w-full" :value="old('proximo_mantenimiento')" />
                        <x-input-error :messages="$errors->get('proximo_mantenimiento')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar Mantenimiento</x-primary-button>
                        <a href="{{ route('mantenimientos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>