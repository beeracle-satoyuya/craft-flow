@props([
    'position' => 'bottom',
    'align' => 'start',
])

<div {{ $attributes->class(['relative']) }} x-data="{ open: false }">
    <div @click="open = !open">
        {{ $slot }}
    </div>
</div>
