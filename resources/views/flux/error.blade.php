@props([
    'name' => null,
])

@if($name)
    @error($name)
        <p {{ $attributes->class(['text-sm text-red-600 dark:text-red-400 mt-1']) }}>
            {{ $message }}
        </p>
    @enderror
@else
    <p {{ $attributes->class(['text-sm text-red-600 dark:text-red-400 mt-1']) }}>
        {{ $slot }}
    </p>
@endif
