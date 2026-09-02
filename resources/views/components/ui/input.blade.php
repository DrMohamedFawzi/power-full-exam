@props([
    'name',
    'label' => null,
    'type' => 'text',
    'hint' => null,
    'icon' => null,
    'value' => null,
])

@php $hasError = $errors->has($name); @endphp

<div class="form-control w-full">
    @if ($label)
        <label class="label" for="{{ $name }}">
            <span class="label-text font-semibold">
                {{ $label }}
                @if ($attributes->has('required'))
                    <span class="text-error" aria-hidden="true">*</span>
                @endif
            </span>
        </label>
    @endif

    <label @class(['input input-bordered flex w-full items-center gap-2', 'input-error' => $hasError])>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="text-base-content/40 size-4 shrink-0" />
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
            {{ $attributes->class(['grow']) }}
        >
    </label>

    @if ($hasError)
        <p id="{{ $name }}-error" class="text-error mt-1 text-xs font-semibold">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="muted mt-1 text-xs">{{ $hint }}</p>
    @endif
</div>
