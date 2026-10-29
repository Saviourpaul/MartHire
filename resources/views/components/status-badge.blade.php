@props(['status'])

@php
    $statusLabel = $status instanceof \BackedEnum
        ? (method_exists($status, 'label') ? $status->label() : $status->value)
        : $status;
@endphp

<span {{ $attributes->class(['nw-status', 'nw-status--'.\Illuminate\Support\Str::slug($statusLabel)]) }}>{{ $statusLabel }}</span>
