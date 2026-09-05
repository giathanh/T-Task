@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-tertiary-container px-4 py-3 text-sm font-medium text-on-tertiary-container']) }}>
        {{ $status }}
    </div>
@endif
