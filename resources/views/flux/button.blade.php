@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';

$variants = [
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    'secondary' => 'bg-zinc-200 text-zinc-900 hover:bg-zinc-300 focus:ring-zinc-500 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost' => 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
    {{ $slot }}
</a>
@else
<button {{ $attributes->class([$classes]) }}>
    {{ $slot }}
</button>
@endif

