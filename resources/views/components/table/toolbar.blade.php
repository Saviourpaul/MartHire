@props(['tabsLabel' => null])
@isset($tabs)
    <nav class="nw-segmented"@if ($tabsLabel) aria-label="{{ $tabsLabel }}"@endif>{{ $tabs }}</nav>
@endisset
<div {{ $attributes->class('nw-toolbar') }}>
    {{ $slot }}
    {{ $filters ?? '' }}
    @isset($actions)
        <div class="nw-toolbar__actions">{{ $actions }}</div>
    @endisset
</div>
