@php
    // Real jobs: pass them in from the route/controller as $jobs. Until then, sample listings are shown.
    $catalog = \App\Support\JobCatalog::make($jobs ?? null);
    $jobs = $catalog->jobs;
    $categories = $catalog->categories;
    $typeCounts = $catalog->counts('type', ['Full-time', 'Part-time', 'Contract']);
    $workplaceCounts = $catalog->counts('workplace', ['Remote', 'Hybrid', 'On-site']);
    $pageSize = 9;
@endphp

<x-layout title="Browse jobs" description="Search open roles by title, skill, place or category. Sign up for a free NearbyWorks account when you find one worth applying for.">
    <div x-data="jobSearch({ jobs: @js($catalog->searchIndex()), categories: @js($categories->pluck('name', 'slug')), pageSize: {{ $pageSize }} })">
        <section class="nw-jobs-hero" aria-labelledby="jobs-page-heading">
            <div class="nw-container">
                <span class="nw-eyebrow">Browse jobs</span>
                <h1 id="jobs-page-heading">Find the role<br><em>that fits.</em></h1>
                <p class="nw-copy">Search by title, skill or company, narrow things down by field, or look through everything. Sign up when you’re ready to apply.</p>

                <form class="nw-search" role="search" aria-label="Search jobs" x-on:submit.prevent="showResults()">
                    <label class="nw-search__field">
                        <x-icon name="search" />
                        <span class="nw-search__text">
                            <span class="nw-search__label">What</span>
                            <input class="nw-search__input" type="search" placeholder="Job title, skill or company" autocomplete="off" x-model.debounce.150ms="query">
                        </span>
                    </label>
                    <label class="nw-search__field">
                        <x-icon name="map-pin" />
                        <span class="nw-search__text">
                            <span class="nw-search__label">Where</span>
                            <input class="nw-search__input" type="search" placeholder="City, country or remote" autocomplete="off" x-model.debounce.150ms="place">
                        </span>
                    </label>
                    <button class="nw-button nw-button--primary nw-search__submit" type="submit">Search jobs <x-icon name="arrow-right" /></button>
                </form>
            </div>
        </section>

        <section class="nw-section nw-section--results" aria-label="Job results">
            <div class="nw-container">
                @if ($jobs->isEmpty())
                    <div class="nw-jobs__empty">
                        <h3>No open roles right now.</h3>
                        <p>Check back soon, or create an account so you’re ready when new roles arrive.</p>
                        <div class="nw-button-row nw-button-row--center">
                            <a href="{{ route('register') }}" class="nw-button nw-button--primary">Create an account <x-icon name="arrow-up-right" /></a>
                        </div>
                    </div>
                @else
                    <div class="nw-jobs-layout">
                        <div class="nw-filters-wrap">
                            <button type="button" class="nw-filter-toggle" aria-controls="job-filters" x-bind:aria-expanded="filtersOpen.toString()" x-on:click="filtersOpen = ! filtersOpen">
                                <x-icon name="sliders" /> Filters
                                <span class="nw-filter-toggle__count" x-show="sidebarCount > 0" x-cloak x-text="sidebarCount"></span>
                            </button>

                            <form id="job-filters" class="nw-filters" aria-label="Filter jobs" x-show="filtersOpen" x-cloak x-on:submit.prevent>
                                @if ($categories->isNotEmpty())
                                    <fieldset class="nw-filters__group">
                                        <legend>Category</legend>
                                        <label class="nw-filter">
                                            <input class="nw-filter__input" type="radio" name="category" value="" x-model="category">
                                            <span class="nw-filter__label">All categories</span>
                                            <small>{{ $jobs->count() }}</small>
                                        </label>
                                        @foreach ($categories as $category)
                                            <label class="nw-filter">
                                                <input class="nw-filter__input" type="radio" name="category" value="{{ $category['slug'] }}" x-model="category">
                                                <span class="nw-filter__label">{{ $category['name'] }}</span>
                                                <small>{{ $category['count'] }}</small>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif

                                @if ($typeCounts->isNotEmpty())
                                    <fieldset class="nw-filters__group">
                                        <legend>Job type</legend>
                                        @foreach ($typeCounts as $type => $count)
                                            <label class="nw-filter">
                                                <input class="nw-filter__input" type="checkbox" value="{{ $type }}" x-model="types">
                                                <span class="nw-filter__label">{{ $type }}</span>
                                                <small>{{ $count }}</small>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif

                                @if ($workplaceCounts->isNotEmpty())
                                    <fieldset class="nw-filters__group">
                                        <legend>Workplace</legend>
                                        @foreach ($workplaceCounts as $workplace => $count)
                                            <label class="nw-filter">
                                                <input class="nw-filter__input" type="checkbox" value="{{ $workplace }}" x-model="workplaces">
                                                <span class="nw-filter__label">{{ $workplace }}</span>
                                                <small>{{ $count }}</small>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif

                                <button type="button" class="nw-link nw-filters__clear" x-show="sidebarCount > 0" x-cloak x-on:click="clearAll()">Clear filters</button>
                            </form>
                        </div>

                        <div class="nw-results" x-ref="results">
                            <div class="nw-results__bar">
                                <p class="nw-jobs__count" role="status" aria-live="polite" x-text="summary">Showing {{ min($pageSize, $jobs->count()) }} of {{ $jobs->count() }} {{ \Illuminate\Support\Str::plural('role', $jobs->count()) }}</p>
                                <label class="nw-sort">
                                    <span>Sort by</span>
                                    <select class="nw-select" x-model="sort">
                                        <option value="newest">Newest first</option>
                                        <option value="title">Job title A to Z</option>
                                    </select>
                                </label>
                            </div>

                            <div class="nw-active" x-show="activeFilters.length > 0" x-cloak>
                                <template x-for="filter in activeFilters" x-bind:key="filter.key + (filter.value ?? '')">
                                    <button type="button" class="nw-chip nw-chip--remove" x-on:click="removeFilter(filter)">
                                        <span x-text="filter.label"></span>
                                        <x-icon name="close" />
                                        <span class="nw-sr-only">Remove this filter</span>
                                    </button>
                                </template>
                                <button type="button" class="nw-link nw-link--small" x-on:click="clearAll()">Clear all</button>
                            </div>

                            <div class="nw-job-grid nw-job-grid--results">
                                @foreach ($jobs as $job)
                                    <div class="nw-job-slot" x-show="isShown({{ $loop->index }})" x-bind:style="{ order: order({{ $loop->index }}) }" @if ($loop->index >= $pageSize) x-cloak @endif>
                                        <x-job-card :job="$job" />
                                    </div>
                                @endforeach
                            </div>

                            <div class="nw-jobs__empty" x-show="matches.length === 0" x-cloak>
                                <h3>No roles match your search yet.</h3>
                                <p>Try a shorter keyword, another place or fewer filters. You can also create an account and come back as new roles arrive.</p>
                                <div class="nw-button-row nw-button-row--center">
                                    <button type="button" class="nw-button nw-button--outline" x-on:click="clearAll()">Clear all filters</button>
                                    <a href="{{ route('register') }}" class="nw-button nw-button--primary">Create an account <x-icon name="arrow-up-right" /></a>
                                </div>
                            </div>

                            <div class="nw-jobs__more" x-show="remaining > 0" x-cloak>
                                <button type="button" class="nw-button nw-button--outline" x-on:click="loadMore()"><span>Show <span x-text="Math.min(remaining, pageSize)">{{ min($pageSize, max(0, $jobs->count() - $pageSize)) }}</span> more roles</span> <x-icon name="arrow-right" /></button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="nw-cta" aria-labelledby="cta-heading"><div class="nw-container nw-cta__inner"><div><span class="nw-eyebrow">Found something promising?</span><h2 id="cta-heading" class="nw-heading">Create your account<br>and <em>apply today.</em></h2></div><a href="{{ route('register') }}" class="nw-button nw-button--light">Get started with NearbyWorks <x-icon name="arrow-up-right" /></a></div></section>
</x-layout>
