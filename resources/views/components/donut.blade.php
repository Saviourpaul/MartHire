@props(['segments', 'label' => 'Total'])

@php
    // Each segment is ['label' => string, 'value' => number, 'tone' => 1-5]. The tone picks its colour, so a
    // legend can use the same number. The circle is sized so its length is 100, which lets a segment's share
    // be used directly as its dash length.
    $segments = collect($segments)->filter(fn ($segment) => $segment['value'] > 0)->values();
    $total = max($segments->sum('value'), 1);
    $offset = 25;
@endphp

<div {{ $attributes->class('nw-donut') }}>
    <svg viewBox="0 0 42 42" role="img" aria-label="{{ $segments->map(fn ($segment) => $segment['label'].': '.$segment['value'])->implode(', ') }}">
        <circle class="nw-donut__track" cx="21" cy="21" r="15.9155" />
        @foreach ($segments as $segment)
            @php
                $share = $segment['value'] / $total * 100;
                $dash = max($share - ($segments->count() > 1 ? 1.4 : 0), 0.5);
            @endphp
            <circle class="nw-donut__segment nw-donut__segment--{{ $segment['tone'] ?? $loop->iteration }}" cx="21" cy="21" r="15.9155" stroke-dasharray="{{ round($dash, 2) }} {{ round(100 - $dash, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}" />
            @php $offset -= $share; @endphp
        @endforeach
    </svg>
    <div class="nw-donut__centre"><strong>{{ $segments->sum('value') }}</strong><span>{{ $label }}</span></div>
</div>
