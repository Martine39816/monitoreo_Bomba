<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar Lectura de Prueba
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <p class="text-sm text-gray-500 mb-4">
                    Esta pantalla simula el envío de datos que normalmente haría un dispositivo ESP32 automáticamente.
                    Úsala mientras no tengas el hardware físico conectado.
                </p>

                <form method="POST" action="{{ route('lecturas.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="sensores_id" value="Sensor" />
                        <select id="sensores_id" name="sensores_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Selecciona --</option>
                            @foreach ($sensores as $sensor)
                                <option value="{{ $sensor->id }}" @selected((string) old('sensores_id') === (string) $sensor->id)>
                                    {{ $sensor->nombre }} — {{ ucfirst($sensor->tipo) }} ({{ $sensor->bomba->nombre ?? 'sin bomba' }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sensores_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="valor_medido" value="Valor Medido" />
                        <x-text-input id="valor_medido" name="valor_medido" type="number" step="0.001" class="mt-1 block w-full" :value="old('valor_medido')" required />
                        <x-input-error :messages="$errors->get('valor_medido')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fecha_hora" value="Fecha y Hora (opcional, por defecto ahora)" />
                        <x-text-input id="fecha_hora" name="fecha_hora" type="datetime-local" class="mt-1 block w-full" :value="old('fecha_hora')" />
                        <x-input-error :messages="$errors->get('fecha_hora')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Registrar Lectura</x-primary-button>
                        <a href="{{ route('lecturas.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>