@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-on-surface-variant']) }}>
    {{ $value ?? $slot }}
</label>
