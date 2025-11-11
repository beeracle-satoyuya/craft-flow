@props([
    'variant' => 'outline',
])

<nav {{ $attributes->class(['space-y-1']) }}>
    {{ $slot }}
</nav>

