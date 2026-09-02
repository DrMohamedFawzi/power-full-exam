@props([
    'value' => 100,
    'showLabel' => true,
])

@php
    $value = max(0, min(100, (int) $value));
    [$tone, $label] = match (true) {
        $value >= 85 => ['bg-success', 'ممتاز'],
        $value >= 60 => ['bg-warning', 'مقبول'],
        default => ['bg-error', 'مشبوه'],
    };
@endphp

<div {{ $attributes->class(['flex flex-col gap-1']) }}>
    @if ($showLabel)
        <div class="flex items-center justify-between text-xs font-semibold">
            <span class="muted">مؤشر النزاهة</span>
            <span class="numeric">{{ $value }}% · {{ $label }}</span>
        </div>
    @endif

    <div class="integrity-bar" role="meter" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="مؤشر النزاهة">
        <span class="{{ $tone }}" style="width: {{ $value }}%"></span>
    </div>
</div>
