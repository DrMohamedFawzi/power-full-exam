@props([
    'name',
    'label' => null,
    'hint' => null,
    'rows' => 4,
    'value' => null,
])

@php $hasError = $errors->has($name); @endphp

<div class="form-control w-full">
    @if ($label)
        <label class="label" for="{{ $name }}">
            <span class="label-text font-semibold">{{ $label }}</span>
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class(['textarea textarea-bordered w-full', 'textarea-error' => $hasError]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p class="text-error mt-1 text-xs font-semibold">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="muted mt-1 text-xs">{{ $hint }}</p>
    @endif
</div>
