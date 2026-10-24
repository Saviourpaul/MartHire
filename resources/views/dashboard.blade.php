@use('Illuminate\Support\Carbon')
@use('Illuminate\Support\Str')

@php
    $accountUser = auth()->user();
    // The users table is moving from `name` to first/last name columns, so accept any of those spellings.
    $givenName = $accountUser->getAttribute('firstname') ?? $accountUser->getAttribute('firstName') ?? '';
    $familyName = $accountUser->getAttribute('lastname') ?? $accountUser->getAttribute('lastName') ?? '';
    $accountName = trim($givenName.' '.$familyName) ?: ($accountUser->getAttribute('name') ?: $accountUser->email);
    $firstName = $givenName ?: Str::before($accountName, ' ');
    $needsEmailVerification = $accountUser instanceof \Illuminate\Contracts\Auth\MustVerifyEmail
        && ! $accountUser->hasVerifiedEmail();

    // Real data: pass $jobs and $activity in from the route/controller. Until then, sample listings and sample activity are shown.
    $usingSample = ! isset($activity);
    $catalog = \App\Support\JobCatalog::make($jobs ?? null);
    $jobsById = $catalog->jobs->keyBy('id');
    $activity = $activity ?? require resource_path('data/sample-activity.php');

    $applications = collect($activity['applications'] ?? []);
    $statusCounts = $applications->countBy('status');
    $recent = $applications
        ->map(fn ($application) => $application + ['job_data' => $jobsById->get($application['job'])])
        ->filter(fn ($application) => $application['job_data'])
        ->sortByDesc(fn ($application) => strtotime($application['applied_at']))
        ->take(8)
        ->values();

    // How much more (or less) happened in the latest half of a weekly series than in the half before it.
    $change = function (?array $values): ?int {
        if (! $values || count($values) < 4) {
            return null;
        }
        $half = intdiv(count($values), 2);

        return array_sum(array_slice($values, $half)) - array_sum(array_slice($values, 0, $half));
    };

    $spark = $activity['spark'] ?? [];
    $stats = [
        ['label' => __('Applications sent'), 'value' => $applications->count(), 'icon' => 'send', 'tone' => 'green', 'spark' => $spark['applications'] ?? null],
        ['label' => __('In review'), 'value' => $statusCounts->get('In review', 0), 'icon' => 'eye', 'tone' => 'lime', 'spark' => $spark['review'] ?? null],
        ['label' => __('Interviews'), 'value' => $statusCounts->get('Interview', 0), 'icon' => 'calendar', 'tone' => 'peach', 'spark' => $spark['interviews'] ?? null],
        ['label' => __('Saved jobs'), 'value' => $activity['saved'] ?? 0, 'icon' => 'bookmark', 'tone' => 'ink', 'spark' => $spark['saved'] ?? null],
    ];

    $statuses = ['Applied' => 1, 'In review' => 2, 'Interview' => 3, 'Offer' => 4, 'Declined' => 5];
    $segments = collect($statuses)->map(fn ($tone, $label) => ['label' => $label, 'value' => $statusCounts->get($label, 0), 'tone' => $tone])->values();

    $series = $activity['series'] ?? null;
    $steps = collect($activity['profile'] ?? [])->map(fn ($done) => $done ?? ! $needsEmailVerification);
    $strength = $steps->isEmpty() ? null : (int) round($steps->filter()->count() / $steps->count() * 100);

    $nextInterview = collect($activity['interviews'] ?? [])
        ->map(fn ($interview) => $interview + ['job_data' => $jobsById->get($interview['job'])])
        ->filter(fn ($interview) => $interview['job_data'] && Carbon::parse($interview['at'])->isFuture())
        ->sortBy(fn ($interview) => strtotime($interview['at']))
        ->first();

    $appliedIds = $applications->pluck('job')->all();
    $suggested = $catalog->jobs->reject(fn ($job) => in_array($job['id'], $appliedIds, true))->take(3);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Dashboard') }}</x-slot>

    <x-slot name="header">
        <h1>{{ __('Welcome back, :name.', ['name' => $firstName]) }}</h1>
        <p>{{ __('Here’s how your job search is going.') }}</p>
    </x-slot>

    <x-slot name="actions">
        <a class="nw-button nw-button--outline nw-button--small" href="{{ route('profile.edit') }}">{{ __('Edit profile') }}</a>
    </x-slot>

    @if ($needsEmailVerification)
        <div class="nw-notice" role="status">
            {{ __('Please verify your email address so employers can reach you.') }}
            <a class="nw-link" href="{{ route('profile.edit') }}#profile-information">{{ __('Verify now') }}</a>
        </div>
    @endif

    @if ($usingSample)
        <div class="nw-notice nw-notice--sample" role="note">
            <x-icon name="spark" />
            <span>{{ __('You’re looking at sample activity. Your own applications will appear here once they’re connected.') }}</span>
        </div>
    @endif

    <section class="nw-stats" aria-label="{{ __('At a glance') }}">
        @foreach ($stats as $stat)
            @php $delta = $change($stat['spark']); @endphp
            <article class="nw-stat nw-stat--{{ $stat['tone'] }}">
                <div class="nw-stat__top">
                    <div>
                        <p class="nw-stat__label">{{ $stat['label'] }}</p>
                        <p class="nw-stat__value">{{ $stat['value'] }}</p>
                    </div>
                    <span class="nw-stat__icon" aria-hidden="true"><x-icon :name="$stat['icon']" /></span>
                </div>
                @if ($delta !== null)
                    <p class="nw-stat__trend {{ $delta > 0 ? 'is-up' : ($delta < 0 ? 'is-down' : '') }}">
                        @if ($delta !== 0)
                            <x-icon :name="$delta > 0 ? 'trending-up' : 'trend-down'" />
                            <strong>{{ $delta > 0 ? '+' : '' }}{{ $delta }}</strong> {{ __('vs the 4 weeks before') }}
                        @else
                            {{ __('Same as the 4 weeks before') }}
                        @endif
                    </p>
                @endif
                @if ($stat['spark'])
                    <x-sparkline :values="$stat['spark']" />
                @endif
            </article>
        @endforeach
    </section>

    @if ($series || $applications->isNotEmpty())
        <div class="nw-dash-grid">
            @if ($series)
                <section class="nw-panel nw-chart" aria-labelledby="chart-heading"
                    x-data="areaChart({
                        labels: @js($series['labels']),
                        series: @js([
                            'applications' => ['label' => __('Applications sent'), 'values' => $series['applications']],
                            'responses' => ['label' => __('Responses received'), 'values' => $series['responses']],
                        ]),
                        order: ['applications', 'responses'],
                    })">
                    <header class="nw-panel__head">
                        <div>
                            <h2 id="chart-heading">{{ __('Your activity') }}</h2>
                            <p>{{ __('Applications sent and responses received, month by month.') }}</p>
                        </div>
                        <div class="nw-segmented" role="group" aria-label="{{ __('Chart data') }}">
                            <button type="button" x-bind:aria-pressed="(key === 'applications').toString()" x-on:click="key = 'applications'">{{ __('Applications') }}</button>
                            <button type="button" x-bind:aria-pressed="(key === 'responses').toString()" x-on:click="key = 'responses'">{{ __('Responses') }}</button>
                        </div>
                    </header>

                    <div class="nw-chart__frame" x-ref="frame" x-on:pointermove="move($event)" x-on:pointerleave="leave()">
                        <svg class="nw-chart__svg" x-bind:viewBox="`0 0 ${width} ${height}`" x-bind:width="width" x-bind:height="height" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="chart-fill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" class="nw-chart__stop nw-chart__stop--top" />
                                    <stop offset="100%" class="nw-chart__stop nw-chart__stop--bottom" />
                                </linearGradient>
                            </defs>
                            <g x-html="gridMarkup"></g>
                            <path class="nw-chart__area" x-bind:d="area" fill="url(#chart-fill)" />
                            <path class="nw-chart__line" x-bind:d="line" />
                            <line class="nw-chart__cursor" x-show="active" x-bind:x1="active ? active.x : 0" x-bind:x2="active ? active.x : 0" x-bind:y1="pad.top" x-bind:y2="y(0)" />
                            <circle class="nw-chart__dot" r="5" x-show="active" x-bind:cx="active ? active.x : 0" x-bind:cy="active ? active.y : 0" />
                        </svg>
                        <div class="nw-chart__tip" x-show="active" x-cloak x-bind:style="active ? { left: Math.min(Math.max(active.x, 84), width - 84) + 'px', top: active.y + 'px' } : {}">
                            <span x-text="active ? active.label : ''"></span>
                            <strong><span x-text="active ? active.value : ''"></span> <span x-text="series[key].label.toLowerCase()"></span></strong>
                        </div>
                    </div>

                    <div class="nw-sr-only">
                        <table>
                            <caption>{{ __('Applications and responses by month') }}</caption>
                            <thead><tr><th>{{ __('Month') }}</th><th>{{ __('Applications sent') }}</th><th>{{ __('Responses received') }}</th></tr></thead>
                            <tbody>
                                @foreach ($series['labels'] as $index => $label)
                                    <tr><th>{{ $label }}</th><td>{{ $series['applications'][$index] ?? 0 }}</td><td>{{ $series['responses'][$index] ?? 0 }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($applications->isNotEmpty())
                <section class="nw-panel nw-breakdown" aria-labelledby="status-heading">
                    <header class="nw-panel__head">
                        <div>
                            <h2 id="status-heading">{{ __('Applications by status') }}</h2>
                            <p>{{ __('Where each application stands.') }}</p>
                        </div>
                    </header>
                    <div class="nw-status-breakdown">
                        <x-donut :segments="$segments" :label="__('Applications')" />
                        <ul class="nw-legend">
                            @foreach ($segments as $segment)
                                <li>
                                    <span class="nw-legend__dot nw-legend__dot--{{ $segment['tone'] }}" aria-hidden="true"></span>
                                    <span>{{ $segment['label'] }}</span>
                                    <strong>{{ $segment['value'] }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif
        </div>
    @endif

    <div class="nw-dash-grid">
        <section id="applications" class="nw-panel" aria-labelledby="applications-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="applications-heading">{{ __('Recent applications') }}</h2>
                    <p>{{ __('The roles you’ve applied for, newest first.') }}</p>
                </div>
                <a class="nw-link nw-link--small" href="{{ route('jobs.index') }}">{{ __('Browse jobs') }} <x-icon name="arrow-up-right" /></a>
            </header>

            @if ($recent->isEmpty())
                <div class="nw-empty">
                    <h3>{{ __('No applications yet.') }}</h3>
                    <p>{{ __('When you apply for a role, you’ll be able to follow it here.') }}</p>
                    <a class="nw-button nw-button--primary" href="{{ route('jobs.index') }}">{{ __('Find a role') }} <x-icon name="arrow-up-right" /></a>
                </div>
            @else
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Applied') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col"><span class="nw-sr-only">{{ __('View role') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $application)
                                @php $job = $application['job_data']; @endphp
                                <tr>
                                    <td class="nw-table__main">
                                        <div class="nw-table__role">
                                            <x-company-mark :company="$job['company'] ?? 'NearbyWorks'" />
                                            <div>
                                                <a href="{{ route('jobs.show', $job['id']) }}">{{ $job['title'] }}</a>
                                                <small>{{ collect([$job['company'] ?? null, $job['location'] ?? null])->filter()->implode(', ') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ Carbon::parse($application['applied_at'])->format('j M Y') }}</td>
                                    <td><x-status-badge :status="$application['status']" /></td>
                                    <td class="nw-table__go"><a href="{{ route('jobs.show', $job['id']) }}" aria-label="{{ __('View :title', ['title' => $job['title']]) }}"><x-icon name="arrow-up-right" /></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="nw-dash-stack">
            @if ($nextInterview)
                @php
                    $when = Carbon::parse($nextInterview['at']);
                    $interviewJob = $nextInterview['job_data'];
                @endphp
                <section class="nw-panel nw-interview" aria-labelledby="interview-heading">
                    <header class="nw-panel__head">
                        <div>
                            <h2 id="interview-heading">{{ __('Next interview') }}</h2>
                            <p>{{ $when->diffForHumans() }}</p>
                        </div>
                    </header>
                    <div class="nw-interview__body">
                        <time class="nw-interview__date" datetime="{{ $when->toIso8601String() }}">
                            <strong>{{ $when->format('j') }}</strong>
                            <span>{{ $when->format('M') }}</span>
                        </time>
                        <div>
                            <a class="nw-interview__role" href="{{ route('jobs.show', $interviewJob['id']) }}">{{ $interviewJob['title'] }}</a>
                            <small>{{ $interviewJob['company'] ?? '' }}</small>
                            <small><x-icon name="clock" /> {{ $when->format('l, g:i a') }}@if (! empty($nextInterview['format'])), {{ $nextInterview['format'] }}@endif</small>
                        </div>
                    </div>
                </section>
            @endif

            @if ($strength !== null)
                <section class="nw-panel" aria-labelledby="strength-heading">
                    <header class="nw-panel__head">
                        <div>
                            <h2 id="strength-heading">{{ __('Profile strength') }}</h2>
                            <p>{{ __('A fuller profile gives employers more to go on.') }}</p>
                        </div>
                    </header>
                    <div class="nw-meter" role="progressbar" aria-label="{{ __('Profile strength') }}" aria-valuenow="{{ $strength }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $strength }}%"></span>
                    </div>
                    <p class="nw-meter__caption"><strong>{{ $strength }}%</strong> {{ __('complete') }}</p>
                    <ul class="nw-checklist">
                        @foreach ($steps as $label => $done)
                            <li class="{{ $done ? 'is-done' : '' }}">
                                <span class="nw-checklist__box" aria-hidden="true"><x-icon name="check" /></span>
                                <span>{{ $label }}<span class="nw-sr-only">{{ $done ? __(' (done)') : __(' (to do)') }}</span></span>
                                @unless ($done)
                                    <a class="nw-link" href="{{ route('profile.edit') }}">{{ __('Add') }}</a>
                                @endunless
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    @if (! empty($activity['activity']))
        <section class="nw-panel nw-activity" aria-labelledby="feed-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="feed-heading">{{ __('Recent activity') }}</h2>
                    <p>{{ __('What’s happened lately.') }}</p>
                </div>
            </header>
            <ol class="nw-feed">
                @foreach (array_slice($activity['activity'], 0, 6) as $item)
                    <li>
                        <span class="nw-feed__icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'spark'" /></span>
                        <div>
                            <p>{{ $item['text'] }}</p>
                            <time datetime="{{ Carbon::parse($item['at'])->toIso8601String() }}">{{ Carbon::parse($item['at'])->diffForHumans() }}</time>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($suggested->isNotEmpty())
        <section id="recommended" class="nw-dash-section" aria-labelledby="recommended-heading">
            <div class="nw-dash-section__head">
                <div>
                    <h2 id="recommended-heading">{{ __('New roles to look at') }}</h2>
                    <p>{{ __('Fresh listings you haven’t applied for yet.') }}</p>
                </div>
                <a class="nw-link" href="{{ route('jobs.index') }}">{{ __('Browse all jobs') }} <x-icon name="arrow-right" /></a>
            </div>
            <div class="nw-job-grid">
                @foreach ($suggested as $job)
                    <x-job-card :job="$job" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
