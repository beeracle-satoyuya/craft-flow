@props([
    'icon' => 'bars-3',
    'inset' => null,
])

<button {{ $attributes->class(['p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800']) }} data-flux-sidebar-toggle>
    <flux:icon :name="$icon" class="w-6 h-6" />
</button>
