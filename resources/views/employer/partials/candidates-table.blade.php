@php($stages = \App\Enums\CandidatePipelineStage::cases())
<main>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        <div class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-6 dark:border-gray-800">
            <div><h1 class="text-xl font-semibold text-gray-900 dark:text-white/90">{{ $title }}</h1><p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Track candidates through one recruitment pipeline.</p></div>
            <nav class="flex flex-wrap gap-2" aria-label="Candidate pipeline stages">
                <a href="{{ route($routeName) }}" class="rounded-lg px-3 py-2 text-theme-sm font-medium {{ ! $statusFilter ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300' }}">All</a>
                @foreach ($stages as $stage)<a href="{{ route($routeName, ['stage' => $stage->value]) }}" class="rounded-lg px-3 py-2 text-theme-sm font-medium {{ $statusFilter === $stage ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300' }}">{{ $stage->label() }}</a>@endforeach
            </nav>
        </div>
        @if (session('success'))<div class="mb-6 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-theme-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400" role="status">{{ session('success') }}</div>@endif
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <form action="{{ route($routeName) }}" method="GET" class="grid gap-4 border-b border-gray-100 p-5 md:grid-cols-[1fr_auto_auto] dark:border-gray-800">
                @if ($statusFilter)<input type="hidden" name="stage" value="{{ $statusFilter->value }}">@endif
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search candidate, job, or reference" class="h-11 rounded-lg border border-gray-300 bg-white px-3 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <select name="job_id" class="h-11 rounded-lg border border-gray-300 bg-white px-3 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><option value="">All jobs</option>@foreach ($jobs as $job)<option value="{{ $job->id }}" @selected((string) request('job_id') === (string) $job->id)>{{ $job->title }}</option>@endforeach</select>
                <button class="h-11 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Filter</button>
            </form>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px]"><thead class="bg-gray-50 dark:bg-white/[0.02]"><tr class="text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400"><th class="px-5 py-3">Candidate</th><th class="px-5 py-3">Job</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Documents</th><th class="px-5 py-3">Pipeline</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($applications as $application)
                    <tr><td class="px-5 py-4"><p class="font-medium text-gray-800 dark:text-white/90">{{ $application->first_name }} {{ $application->last_name }}</p><p class="text-theme-xs text-gray-500">{{ $application->email }}</p></td><td class="px-5 py-4 text-theme-sm text-gray-700 dark:text-gray-300">{{ $application->job->title }}</td><td class="px-5 py-4 text-theme-sm text-gray-500">{{ $application->submitted_at?->format('M d, Y') }}</td><td class="px-5 py-4 text-theme-sm text-gray-500">{{ $application->documents_count }} file(s)</td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('employer.applications.show', $application) }}" class="inline-flex h-9 items-center rounded-lg border border-gray-300 px-3 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Review</a></td></tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-theme-sm text-gray-500">No candidates found.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $applications->links() }}</div>
        </section>
    </div>
</main>
