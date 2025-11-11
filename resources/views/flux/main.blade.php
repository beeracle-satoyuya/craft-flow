@props([
    'container' => false,
])

<main {{ $attributes->class([
    'py-6 px-6',
    'max-w-7xl mx-auto' => $container,
]) }}>
    {{ $slot }}
</main>
