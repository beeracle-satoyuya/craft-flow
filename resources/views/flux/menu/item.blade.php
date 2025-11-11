@props([
    'icon' => null,
    'href' => null,
    'as' => 'a',
])

@php
$tag = $as;
$attrs = $href ? ['href' => $href] : [];
@endphp

<{{ $tag }} {{ $attributes->merge($attrs)->class([
    'flex items-center gap-3 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300',
    'hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors',
]) }}>
    @if($icon)
    <flux:icon :name="$icon" class="w-4 h-4" />
    @endif
    <span>{{ $slot }}</span>
</{{ $tag }}>
