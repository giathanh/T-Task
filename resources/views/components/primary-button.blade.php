<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-10 items-center justify-center gap-2 rounded-full bg-primary px-6 text-sm font-medium tracking-[0.1px] text-on-primary shadow-elevation-1 transition hover:shadow-elevation-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary active:shadow-none disabled:pointer-events-none disabled:opacity-40']) }}>
    {{ $slot }}
</button>
