@props([
    'icon' => 'inbox',
    'title' => 'لا توجد بيانات',
    'description' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-6 py-14 text-center']) }}>
    <span class="bg-base-200 text-base-content/40 grid size-16 place-items-center rounded-2xl">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-8" />
    </span>

    <h3 class="text-base font-bold">{{ $title }}</h3>

    @if ($description)
        <p class="muted max-w-sm">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
