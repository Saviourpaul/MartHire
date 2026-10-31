@props(['onDark' => false])

{{-- Marthire logo. The light-surface artwork (navy + lavender) and the dark-surface artwork (white + lavender) are swapped
     by the theme via the existing .nw-icon--to-dark / .nw-icon--to-light helpers, which set `display`, so no inline
     display here. Pass :on-dark="true" for surfaces that are always dark (e.g. the account sidebar). --}}
<span {{ $attributes->merge(['class' => 'nw-wordmark']) }}>
    @if ($onDark)
        <img src="{{ asset('asset/images/marthire-logo-dark.png') }}" alt="Marthire" width="543" height="178" style="height:1.9em;width:auto">
    @else
        <img class="nw-icon--to-dark" src="{{ asset('asset/images/marthire-logo.png') }}" alt="Marthire" width="543" height="178" style="height:1.9em;width:auto">
        <img class="nw-icon--to-light" src="{{ asset('asset/images/marthire-logo-dark.png') }}" alt="" aria-hidden="true" width="543" height="178" style="height:1.9em;width:auto">
    @endif
</span>
