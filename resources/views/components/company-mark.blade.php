@props(['company', 'size' => null, 'logo' => null])

@php
    $initials = collect(preg_split('/[\s&]+/', $company, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $tone = ['mint', 'peach', 'accent', 'soft'][crc32($company) % 4];
@endphp

<span {{ $attributes->class(['nw-monogram', "nw-monogram--{$tone}", "nw-monogram--{$size}" => $size, 'nw-monogram--logo' => $logo]) }}
      @if ($logo) x-data="{ logoFailed: false }" @endif aria-hidden="true">
    @if ($logo)
        <img src="{{ $logo }}" alt="" width="{{ $size === 'lg' ? 76 : 46 }}" height="{{ $size === 'lg' ? 76 : 46 }}"
             class="nw-monogram__image" x-show="!logoFailed"
             x-init="if ($el.complete && !$el.naturalWidth) logoFailed = true"
             x-on:error="logoFailed = true">
        <span x-show="logoFailed" x-cloak>{{ $initials }}</span>
    @else
        {{ $initials }}
    @endif
</span>
