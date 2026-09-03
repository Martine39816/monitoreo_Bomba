<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Monitoreo de Bombas de Agua</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="max-w-lg w-full mx-auto px-6 text-center">

        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            Sistema de Monitoreo de Bombas de Agua
        </h1>
        <p class="text-gray-500 mb-8">
            Centros de Salud de Cercado, Cochabamba
        </p>

        <div class="bg-white shadow rounded-lg p-8">
            @auth
                <p class="text-gray-600 mb-4">Ya tienes una sesión activa.</p>
                <a href="{{ route('dashboard') }}"
                   class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Ir al Panel Principal
                </a>
            @else
                <p class="text-gray-600 mb-4">Inicia sesión para continuar.</p>
                <a href="{{ route('login') }}"
                   class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Iniciar Sesión
                </a>
            @endauth
        </div>

    </div>

</body>
</html>