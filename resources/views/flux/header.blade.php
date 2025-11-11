@props([
    'container' => false,
])

<header {{ $attributes->class([
    'px-6 py-4 flex items-center',
    'max-w-7xl mx-auto' => $container,
]) }}>
    {{ $slot }}
</header>
