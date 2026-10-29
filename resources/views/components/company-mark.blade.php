@props(['company', 'size' => null])

@php
    $initials = collect(preg_split('/[\s&]+/', $company, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $tone = ['mint', 'peach', 'accent', 'soft'][crc32($company) % 4];
@endphp

<span {{ $attributes->class(['nw-monogram', "nw-monogram--{$tone}", "nw-monogram--{$size}" => $size]) }} aria-hidden="true">{{ $initials }}</span>
