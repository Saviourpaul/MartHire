<x-layout>
    <main class="mx-auto max-w-7xl p-4 pb-20 sm:p-6">
        <a href="{{ route('admin.jobs.index') }}"
            class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">Back to Job Moderation</a>
        <div class="my-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="break-words text-xl font-semibold text-gray-900 dark:text-white">{{ $job->title }}</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Content review · Job #{{ $job->id }}</p>
            </div>
            @include('admin.partials.job-moderation-actions', ['job' => $job])
        </div>
        @if (session('success'))
            <div role="status"
                class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div role="alert"
                class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ session('error') ?: $errors->first() }}</div>
        @endif
        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-3">
            <section
                class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 sm:p-6 xl:col-span-2 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-6 flex items-start gap-4">
                    <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo"
                        class="h-16 w-16 shrink-0 rounded-lg border border-gray-200 object-contain dark:border-gray-700">
                    <div class="min-w-0">
                        <h2 class="break-words text-lg font-semibold text-gray-900 dark:text-white">{{ $job->company }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->category ?: 'Uncategorized' }}
                        </p>
                    </div>
                </div>
                <dl
                    class="grid grid-cols-1 gap-4 border-y border-gray-200 py-5 text-sm sm:grid-cols-2 dark:border-gray-700">
                    @foreach ([
        'Location' => $job->location ?: 'Not specified',
        'Employment type' => $job->employmentTypeLabel(),
        'Start date' => $job->start_date?->format('M j, Y') ?: 'Not specified',
        'Application deadline' => $job->due_date?->format('M j, Y') ?: 'Not specified',
        'Submitted' => $job->created_at?->format('M j, Y, g:i A'),
        'Last updated' => $job->updated_at?->format('M j, Y, g:i A'),
    ] as $label => $value)
                        <div class="min-w-0">
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="mt-1 break-words font-medium text-gray-900 dark:text-gray-100">
                                {{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <h2 class="mt-6 text-base font-semibold text-gray-900 dark:text-white">Job description, responsibilities
                    and requirements</h2>
                <div data-job-description
                    class="job-description-content format format-sm format-gray mt-4 max-w-none break-words sm:format-base dark:format-invert">
                    {!! app(\App\Support\JobDescription::class)->toHtml($job->description) !!}</div>
            </section>
            <aside class="min-w-0 space-y-6">
                <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Moderation</h2>
                    @if ($job->suspension_reason)
                        <p class="mt-3 font-medium text-amber-800 dark:text-amber-300">{{ $job->isSuspended() ? 'Suspended' : 'Previous suspension' }}</p>
                        <p
                            class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-gray-700 dark:text-gray-200">
                            {{ $job->suspension_reason }}</p>
                        <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">Suspended
                            {{ $job->suspended_at?->format('M j, Y, g:i A') ?: 'Date unavailable' }} by
                            {{ $job->suspendedBy ? $job->suspendedBy->first_name . ' ' . $job->suspendedBy->last_name : 'Former administrator' }}.
                        </p>
                    @else
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Not suspended</p>
                    @endif
                    <p class="mt-4 text-sm leading-6 text-gray-500 dark:text-gray-400">Moderation controls public
                        visibility. Employers remain responsible for job content and their recruitment workflow.</p>
                </section>
                <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Job status</h2>
                    <p class="mt-3 text-sm text-gray-700 dark:text-gray-200">{{ $job->status->label() }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($job->applications_count) }} applications</p>
                </section>
                <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Employer</h2>
                    <p class="mt-3 break-words text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $job->employer?->first_name }} {{ $job->employer?->last_name }}</p>
                    <p class="mt-2 break-words text-sm text-gray-500 dark:text-gray-400">
                        {{ $job->employer?->email ?: 'Employer unavailable' }}</p>
                </section>
            </aside>
        </div>
    </main>
</x-layout>
