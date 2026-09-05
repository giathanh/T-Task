@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl bg-secondary-container px-4 py-3 text-start text-base font-medium text-on-secondary-container transition'
            : 'block w-full rounded-xl px-4 py-3 text-start text-base font-medium text-on-surface-variant transition hover:bg-on-surface/8 hover:text-on-surface';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
