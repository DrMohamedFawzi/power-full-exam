<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? '') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl">
    <div class="flex min-h-screen flex-col">
        <header class="container mx-auto flex items-center justify-between p-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-9" />
                <span class="text-lg font-extrabold">{{ config('app.name') }}</span>
            </a>
            <x-ui.theme-toggle />
        </header>

        <main class="flex flex-1 items-center justify-center p-4">
            <div class="w-full max-w-md">
                <x-ui.flash />
                {{ $slot }}
            </div>
        </main>

        <footer class="muted p-6 text-center">
            © {{ date('Y') }} {{ config('app.name') }} — منصة الاختبارات الآمنة
        </footer>
    </div>

    <x-ui.toast-host />
</body>
</html>
