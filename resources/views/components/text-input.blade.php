@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block h-12 rounded-t-lg border-0 border-b-2 border-outline-variant bg-surface-container-highest px-4 text-base text-on-surface outline-none transition hover:border-on-surface focus:border-primary focus:ring-0 disabled:opacity-40']) }}>
