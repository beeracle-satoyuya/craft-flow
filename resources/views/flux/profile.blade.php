@props([
    'name' => null,
    'initials' => null,
    'icon-trailing' => null,
])

<button {{ $attributes->class([
    'flex items-center gap-3 w-full px-3 py-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors',
]) }}>
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white text-sm font-medium">
        {{ $initials }}
    </div>
    @if($name)
    <div class="flex-1 text-left">
        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $name }}</div>
    </div>
    @endif
    @if($iconTrailing ?? null)
    <flux:icon :name="$iconTrailing" class="w-4 h-4 text-zinc-500" />
    @endif
</button>
