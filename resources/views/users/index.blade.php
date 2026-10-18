<x-app-layout>

    @php $esAdmin = auth()->user()->esAdministrador(); @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $esAdmin ? 'Gestión de Usuarios' : 'Técnicos de mi Centro' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">
                        {{ $esAdmin ? 'Lista de Usuarios' : 'Técnicos asignados a mi centro' }}
                    </h3>

                    @if ($esAdmin)
                        <a href="{{ route('users.create') }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded">
                            Nuevo Usuario
                        </a>
                    @endif
                </div>

                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Nombre</th>
                            <th class="border p-2">Correo</th>
                            <th class="border p-2">Rol</th>
                            <th class="border p-2">Centro de Salud</th>
                            <th class="border p-2">Estado</th>
                            <th class="border p-2">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="border p-2">{{ $user->id }}</td>
                            <td class="border p-2">{{ $user->name }}</td>
                            <td class="border p-2">{{ $user->correo }}</td>
                            <td class="border p-2 capitalize">{{ $user->rol }}</td>
                            <td class="border p-2">{{ $user->centroSalud->nombre ?? '—' }}</td>
                            <td class="border p-2">
                                {{ $user->estado ? 'Activo' : 'Inactivo' }}
                            </td>
                            <td class="border p-2 space-x-2">
                                <a href="{{ route('users.show', $user->id) }}" class="text-gray-600">Ver</a>
                                @if ($esAdmin)
                                    <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600">Editar</a>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4">
                                {{ $esAdmin ? 'No existen usuarios registrados.' : 'No hay técnicos asignados a tu centro.' }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>

</x-app-layout>