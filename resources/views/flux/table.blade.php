@props([
    'heading' => null,
])

<div {{ $attributes->class(['overflow-x-auto']) }}>
    @if($heading)
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h3>
    </div>
    @endif
    
    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
        {{ $slot }}
    </table>
</div>

