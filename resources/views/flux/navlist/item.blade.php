@props([
    'icon' => null,
    'href' => '#',
    'current' => false,
])

@php
$classes = $current
    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400'
    : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800';
@endphp

<a href="{{ $href }}" {{ $attributes->class([
    $classes,
    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
]) }}>
    @if($icon)
    <flux:icon :name="$icon" class="w-5 h-5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</a>
