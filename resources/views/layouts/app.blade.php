<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'لوحة التحكم') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <x-layout.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar />

            <main class="flex-1 p-4 lg:p-8">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset

                <x-ui.flash />

                {{ $slot }}
            </main>

            <x-layout.footer />
        </div>
    </div>

    <x-ui.toast-host />
</body>
</html>
