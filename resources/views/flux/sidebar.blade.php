@props([
    'sticky' => false,
    'stashable' => false,
])

<aside {{ $attributes->class([
    'w-64 h-screen overflow-y-auto flex flex-col p-6',
    'sticky top-0' => $sticky,
]) }} data-flux-sidebar>
    {{ $slot }}
</aside>

