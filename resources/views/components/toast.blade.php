@props(['message', 'type' => 'info', 'duration' => null])

@php
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $duration ??= match ($type) {
        'error', 'warning' => 0,
        'info' => 8500,
        default => 6500,
    };
    $icon = match ($type) {
        'success' => 'check-circle',
        'error' => 'circle-x',
        'warning' => 'alert-triangle',
        default => 'info',
    };
@endphp

<li class="nw-toast nw-toast--{{ $type }}" data-toast-type="{{ $type }}"
    role="{{ in_array($type, ['error', 'warning'], true) ? 'alert' : 'status' }}" aria-atomic="true"
    x-data="toastNotification({ duration: @js($duration) })" x-show="open" x-transition.opacity.duration.150ms
    x-on:pointerenter="hovered = true; pause()" x-on:pointerleave="hovered = false; resume()"
    x-on:focusin="focused = true; pause()"
    x-on:focusout="if (!$el.contains($event.relatedTarget)) { focused = false; resume(); }">
    <x-icon :name="$icon" class="nw-toast__icon" />
    <p class="nw-toast__message">{{ $message }}</p>
    <button type="button" class="nw-toast__close" x-on:click="dismiss()" aria-label="{{ __('Dismiss notification') }}">
        <x-icon name="close" />
    </button>
</li>
