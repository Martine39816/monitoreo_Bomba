<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Dispositivo IoT
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <form method="POST" action="{{ route('dispositivos.update', $dispositivo->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="codigo" value="Código" />
                        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full" :value="old('codigo', $dispositivo->codigo)" required autofocus />
                        <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $dispositivo->nombre)" required />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tipo_dispositivo" value="Tipo de Dispositivo" />
                        <x-text-input id="tipo_dispositivo" name="tipo_dispositivo" type="text" class="mt-1 block w-full" :value="old('tipo_dispositivo', $dispositivo->tipo_dispositivo)" />
                        <x-input-error :messages="$errors->get('tipo_dispositivo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="modelo" value="Modelo" />
                        <x-text-input id="modelo" name="modelo" type="text" class="mt-1 block w-full" :value="old('modelo', $dispositivo->modelo)" />
                        <x-input-error :messages="$errors->get('modelo')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="direccion_ip" value="Dirección IP" />
                            <x-text-input id="direccion_ip" name="direccion_ip" type="text" class="mt-1 block w-full" :value="old('direccion_ip', $dispositivo->direccion_ip)" />
                            <x-input-error :messages="$errors->get('direccion_ip')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="puerto" value="Puerto" />
                            <x-text-input id="puerto" name="puerto" type="number" class="mt-1 block w-full" :value="old('puerto', $dispositivo->puerto)" />
                            <x-input-error :messages="$errors->get('puerto')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="mac_address" value="Dirección MAC" />
                        <x-text-input id="mac_address" name="mac_address" type="text" class="mt-1 block w-full" :value="old('mac_address', $dispositivo->mac_address)" />
                        <x-input-error :messages="$errors->get('mac_address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="firmware" value="Versión de Firmware" />
                        <x-text-input id="firmware" name="firmware" type="text" class="mt-1 block w-full" :value="old('firmware', $dispositivo->firmware)" />
                        <x-input-error :messages="$errors->get('firmware')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="centros_salud_id" value="Centro de Salud" />
                        <select id="centros_salud_id" name="centros_salud_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($centros as $centro)
                                <option value="{{ $centro->id }}" @selected((string) old('centros_salud_id', $dispositivo->centros_salud_id) === (string) $centro->id)>
                                    {{ $centro->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('centros_salud_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="estado" value="Estado" />
                        <select id="estado" name="estado" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" @selected(old('estado', $dispositivo->estado) === $estado)>
                                    {{ ucfirst(str_replace('_', ' ', $estado)) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                        <a href="{{ route('dispositivos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>