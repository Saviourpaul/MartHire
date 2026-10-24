@props(['job'])

@php
    // Synced postings retain their hosted application destination.
    $applyUrl = ($job['apply_url'] ?? null) ?: (auth()->check()
        ? route('jobs.apply', $job['id'])
        : route('register', ['job' => $job['id']]));
@endphp

<a {{ $attributes->class('nw-button nw-button--primary') }} href="{{ $applyUrl }}">
    {{ auth()->guest() && empty($job['apply_url']) ? __('Sign up to apply') : __('Apply now') }}
    <span class="nw-sr-only">{{ __('for :title', ['title' => $job['title']]) }}@if (! empty($job['company'])) {{ __('at :company', ['company' => $job['company']]) }}@endif</span>
    <x-icon name="arrow-up-right" />
</a>
