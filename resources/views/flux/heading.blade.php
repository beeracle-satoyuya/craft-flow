@props([
    'size' => '2xl',
])

@php
$sizes = [
    'xl' => 'text-xl',
    '2xl' => 'text-2xl',
    '3xl' => 'text-3xl',
    '4xl' => 'text-4xl',
];

$sizeClass = $sizes[$size] ?? $sizes['2xl'];
@endphp

<h1 {{ $attributes->class([$sizeClass, 'font-bold text-zinc-900 dark:text-white']) }}>
    {{ $slot }}
</h1>
