@use('Illuminate\Support\Str')

@php
    $primaryColor = $settings?->brand_primary_color ?: '#0a8754';
    $accentColor = $settings?->brand_accent_color ?: '#c9f542';
    $logoUrl = $settings?->companyLogoUrl();
    $industry = $settings?->industry;
    $organizationSize = $settings?->organization_size;
    $organizationMeta = collect([$industry, $organizationSize])->filter()->implode(' · ');
    $organizationInitial = Str::upper(Str::substr($organization->name, 0, 1));
    $pageDescription = filled($settings?->about)
        ? Str::limit(trim(strip_tags($settings->about)), 155)
        : 'Explore current job opportunities at '.$organization->name.'.';
@endphp

<x-layout :title="$organization->name.' careers'" :description="$pageDescription">
    <main class="nw-careers-page" style="--nw-careers-primary: {{ $primaryColor }}; --nw-careers-accent: {{ $accentColor }}">
        <header class="nw-careers-hero">
            <div class="nw-careers-hero__brand">
                @if ($logoUrl)
                    <img class="nw-careers-hero__logo" src="{{ $logoUrl }}" alt="{{ $organization->name }} logo">
                @else
                    <span class="nw-careers-hero__monogram" aria-hidden="true">{{ $organizationInitial }}</span>
                @endif
                <div>
                    <p class="nw-eyebrow">{{ __('Careers at') }}</p>
                    <h1>{{ $organization->name }}</h1>
                    @if ($organizationMeta !== '')
                        <p class="nw-careers-hero__meta">{{ $organizationMeta }}</p>
                    @endif
                </div>
            </div>

            @if (filled($settings?->about))
                <p class="nw-careers-hero__about">{{ $settings->about }}</p>
            @endif
        </header>

        <section class="nw-careers-listings" aria-labelledby="organization-jobs-heading">
            <div class="nw-section-heading">
                <div>
                    <p class="nw-eyebrow">{{ __('Join the team') }}</p>
                    <h2 id="organization-jobs-heading" class="nw-heading">{{ __('Open roles') }}</h2>
                </div>
                <span class="nw-careers-count">{{ trans_choice(':count open role|:count open roles', $jobs->count(), ['count' => $jobs->count()]) }}</span>
            </div>

            @if ($jobs->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No open roles right now') }}</h3>
                    <p>{{ __('Check back later for new opportunities at :organization.', ['organization' => $organization->name]) }}</p>
                    <a class="nw-link" href="{{ route('jobs.index') }}">{{ __('Browse all jobs') }} <x-icon name="arrow-right" /></a>
                </div>
            @else
                <div class="nw-careers-grid">
                    @foreach ($jobs as $job)
                        @php
                            $jobMeta = collect([$job['location_label'] ?? $job['location'] ?? null, $job['workplace'] ?? null, $job['type'] ?? null])
                                ->filter()->implode(' · ');
                        @endphp
                        <article class="nw-career-job">
                            <div class="nw-career-job__top">
                                <div>
                                    <p class="nw-eyebrow">{{ $job['field'] ?? __('Opportunity') }}</p>
                                    <h3><a href="{{ $job['detail_url'] }}">{{ $job['title'] }}</a></h3>
                                </div>
                                <x-icon name="briefcase" />
                            </div>

                            @if ($jobMeta !== '')
                                <p>{{ $jobMeta }}</p>
                            @endif

                            @if (filled($job['summary'] ?? null))
                                <p>{{ $job['summary'] }}</p>
                            @endif

                            <a class="nw-button nw-button--outline nw-button--small" href="{{ $job['detail_url'] }}">
                                {{ __('View role') }} <x-icon name="arrow-right" />
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif

            <footer class="nw-careers-footer">
                <a class="nw-link" href="{{ route('jobs.index') }}">{{ __('Explore all opportunities') }} <x-icon name="arrow-right" /></a>
            </footer>
        </section>
    </main>
</x-layout>
