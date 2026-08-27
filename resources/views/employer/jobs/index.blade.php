<x-layout>
    @php
        $sortIcon = fn ($column) => $sortColumn === $column
            ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;')
            : '&harr;';
        $sortUrl = fn ($column) => request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => ($sortColumn === $column && $sortDirection === 'asc') ? 'desc' : 'asc',
        ]);
        $statusClass = fn ($status) => match ($status->value) {
            'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            'rejected' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
            default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
        };
    @endphp

    <div x-data="{ isCreateJobModal: false, isEditJobModal: null, isDeleteJobModal: null }">
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Jobs</h1>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Manage your job posts and review status.</p>
                </div>
                <button type="button" @click="isCreateJobModal = true" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                    Create Job
                </button>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-theme-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                    {{ session('success') }}
                </div>
            @endif

            <div id="jobsAjaxMessage" class="mb-6 hidden rounded-lg border px-4 py-3 text-theme-sm font-medium"></div>

            <section data-table-refresh-region data-table-refresh-id="employer-jobs" class="overflow-hidden rounded-xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4 flex flex-col gap-3 px-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" data-table-refresh-button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <svg data-table-refresh-icon class="fill-current" width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.75 6.667a.75.75 0 0 1-.75.75h-3.333a.75.75 0 0 1 0-1.5h1.516A5.25 5.25 0 0 0 5.02 7.553a.75.75 0 1 1-1.372-.606 6.75 6.75 0 0 1 11.602-1.9V3.75a.75.75 0 0 1 1.5 0v2.917ZM3.25 13.333a.75.75 0 0 1 .75-.75h3.333a.75.75 0 0 1 0 1.5H5.817a5.25 5.25 0 0 0 9.163-1.636.75.75 0 1 1 1.372.606 6.75 6.75 0 0 1-11.602 1.9v1.297a.75.75 0 0 1-1.5 0v-2.917Z"/></svg>
                        <span data-table-refresh-label>Reload</span>
                    </button>
                    <form method="GET" action="{{ route('jobs') }}" class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <div class="relative w-full sm:max-w-sm">
                            <button type="submit" class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z"/></svg>
                            </button>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search jobs..." class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Search</button>
                        @if (request()->filled('search'))
                            <a href="{{ route('jobs') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Clear</a>
                        @endif
                    </form>
                </div>
                <div data-table-refresh-message class="mx-4 mb-4 hidden rounded-lg border px-4 py-3 text-theme-sm font-medium"></div>

                <div class="max-w-full overflow-x-auto">
                    <table class="w-full min-w-[980px]">
                        <thead>
                            <tr class="border-t border-gray-200 dark:border-gray-800">
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Logo</th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400"><a href="{{ $sortUrl('title') }}">Title {!! $sortIcon('title') !!}</a></th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400"><a href="{{ $sortUrl('category') }}">Category {!! $sortIcon('category') !!}</a></th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400"><a href="{{ $sortUrl('start_date') }}">Start {!! $sortIcon('start_date') !!}</a></th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400"><a href="{{ $sortUrl('due_date') }}">Due {!! $sortIcon('due_date') !!}</a></th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Applications</th>
                                <th class="px-6 py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs as $job)
                                <tr class="border-t border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900">
                                    <td class="px-6 py-4"><img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" class="h-10 w-10 rounded-lg object-contain"></td>
                                    <td class="px-6 py-4">
                                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $job->title }}</p>
                                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $job->company }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-theme-sm text-gray-700 dark:text-gray-400">{{ $job->category ?: 'Uncategorized' }}</td>
                                    <td class="px-6 py-4 text-theme-sm text-gray-700 dark:text-gray-400">{{ $job->start_date?->format('M j, Y') }}</td>
                                    <td class="px-6 py-4 text-theme-sm text-gray-700 dark:text-gray-400">{{ $job->due_date?->format('M j, Y') }}</td>
                                    <td class="px-6 py-4 text-theme-sm text-gray-700 dark:text-gray-400">{{ number_format($job->applications_count) }}</td>
                                    <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($job->status) }}">{{ $job->status->label() }}</span></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('jobs.show', $job) }}" class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400" aria-label="View job">
                                                <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 4.25C6.55 4.25 3.3 6.65 2 10.5c1.3 3.85 4.55 6.25 8.5 6.25s7.2-2.4 8.5-6.25c-1.3-3.85-4.55-6.25-8.5-6.25Zm0 10.5a4.25 4.25 0 1 1 0-8.5 4.25 4.25 0 0 1 0 8.5Zm0-1.5a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Z"/></svg>
                                            </a>
                                            <button type="button" @click="isEditJobModal = {{ $job->id }}" class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400" aria-label="Edit job">
                                                <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0911 3.53206C16.2124 2.65338 14.7878 2.65338 13.9091 3.53206L5.6074 11.8337C5.29899 12.1421 5.08687 12.5335 4.99684 12.9603L4.26177 16.445C4.20943 16.6931 4.286 16.9508 4.46529 17.1301C4.64458 17.3094 4.90232 17.3859 5.15042 17.3336L8.63507 16.5985C9.06184 16.5085 9.45324 16.2964 9.76165 15.988L18.0633 7.68631C18.942 6.80763 18.942 5.38301 18.0633 4.50433L17.0911 3.53206Z"/></svg>
                                            </button>
                                            <button type="button" @click="isDeleteJobModal = {{ $job->id }}" class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-500" aria-label="Delete job">
                                                <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.04142 4.29199C7.04142 3.04935 8.04878 2.04199 9.29142 2.04199H11.7081C12.9507 2.04199 13.9581 3.04935 13.9581 4.29199V4.54199H17.166C17.5802 4.54199 17.916 4.87778 17.916 5.29199C17.916 5.70621 17.5802 6.04199 17.166 6.04199H16.8752V16.7087C16.8752 17.9513 15.8678 18.9587 14.6252 18.9587H6.37516C5.13252 18.9587 4.12516 17.9513 4.12516 16.7087V6.04199H3.8335C3.41928 6.04199 3.0835 5.70621 3.0835 5.29199C3.0835 4.87778 3.41928 4.54199 3.8335 4.54199H7.04142ZM8.54142 4.54199H12.4581V4.29199C12.4581 3.87778 12.1223 3.54199 11.7081 3.54199H9.29142C8.87721 3.54199 8.54142 3.87778 8.54142 4.29199V4.54199Z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td colspan="8" class="px-6 py-12 text-center text-theme-sm text-gray-500 dark:text-gray-400">No jobs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($jobs->hasPages())
                    <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800">
                        {{ $jobs->links() }}
                    </div>
                @endif
            </section>
        </div>
    </main>

    <div x-show="isCreateJobModal" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isCreateJobModal = false" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
            <button type="button" @click="isCreateJobModal = false" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
            </button>
            <form action="{{ route('jobs.store') }}" method="POST" enctype="multipart/form-data" data-ajax-job-form>
                @csrf
                <div class="mb-5 pr-12">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Create Job</h2>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">New jobs are submitted for admin review.</p>
                </div>
                <div class="mb-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
                @include('employer.jobs._form', ['job' => null, 'prefix' => 'create_job'])
                <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                    <button type="button" @click="isCreateJobModal = false" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white sm:w-auto">Create Job</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($jobs as $job)
        <div x-show="isEditJobModal === {{ $job->id }}" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
            <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
            <div @click.outside="isEditJobModal = null" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
                <button type="button" @click="isEditJobModal = null" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
                </button>
            <form action="{{ route('jobs.update', $job) }}" method="POST" enctype="multipart/form-data" data-ajax-job-form>
                @csrf
                @method('PUT')
                <div class="mb-5 pr-12">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit Job</h2>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Changes are resubmitted for admin review.</p>
                </div>
                <div class="mb-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
                @include('employer.jobs._form', ['job' => $job, 'prefix' => 'edit_job_'.$job->id])
                <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                    <button type="button" @click="isEditJobModal = null" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white sm:w-auto">Save Changes</button>
                </div>
            </form>
            </div>
        </div>

        <div x-show="isDeleteJobModal === {{ $job->id }}" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
            <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
            <div @click.outside="isDeleteJobModal = null" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
                <button type="button" @click="isDeleteJobModal = null" class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"/></svg>
                </button>
            <form action="{{ route('jobs.destroy', $job) }}" method="POST" data-ajax-job-form>
                @csrf
                @method('DELETE')
                <h2 class="pr-12 text-lg font-semibold text-gray-800 dark:text-white/90">Delete Job</h2>
                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">This will permanently remove {{ $job->title }}.</p>
                <div class="mt-4 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-theme-sm text-error-700" data-form-error></div>
                <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                    <button type="button" @click="isDeleteJobModal = null" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Cancel</button>
                    <button type="submit" class="flex w-full justify-center rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-600 sm:w-auto">Delete</button>
                </div>
            </form>
            </div>
        </div>
    @endforeach

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
