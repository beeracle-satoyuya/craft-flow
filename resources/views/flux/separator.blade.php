@props([
    'text' => null,
])

<div {{ $attributes->class(['relative my-6']) }}>
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
    </div>
    @if($text)
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-zinc-900 px-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $text }}
        </span>
    </div>
    @endif
</div>
