<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Usuario
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-3">

                <div><span class="font-semibold">Nombre:</span> {{ $user->name }}</div>
                <div><span class="font-semibold">Correo:</span> {{ $user->correo }}</div>
                <div><span class="font-semibold">Teléfono:</span> {{ $user->telefono ?? '—' }}</div>
                <div><span class="font-semibold">Rol:</span> <span class="capitalize">{{ $user->rol }}</span></div>
                <div><span class="font-semibold">Centro de Salud:</span> {{ $user->centroSalud->nombre ?? '—' }}</div>
                <div><span class="font-semibold">Estado:</span> {{ $user->estado ? 'Activo' : 'Inactivo' }}</div>
                <div><span class="font-semibold">Último acceso:</span> {{ $user->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}</div>

                <div class="pt-4">
                    <a href="{{ route('users.index') }}" class="text-blue-600">← Volver al listado</a>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>