@props([])

<div {{ $attributes->class([
    'absolute right-0 mt-2 rounded-lg bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 py-1',
    'z-50',
]) }} x-show="open" @click.outside="open = false" x-cloak>
    {{ $slot }}
</div>

