@php
    // Real jobs: pass them in from the route/controller as $jobs. Until then, sample listings are shown.
    $catalog = \App\Support\JobCatalog::make($jobs ?? null);
    $jobs = $catalog->jobs;
    $job = $job ?? $jobs->firstWhere('id', $id) ?? abort(404);
    $company = $job['company'] ?? 'NearbyWorks';
    $categorySlug = ! empty($job['field']) ? \Illuminate\Support\Str::slug($job['field']) : null;
    $posted = ! empty($job['posted_at']) ? \Illuminate\Support\Carbon::parse($job['posted_at'])->diffForHumans() : null;
    $similar = $jobs->where('id', '!=', $job['id']);
    $sameField = empty($job['field']) ? collect() : $similar->where('field', $job['field'])->take(3);
    $similar = $sameField->concat($similar->whereNotIn('id', $sameField->pluck('id'))->take(3 - $sameField->count()))->values();
    $sections = [
        'responsibilities' => 'What you’ll do',
        'requirements' => 'What we’re looking for',
        'benefits' => 'What’s on offer',
    ];
    $jsonLd = array_filter([
        '@context' => 'https://schema.org/',
        '@type' => 'JobPosting',
        'title' => $job['title'],
        'description' => ($job['content_html'] ?? '') ?: e($job['summary'] ?? $job['description'] ?? ''),
        'datePosted' => $job['posted_at'] ?? null,
        'employmentType' => $job['type'] ?? null,
        'hiringOrganization' => ['@type' => 'Organization', 'name' => $company],
        'jobLocation' => ! empty($job['location']) ? ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job['location']]] : null,
    ]);
@endphp

<x-layout :title="$job['title'].(! empty($job['company']) ? ' at '.$job['company'] : '')" :description="$job['summary'] ?? 'Find out more about this role and sign up to apply on NearbyWorks.'">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
    <article class="nw-job-detail" aria-labelledby="job-title">
        <div class="nw-container">
            <nav class="nw-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('jobs.index') }}"><x-icon name="arrow-left" /> All jobs</a>
                @if ($categorySlug)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('jobs.index', ['category' => $categorySlug]) }}">{{ $job['field'] }}</a>
                @endif
            </nav>

            <header class="nw-job-detail__header">
                <x-company-mark :company="$company" size="lg" />
                <div>
                    <p class="nw-job-detail__company">{{ $company }}</p>
                    <h1 id="job-title">{{ $job['title'] }}</h1>
                    <ul class="nw-job-detail__meta">
                        @if (! empty($job['location']))
                            <li><x-icon name="map-pin" /> {{ $job['location'] }}</li>
                        @endif
                        @if (! empty($job['type']))
                            <li><x-icon name="briefcase" /> {{ $job['type'] }}</li>
                        @endif
                        @if (! empty($job['workplace']))
                            <li><x-icon name="globe" /> {{ $job['workplace'] }}</li>
                        @endif
                        @if (! empty($job['salary']))
                            <li><x-icon name="wallet" /> {{ $job['salary'] }}</li>
                        @endif
                        @if ($posted)
                            <li><x-icon name="clock" /> Posted {{ $posted }}</li>
                        @endif
                    </ul>
                </div>
            </header>

            <div class="nw-job-detail__grid">
                <div class="nw-job-detail__body">
                    @if (! empty($job['summary']) || ! empty($job['description']) || ! empty($job['content_html']))
                        <section aria-labelledby="about-role">
                            <h2 id="about-role">About the role</h2>
                            @if (! empty($job['summary']))
                                <p class="nw-copy">{{ $job['summary'] }}</p>
                            @endif
                            @if (! empty($job['description']))
                                @foreach (preg_split('/\R{2,}/', trim($job['description'])) as $paragraph)
                                    <p class="nw-copy">{{ $paragraph }}</p>
                                @endforeach
                            @endif
                            {{-- Employer HTML, sanitised server-side (App\Support\HtmlSanitizer) --}}
                            @if (! empty($job['content_html']))
                                <div class="nw-copy nw-job-detail__content">{!! $job['content_html'] !!}</div>
                            @endif
                        </section>
                    @endif

                    @foreach ($sections as $key => $heading)
                        @if (! empty($job[$key]))
                            <section aria-labelledby="section-{{ $key }}">
                                <h2 id="section-{{ $key }}">{{ $heading }}</h2>
                                <ul class="nw-check-list nw-check-list--detail">
                                    @foreach ($job[$key] as $item)
                                        <li><x-icon name="check" /> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    @endforeach

                    @if (! empty($job['tags']))
                        <section aria-labelledby="section-skills">
                            <h2 id="section-skills">Skills</h2>
                            <ul class="nw-job__tags nw-job__tags--detail">
                                @foreach ($job['tags'] as $tag)
                                    <li>{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($job['company_about']))
                        <section class="nw-company-about" aria-labelledby="section-company">
                            <x-company-mark :company="$company" />
                            <div>
                                <h2 id="section-company">About {{ $company }}</h2>
                                <p class="nw-copy">{{ $job['company_about'] }}</p>
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="nw-apply-card" aria-label="Apply for this role"
                    x-data="{ copied: false, copy() { if (! navigator.clipboard) return; navigator.clipboard.writeText(window.location.href).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2200); }); } }">
                    <span class="nw-eyebrow">Interested?</span>
                    @if (! empty($job['salary']))
                        <p class="nw-apply-card__salary">{{ $job['salary'] }}</p>
                    @endif
                    @if (! empty($job['type']) || ! empty($job['workplace']))
                        <p class="nw-apply-card__type">{{ collect([$job['type'] ?? null, $job['workplace'] ?? null])->filter()->implode(', ') }}</p>
                    @endif
                    @if (! empty($job['closed']))
                        <p class="nw-form-hint">This role is no longer accepting applications.</p>
                        <a class="nw-link" href="{{ route('jobs.index') }}">Browse open roles <x-icon name="arrow-right" /></a>
                    @else
                        <x-apply-button :job="$job" class="nw-apply-card__button" />
                        @if (auth()->guest() && empty($job['apply_url']))
                            <p class="nw-form-hint">Applying needs a free NearbyWorks account. Sign up takes a minute.</p>
                            <p class="nw-form-hint">Already have one? <a class="nw-link" href="{{ route('login') }}">Log in</a></p>
                        @endif
                    @endif
                    <button type="button" class="nw-apply-card__share" x-on:click="copy()">
                        <x-icon name="share" />
                        <span x-text="copied ? 'Link copied' : 'Copy link to this role'">Copy link to this role</span>
                    </button>
                </aside>
            </div>
        </div>
    </article>

    @if ($similar->isNotEmpty())
        <section class="nw-section nw-section--soft" aria-labelledby="similar-heading">
            <div class="nw-container">
                <div class="nw-section-heading">
                    <div><h2 id="similar-heading" class="nw-heading">More roles<br><em>you might like.</em></h2></div>
                    <a class="nw-link" href="{{ route('jobs.index') }}">Browse all jobs <x-icon name="arrow-right" /></a>
                </div>
                <div class="nw-job-grid">
                    @foreach ($similar as $other)
                        <x-job-card :job="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layout>
