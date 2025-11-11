@props([
    'name' => null,
])

@if($name)
    <x-dynamic-component :component="'flux::icon.' . $name" {{ $attributes }} />
@endif

