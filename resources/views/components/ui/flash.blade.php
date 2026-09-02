@php
    $flashes = array_filter([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ]);
    $icons = [
        'success' => 'check-circle',
        'error' => 'x-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'information-circle',
    ];
@endphp

@if ($flashes || $errors->any())
    <div class="mb-5 space-y-3">
        @foreach ($flashes as $type => $message)
            <div role="alert" class="alert alert-{{ $type }} alert-soft animate-in">
                <x-dynamic-component :component="'heroicon-o-'.$icons[$type]" class="size-5" />
                <span>{{ $message }}</span>
            </div>
        @endforeach

        @if ($errors->any())
            <div role="alert" class="alert alert-error alert-soft animate-in items-start">
                <x-heroicon-o-exclamation-circle class="size-5" />
                <div>
                    <p class="font-bold">تعذّر إتمام العملية</p>
                    <ul class="mt-1 list-inside list-disc text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
