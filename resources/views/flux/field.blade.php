@props([
    'label' => null,
    'error' => null,
    'help' => null,
])

<div {{ $attributes->class(['space-y-1']) }}>
    @if($label)
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ $label }}
    </label>
    @endif
    
    {{ $slot }}
    
    @if($error)
    <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
    
    @if($help)
    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $help }}</p>
    @endif
</div>
