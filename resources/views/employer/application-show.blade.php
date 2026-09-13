<x-layout>
    <div x-data="{ confirmReview: false, confirmed: false }" class="mx-auto max-w-(--breakpoint-2xl) space-y-6 p-4 pb-20 md:p-6 md:pb-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-6 sm:flex-row sm:items-start sm:justify-between dark:border-gray-800">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-theme-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Application {{ $application->reference }}</p>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                </div>
                <h1 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white/90">Review application</h1>
                <p class="mt-1 text-theme-sm text-gray-600 dark:text-gray-300">{{ $application->job->title }} at {{ $application->job->company }}</p>
            </div>
            <a href="{{ route('employer.Applied-Candidates') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 focus:outline-none focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">Back to candidates</a>
        </header>

        @if (session('success'))
            <div class="flex gap-3 rounded-lg border border-success-200 bg-success-50 p-4 text-theme-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400" role="status">
                <span class="font-semibold">Success</span>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-error-200 bg-error-50 p-4 text-theme-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400" role="alert">
                <p class="font-semibold">The document status could not be updated.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" aria-labelledby="applicant-heading">
            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between lg:p-6">
                <div class="flex min-w-0 items-center gap-4">
                    <img src="{{ $application->profileImageUrl() }}" alt="{{ $application->first_name }} {{ $application->last_name }} profile photo" class="size-16 shrink-0 rounded-full border-2 border-white object-cover shadow-theme-sm ring-1 ring-gray-200 dark:border-gray-900 dark:ring-gray-700" onerror="this.onerror=null;this.src='{{ asset('admin/assets/images/Avatar.png') }}';">
                    <div class="min-w-0">
                        <p class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Applicant</p>
                        <h2 id="applicant-heading" class="mt-1 truncate text-lg font-semibold text-gray-900 dark:text-white/90">{{ trim(collect([$application->first_name, $application->middle_name, $application->last_name])->filter()->implode(' ')) }}</h2>
                        <p class="mt-1 truncate text-theme-sm text-gray-600 dark:text-gray-300">{{ $application->email }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-theme-sm sm:shrink-0">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Submitted</dt>
                        <dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $application->submitted_at->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Documents</dt>
                        <dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $application->documents->count() }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <main class="space-y-6 xl:col-span-8">
                <section class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" aria-labelledby="documents-heading">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800">
                        <div>
                            <h2 id="documents-heading" class="text-lg font-semibold text-gray-900 dark:text-white/90">Submitted documents</h2>
                            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Review each file, then apply one status to the entire submission.</p>
                        </div>
                        <span class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $application->documents->count() }} file(s)</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px]">
                            <thead class="bg-gray-50/70 dark:bg-white/[0.02]">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-theme-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Document</th>
                                    <th scope="col" class="px-5 py-3 text-left text-theme-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                                    <th scope="col" class="px-5 py-3 text-left text-theme-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Last reviewed</th>
                                    <th scope="col" class="px-5 py-3 text-right text-theme-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($application->documents as $document)
                                    <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                        <td class="px-5 py-4">
                                            <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $document->document_name }}</p>
                                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $document->original_name }}</p>
                                        </td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $document->status->badgeClass() }}">{{ $document->status->label() }}</span></td>
                                        <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-300">{{ $document->reviewed_at?->format('M d, Y') ?: 'Not reviewed' }}</td>
                                        <td class="px-5 py-4 text-right"><a href="{{ $document->canPreviewInline() ? $document->previewUrl() : $document->downloadUrl() }}" target="_blank" rel="noopener" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">View file</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No documents were submitted with this application.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]" aria-labelledby="application-details-heading">
                    <h2 id="application-details-heading" class="text-lg font-semibold text-gray-900 dark:text-white/90">Application details</h2>
                    <dl class="mt-5 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Phone</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->phone ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nationality</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->nationality ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Location</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ collect([$application->local_government_area, $application->state_of_origin])->filter()->implode(', ') ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date of birth</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->date_of_birth?->format('M d, Y') ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Gender</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->gender ? ucfirst($application->gender) : 'Not provided' }}</dd></div>
                        <div><dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Marital status</dt><dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $application->marital_status ? ucfirst($application->marital_status) : 'Not provided' }}</dd></div>
                    </dl>
                </section>
            </main>

            <aside class="space-y-6 xl:col-span-4">
                <section class="rounded-xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]" aria-labelledby="review-documents-heading">
                    <h2 id="review-documents-heading" class="text-lg font-semibold text-gray-900 dark:text-white/90">Update document status</h2>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">This applies the same decision and note to every submitted document.</p>
                    <form id="bulk-document-status-form" action="{{ route('employer.applications.documents.review', $application) }}" method="POST" class="mt-5 space-y-4" @submit.prevent="if (confirmed) { $el.submit() } else { confirmReview = true }">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="document-status" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Document status</label>
                            <select id="document-status" name="status" required class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-theme-sm text-gray-800 shadow-theme-xs transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Select a status</option>
                                @foreach (\App\Enums\ApplicationStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('status') === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="document-remarks" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Shared note <span class="font-normal text-gray-500 dark:text-gray-400">Optional</span></label>
                            <textarea id="document-remarks" name="remarks" rows="4" maxlength="2000" class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs transition placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Add context for the applicant">{{ old('remarks') }}</textarea>
                        </div>
                        <button type="submit" @disabled($application->documents->isEmpty()) class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-50">Update Document Status</button>
                    </form>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]" aria-labelledby="application-status-heading">
                    <h2 id="application-status-heading" class="text-lg font-semibold text-gray-900 dark:text-white/90">Application status</h2>
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/[0.04]">
                        <span class="text-theme-sm text-gray-600 dark:text-gray-300">Current decision</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                    </div>
                    @if ($application->reviewed_at)
                        <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">Last updated {{ $application->reviewed_at->format('M d, Y, g:i A') }}</p>
                    @endif
                </section>
            </aside>
        </div>

        <div x-cloak x-show="confirmReview" x-transition class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 p-4" @keydown.escape.window="confirmReview = false; confirmed = false">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900" @click.outside="confirmReview = false; confirmed = false" role="dialog" aria-modal="true" aria-labelledby="document-review-title">
                <h2 id="document-review-title" class="text-lg font-semibold text-gray-900 dark:text-white/90">Update all document statuses?</h2>
                <p class="mt-2 text-theme-sm leading-6 text-gray-500 dark:text-gray-400">The selected status and note will be applied to all {{ $application->documents->count() }} submitted document(s). This action is recorded in each document's review history.</p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="confirmReview = false; confirmed = false" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Cancel</button>
                    <button type="submit" form="bulk-document-status-form" @click="confirmed = true" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/30">Confirm update</button>
                </div>
            </div>
        </div>
    </div>
</x-layout>
