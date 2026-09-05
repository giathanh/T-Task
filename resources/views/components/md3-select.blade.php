@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
])

@php
    $label = $label ?? Str::of($name)->replace('_', ' ')->title();
    $hasError = $errors->has($name);
    $selected = old($name, $value);
@endphp

<div>
    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $attributes->merge([
                'class' => 'block h-14 w-full appearance-none rounded-t-xl border-0 border-b-2 bg-surface-container-highest px-4 pb-1.5 pt-6 text-base text-on-surface outline-none transition focus:ring-0 '
                    . ($hasError ? 'border-error' : 'border-outline-variant hover:border-on-surface focus:border-primary'),
            ]) }}
        >
            @if ($placeholder)
                <option value="" @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}" @selected((string) $selected === (string) $option['value'])>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <label
            for="{{ $name }}"
            class="pointer-events-none absolute start-4 top-2 text-xs {{ $hasError ? 'text-error' : 'text-on-surface-variant' }}"
        >{{ $label }}</label>
        <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
