@props([
    'name',
    'label' => null,
    'type' => 'text',
    'id' => null,
    'value' => null,
])

@php
    $id = $id ?? $name;
    $label = $label ?? Str::of($name)->replace('_', ' ')->title();
    $hasError = $errors->has($name);
@endphp

<div>
    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder=" "
            {{ $attributes->merge([
                'class' => 'peer block h-14 w-full rounded-t-xl border-0 border-b-2 bg-surface-container-highest px-4 pt-5 pb-1.5 text-base text-on-surface outline-none transition focus:ring-0 '
                    . ($hasError ? 'border-error' : 'border-outline-variant hover:border-on-surface focus:border-primary'),
            ]) }}
        >
        <label
            for="{{ $id }}"
            class="pointer-events-none absolute start-4 top-4 origin-left text-base transition-all duration-150 ease-out peer-focus:top-2 peer-focus:text-xs peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:text-xs {{ $hasError ? 'text-error' : 'text-on-surface-variant peer-focus:text-primary' }}"
        >{{ $label }}</label>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
