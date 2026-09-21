@use('App\Http\Requests\StoreApplicationFormRequest')

<x-layout title="Job Application">
    @php
        $wizardSteps = ['Personal details', 'Identity documents', 'Education', 'Review'];
        $educationDocumentTypes = [
            'ssce' => 'SSCE',
            'ond' => 'OND',
            'bsc' => 'BSc',
            'bed' => 'B.Ed',
            'nysc' => 'NYSC',
            'msc' => 'MSc',
            'phd' => 'PhD',
            'other' => 'Other',
        ];
        $educationRows = collect(old('education_documents', [['type' => '']]))->values();
        $educationRows = $educationRows->isEmpty() ? collect([['type' => '']]) : $educationRows;
        $selectedState = old('state_of_origin', $user->state_of_origin);
        $selectedLga = old('local_government_area', $user->local_government_area);
        $profileImageAccept = collect(StoreApplicationFormRequest::PROFILE_IMAGE_TYPES)->map(fn ($type) => '.'.$type)->implode(',');
        $documentAccept = collect(StoreApplicationFormRequest::DOCUMENT_TYPES)->map(fn ($type) => '.'.$type)->implode(',');
        $profileImageMaxMb = StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB / 1024;
        $documentMaxMb = StoreApplicationFormRequest::DOCUMENT_MAX_KB / 1024;
        $errorKeys = array_keys($errors->getMessages());
        $initialStep = 3;

        foreach ($errorKeys as $errorKey) {
            $step = match (true) {
                str_starts_with($errorKey, 'education_documents') => 2,
                in_array($errorKey, ['nin_number', 'nin_document', 'bvn_number', 'bvn_document'], true) => 1,
                $errorKey === 'job' => 3,
                default => 0,
            };

            $initialStep = min($initialStep, $step);
        }

        if ($errorKeys === []) {
            $initialStep = 0;
        }

        $inputClass = 'mt-1.5 block w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-500 dark:focus:border-brand-400 dark:disabled:bg-white/[0.03]';
        $labelClass = 'block text-theme-sm font-medium text-gray-700 dark:text-gray-300';
        $errorClass = 'mt-1.5 text-theme-xs text-red-600 dark:text-red-400';
    @endphp

    <main class="mx-auto w-full max-w-5xl p-4 pb-20 md:p-6 md:pb-8">
        <div class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-6 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('job-details', $job) }}" class="inline-flex items-center text-theme-sm font-medium text-gray-500 transition hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400">
                    Back to job
                </a>
                <h1 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-white/90">Apply for {{ $job->title }}</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $job->company }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-theme-sm sm:text-right">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Deadline</dt>
                    <dd class="font-medium text-gray-800 dark:text-white/90">{{ $job->due_date->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Location</dt>
                    <dd class="font-medium text-gray-800 dark:text-white/90">{{ $job->location }}</dd>
                </div>
            </dl>
        </div>

        <x-application-wizard-progress :steps="$wizardSteps" />

        <form class="mt-6 space-y-6" action="{{ route('applications.store', $job) }}" method="POST" enctype="multipart/form-data"
            data-application-wizard data-initial-step="{{ $initialStep }}" data-completed-steps="{{ $initialStep }}"
            data-confirm-title="Submit application?" data-confirm-text="Please confirm your information and uploaded documents are correct."
            data-confirm-button="Submit application" novalidate>
            @csrf

            <div class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                data-validation-summary role="alert" tabindex="-1"></div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                    data-server-validation-summary role="alert">
                    Please review the highlighted fields before submitting your application again.
                </div>
            @endif

            <x-application-wizard-step :title="$wizardSteps[0]" :index="0" description="Confirm your contact information and add a profile photo.">
                <div class="space-y-6">
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02] sm:flex-row sm:items-center">
                        <div class="size-20 shrink-0 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" data-profile-preview-frame>
                            <img id="profile-image-preview" class="size-full object-cover" src="{{ $user->profileImageUrl() }}"
                                data-default-src="{{ $user->profileImageUrl() }}" alt="Profile photo preview" width="80" height="80">
                        </div>
                        <div class="min-w-0 flex-1" data-field>
                            <label for="profile-image-input" class="{{ $labelClass }}">Profile photo @if (blank($user->profile_image_path))<span class="text-red-600 dark:text-red-400">*</span>@endif</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <label for="profile-image-input" class="inline-flex h-10 cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                    Choose photo
                                </label>
                                <span class="text-theme-xs text-gray-500 dark:text-gray-400" data-profile-preview-status>Choose a photo to preview it before submission.</span>
                            </div>
                            <input id="profile-image-input" type="file" name="profile_image" class="sr-only" accept="{{ $profileImageAccept }}"
                                @required(blank($user->profile_image_path)) data-file-kind="profile-image"
                                data-max-kb="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB }}"
                                data-allowed-types='@json(StoreApplicationFormRequest::PROFILE_IMAGE_TYPES)'
                                data-min-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_WIDTH }}"
                                data-min-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_HEIGHT }}"
                                data-max-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_WIDTH }}"
                                data-max-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_HEIGHT }}"
                                aria-describedby="profile-image-help profile-image-error">
                            <p id="profile-image-help" class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">JPG, PNG, or WebP. Up to {{ $profileImageMaxMb }}MB; {{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_WIDTH }}x{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_HEIGHT }} to {{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_WIDTH }}x{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_HEIGHT }} pixels.</p>
                            <p id="profile-image-error" class="{{ $errorClass }} {{ $errors->has('profile_image') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('profile_image'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div data-field>
                            <label for="first-name" class="{{ $labelClass }}">First name <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="first-name" type="text" name="first_name" class="{{ $inputClass }} @error('first_name') border-red-500 @enderror" value="{{ old('first_name', $user->first_name) }}" minlength="2" maxlength="100" autocomplete="given-name" required aria-describedby="first-name-error">
                            <p id="first-name-error" class="{{ $errorClass }} {{ $errors->has('first_name') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('first_name'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="middle-name" class="{{ $labelClass }}">Middle name</label>
                            <input id="middle-name" type="text" name="middle_name" class="{{ $inputClass }} @error('middle_name') border-red-500 @enderror" value="{{ old('middle_name') }}" maxlength="100" autocomplete="additional-name" aria-describedby="middle-name-error">
                            <p id="middle-name-error" class="{{ $errorClass }} {{ $errors->has('middle_name') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('middle_name'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="last-name" class="{{ $labelClass }}">Last name <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="last-name" type="text" name="last_name" class="{{ $inputClass }} @error('last_name') border-red-500 @enderror" value="{{ old('last_name', $user->last_name) }}" minlength="2" maxlength="100" autocomplete="family-name" required aria-describedby="last-name-error">
                            <p id="last-name-error" class="{{ $errorClass }} {{ $errors->has('last_name') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('last_name'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="phone" class="{{ $labelClass }}">Phone number <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="phone" type="tel" name="phone" class="{{ $inputClass }} @error('phone') border-red-500 @enderror" value="{{ old('phone', $user->phone) }}" autocomplete="tel" pattern="\+?[0-9\s().-]{7,20}" maxlength="20" required aria-describedby="phone-error">
                            <p id="phone-error" class="{{ $errorClass }} {{ $errors->has('phone') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('phone'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="email" class="{{ $labelClass }}">Email address <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="email" type="email" name="email" class="{{ $inputClass }} @error('email') border-red-500 @enderror" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required aria-describedby="email-error">
                            <p id="email-error" class="{{ $errorClass }} {{ $errors->has('email') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('email'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="nationality" class="{{ $labelClass }}">Nationality <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="nationality" type="text" name="nationality" class="{{ $inputClass }} @error('nationality') border-red-500 @enderror" value="{{ old('nationality', $user->nationality ?? 'Nigeria') }}" maxlength="100" autocomplete="country-name" required aria-describedby="nationality-error">
                            <p id="nationality-error" class="{{ $errorClass }} {{ $errors->has('nationality') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('nationality'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="date-of-birth" class="{{ $labelClass }}">Date of birth <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="date-of-birth" type="date" name="date_of_birth" class="{{ $inputClass }} @error('date_of_birth') border-red-500 @enderror" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" min="1900-01-01" max="{{ now()->subDay()->toDateString() }}" required aria-describedby="date-of-birth-error">
                            <p id="date-of-birth-error" class="{{ $errorClass }} {{ $errors->has('date_of_birth') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('date_of_birth'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="gender" class="{{ $labelClass }}">Gender <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select id="gender" name="gender" class="{{ $inputClass }} @error('gender') border-red-500 @enderror" required aria-describedby="gender-error">
                                <option value="">Select gender</option>
                                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p id="gender-error" class="{{ $errorClass }} {{ $errors->has('gender') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('gender'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="marital-status" class="{{ $labelClass }}">Marital status <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select id="marital-status" name="marital_status" class="{{ $inputClass }} @error('marital_status') border-red-500 @enderror" required aria-describedby="marital-status-error">
                                <option value="">Select status</option>
                                @foreach (['single' => 'Single', 'married' => 'Married', 'Other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p id="marital-status-error" class="{{ $errorClass }} {{ $errors->has('marital_status') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('marital_status'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="zipcode" class="{{ $labelClass }}">Zipcode <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="zipcode" type="text" name="zipcode" class="{{ $inputClass }} @error('zipcode') border-red-500 @enderror" value="{{ old('zipcode', $user->zipcode) }}" minlength="3" maxlength="20" pattern="[A-Za-z0-9\s-]{3,20}" autocomplete="postal-code" required aria-describedby="zipcode-error">
                            <p id="zipcode-error" class="{{ $errorClass }} {{ $errors->has('zipcode') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('zipcode'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="state-of-origin" class="{{ $labelClass }}">State of origin <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select id="state-of-origin" name="state_of_origin" class="{{ $inputClass }} @error('state_of_origin') border-red-500 @enderror" required data-state-of-origin aria-describedby="state-of-origin-error">
                                <option value="">Select state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->name }}" data-lga-url="{{ route('locations.states.local-government-areas', $state) }}" @selected($selectedState === $state->name)>{{ $state->name }}</option>
                                @endforeach
                            </select>
                            <p id="state-of-origin-error" class="{{ $errorClass }} {{ $errors->has('state_of_origin') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('state_of_origin'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="local-government-area" class="{{ $labelClass }}">Local government area <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select id="local-government-area" name="local_government_area" class="{{ $inputClass }} @error('local_government_area') border-red-500 @enderror" required data-local-government-area data-selected-lga="{{ $selectedLga }}" aria-describedby="local-government-area-error">
                                <option value="">Select state first</option>
                            </select>
                            <p id="local-government-area-error" class="{{ $errorClass }} {{ $errors->has('local_government_area') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('local_government_area'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    <div data-field>
                        <label for="address" class="{{ $labelClass }}">Address <span class="text-red-600 dark:text-red-400">*</span></label>
                        <textarea id="address" name="address" rows="3" class="{{ $inputClass }} resize-y @error('address') border-red-500 @enderror" minlength="5" maxlength="255" autocomplete="street-address" required aria-describedby="address-error">{{ old('address', $user->address) }}</textarea>
                        <p id="address-error" class="{{ $errorClass }} {{ $errors->has('address') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('address'){{ $message }}@enderror</p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end border-t border-gray-100 pt-5 dark:border-gray-800">
                    <button type="button" data-wizard-next class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30">Next</button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step :title="$wizardSteps[1]" :index="1" description="Provide the identity details required for this application.">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-5 rounded-lg border border-gray-200 p-4 dark:border-gray-800 sm:p-5">
                        <div>
                            <h3 class="text-theme-sm font-semibold text-gray-900 dark:text-white/90">National Identity Number</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Enter your 11-digit NIN and upload the supporting document.</p>
                        </div>
                        <div data-field>
                            <label for="nin-number" class="{{ $labelClass }}">NIN number <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="nin-number" type="text" name="nin_number" class="{{ $inputClass }} @error('nin_number') border-red-500 @enderror" value="{{ old('nin_number') }}" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" required aria-describedby="nin-number-error">
                            <p id="nin-number-error" class="{{ $errorClass }} {{ $errors->has('nin_number') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('nin_number'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="nin-document" class="{{ $labelClass }}">NIN document <span class="text-red-600 dark:text-red-400">*</span></label>
                            <div class="mt-1.5 rounded-lg border border-dashed border-gray-300 p-4 text-center transition hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-gray-700 dark:hover:bg-brand-500/5" data-document-dropzone tabindex="0" role="button" aria-label="Choose NIN document">
                                <input id="nin-document" type="file" name="nin_document" class="sr-only" accept="{{ $documentAccept }}" required data-document-file data-file-kind="document" data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}" data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)' aria-describedby="nin-document-help nin-document-error">
                                <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">Choose or drop a document</p>
                                <p id="nin-document-help" class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">PDF, JPG, or PNG. Up to {{ $documentMaxMb }}MB.</p>
                                <p class="mt-2 hidden text-theme-xs font-medium text-brand-600 dark:text-brand-400" data-document-file-name></p>
                            </div>
                            <p id="nin-document-error" class="{{ $errorClass }} {{ $errors->has('nin_document') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('nin_document'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    <div class="space-y-5 rounded-lg border border-gray-200 p-4 dark:border-gray-800 sm:p-5">
                        <div>
                            <h3 class="text-theme-sm font-semibold text-gray-900 dark:text-white/90">Bank Verification Number</h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Enter your 11-digit BVN and upload the supporting document.</p>
                        </div>
                        <div data-field>
                            <label for="bvn-number" class="{{ $labelClass }}">BVN number <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input id="bvn-number" type="text" name="bvn_number" class="{{ $inputClass }} @error('bvn_number') border-red-500 @enderror" value="{{ old('bvn_number') }}" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" required aria-describedby="bvn-number-error">
                            <p id="bvn-number-error" class="{{ $errorClass }} {{ $errors->has('bvn_number') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('bvn_number'){{ $message }}@enderror</p>
                        </div>
                        <div data-field>
                            <label for="bvn-document" class="{{ $labelClass }}">BVN document <span class="text-red-600 dark:text-red-400">*</span></label>
                            <div class="mt-1.5 rounded-lg border border-dashed border-gray-300 p-4 text-center transition hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-gray-700 dark:hover:bg-brand-500/5" data-document-dropzone tabindex="0" role="button" aria-label="Choose BVN document">
                                <input id="bvn-document" type="file" name="bvn_document" class="sr-only" accept="{{ $documentAccept }}" required data-document-file data-file-kind="document" data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}" data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)' aria-describedby="bvn-document-help bvn-document-error">
                                <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">Choose or drop a document</p>
                                <p id="bvn-document-help" class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">PDF, JPG, or PNG. Up to {{ $documentMaxMb }}MB.</p>
                                <p class="mt-2 hidden text-theme-xs font-medium text-brand-600 dark:text-brand-400" data-document-file-name></p>
                            </div>
                            <p id="bvn-document-error" class="{{ $errorClass }} {{ $errors->has('bvn_document') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('bvn_document'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-800">
                    <button type="button" data-wizard-previous class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">Previous</button>
                    <button type="button" data-wizard-next class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30">Next</button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step :title="$wizardSteps[2]" :index="2" description="Add at least one educational qualification document.">
                <div id="education-documents" class="space-y-4" data-education-documents>
                    @foreach ($educationRows as $index => $row)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 sm:p-5" data-education-document>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-theme-sm font-semibold text-gray-900 dark:text-white/90" data-document-title>Qualification document {{ $index + 1 }}</h3>
                                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Select the qualification and attach its document.</p>
                                </div>
                                <button type="button" data-remove-document class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">Remove</button>
                            </div>
                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <div data-field>
                                    <label for="education-document-type-{{ $index }}" class="{{ $labelClass }}" data-document-type-label>Document type <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <select id="education-document-type-{{ $index }}" name="education_documents[{{ $index }}][type]" class="{{ $inputClass }} @error('education_documents.'.$index.'.type') border-red-500 @enderror" required data-document-type aria-describedby="education-document-type-error-{{ $index }}">
                                        <option value="">Select document type</option>
                                        @foreach ($educationDocumentTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(($row['type'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p id="education-document-type-error-{{ $index }}" class="{{ $errorClass }} {{ $errors->has('education_documents.'.$index.'.type') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('education_documents.'.$index.'.type'){{ $message }}@enderror</p>
                                </div>
                                <div data-field>
                                    <label for="education-document-file-{{ $index }}" class="{{ $labelClass }}" data-document-file-label>Upload document <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <div class="mt-1.5 rounded-lg border border-dashed border-gray-300 p-4 text-center transition hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-gray-700 dark:hover:bg-brand-500/5" data-document-dropzone tabindex="0" role="button" aria-label="Choose qualification document">
                                        <input id="education-document-file-{{ $index }}" type="file" name="education_documents[{{ $index }}][file]" class="sr-only" accept="{{ $documentAccept }}" required data-document-file data-file-kind="document" data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}" data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)' aria-describedby="education-document-file-help-{{ $index }} education-document-file-error-{{ $index }}">
                                        <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">Choose or drop a document</p>
                                        <p id="education-document-file-help-{{ $index }}" class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400" data-document-file-help>PDF, JPG, or PNG. Up to {{ $documentMaxMb }}MB.</p>
                                        <p class="mt-2 hidden text-theme-xs font-medium text-brand-600 dark:text-brand-400" data-document-file-name></p>
                                    </div>
                                    <p id="education-document-file-error-{{ $index }}" class="{{ $errorClass }} {{ $errors->has('education_documents.'.$index.'.file') ? 'block' : 'hidden' }}" data-validation-message aria-live="polite">@error('education_documents.'.$index.'.file'){{ $message }}@enderror</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @error('education_documents')
                    <p class="{{ $errorClass }}" data-education-documents-error role="alert">{{ $message }}</p>
                @enderror

                <button type="button" data-add-document class="mt-4 inline-flex h-10 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-4 text-theme-sm font-medium text-brand-700 transition hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:bg-brand-500/15">Add another document</button>

                <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-800">
                    <button type="button" data-wizard-previous class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">Previous</button>
                    <button type="button" data-wizard-next class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30">Next</button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step :title="$wizardSteps[3]" :index="3" description="Review the application before sending it to {{ $job->company }}.">
                <dl class="grid divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200 text-theme-sm dark:divide-gray-800 dark:border-gray-800 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                    <div class="p-4"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Job</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $job->title }}</dd></div>
                    <div class="p-4"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Company</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $job->company }}</dd></div>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Applicant</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90" data-summary-full-name>{{ trim(old('first_name', $user->first_name).' '.old('last_name', $user->last_name)) ?: 'Not provided' }}</dd></div>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Contact</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90" data-summary-contact>{{ old('phone', $user->phone) ?: 'Not provided' }}</dd></div>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Origin</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90" data-summary-origin>{{ collect([$selectedLga, $selectedState])->filter()->implode(', ') ?: 'Not provided' }}</dd></div>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Nationality</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90" data-summary-nationality>{{ old('nationality', $user->nationality ?? 'Nigeria') ?: 'Not provided' }}</dd></div>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800 sm:col-span-2"><dt class="text-theme-xs text-gray-500 dark:text-gray-400">Qualification documents</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90" data-summary-documents>{{ $educationRows->count() }}</dd></div>
                </dl>

                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-theme-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                    Your application will be submitted with the documents selected in the previous steps.
                </div>

                <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-800">
                    <button type="button" data-wizard-previous class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">Previous</button>
                    <button type="submit" data-wizard-submit class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <span data-submit-label>Submit application</span>
                        <span class="ml-2 hidden" data-submit-spinner aria-hidden="true">Submitting...</span>
                    </button>
                </div>
            </x-application-wizard-step>

            <template id="education-document-template">
                <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 sm:p-5" data-education-document>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-theme-sm font-semibold text-gray-900 dark:text-white/90" data-document-title></h3>
                            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Select the qualification and attach its document.</p>
                        </div>
                        <button type="button" data-remove-document class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-theme-xs font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05]">Remove</button>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div data-field>
                            <label class="{{ $labelClass }}" data-document-type-label>Document type <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select class="{{ $inputClass }}" required data-document-type>
                                <option value="">Select document type</option>
                                @foreach ($educationDocumentTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $errorClass }} hidden" data-validation-message aria-live="polite"></p>
                        </div>
                        <div data-field>
                            <label class="{{ $labelClass }}" data-document-file-label>Upload document <span class="text-red-600 dark:text-red-400">*</span></label>
                            <div class="mt-1.5 rounded-lg border border-dashed border-gray-300 p-4 text-center transition hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-gray-700 dark:hover:bg-brand-500/5" data-document-dropzone tabindex="0" role="button" aria-label="Choose qualification document">
                                <input type="file" class="sr-only" accept="{{ $documentAccept }}" required data-document-file data-file-kind="document" data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}" data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'>
                                <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">Choose or drop a document</p>
                                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400" data-document-file-help>PDF, JPG, or PNG. Up to {{ $documentMaxMb }}MB.</p>
                                <p class="mt-2 hidden text-theme-xs font-medium text-brand-600 dark:text-brand-400" data-document-file-name></p>
                            </div>
                            <p class="{{ $errorClass }} hidden" data-validation-message aria-live="polite"></p>
                        </div>
                    </div>
                </article>
            </template>
        </form>
    </main>

    @push('page-scripts')
        @vite('resources/js/application-wizard.js')
    @endpush
</x-layout>
