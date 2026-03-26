<span {{ $attributes->class('relative inline-flex items-center justify-center') }}>
    <img
        src="{{ asset('tg_logo_dark.png') }}"
        alt="Toernooigenerator"
        class="h-auto w-auto max-h-full max-w-full object-contain dark:hidden"
    >
    <img
        src="{{ asset('tg_logo_light.png') }}"
        alt="Toernooigenerator"
        class="hidden h-auto w-auto max-h-full max-w-full object-contain dark:block"
    >
</span>
