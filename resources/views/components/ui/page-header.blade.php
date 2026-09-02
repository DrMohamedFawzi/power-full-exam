@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if (! empty($breadcrumbs))
            <nav class="breadcrumbs muted p-0 text-sm" aria-label="مسار التنقل">
                <ul>
                    @foreach ($breadcrumbs as $label => $url)
                        <li>
                            @if ($url)
                                <a href="{{ $url }}" class="hover:text-primary transition-colors">{{ $label }}</a>
                            @else
                                <span class="text-base-content/80">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <h1 class="mt-1 text-2xl font-extrabold lg:text-3xl">{{ $title }}</h1>

        @if ($description)
            <p class="muted mt-1 max-w-2xl">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
