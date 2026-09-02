@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'primary',
    'hint' => null,
    'trend' => null,
])

@php
    $tone = [
        'primary' => 'bg-primary/10 text-primary',
        'secondary' => 'bg-secondary/10 text-secondary',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'error' => 'bg-error/10 text-error',
        'info' => 'bg-info/10 text-info',
    ][$color] ?? 'bg-primary/10 text-primary';
@endphp

<div {{ $attributes->class(['surface flex items-center gap-4 p-5']) }}>
    @if ($icon)
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $tone }}">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
        </span>
    @endif

    <div class="min-w-0">
        <p class="muted truncate">{{ $label }}</p>
        <p class="numeric text-2xl font-extrabold">{{ $value }}</p>
        @if ($hint)
            <p class="muted mt-0.5 truncate text-xs">{{ $hint }}</p>
        @endif
    </div>

    @if ($trend !== null)
        <span @class([
            'ms-auto shrink-0 text-sm font-bold numeric',
            'text-success' => $trend >= 0,
            'text-error' => $trend < 0,
        ])>{{ $trend >= 0 ? '+' : '' }}{{ $trend }}%</span>
    @endif
</div>
