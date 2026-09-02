@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'padding' => 'p-5',
])

<section {{ $attributes->class(['surface flex flex-col']) }}>
    @if ($title || isset($actions))
        <header class="border-base-300 flex items-start justify-between gap-4 border-b px-5 py-4">
            <div class="flex items-start gap-3">
                @if ($icon)
                    <span class="bg-primary/10 text-primary grid size-9 shrink-0 place-items-center rounded-xl">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" />
                    </span>
                @endif
                <div>
                    @if ($title)
                        <h2 class="section-title">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="muted mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding }} flex-1">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-base-300 bg-base-200/40 rounded-b-box border-t px-5 py-3">
            {{ $footer }}
        </footer>
    @endisset
</section>
