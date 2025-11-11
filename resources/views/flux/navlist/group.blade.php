@props([
    'heading' => null,
])

<div {{ $attributes->class(['space-y-1']) }}>
    @if($heading)
    <div class="px-3 py-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
        {{ $heading }}
    </div>
    @endif
    {{ $slot }}
</div>
