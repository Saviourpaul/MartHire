<x-admin-layout title="Application Status">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Application Status</h3>
                <p class="text-muted mb-0">{{ $application->reference }} - {{ $application->job->title }}</p>
            </div>
<<<<<<< Updated upstream
            <div class="col-auto">
                <a href="{{ route('client.jobs') }}" class="btn btn-outline-secondary">Back to Jobs</a>
=======
            <!-- Breadcrumb End -->

            <!-- Content Start -->
            <div class="space-y-6">
                <div
                    class="flex flex-col justify-between gap-6 rounded-2xl border border-gray-200 bg-white px-6 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/3">
                    <div class="flex flex-col gap-2.5 divide-gray-300 sm:flex-row sm:divide-x dark:divide-gray-700">
                        <div class="flex items-center gap-2 sm:pr-3">
                            <span class="text-base font-medium text-gray-700 dark:text-gray-400">
                                Application ID
                                <span
                                    class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-medium">{{ $application->reference }}</span>
                        </div>
                        <p class="text-sm text-gray-500 sm:pl-3 dark:text-gray-400">
                            Submitted : {{ $application->submitted_at->format('M d, Y g:i A') }}
                        </p>
                         <span
                    class="inline-flex rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                    </div>

                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                   

                    <div class="space-y-6 lg:col-span-4 2xl:col-span-3">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                            <h2 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                                Applicant Details
                            </h2>
                            <div class="flex-1">
                                <div class="mb-6 flex flex-col gap-5 sm:flex-row xl:items-center xl:justify-between">
                                    <div class="flex w-full flex-col items-start gap-6 sm:flex-row sm:items-center">
                                        <div
                                            class="border-gray-20 overflow-hidden rounded-full border dark:border-gray-800">
                                            <img {{ $application->profile_image_path }} class="size-20"
                                                alt="user" />
                                        </div>
                                        <div class="text-left">
                                            <h4 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                                                {{ $application->applicant->first_name }}
                                                {{ $application->applicant->last_name }}
                                            </h4>
                                            <div class="flex items-center gap-1 sm:gap-3">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    email Address |
                                                </p>
                                                <div class="hidden h-3.5 w-px bg-gray-300 sm:block dark:bg-gray-700">
                                                </div>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $application->email }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="relative grid max-w-4xl grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 xl:gap-x-11 xl:gap-y-7">
                                    <div class="w-full">
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            First Name
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->first_name }}
                                        </p>
                                    </div>
                                    <div class="w-full">
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Last Name
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->last_name }}
                                        </p>
                                    </div>
                                    <div class="w-full">
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Middle Name
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->middle_name ?: 'Not Provided' }}
                                        </p>
                                    </div>
                                    <div class="w-full">
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Gender
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->gender ?: 'Not Provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Phone
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->phone }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            {{ $application->country ? 'Country' : 'Nationality (historical)' }}
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->country ?: $application->nationality ?: 'Not provided' }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Date of Birth
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->date_of_birth->format('M d, Y  ') }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            Marital status
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->marital_status }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            {{ $application->state ? 'State' : 'State of origin (historical)' }}
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->state ?: $application->state_of_origin ?: 'Not provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                            {{ $application->city ? 'City' : 'LGA (historical)' }}
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            {{ $application->city ?: $application->local_government_area ?: 'Not provided' }}
                                        </p>
                                    </div>

                                    <div class="hidden xl:block"></div>

                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                            <h2 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                                All Documents
                            </h2>

                            <!-- Timeline item -->
                            @forelse($application->documents as $document)
                                <div class="relative pb-7 pl-11">

                                    <!-- Icon -->
                                    <div
                                        class="absolute top-0 left-0 z-10 flex h-12 w-12 items-center justify-center rounded-xl bg-warning-500/[0.08] text-warning-500 ">
                                        <!-- Document  icon -->
                                        <svg class="fill-current" width="25" height="24" viewBox="0 0 25 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M19.8335 19.75C19.8335 20.9926 18.8261 22 17.5835 22H7.0835C5.84086 22 4.8335 20.9926 4.8335 19.75V9.62105C4.8335 9.02455 5.07036 8.45247 5.49201 8.03055L10.8597 2.65951C11.2817 2.23725 11.8542 2 12.4512 2H17.5835C18.8261 2 19.8335 3.00736 19.8335 4.25V19.75ZM17.5835 20.5C17.9977 20.5 18.3335 20.1642 18.3335 19.75V4.25C18.3335 3.83579 17.9977 3.5 17.5835 3.5H12.5815L12.5844 7.49913C12.5853 8.7424 11.5776 9.75073 10.3344 9.75073H6.3335V19.75C6.3335 20.1642 6.66928 20.5 7.0835 20.5H17.5835ZM7.39262 8.25073L11.0823 4.55876L11.0844 7.5002C11.0847 7.91462 10.7488 8.25073 10.3344 8.25073H7.39262ZM8.5835 14.5C8.5835 14.0858 8.91928 13.75 9.3335 13.75H15.3335C15.7477 13.75 16.0835 14.0858 16.0835 14.5C16.0835 14.9142 15.7477 15.25 15.3335 15.25H9.3335C8.91928 15.25 8.5835 14.9142 8.5835 14.5ZM8.5835 17.5C8.5835 17.0858 8.91928 16.75 9.3335 16.75H12.3335C12.7477 16.75 13.0835 17.0858 13.0835 17.5C13.0835 17.9142 12.7477 18.25 12.3335 18.25H9.3335C8.91928 18.25 8.5835 17.9142 8.5835 17.5Z"
                                                fill=""></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4 flex justify-between">
                                        <div>
                                            <h4 class="font-medium text-gray-800 dark:text-white/90">
                                                {{ $document->document_name }}
                                            </h4>
                                            
                                        </div>
                                        <a
                                            href="{{ $document->canPreviewInline() ? $document->previewUrl() : $document->downloadUrl() }}" target="_blank">
                                            <button
                                                class="shadow-theme-xs rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 pl-">
                                                Preview
                                            </button>
                                        </a>
                                    </div>

                                    <!-- Vertical line -->
                                    <!--div
                                    class="absolute top-8 left-6 h-full w-px border border-dashed border-gray-300 dark:border-gray-700 m-11">
                                </div-->

                                </div>
                            @empty
                                <div class="px-5 py-8 text-theme-sm text-gray-500">No documents submitted.</div>
                            @endforelse


                           
                            
                        </div>
                    </div>
                    
                    <aside class="space-y-6 xl:col-span-4">
                       
                        <section
                            class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white/90 mb-4">Pipeline History</h2>
                            
                             @forelse($application->statusHistories->sortBy('created_at') as $history)

                            <!-- Timeline item -->
                            <div class="relative pb-7 pl-11">
                                <!-- Icon -->
                                <div
                                    class="text-brand-500 bg-brand-50 dark:ring-brand-500/15 ring-brand-50 dark:bg-brand-950 absolute top-0 left-0 z-20 flex h-10 w-10 items-center justify-center rounded-full border-2 border-white ring-2 dark:border-gray-700">
                                    <!--Card icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="15" viewBox="0 0 20 20" fill="none">
                              <path d="M15.1039 13.3343L13.5141 14.924L12.6039 14.0137M9.99967 3.33414H6.56405C6.11247 3.33414 5.69599 3.5777 5.47459 3.97128L3.49355 7.49292C3.44274 7.58326 3.40357 7.67918 3.37664 7.77839M9.99967 3.33414H13.4353C13.8869 3.33414 14.3034 3.5777 14.5248 3.97128L16.5058 7.49292C16.5566 7.58326 16.5958 7.67918 16.6227 7.77839M9.99967 3.33414V7.77839M9.99967 7.77839L16.6227 7.77839M9.99967 7.77839L3.37664 7.77839M16.6227 7.77839C16.6516 7.88467 16.6663 7.99474 16.6663 8.10578V8.43098M3.37664 7.77839C3.3478 7.88467 3.33301 7.99474 3.33301 8.10578V15.4168C3.33301 16.1071 3.89265 16.6668 4.58301 16.6668H8.02525M17.708 14.1292C17.708 16.2578 15.9824 17.9833 13.8538 17.9833C11.7252 17.9833 9.99967 16.2578 9.99967 14.1292C9.99967 12.0006 11.7252 10.275 13.8538 10.275C15.9824 10.275 17.708 12.0006 17.708 14.1292Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                                </div>
                                <div class="ml-4 flex justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-800 dark:text-white/90"{{ $application->status->badgeClass() }}>
                                            {{ $history->to_status->label() }}
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                           {{ $history->remarks }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $history->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Vertical line -->
                                <div class="border-brand-500 absolute top-5 left-5 h-full w-px border border-dashed"></div>

                                
                            </div>
                            @empty<li class="py-3 text-theme-sm text-gray-500">No progress updates yet.</li>
                        @endforelse

                         
                        </section>
                    </aside>
                </div>
>>>>>>> Stashed changes
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 class="card-title mb-1">{{ $application->job->title }}</h5>
                            <p class="mb-1">{{ $application->job->company }}</p>
                            <p class="text-muted mb-0">Submitted {{ $application->submitted_at->format('M d, Y') }}</p>
                        </div>
                        <span class="badge {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                    </div>

                    @if ($application->employer_remarks)
                        <div class="alert alert-info mt-3 mb-0">{{ $application->employer_remarks }}</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Document Review</h5>
                    <div class="table-responsive">
                        <table class="table table-center table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Status</th>
                                    
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($application->documents as $document)
                                    <tr>
                                        <td>{{ $document->document_name }}</td>
                                        <td><span class="badge {{ $document->status->badgeClass() }}">{{ $document->status->label() }}</span></td>
                                        <td>{{ $document->reviewed_at?->format('M d, Y') ?: 'Pending review' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile Information </h5>
                    <p><strong>Name:</strong> {{ $application->first_name }} {{ $application->last_name }}</p>
                    <p><strong>Email:</strong> {{ $application->email }}</p>
                    <p><strong>Phone:</strong> {{ $application->phone }}</p>
                    <p><strong>Nationality:</strong> {{ $application->nationality ?: 'Not provided' }}</p>
                    <p><strong>Origin:</strong> {{ collect([$application->local_government_area, $application->state_of_origin])->filter()->implode(', ') ?: 'Not provided' }}</p>
                    <p class="mb-0"><strong>Address:</strong> {{ $application->address ?: 'Not provided' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status History</h5>
                    <div class="list-group">
                        @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $history->to_status->label() }}</strong>
                                    <small>{{ $history->created_at->diffForHumans() }}</small>
                                </div>
                                @if ($history->remarks)
                                    <p class="mb-0">{{ $history->remarks }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-muted">No status changes yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
