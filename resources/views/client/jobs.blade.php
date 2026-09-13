<x-layout>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">My Applications</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Track and review your job applications.</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ number_format($applications->total()) }} total</p>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <th class="px-6 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Job</th>
                            <th class="px-6 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reference</th>
                            <th class="px-6 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Submitted</th>
                            <th class="px-6 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Documents</th>
                            <th class="px-6 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-right text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($applications as $application)
                            <tr>
                                <td class="px-6 py-3.5">
                                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->job->title }}</p>
                                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $application->job->company }}</p>
                                </td>
                                <td class="px-6 py-3.5 text-theme-sm text-gray-700 dark:text-gray-300">{{ $application->reference }}</td>
                                <td class="px-6 py-3.5 text-theme-sm text-gray-700 dark:text-gray-300">{{ $application->submitted_at?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="px-6 py-3.5 text-theme-sm text-gray-700 dark:text-gray-300">{{ $application->documents->count() }} uploaded</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">
                                        {{ $application->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <a href="{{ route('client.applications.show', $application) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                {{ $applications->links() }}
            </div>
        </section>
    </div>
</x-layout>
