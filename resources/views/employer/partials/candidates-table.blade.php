@php($stages = \App\Enums\CandidatePipelineStage::cases())
<div class="px-5 py-4 sm:px-6 sm:py-5">
    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
        Candidate Applications
    </h3>
    
</div>
<div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
            class="mb-6 flex items-center gap-3 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            <div class="-mt-0.5 text-success-500 shrink-0">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.70186 12.0001C3.70186 7.41711 7.41711 3.70186 12.0001 3.70186C16.5831 3.70186 20.2984 7.41711 20.2984 12.0001C20.2984 16.5831 16.5831 20.2984 12.0001 20.2984C7.41711 20.2984 3.70186 16.5831 3.70186 12.0001ZM12.0001 1.90186C6.423 1.90186 1.90186 6.423 1.90186 12.0001C1.90186 17.5772 6.423 22.0984 12.0001 22.0984C17.5772 22.0984 22.0984 17.5772 22.0984 12.0001C22.0984 6.423 17.5772 1.90186 12.0001 1.90186ZM15.6197 10.7395C15.9712 10.388 15.9712 9.81819 15.6197 9.46672C15.2683 9.11525 14.6984 9.11525 14.347 9.46672L11.1894 12.6243L9.6533 11.0883C9.30183 10.7368 8.73198 10.7368 8.38051 11.0883C8.02904 11.4397 8.02904 12.0096 8.38051 12.3611L10.553 14.5335C10.7217 14.7023 10.9507 14.7971 11.1894 14.7971C11.428 14.7971 11.657 14.7023 11.8257 14.5335L15.6197 10.7395Z"
                        fill=""></path>
                </svg>
            </div>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
            class="mb-6 flex items-center gap-3 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <div class="-mt-0.5 text-error-500 shrink-0">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M20.3499 12.0004C20.3499 16.612 16.6115 20.3504 11.9999 20.3504C7.38832 20.3504 3.6499 12.0004C3.6499 7.38881 7.38833 3.65039 11.9999 3.65039C16.6115 3.65039 20.3499 7.38881 20.3499 12.0004ZM11.9999 22.1504C17.6056 22.1504 22.1499 17.6061 22.1499 12.0004C22.1499 6.3947 17.6056 1.85039 11.9999 1.85039C6.39421 1.85039 1.8499 6.3947 1.8499 12.0004C1.8499 17.6061 6.39421 22.1504 11.9999 22.1504ZM13.0008 16.4753C13.0008 15.923 12.5531 15.4753 12.0008 15.4753L11.9998 15.4753C11.4475 15.4753 10.9998 15.923 10.9998 16.4753C10.9998 17.0276 11.4475 17.4753 11.9998 17.4753L12.0008 17.4753C12.5531 17.4753 13.0008 17.0276 13.0008 16.4753ZM11.9998 6.62898C12.414 6.62898 12.7498 6.96476 12.7498 7.37898L12.7498 13.0555C12.7498 13.4697 12.414 13.8055 11.2498 13.8055C11.5856 13.8055 11.2498 13.4697 11.2498 13.0555L11.2498 7.37898C11.2498 6.96476 11.5856 6.62898 11.9998 6.62898Z"
                        fill="#F04438"></path>
                </svg>
            </div>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    <!-- DataTable -->
    <div data-table-refresh-region data-table-refresh-id="admin-applicants"
        class="overflow-hidden rounded-xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex flex-col gap-2 px-4 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" data-table-refresh-button
                    class="shadow-theme-xs inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <svg data-table-refresh-icon class="fill-current" width="18" height="18" viewBox="0 0 20 20"
                        aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M16.75 6.667a.75.75 0 0 1-.75.75h-3.333a.75.75 0 0 1 0-1.5h1.516A5.25 5.25 0 0 0 5.02 7.553a.75.75 0 1 1-1.372-.606 6.75 6.75 0 0 1 11.602-1.9V3.75a.75.75 0 0 1 1.5 0v2.917ZM3.25 13.333a.75.75 0 0 1 .75-.75h3.333a.75.75 0 0 1 0 1.5H5.817a5.25 5.25 0 0 0 9.163-1.636.75.75 0 1 1 1.372.606 6.75 6.75 0 0 1-11.602 1.9v1.297a.75.75 0 0 1-1.5 0v-2.917Z" />
                    </svg>
                    <span data-table-refresh-label>Reload</span>
                </button>
                <form action="{{ route($routeName) }}" method="GET"
                    class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <button type="submit"
                            class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C11.2676 15.7053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053Z"
                                    fill="" />
                            </svg>
                        </button>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search... candidate, job, or reference"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>



                    <select name="status" data-table-auto-submit
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">All jobs</option>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}" @selected((string) request('job_id') === (string) $job->id)>{{ $job->title }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="shadow-theme-xs flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        Filter
                    </button>
                    <a href="{{ url()->current() }}" data-table-reset-link
                        class="shadow-theme-xs flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        Reset
                    </a>
                </form>
            </div>
        </div>
        <div data-table-refresh-message class="mx-4 mb-4 hidden rounded-lg border px-4 py-3 text-theme-sm font-medium">
        </div>

        <div class="max-w-full overflow-x-auto">
            <div class="min-w-[1102px]">

                <!-- table header start -->
                <div class="grid grid-cols-12 border-t border-gray-200 dark:border-gray-800">
                    <div class="col-span-3 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Candidate</p>
                    </div>

                    <div class="col-span-2 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">email</p>
                    </div>
                    <div class="col-span-2 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Job title</p>
                    </div>

                    <div class="col-span-2 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Submitted</p>
                    </div>
                    <div class="col-span-1 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Documents</p>
                    </div>
                    <div class="col-span-1 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Pipeline</p>
                    </div>


                    <div class="col-span-1 flex items-center px-4 py-3">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Action</p>
                    </div>
                </div>
                <!-- table header end -->

                <!-- table body start -->
                @forelse ($applications as $application)
                    <div>
                        <div
                            class="grid grid-cols-12 border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">

                            <div
                                class="col-span-3 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                               
                                <div class="flex gap-3">

                                    <div class="h-10 w-10 overflow-hidden rounded-full">
                                        <img src="{{ asset('admin/assets/images/Avatar.png') }}"
                                            alt="{{ $application->first_name }}" class="h-full w-full object-cover" />
                                    </div>
                                    <div>
                                        <p class="text-theme-sm block font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->first_name }} {{ $application->last_name }}
                                        </p>

                                    </div>
                                </div>
                            </div>
                            <div
                                class="col-span-2 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400 truncate" title="email">
                                    {{ $application->email }}</p>
                            </div>
                            <div
                                class="col-span-2 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-xs rounded-full px-2 py-0.5 font-medium " title="job-title">
                                    {{ $application->job->title }}
                                </p>
                            </div>
                            <div
                                class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-xs rounded-full px-2 py-0.5 font-medium ">
                                    {{ $application->created_at->diffForHumans() }}</p>
                            </div>

                            <div
                                class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">

                            </div>
                            <div
                                class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400">
                                    {{ $application->documents_count }} file(s)
                                </p>
                            </div>
                            <div
                                class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p
                                    class=" inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">
                                    {{ $application->status->label() }}
                                </p>
                            </div>

                            <div class="col-span-1 flex items-center gap-2 px-4 py-3">
                                <a href="{{ route('employer.applications.show', $application) }}"
                                    class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                                   <svg class="fill-current" width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.8749 13.8619C8.10837 13.8619 5.74279 12.1372 4.79804 9.70241C5.74279 7.26761 8.10837 5.54297 10.8749 5.54297C13.6415 5.54297 16.0071 7.26762 16.9518 9.70243C16.0071 12.1372 13.6415 13.8619 10.8749 13.8619ZM10.8749 4.04297C7.35666 4.04297 4.36964 6.30917 3.29025 9.4593C3.23626 9.61687 3.23626 9.78794 3.29025 9.94552C4.36964 13.0957 7.35666 15.3619 10.8749 15.3619C14.3932 15.3619 17.3802 13.0957 18.4596 9.94555C18.5136 9.78797 18.5136 9.6169 18.4596 9.45932C17.3802 6.30919 14.3932 4.04297 10.8749 4.04297ZM10.8663 7.84413C9.84002 7.84413 9.00808 8.67606 9.00808 9.70231C9.00808 10.7286 9.84002 11.5605 10.8663 11.5605H10.8811C11.9074 11.5605 12.7393 10.7286 12.7393 9.70231C12.7393 8.67606 11.9074 7.84413 10.8811 7.84413H10.8663Z" fill=""></path>
                                </svg>
                                </a>

                                
                            </div>
                        </div>

                    </div>
                @empty
                    <div
                        class="border-t border-gray-100 px-5 py-12 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        No Candidate Found
                    </div>
                @endforelse
                <!-- table body end -->
            </div>
        </div>

        <!-- Pagination Controls -->
        <div class="border-t border-gray-100 py-4 pr-4 pl-[18px] dark:border-gray-800">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
                <p
                    class="border-b border-gray-100 pb-3 text-center text-sm font-medium text-gray-500 xl:border-b-0 xl:pb-0 xl:text-left dark:border-gray-800 dark:text-gray-400">
                    Showing {{ $applications->firstItem() ?? 0 }} of
                    {{ $applications->total() }} entries
                </p>
                <div class="flex items-center justify-center gap-0.5 pt-3 xl:justify-end xl:pt-0">
                    {{ $applications->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
