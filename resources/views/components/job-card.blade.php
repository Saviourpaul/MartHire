@props(['job'])

@php
    $posted = ! empty($job['posted_at']) ? \Illuminate\Support\Carbon::parse($job['posted_at'])->diffForHumans() : null;
    $locationLabel = $job['location_label'] ?? $job['location'] ?? null;
@endphp

<article {{ $attributes->class('nw-job') }}>
    <header class="nw-job__top">
        <x-company-mark :company="$job['company'] ?? 'Marthire'" :logo="$job['logo_url'] ?? null" />
        <div>
            <p class="nw-job__company">{{ $job['company'] ?? 'Marthire' }}</p>
            @if ($posted)
                <p class="nw-job__posted"><x-icon name="clock" /> {{ $posted }}</p>
            @endif
        </div>
        @if (! empty($job['workplace']))
            <span class="nw-job__badge">{{ $job['workplace'] }}</span>
        @endif
    </header>

    <h3 class="nw-job__title"><a href="{{ $job['detail_url'] ?? route('workable-jobs.show', $job['id']) }}">{{ $job['title'] }}</a></h3>
    @if (! empty($job['summary']))
        <p class="nw-job__summary">{{ $job['summary'] }}</p>
    @endif

    @if (! empty($job['location']) || ! empty($job['country']) || ! empty($job['type']) || ! empty($job['salary']))
        <ul class="nw-job__meta" aria-label="{{ __('Role details') }}">
            @if (! empty($locationLabel))
                <li><x-icon name="map-pin" /> {{ $locationLabel }}</li>
            @endif
            @if (! empty($job['country']))
                <li><x-job-country :job="$job" /></li>
            @endif
            @if (! empty($job['type']))
                <li><x-icon name="briefcase" /> {{ $job['type'] }}</li>
            @endif
            @if (! empty($job['salary']))
                <li><x-icon name="wallet" /> {{ $job['salary'] }}</li>
            @endif
        </ul>
    @endif

    @if (! empty($job['tags']))
        <ul class="nw-job__tags" aria-label="{{ __('Skills') }}">
            @foreach ($job['tags'] as $tag)
                <li>{{ $tag }}</li>
            @endforeach
        </ul>
    @endif

    <div class="nw-job__footer">
        <x-apply-button :job="$job" class="nw-button--small" />
    </div>
</article>
