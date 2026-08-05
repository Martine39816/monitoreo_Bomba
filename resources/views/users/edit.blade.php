<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Usuario
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                <form method="POST" action="{{ route('users.update', $user->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                                      :value="old('nombre', $user->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="apellido" value="Apellido" />
                        <x-text-input id="apellido" name="apellido" type="text" class="mt-1 block w-full"
                                      :value="old('apellido', $user->apellido)" required />
                        <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email', $user->correo)" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="telefono" value="Teléfono" />
                        <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
                                      :value="old('telefono', $user->telefono)" />
                        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rol" value="Rol" />
                            <select id="rol" name="rol" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                <option value="administrador" @selected(old('rol', $user->rol) === 'administrador')>Administrador</option>
                                <option value="tecnico" @selected(old('rol', $user->rol) === 'tecnico')>Técnico</option>
                                <option value="director" @selected(old('rol', $user->rol) === 'director')>Director</option>
                            </select>
                        <x-input-error :messages="$errors->get('rol')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="centros_salud_id" value="Centro de Salud" />
                        <select id="centros_salud_id" name="centros_salud_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            @foreach ($centros as $centro)
                                <option value="{{ $centro->id }}" @selected((string) old('centros_salud_id', $user->centros_salud_id) === (string) $centro->id)>
                                    {{ $centro->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('centros_salud_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="estado" value="Estado" />
                        <select id="estado" name="estado" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="1" @selected(old('estado', (int) $user->estado) == 1)>Activo</option>
                            <option value="0" @selected(old('estado', (int) $user->estado) == 0)>Inactivo</option>
                        </select>
                        <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Nueva Contraseña (opcional)" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        <p class="text-xs text-gray-500 mt-1">Déjalo en blanco si no quieres cambiarla.</p>
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar Nueva Contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>