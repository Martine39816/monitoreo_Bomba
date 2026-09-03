<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Sensor
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <form method="POST" action="{{ route('sensores.update', $sensor->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="codigo" value="Código" />
                        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full" :value="old('codigo', $sensor->codigo)" required autofocus />
                        <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $sensor->nombre)" required />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tipo" value="Tipo de Sensor" />
                        <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo }}" @selected(old('tipo', $sensor->tipo) === $tipo)>
                                    {{ ucfirst($tipo) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="marca" value="Marca" />
                            <x-text-input id="marca" name="marca" type="text" class="mt-1 block w-full" :value="old('marca', $sensor->marca)" />
                            <x-input-error :messages="$errors->get('marca')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="modelo" value="Modelo" />
                            <x-text-input id="modelo" name="modelo" type="text" class="mt-1 block w-full" :value="old('modelo', $sensor->modelo)" />
                            <x-input-error :messages="$errors->get('modelo')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="valor_minimo" value="Valor Mínimo" />
                            <x-text-input id="valor_minimo" name="valor_minimo" type="number" step="0.01" class="mt-1 block w-full" :value="old('valor_minimo', $sensor->valor_minimo)" />
                            <x-input-error :messages="$errors->get('valor_minimo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="valor_maximo" value="Valor Máximo" />
                            <x-text-input id="valor_maximo" name="valor_maximo" type="number" step="0.01" class="mt-1 block w-full" :value="old('valor_maximo', $sensor->valor_maximo)" />
                            <x-input-error :messages="$errors->get('valor_maximo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="unidad_medida" value="Unidad" />
                            <x-text-input id="unidad_medida" name="unidad_medida" type="text" class="mt-1 block w-full" :value="old('unidad_medida', $sensor->unidad_medida)" />
                            <x-input-error :messages="$errors->get('unidad_medida')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="precision_sensor" value="Precisión" />
                        <x-text-input id="precision_sensor" name="precision_sensor" type="number" step="0.01" class="mt-1 block w-full" :value="old('precision_sensor', $sensor->precision_sensor)" />
                        <x-input-error :messages="$errors->get('precision_sensor')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="dispositivos_iot_id" value="Dispositivo IoT" />
                        <select id="dispositivos_iot_id" name="dispositivos_iot_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($dispositivos as $dispositivo)
                                <option value="{{ $dispositivo->id }}" @selected((string) old('dispositivos_iot_id', $sensor->dispositivos_iot_id) === (string) $dispositivo->id)>
                                    {{ $dispositivo->nombre }} ({{ $dispositivo->codigo }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dispositivos_iot_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bombas_id" value="Bomba" />
                        <select id="bombas_id" name="bombas_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($bombas as $bomba)
                                <option value="{{ $bomba->id }}" @selected((string) old('bombas_id', $sensor->bombas_id) === (string) $bomba->id)>
                                    {{ $bomba->nombre }} ({{ $bomba->codigo }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('bombas_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="estado" value="Estado" />
                        <select id="estado" name="estado" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" @selected(old('estado', $sensor->estado) === $estado)>
                                    {{ ucfirst(str_replace('_', ' ', $estado)) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="fecha_instalacion" value="Fecha de Instalación" />
                            <x-text-input id="fecha_instalacion" name="fecha_instalacion" type="date" class="mt-1 block w-full" :value="old('fecha_instalacion', $sensor->fecha_instalacion?->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('fecha_instalacion')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="ubicacion" value="Ubicación" />
                            <x-text-input id="ubicacion" name="ubicacion" type="text" class="mt-1 block w-full" :value="old('ubicacion', $sensor->ubicacion)" />
                            <x-input-error :messages="$errors->get('ubicacion')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                        <a href="{{ route('sensores.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>