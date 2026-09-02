<x-layout>
    @php
        $statusClass = function (string $status): string {
            return match (strtolower($status)) {
                'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                'rejected' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            };
        };
        $fieldClass = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    @endphp

    <main x-data="{ previewOpen: false, previewUrl: 'about:blank', previewTitle: 'Document Preview', downloadUrl: '#' }">
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Review Application</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $application->reference }} - {{ $application->job->title }}</p>
                </div>
                <a href="{{ route('employer.Applied-Candidates') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Back to Candidates</a>
            </div>

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)" class="mb-6 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">Please review the highlighted fields and try again.</div>
            @endif

            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12 space-y-6 xl:col-span-8">
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <img src="{{ $application->applicant->profileImageUrl() }}" alt="{{ $application->first_name }} {{ $application->last_name }}" class="size-24 rounded-full object-cover">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $application->first_name }} {{ $application->last_name }}</h2>
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $application->email }} - {{ $application->phone }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($application->status->label()) }}">{{ $application->status->label() }}</span>
                                    <a href="{{ route('applicants.profile.show', $application->applicant) }}" class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">View linked profile</a>
                                </div>
                            </div>
                        </div>

                        <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Application Profile</h3>
                        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            @foreach ([
                                'Middle Name' => $application->middle_name ?: 'Not provided',
                                'Date of Birth' => $application->date_of_birth?->format('M d, Y') ?: 'Not provided',
                                'Gender' => $application->gender ? ucfirst($application->gender) : 'Not provided',
                                'Marital Status' => $application->marital_status ? ucfirst($application->marital_status) : 'Not provided',
                                'Nationality' => $application->nationality ?: 'Not provided',
                                'State of Origin' => $application->state_of_origin ?: 'Not provided',
                                'LGA' => $application->local_government_area ?: 'Not provided',
                            ] as $label => $value)
                                <div><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd></div>
                            @endforeach
                            <div class="sm:col-span-2"><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">Address</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->address ?: 'Not provided' }}</dd></div>
                        </dl>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="mb-4 px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Uploaded Documents</h3></div>
                        <div class="custom-scrollbar max-w-full overflow-x-auto">
                            <table class="w-full min-w-[900px]">
                                <thead><tr class="border-t border-gray-100 dark:border-gray-800"><th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Document</th><th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Number</th><th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th><th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">File</th><th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Review</th></tr></thead>
                                <tbody>
                                    @forelse ($application->documents as $document)
                                        <tr class="border-t border-gray-100 dark:border-gray-800">
                                            <td class="px-6 py-3.5"><p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $document->document_name }}</p><p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $document->original_name }}</p></td>
                                            <td class="px-6 py-3.5 text-theme-sm text-gray-500 dark:text-gray-400">{{ $document->maskedDocumentNumber() ?: 'N/A' }}</td>
                                            <td class="px-6 py-3.5"><span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($document->status->label()) }}">{{ $document->status->label() }}</span></td>
                                            <td class="px-6 py-3.5"><div class="flex flex-wrap gap-2">@if ($document->canPreviewInline())<button type="button" @click="previewOpen = true; previewUrl = @js($document->previewUrl()); previewTitle = @js($document->document_name.' - '.$document->original_name); downloadUrl = @js($document->downloadUrl())" class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Preview</button>@endif<a href="{{ $document->downloadUrl() }}" class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Download</a></div></td>
                                            <td class="px-6 py-3.5">
                                                <form action="{{ route('employer.application-documents.review', $document) }}" method="POST" class="flex min-w-64 flex-col gap-2" data-confirm data-confirm-title="Update document review?" data-confirm-text="This document status will be saved." data-confirm-button="Update Document">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status" class="{{ $fieldClass }}">
                                                        @foreach (\App\Enums\ApplicationStatus::cases() as $status)
                                                            <option value="{{ $status->value }}" @selected($document->status === $status)>{{ $status->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <textarea name="remarks" rows="2" maxlength="2000" placeholder="Remarks" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('remarks', $document->employer_remarks) }}</textarea>
                                                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-500 px-3 text-theme-xs font-medium text-white shadow-theme-xs hover:bg-brand-600">Update Document</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="border-t border-gray-100 dark:border-gray-800"><td colspan="5" class="px-6 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No documents uploaded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <aside class="col-span-12 space-y-6 xl:col-span-4">
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Application Decision</h3>
                        <form action="{{ route('employer.applications.review', $application) }}" method="POST" class="space-y-4" data-confirm data-confirm-title="Save application decision?" data-confirm-text="The applicant's application status will be updated." data-confirm-button="Save Decision">
                            @csrf
                            @method('PATCH')
                            <div><label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status</label><select name="status" class="{{ $fieldClass }}">@foreach (\App\Enums\ApplicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected($application->status === $status)>{{ $status->label() }}</option>@endforeach</select></div>
                            <div><label class="mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400">Remarks</label><textarea name="remarks" rows="4" maxlength="2000" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('remarks', $application->employer_remarks) }}</textarea></div>
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Save Decision</button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Audit Trail</h3>
                        <div class="space-y-3">
                            @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                                <div class="rounded-lg border border-gray-100 p-4 dark:border-gray-800"><div class="flex justify-between gap-3"><strong class="text-theme-sm text-gray-800 dark:text-white/90">{{ $history->to_status->label() }}</strong><span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $history->created_at->diffForHumans() }}</span></div><p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">From {{ $history->from_status?->label() ?: 'New' }} by {{ $history->changedBy?->first_name ?: 'System' }}</p></div>
                            @empty
                                <div class="rounded-lg bg-gray-50 p-4 text-theme-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No status history yet.</div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <div x-show="previewOpen" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-4 sm:p-5">
            <div class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
            <div @click.outside="previewOpen = false; previewUrl = 'about:blank'" class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="previewTitle"></h3><button type="button" @click="previewOpen = false; previewUrl = 'about:blank'" class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-white/[0.05] dark:text-gray-400">x</button></div>
                <iframe title="Document preview" :src="previewUrl" class="h-[70vh] w-full border-0"></iframe>
                <div class="flex justify-end gap-3 border-t border-gray-100 p-4 dark:border-gray-800"><a :href="downloadUrl" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Download</a><button type="button" @click="previewOpen = false; previewUrl = 'about:blank'" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Done</button></div>
            </div>
        </div>
    </main>
</x-layout>
