@props(['status'])

<span {{ $attributes->class(['nw-status', 'nw-status--'.\Illuminate\Support\Str::slug($status)]) }}>{{ $status }}</span>
