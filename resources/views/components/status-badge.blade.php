@use('BackedEnum', 'NativeBackedEnum')
@use('App\Enums\StatusBadgeTone')

@props(['status', 'label' => null, 'tone' => null])

@php
    $statusLabel = $label ?? ($status instanceof NativeBackedEnum
        ? (method_exists($status, 'label') ? $status->label() : $status->value)
        : $status);

    $badgeTone = $tone instanceof StatusBadgeTone
        ? $tone
        : (StatusBadgeTone::tryFrom((string) $tone)
            ?? ($status instanceof NativeBackedEnum && method_exists($status, 'badgeTone')
                ? $status->badgeTone()
                : StatusBadgeTone::Neutral));
@endphp

<span {{ $attributes->class(['nw-status', 'nw-status--'.$badgeTone->value]) }}>{{ $statusLabel }}</span>
