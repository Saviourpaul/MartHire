@props(['values', 'width' => 120, 'height' => 44])

@php
    $values = array_values($values);
    $count = count($values);
    $low = min($values);
    $range = max(max($values) - $low, 1);
    $points = [];

    foreach ($values as $index => $value) {
        $x = $count > 1 ? $index / ($count - 1) * $width : 0;
        $y = $height - 5 - (($value - $low) / $range) * ($height - 12);
        $points[] = round($x, 1).','.round($y, 1);
    }

    $line = 'M'.implode(' L', $points);
    $area = $line.' L'.$width.','.$height.' L0,'.$height.' Z';
@endphp

<svg {{ $attributes->class('nw-spark') }} viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path class="nw-spark__area" d="{{ $area }}" />
    <path class="nw-spark__line" d="{{ $line }}" />
</svg>
