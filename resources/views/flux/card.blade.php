@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm',
    'p-6' => $padding,
]) }}>
    {{ $slot }}
</div>

