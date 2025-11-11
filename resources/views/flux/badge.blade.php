@props([
    'color' => 'zinc',
    'size' => 'md',
])

@php
$colors = [
    'zinc' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
    'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
];

$colorClass = $colors[$color] ?? $colors['zinc'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->class([$colorClass, $sizeClass, 'inline-flex items-center rounded-full font-medium']) }}>
    {{ $slot }}
</span>

