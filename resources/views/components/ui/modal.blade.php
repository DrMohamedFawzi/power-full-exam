@props([
    'id',
    'title' => null,
    'size' => 'max-w-lg',
])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'modal']) }}>
    <div class="modal-box {{ $size }} w-full">
        @if ($title)
            <h3 class="text-lg font-bold">{{ $title }}</h3>
        @endif

        <div class="py-4">
            {{ $slot }}
        </div>

        <div class="modal-action">
            @isset($actions)
                {{ $actions }}
            @endisset

            <form method="dialog">
                <button class="btn btn-ghost">إغلاق</button>
            </form>
        </div>
    </div>

    <form method="dialog" class="modal-backdrop">
        <button aria-label="إغلاق">close</button>
    </form>
</dialog>
