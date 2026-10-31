@props(['job'])

@if (! empty($job['country']))
    <span {{ $attributes->class('nw-job-country') }}>
        @if (! empty($job['flag_url']))
            <img src="{{ $job['flag_url'] }}" width="20" height="20" alt="" loading="lazy">
        @elseif (! empty($job['flag_emoji']))
            <span class="nw-job-country__flag" aria-hidden="true">{{ $job['flag_emoji'] }}</span>
        @endif
        <span>{{ $job['country'] }}</span>
    </span>
@endif
