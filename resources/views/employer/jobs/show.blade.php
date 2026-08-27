<x-layout>
    @php
        $statusClass = match ($job->status->value) {
            'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            'rejected' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
            default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
        };
    @endphp

    <div x-data="{ isEditJobModal: false, isDeleteJobModal: false }">
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <a href="{{ route('jobs') }}" class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">Back to Jobs</a>
                    <h1 class="mt-2 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Submitted {{ $job->created_at?->format('M j, Y, g:i A') }}</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="isEditJobModal = true" class="h-11 rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Edit</button>
                    <button type="button" @click="isDeleteJobModal = true" class="h-11 rounded-lg bg-error-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-error-600">Delete</button>
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

                    <div class="grid grid-cols-1 gap-4 border-y border-gray-100 py-5 sm:grid-cols-3 dark:border-gray-800">
                        <div>
                            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Category</p>
                            <p class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->category ?: 'Uncategorized' }}</p>
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
                        <div class="mt-3 whitespace-pre-line text-theme-sm leading-6 text-gray-600 dark:text-gray-400">{{ strip_tags($job->description) }}</div>
                    </div>
                </section>

                <aside class="col-span-12 space-y-4 xl:col-span-4">
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
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Review Status</h3>
                        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">Edits move this job back to pending review.</p>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <div x-show="isEditJobModal" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isEditJobModal = false" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
            <button type="button" @click="isEditJobModal = false" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
            </button>
        <form action="{{ route('jobs.update', $job) }}" method="POST" enctype="multipart/form-data" data-ajax-job-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ route('jobs.show', $job) }}">
            <div class="mb-5 pr-12">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit Job</h2>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Changes are resubmitted for admin review.</p>
            </div>
            <div class="mb-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
            @include('employer.jobs._form', ['job' => $job, 'prefix' => 'show_edit_job_'.$job->id])
            <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                <button type="button" @click="isEditJobModal = false" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white sm:w-auto">Save Changes</button>
            </div>
        </form>
        </div>
    </div>

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
                <button type="submit" class="flex w-full justify-center rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-600 sm:w-auto">Delete</button>
            </div>
        </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-ajax-job-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const submitButton = form.querySelector('[type="submit"]');
                const formError = form.querySelector('[data-form-error]');
                const token = form.querySelector('input[name="_token"]')?.value;

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
                        Object.entries(payload.errors || {}).forEach(([field, messages]) => {
                            const target = form.querySelector(`[data-field-error="${field}"]`);
                            if (target) {
                                target.textContent = messages[0] || 'Invalid value.';
                                target.classList.remove('hidden');
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
