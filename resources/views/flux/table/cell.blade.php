@props([
    'header' => false,
])

@if($header)
<th {{ $attributes->class(['px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider']) }}>
    {{ $slot }}
</th>
@else
<td {{ $attributes->class(['px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100']) }}>
    {{ $slot }}
</td>
@endif

