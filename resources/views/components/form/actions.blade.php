@props(['cancel' => null])
<div {{ $attributes->class('nw-actions') }}>
    @isset($cancel)
        {{ $cancel }}
    @endisset
    {{ $slot }}
</div>
