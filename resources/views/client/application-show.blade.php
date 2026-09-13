<x-layout>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Application Status</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $application->reference }} - {{ $application->job->title }}</p>
            </div>
            <a href="{{ route('client.jobs-listings') }}" class="inline-flex items-center gap-2 text-theme-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">
                Back to Jobs
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-theme-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400" role="status">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400" role="alert">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $application->job->title }}</h2>
                            <p class="mt-1 text-theme-sm text-gray-600 dark:text-gray-300">{{ $application->job->company }}</p>
                            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Submitted {{ $application->submitted_at->format('M d, Y') }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                    </div>

                    @if ($application->employer_remarks)
                        <div class="mt-4 rounded-lg border border-brand-500/30 bg-brand-50 px-4 py-3 text-theme-sm font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ $application->employer_remarks }}</div>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Document Review</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="px-4 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Document</th>
                                    <th class="px-4 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($application->documents as $document)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">{{ $document->document_name }}</p>
                                            <a href="{{ $document->canPreviewInline() ? $document->previewUrl() : $document->downloadUrl() }}" target="_blank" rel="noopener" class="mt-1 inline-block text-theme-xs font-medium text-brand-500 hover:text-brand-600">View submitted file</a>
                                        </td>
                                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $document->status->badgeClass() }}">{{ $document->status->label() }}</span></td>
                                        <td class="px-4 py-3 text-theme-sm text-gray-700 dark:text-gray-300">{{ $document->reviewed_at?->format('M d, Y') ?: 'Pending review' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Profile Information</h2>
                    <div class="space-y-3">
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Name:</strong> {{ $application->first_name }} {{ $application->last_name }}</p>
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Email:</strong> {{ $application->email }}</p>
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Phone:</strong> {{ $application->phone }}</p>
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Nationality:</strong> {{ $application->nationality ?: 'Not provided' }}</p>
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Origin:</strong> {{ collect([$application->local_government_area, $application->state_of_origin])->filter()->implode(', ') ?: 'Not provided' }}</p>
                        <p class="text-theme-sm text-gray-700 dark:text-gray-300"><strong class="font-medium text-gray-800 dark:text-white/90">Address:</strong> {{ $application->address ?: 'Not provided' }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Status History</h2>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                            <div class="py-3">
                                <div class="flex items-center justify-between">
                                    <strong class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $history->to_status->label() }}</strong>
                                    <small class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $history->created_at->diffForHumans() }}</small>
                                </div>
                                @if ($history->remarks)
                                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $history->remarks }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="py-3 text-theme-sm text-gray-500 dark:text-gray-400">No status changes yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
