<x-layout>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        <div data-table-refresh-region data-table-refresh-id="public-jobs">
        <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Browse all jobs</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Approved openings currently accepting applications.</p>
            </div>
            <form action="{{ route('client.jobs-listings') }}" method="GET" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-[320px]">
                    <button type="submit" class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C11.2676 15.7053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053Z" />
                        </svg>
                    </button>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search title, company, category..."
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Search</button>
                <a href="{{ route('client.jobs-listings') }}" data-table-reset-link class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Reset</a>
            </form>
        </div>
        <section class="space-y-5">
            <!--div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">   
                <button type="button" data-table-refresh-button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <svg data-table-refresh-icon class="fill-current" width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.75 6.667a.75.75 0 0 1-.75.75h-3.333a.75.75 0 0 1 0-1.5h1.516A5.25 5.25 0 0 0 5.02 7.553a.75.75 0 1 1-1.372-.606 6.75 6.75 0 0 1 11.602-1.9V3.75a.75.75 0 0 1 1.5 0v2.917ZM3.25 13.333a.75.75 0 0 1 .75-.75h3.333a.75.75 0 0 1 0 1.5H5.817a5.25 5.25 0 0 0 9.163-1.636.75.75 0 1 1 1.372.606 6.75 6.75 0 0 1-11.602 1.9v1.297a.75.75 0 0 1-1.5 0v-2.917Z"/></svg>
                    <span data-table-refresh-label>Reload</span>
                </button>
            </div-->

            <div data-table-refresh-message class="hidden rounded-lg border px-4 py-3 text-theme-sm font-medium"></div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($jobs as $job)
                    @php
                        $hasApplied = $appliedJobIds->contains($job->id);
                    @endphp

                    <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <a href="{{ route('job-details', $job) }}" class="mb-5 flex aspect-[16/9] items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                            <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" class="h-full w-full object-contain p-4">
                        </a>

                        <div class="flex flex-1 flex-col">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-theme-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $job->category ?: 'Uncategorized' }}</span>
                            </div>

                            <h2 class="text-theme-xl font-semibold text-gray-800 dark:text-white/90">
                                <a href="{{ route('job-details', $job) }}" class="hover:text-brand-500">{{ $job->title }}</a>
                            </h2>
                            <p class="mt-1 text-theme-sm font-medium text-gray-600 dark:text-gray-300">{{ $job->company }}</p>
                            <!--p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                Posted by {{ trim(($job->employer?->first_name ?? '').' '.($job->employer?->last_name ?? '')) ?: 'Employer' }}
                            </p-->

                            <dl class="mt-4 grid grid-cols-1 gap-2 text-theme-xs text-gray-500 dark:text-gray-400">
                                <div class="flex justify-between gap-4"><dt>Location</dt><dd class="text-right text-gray-700 dark:text-gray-300">{{ $job->location ?: 'Not specified' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt>Employment Type</dt><dd class="text-right text-gray-700 dark:text-gray-300">{{ $job->employmentTypeLabel() }}</dd></div>
                                <div class="flex justify-between gap-4"><dt>Posted</dt><dd class="text-right text-gray-700 dark:text-gray-300">{{ $job->created_at?->diffForHumans() }}</dd></div>
                                <div class="flex justify-between gap-4"><dt>Deadline</dt><dd class="text-right text-gray-700 dark:text-gray-300">{{ $job->due_date?->format('M j, Y') }}</dd></div>
                            </dl>

                            <div class="mt-4 space-y-3">
                                <div>
                                    <h3 class="text-theme-xs font-semibold text-gray-700 dark:text-gray-300">Description</h3>
                                    <p class="mt-1 text-theme-sm leading-6 text-gray-500 dark:text-gray-400">{{ Str::limit(strip_tags($job->description), 170) }}</p>
                                </div>     
                            </div>

                            <div class="">
                                <a href="{{ route('job-details', $job) }}" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">View Details</a>

                                @auth
                                    @if (auth()->user()->isApplicant() && $hasApplied)
                                        <span class="inline-flex h-11 flex-1 items-center justify-center rounded-lg bg-gray-100 px-4 text-theme-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Already Applied</span>
                                    @elseif (auth()->user()->isApplicant())
                                        <a href="{{ route('applications.create', $job) }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Apply Now</a>
                                        
                                    @else
                                        <span class="inline-flex h-11 flex-1 items-center justify-center rounded-lg bg-gray-100 px-4 text-theme-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Applicants Only</span>
                                    @endif
                                @else
                               
                                    <a href="{{ route('applications.create', $job) }}"class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Apply Now</a>
                                @endauth
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-white/[0.03]">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">No jobs match your search </h2>
                        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">Check back later for new openings.</p>
                    </div>
                @endforelse
            </div>

            @if ($jobs->hasPages())
                <div class="border-t border-gray-100 pt-5 dark:border-gray-800">
                    {{ $jobs->links() }}
                </div>
            @endif
        </section>
        </div>
    </div>
</x-layout>

