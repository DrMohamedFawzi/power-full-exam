@props([
    'color' => 'neutral',
    'icon' => null,
    'size' => 'sm',
])

@php
    $tone = [
        'primary' => 'badge-primary',
        'secondary' => 'badge-secondary',
        'accent' => 'badge-accent',
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'error' => 'badge-error',
        'info' => 'badge-info',
        'neutral' => 'badge-neutral',
    ][$color] ?? 'badge-neutral';
@endphp

<span {{ $attributes->class(['badge badge-soft gap-1 font-semibold', $tone, 'badge-'.$size]) }}>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-3.5" />
    @endif
    {{ $slot }}
</span>
