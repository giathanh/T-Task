@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-secondary-container px-4 py-2 text-sm font-medium text-on-secondary-container transition'
            : 'inline-flex items-center rounded-full px-4 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-on-surface/8 hover:text-on-surface';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
