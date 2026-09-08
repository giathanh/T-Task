<button {{ $attributes->merge(['type' => 'submit', 'class' => 'glass-button glass-button--error inline-flex h-10 items-center justify-center gap-2 rounded-full px-6 text-sm font-medium tracking-[0.1px] text-on-error focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error disabled:pointer-events-none disabled:opacity-40']) }}>
    {{ $slot }}
</button>
