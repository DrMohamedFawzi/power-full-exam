@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'hint' => null,
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

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class(['select select-bordered w-full', 'select-error' => $hasError]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $selected) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hasError)
        <p class="text-error mt-1 text-xs font-semibold">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="muted mt-1 text-xs">{{ $hint }}</p>
    @endif
</div>
