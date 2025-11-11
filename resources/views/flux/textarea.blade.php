<textarea {{ $attributes->class([
    'block w-full rounded-lg border-zinc-300 shadow-sm',
    'focus:border-indigo-500 focus:ring-indigo-500',
    'dark:bg-zinc-800 dark:border-zinc-700 dark:text-white',
    'disabled:bg-zinc-100 disabled:cursor-not-allowed dark:disabled:bg-zinc-900',
]) }}>{{ $slot }}</textarea>
