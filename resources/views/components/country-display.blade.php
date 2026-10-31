@props(['country' => null, 'code' => null, 'name' => null])

@php
    $display = app(\App\Services\CountryDisplayService::class)->present(
        $code ?? (is_array($country) ? ($country['code'] ?? null) : null),
        $name ?? (is_array($country) ? ($country['name'] ?? null) : null),
    );
@endphp
<span {{ $attributes->class('nw-job-country') }} data-country-code="{{ $display['code'] }}" x-data="{ flagFailed: false }">
    @if ($display['flag_url'])
        <img src="{{ $display['flag_url'] }}" width="20" height="20" alt="" x-show="!flagFailed" x-init="if ($el.complete && !$el.naturalWidth) flagFailed = true" x-on:error="flagFailed = true">
        <span x-show="flagFailed" x-cloak aria-hidden="true"><x-icon name="globe" class="h-5 w-5 shrink-0" /></span>
    @else
        <x-icon name="globe" class="h-5 w-5 shrink-0" />
    @endif
    <span>{{ $display['name'] }}</span>
</span>
