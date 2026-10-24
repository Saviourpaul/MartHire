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

<x-layout title="Good people. Great possibilities." description="Search open roles, sign up and apply. NearbyWorks brings employers and job seekers together to make the next connection count.">
    <div x-data="jobBoard(@js($jobIndex), 6)">
        <section class="nw-hero" aria-labelledby="home-heading">
            <div class="nw-container nw-hero__grid">
                <div class="nw-hero__copy">
                    <span class="nw-eyebrow">A better way to come together</span>
                    <h1 id="home-heading">Good people.<br><em>Great possibilities.</em></h1>
                    <p class="nw-copy">Search open roles, apply with your own profile, or find your next hire. NearbyWorks brings employers and job seekers together to make the next connection count.</p>
                    <a href="{{ route('register') }}" class="nw-link">Hiring? Start your hiring journey <x-icon name="arrow-right" /></a>
                    <div class="nw-hero__footnote"><x-icon name="users" /> For the people hiring. And the people ready for what’s next.</div>
                </div>
                <div class="nw-hero__search">
                    <form class="nw-search" role="search" aria-label="Search jobs" method="get" action="{{ route('jobs.index') }}">
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
                        <button class="nw-button nw-button--primary nw-search__submit" type="submit">Search jobs <x-icon name="arrow-right" /></button>
                    </form>
                    @if ($categories->isNotEmpty())
                        <div class="nw-popular" role="group" aria-label="Popular categories">
                            <span>Popular categories</span>
                            @foreach ($categories->take(5) as $category)
                                <a class="nw-chip" href="{{ route('jobs.index', ['category' => $category['slug']]) }}">{{ $category['name'] }}</a>
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
                                <a class="nw-pin__link" href="{{ route('jobs.index', ['place' => $pin['country']]) }}" aria-label="{{ $pin['country'] }}: {{ $pin['count'] }} {{ \Illuminate\Support\Str::plural('role', $pin['count']) }}">
                                    <img src="{{ asset('asset/nearbyworks/flags/'.$pin['code'].'.svg') }}" width="40" height="40" alt="" loading="lazy" onerror="this.hidden = true">
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
                <span><x-icon name="briefcase" /> Opportunities worth exploring</span>
                <span><x-icon name="users" /> People worth getting to know</span>
                <span><x-icon name="globe" /> One place to connect</span>
            </div>
        </div>

        <section id="jobs" class="nw-section" aria-labelledby="jobs-heading">
            <div class="nw-container">
                <div class="nw-section-heading">
                    <div><span class="nw-eyebrow">Latest jobs</span><h2 id="jobs-heading" class="nw-heading">Fresh roles,<br><em>ready when you are.</em></h2></div>
                    <p>Look around as long as you like. When something fits, create a free account and apply in a few steps.</p>
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
                        <a href="{{ route('jobs.index') }}" class="nw-button nw-button--outline">View all {{ $jobs->count() }} {{ \Illuminate\Support\Str::plural('job', $jobs->count()) }} <x-icon name="arrow-right" /></a>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @if ($categories->isNotEmpty())
        <section id="categories" class="nw-section nw-section--soft" aria-labelledby="categories-heading">
            <div class="nw-container">
                <div class="nw-section-heading">
                    <div><span class="nw-eyebrow">Browse by category</span><h2 id="categories-heading" class="nw-heading">Find work in the<br>field <em>you love.</em></h2></div>
                    <p>Pick a field to see every open role in it, from first jobs to leadership.</p>
                </div>
                <div class="nw-category-grid">
                    @foreach ($categories as $category)
                        <a class="nw-category" href="{{ route('jobs.index', ['category' => $category['slug']]) }}">
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
                <div><span class="nw-eyebrow">Two sides. One better connection.</span><h2 id="audience-heading" class="nw-heading">Your next chapter<br>starts with <em>who.</em></h2></div>
                <p>Whether you’re growing a team or ready for a new direction, there’s a place for you here.</p>
            </div>
            <div class="nw-audience-switch" role="tablist" aria-label="Explore NearbyWorks">
                <button id="for-employers" x-ref="employersTab" role="tab" aria-controls="employers-panel" :aria-selected="(audience === 'employers').toString()" :tabindex="audience === 'employers' ? 0 : -1" @click="select('employers')" @keydown.right.prevent="select('job-seekers', true)" @keydown.left.prevent="select('job-seekers', true)" @keydown.home.prevent="select('employers', true)" @keydown.end.prevent="select('job-seekers', true)">For employers</button>
                <button id="for-job-seekers" x-ref="jobseekersTab" role="tab" aria-controls="jobseekers-panel" :aria-selected="(audience === 'job-seekers').toString()" :tabindex="audience === 'job-seekers' ? 0 : -1" @click="select('job-seekers')" @keydown.right.prevent="select('employers', true)" @keydown.left.prevent="select('employers', true)" @keydown.home.prevent="select('employers', true)" @keydown.end.prevent="select('job-seekers', true)">For job seekers</button>
            </div>
            <div id="employers-panel" class="nw-audience-grid" role="tabpanel" aria-labelledby="for-employers" tabindex="0" x-show="audience === 'employers'">
                <div class="nw-audience-content">
                    <h3>Great teams begin<br>with the right people.</h3>
                    <p>Bring your hiring story to the people who should hear it. Make room for talent, potential, and a fresh perspective.</p>
                    <ul class="nw-check-list"><li><x-icon name="check" /> Put your opportunities in focus</li><li><x-icon name="check" /> Make meaningful talent connections</li><li><x-icon name="check" /> Build what comes next, together</li></ul>
                    <a href="{{ route('register') }}" class="nw-link">Start your hiring journey <x-icon name="arrow-right" /></a>
                </div>
                <div class="nw-audience-art" aria-label="The foundations of better hiring">
                    <span class="nw-art-label">Make space for possibility</span>
                    <div class="nw-opportunity"><x-icon name="briefcase" /><div><strong>Your opportunity</strong><span>A role with purpose. A team with ambition.</span></div></div>
                    <div class="nw-opportunity"><x-icon name="users" /><div><strong>The next great addition</strong><span>Skills, experience, and a new perspective.</span></div></div>
                    <p class="nw-art-caption">People and opportunities. Better together.</p>
                </div>
            </div>
            <div id="jobseekers-panel" class="nw-audience-grid" role="tabpanel" aria-labelledby="for-job-seekers" tabindex="0" x-show="audience === 'job-seekers'" x-cloak>
                <div class="nw-audience-content">
                    <h3>Your potential deserves<br>a new possibility.</h3>
                    <p>You’re more than a list of past roles. Take the first step toward the people and opportunities that could shape your future.</p>
                    <ul class="nw-check-list"><li><x-icon name="check" /> Start with your own NearbyWorks account</li><li><x-icon name="check" /> Keep your profile details up to date</li><li><x-icon name="check" /> Be ready for your next chapter</li></ul>
                    <a href="{{ route('register') }}" class="nw-link">Take your next step <x-icon name="arrow-right" /></a>
                </div>
                <div class="nw-audience-art nw-audience-art--peach" aria-label="Your next chapter">
                    <span class="nw-art-label">Bring your whole story</span>
                    <div class="nw-opportunity"><x-icon name="spark" /><div><strong>What makes you, you</strong><span>Your experience. Your ambition. Your perspective.</span></div></div>
                    <div class="nw-opportunity"><x-icon name="globe" /><div><strong>A world of possibilities</strong><span>A fresh start begins with a connection.</span></div></div>
                    <p class="nw-art-caption">Make your next move a meaningful one.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="nw-section nw-section--soft" aria-labelledby="how-heading">
        <div class="nw-container">
            <div class="nw-section-heading"><div><span class="nw-eyebrow">A thoughtful place to start</span><h2 id="how-heading" class="nw-heading">Less distance.<br><em>More possibility.</em></h2></div><p>A simple starting point for people with something to offer, and teams with something to build.</p></div>
            <div class="nw-feature-grid">
                <article class="nw-feature"><span class="nw-feature__number">01</span><h3>Find a role.</h3><p>Browse open roles and narrow them by type or place. You don’t need an account to look around.</p></article>
                <article class="nw-feature"><span class="nw-feature__number">02</span><h3>Create your account.</h3><p>Found one you like? Sign up in a minute, so your details travel with every application you send.</p></article>
                <article class="nw-feature"><span class="nw-feature__number">03</span><h3>Apply as yourself.</h3><p>Keep your name and contact details current, so each application comes from the person behind it.</p></article>
            </div>
        </div>
    </section>

    <section class="nw-section" aria-labelledby="people-heading">
        <div class="nw-container nw-editorial">
            <img class="nw-editorial__image" src="{{ asset('asset/nearbyworks/conversation.png') }}" width="585" height="444" loading="lazy" alt="A professional smiling during a video conversation">
            <div><span class="nw-eyebrow">Built around people</span><h2 id="people-heading" class="nw-heading">The best next step<br>is a <em>human one.</em></h2><p class="nw-copy">Hiring is about more than filling a role. It’s a new colleague, a fresh perspective, a chance to do meaningful work. That’s the kind of connection NearbyWorks is here for.</p><a class="nw-link" href="{{ url('/About') }}">Get to know NearbyWorks <x-icon name="arrow-right" /></a></div>
        </div>
    </section>

    <aside class="nw-resources" aria-labelledby="resources-heading">
        <div class="nw-container">
            <div class="nw-resources__heading"><h2 id="resources-heading" class="nw-eyebrow">Keep exploring</h2><p>Independent career resources, beyond NearbyWorks.</p></div>
            <div class="nw-resource-grid">
                <a class="nw-resource" href="https://www.linkedin.com/learning/" rel="external"><img src="{{ asset('asset/nearbyworks/linkedin.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>LinkedIn Learning</strong><x-icon name="arrow-up-right" /></a>
                <a class="nw-resource" href="https://www.indeed.com/career-advice" rel="external"><img src="{{ asset('asset/nearbyworks/indeed.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>Indeed Career Guide</strong><x-icon name="arrow-up-right" /></a>
                <a class="nw-resource" href="https://www.glassdoor.com/blog/" rel="external"><img src="{{ asset('asset/nearbyworks/glassdoor.svg') }}" width="27" height="27" loading="lazy" alt=""><strong>Glassdoor career insights</strong><x-icon name="arrow-up-right" /></a>
            </div>
        </div>
    </aside>

    <section class="nw-cta" aria-labelledby="cta-heading"><div class="nw-container nw-cta__inner"><div><span class="nw-eyebrow">Let’s see what’s possible</span><h2 id="cta-heading" class="nw-heading">Your next great connection<br>could start <em>right here.</em></h2></div><a href="{{ route('register') }}" class="nw-button nw-button--light">Get started with NearbyWorks <x-icon name="arrow-up-right" /></a></div></section>
</x-layout>
