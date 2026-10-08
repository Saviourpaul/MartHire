<x-app>
    <section class="pt-28 lg:pt-[206px] pb-12 lg:pb-20 relative" data-jobs-browser>
        <div class="grained-bg absolute top-0 left-0 w-full h-full opacity-[0.36]">
            <img src="{{ asset('assets/images/banner.png') }}" alt="">
        </div>
        <div class="container">
            <div class="row justify-center m-4">
                <div class="lg:col-6 text-center mb-20 ">
                    <h1 class="h1-lg highlighted"><span>Latest</span> jobs</h1>
                </div>
                <form action="{{ route('Browse-jobs') }}" method="GET" data-jobs-filter
                    class="mb-5 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_220px_200px_auto_auto] lg:items-end">
                    <div>
                        <label for="job-search"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Search
                            jobs</label>
                        <input id="job-search" type="search" name="search" value="{{ $search }}"
                            placeholder="Title, company, category, or location"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    </div>
                    <div>
                        <label for="job-category"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                        <select id="job-category" name="category"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="">All categories</option>
                            @foreach ($categories as $jobCategory)
                                <option value="{{ $jobCategory }}" @selected($category === $jobCategory)>{{ $jobCategory }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="job-employment-type"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Employment
                            type</label>
                        <select id="job-employment-type" name="employment_type"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="">All types</option>
                            @foreach (\App\Models\Job::employmentTypeOptions() as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected($employmentType === $typeValue)>{{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-semibold btn btn-primary btn-sm transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/30">
                        Search
                    </button>
                    <a href="{{ route('Browse-jobs') }}" data-jobs-reset
                        class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                        Reset
                    </a>
                </form>
                <p>
        @if ($jobs->total() > 0)
            Showing {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} of {{ $jobs->total() }}
            {{ Str::plural('job', $jobs->total()) }}
       
        @endif
    </p>

            </div>
        </div>

        <p data-jobs-error role="alert"
            class="mb-5 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-300">
            Jobs could not be refreshed. <button type="button" data-jobs-retry
                class="font-semibold underline underline-offset-2">Try again</button>
        </p>
        <p data-jobs-status class="sr-only" aria-live="polite" aria-atomic="true"></p>
        <section data-jobs-results aria-busy="false" class="section mt-0">
            @fragment('jobs-results')
                <div class="container">
                    <div class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                        @forelse ($jobs as $job)
                            <article data-job-card class="card flex h-full min-w-0 flex-col">
                                <a class="block aspect-[16/10] overflow-hidden bg-gray-50 dark:bg-gray-800" href="{{ route('job-details', $job) }}" aria-label="View {{ $job->title }} at {{ $job->company }}">
                                    <img class="w-full" width="425" height="285" src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" loading="lazy">
                                </a>
                                <div class="flex flex-1 flex-col p-4 sm:p-5">
                                    <ul class="tags mb-2 text-xs">
                                        <li>
                                            <a class="tag" href="#">{{ $job->category ?: 'General' }}</a>
                                        </li>
                                        <li>
                                            <a href="#" class="tag">{{ $job->employmentTypeLabel() }}</a>
                                        </li>
                                        <li>
                                           <i class="fa fa-location-dot text-primary mr-1"></i>
                                           <span>{{ $job->location }}</span>
                                        </li>
                                    </ul>
                                    <h3 class="mb-3 line-clamp-2 text-lg font-semibold leading-tight text-gray-900 sm:text-xl dark:text-white">
                                        <a href="{{ route('job-details', $job) }}">{{ $job->title }}</a>
                                    </h3>
                                    <ul class="card-info mb-3 text-xs sm:text-sm">
                                        <li>
                                            <a class="inline-flex items-center space-x-2 transition-all duration-200 hover:text-primary hover:underline" href="#">
                                                <span>{{ $job->company }}</span>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="inline-flex items-center space-x-2">
                                                <img class="h-5 w-5 rounded-full" src="{{ asset('assets/images/svgs/calender.svg') }}" alt="Calendar">
                                                <span>{{ $job->created_at->diffForHumans() }}</span>
                                            </div>
                                        </li>
                                    </ul>
                                    <p class="mb-4 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ Str::limit(strip_tags($job->description), 180) }}</p>
                                    <div class="mt-auto flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                                        <a class="inline-flex items-center text-sm font-medium text-primary transition-all duration-200" href="{{ route('job-details', $job) }}">
                                            <span class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs text-white">
                                                <i class="fa fa-chevron-right"></i>
                                            </span>
                                            View job
                                        </a>
                                        @auth
                                            @if (auth()->user()->isApplicant() && $appliedJobIds->contains($job->id))
                                                <span class="inline-flex items-center text-sm font-medium text-primary">
                                                    <span class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-success text-xs text-white">
                                                        <i class="fa fa-check"></i>
                                                    </span>
                                                    Applied
                                                </span>
                                            @elseif (auth()->user()->isApplicant())
                                                <a class="inline-flex items-center text-sm font-medium text-primary transition-all duration-200" href="{{ route('applications.create', $job) }}">
                                                    <span class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-xs text-white">
                                                        <i class="fa fa-chevron-right"></i>
                                                    </span>
                                                    Apply now
                                                </a>
                                            @else
                                                <span class="inline-flex items-center text-sm font-medium text-primary">
                                                    <span class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-400 text-xs text-white">
                                                        <i class="fa fa-lock"></i>
                                                    </span>
                                                    Applicant only
                                                </span>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white/90">No matching jobs</h2>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Try changing your search or filters, or check back when new jobs are posted.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($jobs->hasPages())
                        <nav data-jobs-pagination class="mx-auto mt-6 w-full max-w-7xl border-t border-gray-200 pt-5 dark:border-gray-800"
                            aria-label="Job listing pages">
                            {{ $jobs->links() }}
                        </nav>
                    @endif
                </div>
            @endfragment
        </section>
        <div data-jobs-skeleton class="hidden" role="status" aria-label="Loading jobs" aria-hidden="true">
            <span class="sr-only">Loading jobs...</span>
            <div class="container">
                <div class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    @for ($i = 0; $i < 6; $i++)
                        <article data-job-skeleton-card class="h-full animate-pulse overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                            <div class="aspect-[16/10] w-full bg-gray-200 dark:bg-gray-800"></div>
                            <div class="p-4 sm:p-5">
                                <div class="mb-4 flex flex-wrap gap-2">
                                    <div class="h-6 w-20 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="h-6 w-16 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="h-6 w-20 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                </div>
                                <div class="mb-3 h-5 w-4/5 rounded bg-gray-200 dark:bg-gray-800"></div>
                                <div class="mb-4 h-3 w-2/3 rounded bg-gray-200 dark:bg-gray-800"></div>
                                <div class="mb-3 h-3 w-full rounded bg-gray-100 dark:bg-gray-800"></div>
                                <div class="mb-6 h-3 w-4/5 rounded bg-gray-100 dark:bg-gray-800"></div>
                                <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                                    <div class="h-8 w-28 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                </div>
                            </div>
                        </article>
                    @endfor
                </div>
            </div>
        </div>
    </section>

    <!-- jobs -->

    <!-- end posts -->

   <section class="cta section-bordered">
        <div class="container">
            <div class="row mx-0 relative justify-center">
                <div class="col-12">
                    <img class="absolute -z-[1] top-0 left-0 w-full h-full" src="{{ asset('assets/images/cta-bg.png') }}" alt="">
                </div>
                <div class="lg:col-10 text-center">
                    <div class="shadow rounded-xl bg-white/40 py-20 border border-border">
                        <div class="md:max-w-[588px] mx-auto">
                            <h2 class="mb-6 highlighted">Ready to improve your <br>
                                <span>recruitment process</span>?</h2>
                            <p class="mb-6">Bring vacancies, applications, applicant records, and candidate decisions
                                into one organized recruitment workflow.</p>
                            <a href="{{ route('contact') }}" class="btn btn-primary">Contact us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app>
