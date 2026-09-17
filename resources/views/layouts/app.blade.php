<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
        rel="stylesheet"
    />

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="font-sans antialiased bg-gray-100">

    <div class="min-h-screen lg:flex">

        {{-- MENÚ LATERAL --}}
        @include('layouts.navigation')

        {{-- CONTENIDO PRINCIPAL --}}
        <main
            class="
                flex-1
                min-w-0
                min-h-screen
                transition-all
                duration-300
                pt-16
                lg:pt-0
            "
        >

            <div class="p-4 sm:p-6 lg:p-8">

                {{ $slot }}

            </div>

        </main>

    </div>

</body>

</html>