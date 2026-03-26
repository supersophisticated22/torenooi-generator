@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-9 items-center justify-center">
            <x-app-logo-icon class="h-9 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Toernooigenerator" {{ $attributes }}>
        <x-slot name="logo" class="flex h-10 items-center justify-center">
            <x-app-logo-icon class="h-10 w-auto" />
        </x-slot>
    </flux:brand>
@endif
