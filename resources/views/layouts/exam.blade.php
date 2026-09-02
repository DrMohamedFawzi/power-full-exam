<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis" class="select-none">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https:; worker-src 'self' blob: data: https:; child-src 'self' blob: data: https:; img-src 'self' data: blob: https:; connect-src 'self' blob: data: https:;">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $title ?? 'جلسة اختبار') — {{ config('app.name') }}</title>
    <!-- Face-API.js AI Face Detection Library (required for live proctoring) -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-base-200 min-h-screen select-none overflow-x-hidden" 
    oncontextmenu="return false;" 
    onselectstart="return false;" 
    ondragstart="return false;"
    oncopy="return false;"
    oncut="return false;"
    onpaste="return false;">

    {{ $slot }}

    <x-ui.toast-host />

    {{-- Strict DevTools & Keyboard Shortcut Shield --}}
    <script>
        (function() {
            // 1. Block Inspection Keyboard Shortcuts
            window.addEventListener('keydown', function(e) {
                // F12
                if (e.key === 'F12' || e.keyCode === 123) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Ctrl+Shift+I / J / C (Inspect)
                if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Cmd+Option+I / J / C (Mac Inspect)
                if (e.metaKey && e.altKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Ctrl+U (View Source), Ctrl+S (Save), Ctrl+P (Print)
                if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            // 2. High-Frequency DevTools Debugger Trap
            let devtoolsOpen = false;
            setInterval(function() {
                const startTime = performance.now();
                (function() {}['constructor']('debugger')());
                const endTime = performance.now();
                if (endTime - startTime > 100) {
                    if (!devtoolsOpen) {
                        devtoolsOpen = true;
                        console.warn('🚨 DevTools Inspect Detected!');
                        if (window.Alpine) {
                            window.dispatchEvent(new CustomEvent('devtools-detected'));
                        }
                    }
                } else {
                    devtoolsOpen = false;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
