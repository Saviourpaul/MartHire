<x-layout>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        
            <div>
                <a href="{{ route('client.jobs-listings') }}" class="mb-3 inline-flex items-center gap-2 text-theme-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">
                    <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7908 5.23017C13.0837 5.52306 13.0837 5.99794 12.7908 6.29083L9.08167 10L12.7908 13.7092C13.0837 14.0021 13.0837 14.4769 12.7908 14.7698C12.4979 15.0627 12.0231 15.0627 11.7302 14.7698L7.49017 10.5298C7.19728 10.2369 7.19728 9.76206 7.49017 9.46917L11.7302 5.23017C12.0231 4.93728 12.4979 4.93728 12.7908 5.23017Z" />
                    </svg>
                    Back to jobs
                </a>
            </div>

           
        
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                class="mb-6 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-theme-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info') || session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                class="mb-6 rounded-lg border border-brand-500/30 bg-brand-50 px-4 py-3 text-theme-sm font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                {{ session('info') ?? session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="xl:col-span-8">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-100 p-5 dark:border-gray-800 sm:p-6">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                                <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" class="h-full w-full object-contain p-3">
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</h2>
                                 <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $job->company }}</p>

                               
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-theme-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $job->category ?: 'Uncategorized' }}</span>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-theme-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Applications open</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 border-b border-gray-100 dark:border-gray-800 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="border-b border-gray-100 p-5 dark:border-gray-800 sm:border-r lg:border-b-0">
                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">Posted Date</p>
                            <p class="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->created_at?->format('M j, Y') }}</p>
                        </div>
                        
                        <div class="border-b border-gray-100 p-5 dark:border-gray-800 sm:border-r sm:border-b-0">
                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">Location</p>
                            <p class="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->location ?: 'Not specified' }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">Employment Type</p>
                            <p class="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->employmentTypeLabel() }}</p>
                        </div>
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Job Description</h3>
                            <div class="mt-3 text-theme-sm leading-7 text-gray-600 dark:text-gray-300">
                                {!! nl2br(e(strip_tags($job->description))) !!}
                            </div>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Requirements</h3>
                            <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-theme-sm leading-7 text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                                {!! nl2br(e(strip_tags($job->description))) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="xl:col-span-4">
                <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Application</h2>
                    <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">Submit your application before the deadline.</p>

                    <dl class="mt-5 space-y-3">
                      
                        <div class="flex justify-between gap-4">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">Deadline</dt>
                            <dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->due_date?->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-theme-sm text-gray-500 dark:text-gray-400">Applications</dt>
                            <dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ number_format($job->applications_count ?? 0) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        @if (! $job->isAcceptingApplications())
                            <div class="rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">
                                This job is not currently not open.
                            </div>
                        @elseif ($existingApplication)
                            <div class="rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-theme-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                <p>You applied on {{ $existingApplication->submitted_at?->format('M j, Y') }}.</p>
                                <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $existingApplication->status->badgeClass() }}">
                                    {{ $existingApplication->status->label() }}
                                </span>
                            </div>
                            <a href="{{ route('client.applications.show', $existingApplication) }}" class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                                View Application
                            </a>
                        @else
                            <a href="{{ route('applications.create', $job) }}" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                                Apply Now
                            </a>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layout>


