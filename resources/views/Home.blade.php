@php
    // Real jobs: pass them in from the route/controller as $jobs. Until then, sample listings are shown.
    $catalog = \App\Support\JobCatalog::make($jobs ?? null);
    $jobs = $catalog->jobs;
    $categories = $catalog->categories;
    $jobIndex = $catalog->searchIndex();
    $typeCounts = $catalog->counts('type', ['Full-time', 'Part-time', 'Contract']);
    $hasRemote = $catalog->counts('workplace')->has('Remote');
    $pins = $catalog->pins();
@endphp

<x-layout title="Recruitment Management Platform" description="Marthire helps organizations keep job vacancies, applications, applicant records, and candidate progression in one structured recruitment workspace.">
    <div x-data="jobBoard(@js($jobIndex), 6)">
        <section class="nw-hero" aria-labelledby="home-heading">
            <div class="nw-container nw-hero__grid">
                <div class="nw-hero__copy">
                    <h1 id="home-heading">One platform to manage<br><em>Recruitment across African businesses,</em><br>and international organisations</h1>
                    <p class="nw-copy">Marthire helps organizations keep job vacancies, applications, applicant records, and candidate progression in one structured recruitment workspace.</p>
                    <a href="{{ route('how-it-works') }}" class="nw-link">See how it works <x-icon name="arrow-right" /></a>
                    <div class="nw-hero__footnote"><x-icon name="users" /> Designed for better recruitment</div>
                </div>
                <div class="nw-hero__search">
                    <form class="nw-search" role="search" aria-label="Search jobs" method="get" action="{{ route('workable-jobs.index') }}">
                        <label class="nw-search__field">
                            <x-icon name="search" />
                            <span class="nw-search__text">
                                <span class="nw-search__label">What</span>
                                <input class="nw-search__input" type="search" name="q" placeholder="Job title, skill or company" autocomplete="off">
                            </span>
                        </label>
                        <label class="nw-search__field">
                            <x-icon name="map-pin" />
                            <span class="nw-search__text">
                                <span class="nw-search__label">Where</span>
                                <input class="nw-search__input" type="search" name="place" placeholder="City, country or remote" autocomplete="off">
                            </span>
                        </label>
                        <button class="nw-button nw-button--primary nw-search__submit" type="submit">Browse jobs <x-icon name="arrow-right" /></button>
                    </form>
                    @if ($categories->isNotEmpty())
                        <div class="nw-popular" role="group" aria-label="Popular categories">
                            <span>Popular categories</span>
                            @foreach ($categories->take(5) as $category)
                                <a class="nw-chip" href="{{ route('workable-jobs.index', ['category' => $category['slug']]) }}">{{ $category['name'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="nw-hero__map" data-hero-map>
                <canvas class="nw-hero__map-canvas" aria-hidden="true"></canvas>
                @if ($pins->isNotEmpty())
                    <ul class="nw-pins" aria-label="Countries with open roles">
                        @foreach ($pins as $pin)
                            <li class="nw-pin{{ $loop->first ? ' is-hub' : '' }}" data-pin data-lat="{{ $pin['lat'] }}" data-lon="{{ $pin['lon'] }}">
                                <a class="nw-pin__link" href="{{ route('workable-jobs.index', ['place' => $pin['country']]) }}" aria-label="{{ $pin['country'] }}: {{ $pin['count'] }} {{ \Illuminate\Support\Str::plural('role', $pin['count']) }}">
                                    @if ($pin['flag_url'])
                                        <img src="{{ $pin['flag_url'] }}" width="40" height="40" alt="" loading="lazy">
                                    @else
                                        <span class="nw-pin__flag" aria-hidden="true">{{ $pin['flag_emoji'] }}</span>
                                    @endif
                                    <span class="nw-pin__label" aria-hidden="true">
                                        <strong>{{ $pin['country'] }}</strong>
                                        <span>{{ $pin['count'] }} {{ \Illuminate\Support\Str::plural('role', $pin['count']) }}</span>
                                        @if ($pin['cities'])
                                            <small>{{ implode(', ', $pin['cities']) }}</small>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($pins->isNotEmpty())
                <div class="nw-container nw-hero__note">
                    <div class="nw-map-note">
                        <span class="nw-map-note__icon"><x-icon name="globe" /></span>
                        <div>
                            <strong>Roles in {{ $pins->count() }} {{ \Illuminate\Support\Str::plural('country', $pins->count()) }}</strong>
                            <small>Pick a flag to see the roles there</small>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <div class="nw-intro-strip">
            <div class="nw-container nw-intro-strip__inner">
                <span><x-icon name="briefcase" /> Centralized applicant tracking</span>
                <span><x-icon name="check" /> Structured shortlisting</span>
                <span><x-icon name="users" /> Hiring-team collaboration</span>
                <span><x-icon name="globe" /> Clear recruitment oversight</span>
            </div>
        </div>

        <section id="jobs" class="nw-section" aria-labelledby="jobs-heading">
            <div class="nw-container">
                <div class="nw-section-heading">
                    <div><span class="nw-eyebrow">For every vacancy</span><h2 id="jobs-heading" class="nw-heading">Recruitment without the<br><em>manual chase</em></h2></div>
                    <p>Marthire brings candidates, recruitment stages, and hiring activity into one organized workflow—so your team can spend less time chasing updates across spreadsheets and emails.</p>
                </div>

                @if ($jobs->isEmpty())
                    <div class="nw-jobs__empty">
                        <h3>No open roles right now.</h3>
                        <p>Check back soon, or create an account so you’re ready when new roles arrive.</p>
                        <div class="nw-button-row nw-button-row--center">
                            <a href="{{ route('register') }}" class="nw-button nw-button--primary">Create an account <x-icon name="arrow-up-right" /></a>
                        </div>
                    </div>
                @else
                    <div class="nw-jobs__bar">
                        @if ($hasRemote || $typeCounts->isNotEmpty())
                            <div class="nw-jobs__filters" role="group" aria-label="Filter jobs">
                                <button type="button" class="nw-chip" x-bind:aria-pressed="(filter === 'all').toString()" x-on:click="filter = 'all'">All roles</button>
                                @if ($hasRemote)
                                    <button type="button" class="nw-chip" x-bind:aria-pressed="(filter === 'remote').toString()" x-on:click="filter = 'remote'">Remote</button>
                                @endif
                                @foreach ($typeCounts->keys() as $type)
                                    <button type="button" class="nw-chip" x-bind:aria-pressed="(filter === @js($type)).toString()" x-on:click="filter = @js($type)">{{ $type }}</button>
                                @endforeach
                            </div>
                        @endif
                        <p class="nw-jobs__count" role="status" aria-live="polite" x-text="summary">Showing {{ min(6, $jobs->count()) }} of {{ $jobs->count() }} {{ \Illuminate\Support\Str::plural('role', $jobs->count()) }}</p>
                    </div>

                    <div class="nw-job-grid">
                        @foreach ($jobs as $job)
                            <x-job-card :job="$job" :x-show="'isShown('.$loop->index.')'" :x-cloak="$loop->index >= 6" />
                        @endforeach
                    </div>

                    <div class="nw-jobs__empty" x-show="matches.length === 0" x-cloak>
                        <h3>No roles in that group yet.</h3>
                        <p>Try another filter, or browse every open role.</p>
                        <div class="nw-button-row nw-button-row--center">
                            <button type="button" class="nw-button nw-button--outline" x-on:click="filter = 'all'">Show all roles</button>
                        </div>
                    </div>

                    <div class="nw-jobs__more">
                        <a href="{{ route('workable-jobs.index') }}" class="nw-button nw-button--outline">View all {{ $jobs->count() }} {{ \Illuminate\Support\Str::plural('job', $jobs->count()) }} <x-icon name="arrow-right" /></a>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @if ($categories->isNotEmpty())
        <section id="categories" class="nw-section nw-section--soft" aria-labelledby="categories-heading">
            <div class="nw-container">
                <div class="nw-section-heading">
                    <div><span class="nw-eyebrow">From application to decision</span><h2 id="categories-heading" class="nw-heading">A complete <em>recruitment workspace</em><br>for every hiring stage</h2></div>
                    <p>Keep every application, profile, and supporting document connected to the right vacancy.</p>
                </div>
                <div class="nw-category-grid">
                    @foreach ($categories as $category)
                        <a class="nw-category" href="{{ route('workable-jobs.index', ['category' => $category['slug']]) }}">
                            <span class="nw-category__icon"><x-icon :name="$category['icon']" /></span>
                            <span class="nw-category__text">
                                <strong>{{ $category['name'] }}</strong>
                                @if ($category['blurb'])
                                    <small>{{ $category['blurb'] }}</small>
                                @endif
                            </span>
                            <span class="nw-category__count">{{ $category['count'] }} {{ \Illuminate\Support\Str::plural('role', $category['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="for-you" class="nw-section" aria-labelledby="audience-heading"
        x-data="{ audience: window.location.hash === '#for-job-seekers' ? 'job-seekers' : 'employers', select(value, focus = false) { this.audience = value; if (focus) this.$nextTick(() => this.$refs[value === 'employers' ? 'employersTab' : 'jobseekersTab'].focus()); } }"
        @hashchange.window="if (window.location.hash === '#for-job-seekers') audience = 'job-seekers'; if (window.location.hash === '#for-employers') audience = 'employers'">
        <div class="nw-container">
            <div class="nw-section-heading">
                <div><span class="nw-eyebrow">For authorized stakeholders</span><h2 id="audience-heading" class="nw-heading">One platform for every<br><em>recruitment workflow</em></h2></div>
                <p>Bring vacancies, applications, documents, and decisions into one organized recruitment process.</p>
            </div>
            <div class="nw-audience-switch" role="tablist" aria-label="Explore Marthire">
                <button id="for-employers" x-ref="employersTab" role="tab" aria-controls="employers-panel" :aria-selected="(audience === 'employers').toString()" :tabindex="audience === 'employers' ? 0 : -1" @click="select('employers')" @keydown.right.prevent="select('job-seekers', true)" @keydown.left.prevent="select('job-seekers', true)" @keydown.home.prevent="select('employers', true)" @keydown.end.prevent="select('job-seekers', true)">For employers</button>
                <button id="for-job-seekers" x-ref="jobseekersTab" role="tab" aria-controls="jobseekers-panel" :aria-selected="(audience === 'job-seekers').toString()" :tabindex="audience === 'job-seekers' ? 0 : -1" @click="select('job-seekers')" @keydown.right.prevent="select('employers', true)" @keydown.left.prevent="select('employers', true)" @keydown.home.prevent="select('employers', true)" @keydown.end.prevent="select('job-seekers', true)">For job seekers</button>
            </div>
            <div id="employers-panel" class="nw-audience-grid" role="tabpanel" aria-labelledby="for-employers" tabindex="0" x-show="audience === 'employers'">
                <div class="nw-audience-content">
                    <h3>Replace disconnected<br>recruitment administration</h3>
                    <p>Bring vacancies, applications, documents, and recruitment decisions into one organized workspace instead of relying on fragmented spreadsheets, email threads, and manual follow-ups.</p>
                    <ul class="nw-check-list"><li><x-icon name="check" /> Applicant Tracking</li><li><x-icon name="check" /> Screening Workflows</li><li><x-icon name="check" /> Candidate Shortlisting</li></ul>
                    <a href="{{ route('register') }}" class="nw-link">Explore the platform <x-icon name="arrow-right" /></a>
                </div>
                <div class="nw-audience-art" aria-label="The foundations of better hiring">
                    <span class="nw-art-label">A single recruitment workspace</span>
                    <div class="nw-opportunity"><x-icon name="briefcase" /><div><strong>Vacancy planning</strong><span>Set up roles and define the workflow your organization will use to fill them.</span></div></div>
                    <div class="nw-opportunity"><x-icon name="users" /><div><strong>Application management</strong><span>Receive and organize applicant information in one place from the moment a candidate applies.</span></div></div>
                    <p class="nw-art-caption">Make screening and shortlisting easier to manage with visible candidate stages and a clearer recruitment pipeline.</p>
                </div>
            </div>
            <div id="jobseekers-panel" class="nw-audience-grid" role="tabpanel" aria-labelledby="for-job-seekers" tabindex="0" x-show="audience === 'job-seekers'" x-cloak>
                <div class="nw-audience-content">
                    <h3>Keep candidate progression<br>clear and consistent</h3>
                    <p>Move applicants through Submitted, Shortlisted, Interview, Selected, and Rejected stages with a recorded history of each progression decision.</p>
                    <ul class="nw-check-list"><li><x-icon name="check" /> Interview Coordination</li><li><x-icon name="check" /> Team Collaboration</li><li><x-icon name="check" /> Recruitment Oversight</li></ul>
                    <a href="{{ route('register') }}" class="nw-link">See the workflow <x-icon name="arrow-right" /></a>
                </div>
                <div class="nw-audience-art nw-audience-art--peach" aria-label="Your next chapter">
                    <span class="nw-art-label">A clearer candidate pipeline</span>
                    <div class="nw-opportunity"><x-icon name="spark" /><div><strong>Screening and shortlisting</strong><span>Review applications, identify qualified candidates, and maintain structured shortlists.</span></div></div>
                    <div class="nw-opportunity"><x-icon name="globe" /><div><strong>Interview management</strong><span>Track interview stages and decisions so candidates continue moving through the process.</span></div></div>
                    <p class="nw-art-caption">Keep hiring teams informed without relying on scattered spreadsheets, inboxes, and disconnected systems.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="nw-section nw-section--soft" aria-labelledby="how-heading">
        <div class="nw-container">
            <div class="nw-section-heading"><div><span class="nw-eyebrow">A more accountable process</span><h2 id="how-heading" class="nw-heading">Recruitment questions<br><em>answered</em></h2></div><p>See where every candidate stands and what action is needed next.</p></div>
            <div class="nw-feature-grid">
                <article class="nw-feature"><span class="nw-feature__number">01</span><h3>What does Marthire help organizations manage?</h3><p>Marthire centralizes vacancies, applications, candidate records, screening, shortlisting, interviews, and hiring administration in one workflow.</p></article>
                <article class="nw-feature"><span class="nw-feature__number">02</span><h3>How does Marthire reduce manual recruitment work?</h3><p>By replacing scattered spreadsheets, emails, and disconnected tools with a shared workspace that keeps recruitment activity and candidate statuses together.</p></article>
                <article class="nw-feature"><span class="nw-feature__number">03</span><h3>Can teams collaborate on recruitment?</h3><p>Authorized administrators and hiring stakeholders can work from the same view of vacancies, candidates, and recruitment progress.</p></article>
            </div>
        </div>
    </section>

    <section class="nw-section" aria-labelledby="people-heading">
        <div class="nw-container nw-editorial">
            <img class="nw-editorial__image" src="{{ asset('asset/marthire/conversation.png') }}" width="585" height="444" loading="lazy" alt="A professional smiling during a video conversation">
            <div><span class="nw-eyebrow">Who is Marthire built for?</span><h2 id="people-heading" class="nw-heading">Recruitment management<br><em>made for Africa</em></h2><p class="nw-copy">Marthire supports structured recruitment for organizations that need clear applicant records, consistent candidate progression, and accountable administration.</p><a class="nw-link" href="{{ url('/About') }}">About us <x-icon name="arrow-right" /></a></div>
        </div>
    </section>

    <aside class="nw-resources" aria-labelledby="resources-heading">
        <div class="nw-container">
            <div class="nw-resources__heading"><h2 id="resources-heading" class="nw-eyebrow">How can our organization access Marthire?</h2><p>Contact Marthire to discuss platform access for your organization. Applicants can create an account and browse available opportunities.</p></div>
            <div class="nw-resource-grid">
                <a class="nw-resource" href="https://www.linkedin.com/learning/" rel="external"><img src="{{ asset('asset/marthire/linkedin.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>LinkedIn Learning</strong><x-icon name="arrow-up-right" /></a>
                <a class="nw-resource" href="https://www.indeed.com/career-advice" rel="external"><img src="{{ asset('asset/marthire/indeed.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>Indeed Career Guide</strong><x-icon name="arrow-up-right" /></a>
                <a class="nw-resource" href="https://www.glassdoor.com/blog/" rel="external"><img src="{{ asset('asset/marthire/glassdoor.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>Glassdoor career insights</strong><x-icon name="arrow-up-right" /></a>
            </div>
        </div>
    </aside>

    <section class="nw-cta" aria-labelledby="cta-heading"><div class="nw-container nw-cta__inner"><div><span class="nw-eyebrow">Get started</span><h2 id="cta-heading" class="nw-heading">Ready to improve your<br><em>recruitment process</em>?</h2></div><a href="{{ url('/ContactUs') }}" class="nw-button nw-button--light">Contact us <x-icon name="arrow-up-right" /></a></div></section>
</x-layout>
