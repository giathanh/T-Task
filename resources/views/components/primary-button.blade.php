<button {{ $attributes->merge(['type' => 'submit', 'class' => 'glass-button glass-button--primary inline-flex h-10 items-center justify-center gap-2 rounded-full px-6 text-sm font-medium tracking-[0.1px] text-on-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-40']) }}>
    {{ $slot }}
</button>
