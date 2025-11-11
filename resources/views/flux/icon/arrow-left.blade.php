@props([
    'variant' => 'outline',
])

@php
$sizes = [
    'micro' => 'w-3 h-3',
    'mini' => 'w-4 h-4',
    'solid' => 'w-5 h-5',
    'outline' => 'w-6 h-6',
];
$sizeClass = $sizes[$variant] ?? $sizes['outline'];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" {{ $attributes->class([$sizeClass]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
</svg>
