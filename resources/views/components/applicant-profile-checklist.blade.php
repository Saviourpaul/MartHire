@props(['checklist'])

<section id="profile-checklist" {{ $attributes->class('nw-panel nw-profile-checklist') }} aria-labelledby="profile-checklist-title">
    <header class="nw-profile-checklist__head">
        <div>
            <h2 id="profile-checklist-title">{{ __('Profile checklist') }}</h2>
            <p>{{ __(':complete of :total complete', ['complete' => $checklist['completed'], 'total' => $checklist['total']]) }}</p>
        </div>
        <span class="nw-profile-checklist__percent" aria-hidden="true">{{ $checklist['percent'] }}%</span>
    </header>

    <div class="nw-profile-checklist__progress" role="progressbar" aria-label="{{ __('Profile completion') }}"
         aria-valuemin="0" aria-valuemax="{{ $checklist['total'] }}" aria-valuenow="{{ $checklist['completed'] }}">
        <span style="width: {{ $checklist['percent'] }}%"></span>
    </div>

    <ul class="nw-profile-checklist__list">
        @foreach ($checklist['items'] as $item)
            <li class="nw-profile-checklist__item">
                <span class="nw-profile-checklist__status {{ $item['complete'] ? 'is-complete' : ($item['partial'] ? 'is-partial' : '') }}" aria-hidden="true">
                    {{ $item['complete'] ? '✓' : ($item['partial'] ? '−' : '') }}
                </span>
                <div class="nw-profile-checklist__copy">
                    <strong>{{ $item['label'] }}</strong>
                    <span>{{ $item['description'] }}</span>
                    @if ($item['optional'])
                        <small>{{ __('Optional · you can choose not to disclose') }}</small>
                    @endif
                </div>
                <button type="button" class="nw-profile-checklist__action" x-on:click="$dispatch('open-applicant-profile-editor', '{{ $item['key'] }}')">
                    {{ $item['action'] }}
                </button>
            </li>
        @endforeach
    </ul>
</section>
