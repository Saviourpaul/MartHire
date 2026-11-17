@use('BackedEnum', 'NativeBackedEnum')
@use('Illuminate\Support\Str')

@props(['status'])

@php
    $statusLabel = $status instanceof NativeBackedEnum
        ? (method_exists($status, 'label') ? $status->label() : $status->value)
        : $status;
@endphp

<span {{ $attributes->class(['nw-status', 'nw-status--'.Str::slug($statusLabel)]) }}>{{ $statusLabel }}</span>
