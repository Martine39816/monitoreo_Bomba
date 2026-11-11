<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre')" required autofocus />
            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="apellido" :value="__('Apellido')" />
            <x-text-input id="apellido" class="block mt-1 w-full" type="text" name="apellido" :value="old('apellido')" required />
            <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="rol" :value="__('Rol')" />
                <select id="rol" name="rol" class="block mt-1 w-full border-gray-300 rounded-md" required>
                    <option value="">-- Selecciona --</option>
                    <option value="administrador" @selected(old('rol') === 'administrador')>Administrador</option>
                    <option value="tecnico" @selected(old('rol') === 'tecnico')>Técnico</option>
                    <option value="director" @selected(old('rol') === 'director')>Director</option>
                </select>
            <x-input-error :messages="$errors->get('rol')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="centros_salud_id" :value="__('Centro de Salud')" />
            <select id="centros_salud_id" name="centros_salud_id" class="block mt-1 w-full border-gray-300 rounded-md" required>
                <option value="">-- Selecciona --</option>
                @foreach ($centros as $centro)
                    <option value="{{ $centro->id }}" @selected((string) old('centros_salud_id') === (string) $centro->id)>
                        {{ $centro->nombre }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('centros_salud_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('users.index') }}">
                {{ __('Cancelar') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Crear Usuario') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>