<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Monitoreo de Agua') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased app-shell">
    <div class="app-frame">
        @include('layouts.navigation')
        <main class="app-main">
            <header class="app-topbar">
                <div class="app-topbar-copy">
                    <span class="app-eyebrow">PLATAFORMA DE SUPERVISIÓN</span>
                    <div class="app-page-heading">{{ $header ?? 'Panel de monitoreo' }}</div>
                </div>
                <div class="app-topbar-user">
                    <span class="app-user-dot"></span>
                    <div>
                        <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong>
                        <small>{{ ucfirst(auth()->user()->rol ?? '') }}</small>
                    </div>
                </div>
            </header>
            <div class="app-content">
                {{ $slot }}
            </div>
            <footer class="app-footer">
                <span>Monitoreo de bombas de agua</span>
                <span>{{ date('Y') }} · Supervisión operativa</span>
            </footer>
        </main>
    </div>
</body>
</html>
