@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
])

@php
    $label = $label ?? Str::of($name)->replace('_', ' ')->title();
    $hasError = $errors->has($name);
@endphp

<div>
    <div class="relative">
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder=" "
            {{ $attributes->merge([
                'class' => 'peer block w-full rounded-t-xl border-0 border-b-2 bg-surface-container-highest px-4 pb-1.5 pt-6 text-base text-on-surface outline-none transition focus:ring-0 '
                    . ($hasError ? 'border-error' : 'border-outline-variant hover:border-on-surface focus:border-primary'),
            ]) }}
        >{{ old($name, $value) }}</textarea>
        <label
            for="{{ $name }}"
            class="pointer-events-none absolute start-4 top-4 origin-left text-base transition-all duration-150 ease-out peer-focus:top-2 peer-focus:text-xs peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:text-xs {{ $hasError ? 'text-error' : 'text-on-surface-variant peer-focus:text-primary' }}"
        >{{ $label }}</label>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
