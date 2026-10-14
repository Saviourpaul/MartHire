<x-layout>
    @php
        $statusClass = $job->status->badgeClass();
    @endphp

    <div x-data="{ isEditJobModal: false, isDeleteJobModal: false }">
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            @if (session('success'))
                <p role="status" class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</p>
            @endif
            @if ($errors->any())
                <p role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{{ $errors->first() }}</p>
            @endif
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <a href="{{ route('jobs') }}" class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">Back to Jobs</a>
                    <h1 class="mt-2 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Submitted {{ $job->created_at?->format('M j, Y, g:i A') }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @can('update', $job)
                        <button type="button" @click="isEditJobModal = true" class="h-11 rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Edit</button>
                    @endcan
                    @if (! $job->isClosed() && auth()->user()->can('close', $job))
                        <div x-data="{ submitting: false }">
                            <button type="button" @click="$refs.closeDialog.showModal()" class="min-h-[44px] rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200">Close job</button>
                            <dialog x-ref="closeDialog" aria-labelledby="close-job-heading" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-xl bg-white p-5 text-gray-900 backdrop:bg-black/50 dark:bg-gray-900 dark:text-white">
                                <form method="POST" action="{{ route('jobs.close', $job) }}" @submit="submitting = true">
                                    @csrf
                                    @method('PATCH')
                                    <h2 id="close-job-heading" class="text-lg font-semibold">Close this job?</h2>
                                    <p class="mt-3 text-sm leading-6">The job will leave public listings and stop accepting applications. Existing applications remain available. Editing a closed job will keep it closed.</p>
                                    <input type="hidden" name="confirm_closure" value="1">
                                    <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                        <button type="button" @click="$refs.closeDialog.close()" class="min-h-[44px] rounded-lg border border-gray-300 px-4 dark:border-gray-600">Cancel</button>
                                        <button type="submit" :disabled="submitting" class="min-h-[44px] rounded-lg bg-blue-600 px-4 text-white disabled:opacity-50">Confirm closure</button>
                                    </div>
                                </form>
                            </dialog>
                        </div>
                    @endif
                    @can('delete', $job)
                        <button type="button" @click="isDeleteJobModal = true" class="h-11 rounded-lg bg-error-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs " style="background-color: rgb(240, 68, 56)">Delete</button>
                    @endcan
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <section class="col-span-12 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-8">
                    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" class="h-16 w-16 rounded-xl border border-gray-100 object-contain dark:border-gray-800">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</h2>
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $job->company }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass }}">{{ $job->status->label() }}</span>
                    </div>

                    @if ($job->isSuspended())
                        <div role="status" class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                            <p class="font-semibold">Suspended by platform moderation</p>
                            <p class="mt-2 whitespace-pre-line break-words">{{ $job->suspension_reason }}</p>
                            <p class="mt-2">You can edit this job to address the policy issue. Saving a valid update reopens it for applications. Existing applications remain available.</p>
                        </div>
                    @elseif ($job->isClosed())
                        <p role="status" class="mb-5 rounded-lg bg-gray-100 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">This job is closed and no longer accepting applications.</p>
                    @endif

                    <div class="grid grid-cols-1 gap-4 border-y border-gray-100 py-5 sm:grid-cols-2 lg:grid-cols-5 dark:border-gray-800">
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Category</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->category ?: 'Uncategorized' }}</p>
                        </div>
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Location</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->location ?: 'Not specified' }}</p>
                        </div>
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Employment Type</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->employmentTypeLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Start Date</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->start_date?->format('M j, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Due Date</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->due_date?->format('M j, Y') }}</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Description</h3>
                        <div class="job-description-content format format-sm format-gray mt-3 max-w-none break-words sm:format-base dark:format-invert" data-job-description>{!! app(\App\Support\JobDescription::class)->toHtml($job->description) !!}</div>
                    </div>
                </section>

                <aside class="col-span-12 space-y-4 xl:col-span-4">
                    @include('employer.jobs._team')
                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Job Summary</h3>
                        <dl class="mt-4 space-y-3">
                            <div class="flex justify-between gap-4">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">Applications</dt>
                                <dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ number_format($job->applications_count) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">Last Updated</dt>
                                <dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->updated_at?->format('M j, Y') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">Reference</dt>
                                <dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">#{{ $job->id }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Job Status</h3>
                        <p class="mt-3 text-sm text-gray-700 dark:text-gray-200">{{ $job->status->label() }}</p>
                        @if ($job->suspension_reason && ! $job->isSuspended())
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Previous suspension: {{ $job->suspension_reason }}</p>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @can('update', $job)
    <div x-show="isEditJobModal" x-cloak @keydown.escape.window="isEditJobModal = false" role="dialog" aria-modal="true" aria-labelledby="edit-job-heading" class="fixed inset-0 z-[99999] flex items-center justify-center overflow-hidden p-2 sm:p-4">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isEditJobModal = false" class="job-form-dialog relative w-full min-w-0 max-w-[900px] overflow-y-auto overscroll-contain rounded-2xl bg-white p-4 sm:p-6 lg:p-8 dark:bg-gray-900">
            <button type="button" @click="isEditJobModal = false" aria-label="Close Edit Job" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
            </button>
        <form action="{{ route('jobs.update', $job) }}" method="POST" enctype="multipart/form-data" data-ajax-job-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ route('jobs.show', $job) }}">
            <div class="mb-5 pr-12">
                <h2 id="edit-job-heading" class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit Job</h2>
            </div>
            <div class="mb-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
            @include('employer.jobs._form', ['job' => $job, 'prefix' => 'show_edit_job_'.$job->id])
            <div class="sticky bottom-0 mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 bg-white py-4 sm:flex-row sm:justify-end dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="isEditJobModal = false" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                <button type="submit" class="btn-primary flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white sm:w-auto">{{ $job->isSuspended() ? 'Save corrections and reopen' : 'Save Changes' }}</button>
            </div>
        </form>
        </div>
    </div>

    @endcan
    @can('delete', $job)
    <div x-show="isDeleteJobModal" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isDeleteJobModal = false" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
            <button type="button" @click="isDeleteJobModal = false" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
            </button>
        <form action="{{ route('jobs.destroy', $job) }}" method="POST" data-ajax-job-form>
            @csrf
            @method('DELETE')
            <h2 class="pr-12 text-lg font-semibold text-gray-800 dark:text-white/90">Delete Job</h2>
            <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">This will permanently remove {{ $job->title }}.</p>
            <div class="mt-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
            <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                <button type="button" @click="isDeleteJobModal = false" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                <button type="submit" class="flex w-full justify-center rounded-lg  px-4 py-2.5 text-sm font-medium text-white  sm:w-auto" style="background-color: rgb(240, 68, 56)">Delete</button>
            </div>
        </form>
        </div>
    </div>

    @endcan
    <script>
        document.querySelectorAll('[data-ajax-job-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const submitButton = form.querySelector('[type="submit"]');
                const formError = form.querySelector('[data-form-error]');
                const token = form.querySelector('input[name="_token"]')?.value;
                const description = form.querySelector('textarea[name="description"]');

                description?.setAttribute('aria-invalid', 'false');

                form.querySelectorAll('[data-field-error]').forEach((element) => {
                    element.textContent = '';
                    element.classList.add('hidden');
                });

                if (formError) {
                    formError.textContent = '';
                    formError.classList.add('hidden');
                }

                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = 'Saving...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token || '',
                        },
                        body: new FormData(form),
                        credentials: 'same-origin',
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        await window.MarthireRecovery?.handle(response, payload);
                        Object.entries(payload.errors || {}).forEach(([field, messages]) => {
                            const target = form.querySelector(`[data-field-error="${field}"]`);
                            if (target) {
                                target.textContent = messages[0] || 'Invalid value.';
                                target.classList.remove('hidden');
                            }
                            if (field === 'description') {
                                description?.setAttribute('aria-invalid', 'true');
                            }
                        });

                        if (formError) {
                            formError.textContent = payload.message || 'Please check the highlighted fields.';
                            formError.classList.remove('hidden');
                        }
                        return;
                    }

                    window.location.href = payload.redirect || window.location.href;
                } catch (error) {
                    if (formError) {
                        formError.textContent = 'Unable to submit the form. Please try again.';
                        formError.classList.remove('hidden');
                    }
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = submitButton.dataset.originalText || 'Submit';
                }
            });
        });
    </script>
    </div>
</x-layout>

