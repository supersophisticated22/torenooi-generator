@props(['current' => 'account'])

@php
    $steps = [
        'account' => 'Account',
        'organization' => 'Organization',
        'plan' => 'Plan',
        'payment' => 'Payment',
        'ready' => 'Ready',
    ];

    $currentIndex = array_search($current, array_keys($steps), true);
@endphp

<div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="grid grid-cols-5 gap-2 text-center text-xs sm:text-sm">
        @foreach ($steps as $key => $label)
            @php($index = array_search($key, array_keys($steps), true))
            <div class="rounded-md px-2 py-2 {{ $index === $currentIndex ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : ($index < $currentIndex ? 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-100' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400') }}">
                {{ $label }}
            </div>
        @endforeach
    </div>
</div>
