<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva Bomba
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <form method="POST" action="{{ route('bombas.store') }}" class="space-y-6">
                    @csrf

                    {{-- ===== Datos generales ===== --}}
                    <div>
                        <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos Generales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="codigo" value="Código" />
                                <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full" :value="old('codigo')" required />
                                <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre')" required />
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="marca" value="Marca" />
                                <x-text-input id="marca" name="marca" type="text" class="mt-1 block w-full" :value="old('marca')" />
                                <x-input-error :messages="$errors->get('marca')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="modelo" value="Modelo" />
                                <x-text-input id="modelo" name="modelo" type="text" class="mt-1 block w-full" :value="old('modelo')" />
                                <x-input-error :messages="$errors->get('modelo')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="serie" value="Número de Serie" />
                                <x-text-input id="serie" name="serie" type="text" class="mt-1 block w-full" :value="old('serie')" required />
                                <x-input-error :messages="$errors->get('serie')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="fecha_instalacion" value="Fecha de Instalación" />
                                <x-text-input id="fecha_instalacion" name="fecha_instalacion" type="date" class="mt-1 block w-full" :value="old('fecha_instalacion')" />
                                <x-input-error :messages="$errors->get('fecha_instalacion')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="centros_salud_id" value="Centro de Salud" />
                                <select id="centros_salud_id" name="centros_salud_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    <option value="">-- Selecciona --</option>
                                    @foreach ($centros as $centro)
                                        <option value="{{ $centro->id }}" @selected((string) old('centros_salud_id') === (string) $centro->id)>
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
                                        <option value="{{ $estado }}" @selected(old('estado', 'activo') === $estado)>
                                            {{ ucfirst(str_replace('_', ' ', $estado)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- ===== Ficha técnica ===== --}}
                    <div>
                        <h3 class="text-lg font-bold border-b pb-2 mb-4">Ficha Técnica</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="potencia_hp" value="Potencia (HP)" />
                                <x-text-input id="potencia_hp" name="potencia_hp" type="number" step="0.01" class="mt-1 block w-full" :value="old('potencia_hp')" />
                                <x-input-error :messages="$errors->get('potencia_hp')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="voltaje_nominal" value="Voltaje Nominal (V)" />
                                <x-text-input id="voltaje_nominal" name="voltaje_nominal" type="number" step="0.01" class="mt-1 block w-full" :value="old('voltaje_nominal')" />
                                <x-input-error :messages="$errors->get('voltaje_nominal')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="corriente_nominal" value="Corriente Nominal (A)" />
                                <x-text-input id="corriente_nominal" name="corriente_nominal" type="number" step="0.01" class="mt-1 block w-full" :value="old('corriente_nominal')" />
                                <x-input-error :messages="$errors->get('corriente_nominal')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="caudal_min" value="Caudal Mínimo" />
                                <x-text-input id="caudal_min" name="caudal_min" type="number" step="0.01" class="mt-1 block w-full" :value="old('caudal_min')" />
                                <x-input-error :messages="$errors->get('caudal_min')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="caudal_max" value="Caudal Máximo" />
                                <x-text-input id="caudal_max" name="caudal_max" type="number" step="0.01" class="mt-1 block w-full" :value="old('caudal_max')" />
                                <x-input-error :messages="$errors->get('caudal_max')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="altura_maxima" value="Altura Máxima" />
                                <x-text-input id="altura_maxima" name="altura_maxima" type="number" step="0.01" class="mt-1 block w-full" :value="old('altura_maxima')" />
                                <x-input-error :messages="$errors->get('altura_maxima')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="temperatura_maxima" value="Temperatura Máxima (°C)" />
                                <x-text-input id="temperatura_maxima" name="temperatura_maxima" type="number" step="0.01" class="mt-1 block w-full" :value="old('temperatura_maxima')" />
                                <x-input-error :messages="$errors->get('temperatura_maxima')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="presion_maxima" value="Presión Máxima" />
                                <x-text-input id="presion_maxima" name="presion_maxima" type="number" step="0.01" class="mt-1 block w-full" :value="old('presion_maxima')" />
                                <x-input-error :messages="$errors->get('presion_maxima')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- ===== Datos del tanque (fusionado) ===== --}}
                    <div>
                        <h3 class="text-lg font-bold border-b pb-2 mb-4">Datos del Tanque</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="tanque_codigo" value="Código del Tanque" />
                                <x-text-input id="tanque_codigo" name="tanque_codigo" type="text" class="mt-1 block w-full" :value="old('tanque_codigo')" />
                                <x-input-error :messages="$errors->get('tanque_codigo')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanque_capacidad_litros" value="Capacidad (litros)" />
                                <x-text-input id="tanque_capacidad_litros" name="tanque_capacidad_litros" type="number" step="0.01" class="mt-1 block w-full" :value="old('tanque_capacidad_litros')" />
                                <x-input-error :messages="$errors->get('tanque_capacidad_litros')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanque_altura_metros" value="Altura (metros)" />
                                <x-text-input id="tanque_altura_metros" name="tanque_altura_metros" type="number" step="0.01" class="mt-1 block w-full" :value="old('tanque_altura_metros')" />
                                <x-input-error :messages="$errors->get('tanque_altura_metros')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanque_diametro_metros" value="Diámetro (metros)" />
                                <x-text-input id="tanque_diametro_metros" name="tanque_diametro_metros" type="number" step="0.01" class="mt-1 block w-full" :value="old('tanque_diametro_metros')" />
                                <x-input-error :messages="$errors->get('tanque_diametro_metros')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- ===== Control ===== --}}
                    <div>
                        <h3 class="text-lg font-bold border-b pb-2 mb-4">Control Operativo</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="modo_operacion" value="Modo de Operación" />
                                <select id="modo_operacion" name="modo_operacion" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    <option value="0" @selected(old('modo_operacion', '0') == '0')>Manual</option>
                                    <option value="1" @selected(old('modo_operacion') == '1')>Automático</option>
                                </select>
                                <x-input-error :messages="$errors->get('modo_operacion')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="encendido" value="Encendida" />
                                <select id="encendido" name="encendido" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    <option value="0" @selected(old('encendido', '0') == '0')>Apagada</option>
                                    <option value="1" @selected(old('encendido') == '1')>Encendida</option>
                                </select>
                                <x-input-error :messages="$errors->get('encendido')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="observaciones" value="Observaciones" />
                            <textarea id="observaciones" name="observaciones" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar Bomba</x-primary-button>
                        <a href="{{ route('bombas.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>