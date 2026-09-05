<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex h-10 items-center justify-center gap-2 rounded-full border border-outline bg-transparent px-6 text-sm font-medium tracking-[0.1px] text-primary transition hover:bg-primary/8 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-40']) }}>
    {{ $slot }}
</button>
