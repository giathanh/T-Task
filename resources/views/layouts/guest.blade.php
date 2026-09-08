<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center gap-8 px-4 py-10 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10 fill-current text-primary" />
                <span class="text-xl font-medium text-on-surface">{{ config('app.name', 'T-Task') }}</span>
            </a>

            <div class="w-full max-w-md rounded-3xl glass-strong p-8 sm:p-10">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
