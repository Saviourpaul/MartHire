@use('App\Http\Requests\StoreApplicationFormRequest')

<x-layout title="Job Application">
    @php
        $steps = ['Personal Information', 'Identification', 'Educational Qualification', 'Application Summary'];
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
        $profileImageAccept = collect(StoreApplicationFormRequest::PROFILE_IMAGE_TYPES)
            ->map(fn($type) => '.' . $type)
            ->implode(',');
        $documentAccept = collect(StoreApplicationFormRequest::DOCUMENT_TYPES)
            ->map(fn($type) => '.' . $type)
            ->implode(',');
        $documentMaxMb = StoreApplicationFormRequest::DOCUMENT_MAX_KB / 1024;
        $stepFields = [
            [
                'profile_image',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'phone',
                'date_of_birth',
                'gender',
                'marital_status',
            ],
            ['nationality', 'state_of_origin', 'local_government_area', 'address', 'zipcode'],
            ['nin_number', 'nin_document', 'education_documents'],
        ];
        $initialStep = 0;

        foreach ($stepFields as $stepIndex => $fieldNames) {
            $hasErrorForStep = collect($errors->keys())->contains(function (string $errorKey) use ($fieldNames): bool {
                return collect($fieldNames)->contains(
                    fn(string $fieldName): bool => $errorKey === $fieldName ||
                        str_starts_with($errorKey, $fieldName . '.'),
                );
            });

            if ($hasErrorForStep) {
                $initialStep = $stepIndex;
                break;
            }
        }
        $input =
            'mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 shadow-sm outline-none transition duration-150 placeholder:text-gray-400 hover:border-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:hover:border-gray-600';
        $file =
            'mt-2 block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-sm transition duration-150 hover:border-gray-400 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-600 dark:file:bg-gray-800 dark:file:text-gray-200';
        $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
        $error = 'mt-1 hidden text-xs font-medium text-error-600 dark:text-error-400';
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-5 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-600 dark:text-brand-400">{{ $job->company }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">Apply for {{ $job->title }}</h1>
                
            </div>
            <a href="{{ route('job-details', $job) }}"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Back
                to job</a>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700"
                role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700"
                role="alert">
                <p>Please correct the highlighted fields. Your application has not been submitted yet.</p>
                @error('job')
                    <p class="mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <form action="{{ route('applications.store', $job) }}" method="POST" enctype="multipart/form-data"
            data-application-wizard data-confirm data-confirm-title="Submit application?"
            data-confirm-text="Please confirm your information and uploaded documents are correct."
            data-confirm-icon="question" data-confirm-button="Submit Application"
            data-initial-step="{{ $initialStep }}" data-completed-steps="{{ $initialStep }}" novalidate>
            @csrf

            <div
                class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Application progress</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" data-progress-status
                            aria-live="polite">
                            Step 1 of {{ count($steps) }}</p>
                    </div>
                    <span
                        class="shrink-0 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300"
                        data-overall-percent>0%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" role="progressbar"
                    aria-label="Application completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                    data-overall-progress>
                    <div class="h-full rounded-full bg-brand-500 transition-[width] duration-300 ease-out"
                        style="width:0%" data-overall-bar>
                    </div>
                </div>
              
            </div>

            <div class="mb-5 hidden rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700"
                data-validation-summary role="alert" tabindex="-1"></div>

            <section data-wizard-step
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Personal Information</h2>
                <div class="mt-6 -mx-2.5 flex flex-wrap gap-y-5">
                    <div class="w-full px-2.5">
                        <label for="profile_image" class="{{ $label }}">Profile photo @if (blank($user->profile_image_path))
                                <span class="text-error-600">*</span>
                            @endif
                        </label>
                        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="relative h-24 w-24 shrink-0" data-profile-preview-frame>
                                <img id="profile-image-preview" src="{{ $user->profileImageUrl() }}"
                                    data-default-src="{{ $user->profileImageUrl() }}" alt="Profile photo preview"
                                    class="h-24 w-24 rounded-full border-2 border-gray-200 bg-gray-50 object-cover shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <span
                                    class="absolute -right-1 bottom-0 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-brand-500 text-xs font-bold text-white shadow-sm dark:border-gray-900"
                                    aria-hidden="true">+</span>
                            </div>
                            <div class="flex-1">
                                <input id="profile_image" type="file" name="profile_image"
                                    class="{{ $file }} @error('profile_image') border-error-500 @enderror"
                                    accept="{{ $profileImageAccept }}" @required(blank($user->profile_image_path)) data-file-input
                                    data-file-kind="profile-image"
                                    data-max-kb="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB }}"
                                    data-allowed-types='@json(StoreApplicationFormRequest::PROFILE_IMAGE_TYPES)'
                                    data-min-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_WIDTH }}"
                                    data-min-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_HEIGHT }}"
                                    data-max-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_WIDTH }}"
                                    data-max-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_HEIGHT }}"
                                    aria-describedby="profile-image-help profile-image-preview-status profile-image-feedback">
                                <p id="profile-image-help" class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Max
                                    {{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB / 1024 }}MB.</p>
                                <p id="profile-image-preview-status" class="mt-1 text-xs text-gray-500"
                                    data-profile-preview-status aria-live="polite">
                                    Choose a photo to preview it before submission.</p>
                                <p id="profile-image-feedback"
                                    class="{{ $error }} @error('profile_image') block @enderror"
                                    data-validation-message>
                                    @error('profile_image')
                                        {{ $message }}
                                    @enderror
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2">
                        <label for="first_name" class="{{ $label }}">First name <span
                                class="text-error-600">*</span></label>
                        <input id="first_name" type="text" name="first_name"
                            value="{{ old('first_name', $user->first_name) }}"
                            class="{{ $input }} @error('first_name') border-error-500 @enderror"
                            autocomplete="given-name" minlength="2" maxlength="100" required>
                        <p class="{{ $error }} @error('first_name') block @enderror" data-validation-message>
                            @error('first_name')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2">
                        <label for="middle_name" class="{{ $label }}">Middle name <span
                                class="text-gray-400">(optional)</span></label>
                        <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}"
                            class="{{ $input }} @error('middle_name') border-error-500 @enderror"
                            autocomplete="additional-name" maxlength="100">
                        <p class="{{ $error }} @error('middle_name') block @enderror" data-validation-message>
                            @error('middle_name')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2">
                        <label for="last_name" class="{{ $label }}">Last name <span
                                class="text-error-600">*</span></label>
                        <input id="last_name" type="text" name="last_name"
                            value="{{ old('last_name', $user->last_name) }}"
                            class="{{ $input }} @error('last_name') border-error-500 @enderror"
                            autocomplete="family-name" minlength="2" maxlength="100" required>
                        <p class="{{ $error }} @error('last_name') block @enderror" data-validation-message>
                            @error('last_name')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2">
                        <label for="email" class="{{ $label }}">Email address <span
                                class="text-error-600">*</span></label>
                        <input id="email" type="email" name="email"
                            value="{{ old('email', $user->email) }}"
                            class="{{ $input }} @error('email') border-error-500 @enderror"
                            autocomplete="email" maxlength="255" required>
                        <p class="{{ $error }} @error('email') block @enderror" data-validation-message>
                            @error('email')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2">
                        <label for="phone" class="{{ $label }}">Phone number <span
                                class="text-error-600">*</span></label>
                        <input id="phone" type="tel" name="phone"
                            value="{{ old('phone', $user->phone) }}"
                            class="{{ $input }} @error('phone') border-error-500 @enderror"
                            autocomplete="tel" pattern="\+?[0-9 .()-]{7,20}" maxlength="20" required>
                        <p class="{{ $error }} @error('phone') block @enderror" data-validation-message>
                            @error('phone')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="date_of_birth" class="{{ $label }}">Date
                            of birth <span class="text-error-600">*</span></label><input id="date_of_birth"
                            type="date" name="date_of_birth"
                            value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                            class="{{ $input }} @error('date_of_birth') border-error-500 @enderror"
                            min="1900-01-01" max="{{ now()->subDay()->toDateString() }}" required>
                        <p class="{{ $error }} @error('date_of_birth') block @enderror"
                            data-validation-message>
                            @error('date_of_birth')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="gender" class="{{ $label }}">Gender
                            <span class="text-error-600">*</span></label><select id="gender" name="gender"
                            class="{{ $input }} @error('gender') border-error-500 @enderror" required>
                            <option value="">Select gender</option>
                            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $text)
                                <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $text }}
                                </option>
                            @endforeach
                        </select>
                        <p class="{{ $error }} @error('gender') block @enderror" data-validation-message>
                            @error('gender')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="marital_status"
                            class="{{ $label }}">Marital status <span
                                class="text-error-600">*</span></label><select id="marital_status"
                            name="marital_status"
                            class="{{ $input }} @error('marital_status') border-error-500 @enderror" required>
                            <option value="">Select status</option>
                            @foreach (['single' => 'Single', 'married' => 'Married', 'Other' => 'Other'] as $value => $text)
                                <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $text }}
                                </option>
                            @endforeach
                        </select>
                        <p class="{{ $error }} @error('marital_status') block @enderror"
                            data-validation-message>
                            @error('marital_status')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>
            </section>

            <section data-wizard-step
                class="hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Location Information</h2>
                <div class="mt-6 -mx-2.5 flex flex-wrap gap-y-5">
                    <div class="w-full px-2.5 xl:w-1/2"><label for="nationality"
                            class="{{ $label }}">Nationality <span
                                class="text-error-600">*</span></label><input id="nationality" type="text"
                            name="nationality" value="{{ old('nationality', $user->nationality ?? 'Nigeria') }}"
                            class="{{ $input }} @error('nationality') border-error-500 @enderror"
                            maxlength="100" autocomplete="country-name" required>
                        <p class="{{ $error }} @error('nationality') block @enderror" data-validation-message>
                            @error('nationality')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="state_of_origin"
                            class="{{ $label }}">State of origin <span
                                class="text-error-600">*</span></label><select id="state_of_origin"
                            name="state_of_origin"
                            class="{{ $input }} @error('state_of_origin') border-error-500 @enderror" required
                            data-state-of-origin>
                            <option value="">Select state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}"
                                    data-lga-url="{{ route('locations.states.local-government-areas', $state) }}"
                                    @selected($selectedState === $state->name)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                        <p class="{{ $error }} @error('state_of_origin') block @enderror"
                            data-validation-message>
                            @error('state_of_origin')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="local_government_area"
                            class="{{ $label }}">Local government area <span
                                class="text-error-600">*</span></label><select id="local_government_area"
                            name="local_government_area"
                            class="{{ $input }} @error('local_government_area') border-error-500 @enderror"
                            required data-local-government-area data-selected-lga="{{ $selectedLga }}">
                            <option value="">Select LGA</option>
                            @if ($selectedLga)
                                <option value="{{ $selectedLga }}" selected>{{ $selectedLga }}</option>
                            @endif
                        </select>
                        <p class="{{ $error }} @error('local_government_area') block @enderror"
                            data-validation-message>
                            @error('local_government_area')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="address" class="{{ $label }}">Address
                            <span class="text-error-600">*</span></label><input id="address" type="text"
                            name="address" value="{{ old('address', $user->address) }}"
                            class="{{ $input }} @error('address') border-error-500 @enderror" minlength="5"
                            maxlength="255" autocomplete="street-address" required>
                        <p class="{{ $error }} @error('address') block @enderror" data-validation-message>
                            @error('address')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="zipcode" class="{{ $label }}">Zipcode
                            <span class="text-error-600">*</span></label><input id="zipcode" type="text"
                            name="zipcode" value="{{ old('zipcode') }}"
                            class="{{ $input }} @error('zipcode') border-error-500 @enderror" minlength="3"
                            maxlength="20" autocomplete="postal-code" required>
                        <p class="{{ $error }} @error('zipcode') block @enderror" data-validation-message>
                            @error('zipcode')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>
            </section>

            <section data-wizard-step
                class="hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Means of Identification</h2>
                <p class="mt-1 text-sm text-gray-500">Accepted files: PDF, JPG, JPEG, or PNG. Max
                    {{ $documentMaxMb }}MB each.</p>
                <div class="mt-6 -mx-2.5 flex flex-wrap gap-y-5">
                    <div class="w-full px-2.5 xl:w-1/2"><label for="nin_number" class="{{ $label }}">NIN
                            number <span class="text-error-600">*</span></label><input id="nin_number" type="text" placeholder="NIN "
                            name="nin_number" value="{{ old('nin_number') }}"
                            class="{{ $input }} @error('nin_number') border-error-500 @enderror"
                            inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" required>
                        <p class="{{ $error }} @error('nin_number') block @enderror" data-validation-message>
                            @error('nin_number')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                    <div class="w-full px-2.5 xl:w-1/2"><label for="nin_document" class="{{ $label }}">NIN
                            document <span class="text-error-600">*</span></label>
                           

                            <input type="file" id="nin_document" name="nin_document" @error('nin_document') border-error-500 @enderror class="focus:border-ring-brand-300 shadow-theme-xs focus:file:ring-brand-300 h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pr-3 file:pl-3.5 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:text-white/90 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400 dark:placeholder:text-gray-400"  accept="{{ $documentAccept }}" required data-file-input
                            data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                            data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'>
                        <p class="{{ $error }} @error('nin_document') block @enderror"
                            data-validation-message>
                            @error('nin_document')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Education documents</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload at least one and no more than ten qualification
                            documents.</p>
                    </div><button type="button"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        data-add-document>Add another document</button>
                </div>
                @error('education_documents')
                    <p class="mt-3 text-xs font-medium text-error-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 space-y-4" data-education-documents>
                    @foreach ($educationRows as $index => $document)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"
                            data-education-document>
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white" data-document-title>
                                    Qualification Document {{ $index + 1 }}</h4><button type="button"
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-error-600 hover:bg-error-50"
                                    data-remove-document
                                    @if ($educationRows->count() === 1) hidden @endif>Remove</button>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div><label for="education-document-type-{{ $index }}"
                                        class="{{ $label }}" data-document-type-label>Document type <span
                                            class="text-error-600">*</span></label><select
                                        id="education-document-type-{{ $index }}"
                                        name="education_documents[{{ $index }}][type]"
                                        class="{{ $input }} @error("education_documents.$index.type") border-error-500 @enderror"
                                        required data-document-type>
                                        <option value="">Select type</option>
                                        @foreach ($educationDocumentTypes as $value => $text)
                                            <option value="{{ $value }}" @selected(data_get($document, 'type') === $value)>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                    <p class="{{ $error }} @error("education_documents.$index.type") block @enderror"
                                        data-validation-message>
                                        @error("education_documents.$index.type")
                                            {{ $message }}
                                        @enderror
                                    </p>
                                </div>
                                <div class="xl:col-span-2" data-field><label for="education-document-file-{{ $index }}"
                                        class="{{ $label }}" data-document-file-label>Document file <span
                                            class="text-error-600">*</span></label>
                                    <div class="mt-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-brand-500 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-brand-500/5"
                                        data-document-dropzone role="button" tabindex="0"
                                        aria-label="Choose an education document file">
                                        <input id="education-document-file-{{ $index }}" type="file"
                                            name="education_documents[{{ $index }}][file]" class="sr-only"
                                            accept="{{ $documentAccept }}" required data-file-input data-document-file
                                            data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                                            data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'>
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a1 1 0 0 1 .7.3l4 4a1 1 0 1 1-1.4 1.4L13 6.4V16a1 1 0 1 1-2 0V6.4L8.7 8.7a1 1 0 0 1-1.4-1.4l4-4A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-2a1 1 0 1 1 2 0v2a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-2a1 1 0 0 1 1-1Z" /></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-white">Drop a document here</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, JPG, JPEG, or PNG up to {{ $documentMaxMb }}MB</p>
                                        <span class="mt-3 inline-block text-sm font-medium text-brand-600 underline dark:text-brand-400">Browse files</span>
                                        <p class="mt-3 hidden truncate text-xs font-medium text-success-700 dark:text-success-400" data-document-file-name></p>
                                    </div>
                                    <p class="{{ $error }} @error("education_documents.$index.file") block @enderror"
                                        data-validation-message>
                                        @error("education_documents.$index.file")
                                            {{ $message }}
                                        @enderror
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section data-wizard-step
                class="hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Application Summary</h2>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Applicant</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900" data-summary-full-name>Not provided</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Contact</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900" data-summary-contact>Not provided</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Origin</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900" data-summary-origin>Not provided</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Education documents</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900"><span data-summary-documents>0</span>
                            selected</dd>
                    </div>
                </dl>
            </section>

            <div
                class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <button type="button"
                    class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    data-wizard-previous>Previous</button>
                <div class="flex flex-col gap-3 sm:flex-row"><button type="button"
                        class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600"
                        data-wizard-next>Next</button><button type="submit"
                        class="hidden h-11 items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-70"
                        data-wizard-submit><span>Submit Application</span><span
                            class="ml-2 hidden h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                            data-submit-spinner aria-hidden="true"></span></button></div>
            </div>
        </form>
    </div>
    <template id="education-document-template">
        <div class="rounded-lg border border-gray-200 p-4" data-education-document>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-gray-900" data-document-title>Qualification Document</h4><button
                    type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium text-error-600 hover:bg-error-50"
                    data-remove-document>Remove</button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div><label class="{{ $label }}" data-document-type-label>Document type <span
                            class="text-error-600">*</span></label><select class="{{ $input }}" required
                        data-document-type>
                        <option value="">Select type</option>
                        @foreach ($educationDocumentTypes as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $error }}" data-validation-message></p>
                </div>
                <div class="xl:col-span-2" data-field><label class="{{ $label }}" data-document-file-label>Document file
                        <span class="text-error-600">*</span></label>
                    <div class="mt-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-brand-500 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-brand-500/5"
                        data-document-dropzone role="button" tabindex="0" aria-label="Choose an education document file">
                        <input type="file" class="sr-only" accept="{{ $documentAccept }}" required data-file-input
                            data-document-file data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                            data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a1 1 0 0 1 .7.3l4 4a1 1 0 1 1-1.4 1.4L13 6.4V16a1 1 0 1 1-2 0V6.4L8.7 8.7a1 1 0 0 1-1.4-1.4l4-4A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-2a1 1 0 1 1 2 0v2a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-2a1 1 0 0 1 1-1Z" /></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-white">Drop a document here</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, JPG, JPEG, or PNG up to {{ $documentMaxMb }}MB</p>
                        <span class="mt-3 inline-block text-sm font-medium text-brand-600 underline dark:text-brand-400">Browse files</span>
                        <p class="mt-3 hidden truncate text-xs font-medium text-success-700 dark:text-success-400" data-document-file-name></p>
                    </div>
                    <p class="{{ $error }}" data-validation-message></p>
                </div>
            </div>
        </div>
    </template>
    @push('scripts')
        <script defer src="{{ asset('assets/js/application-wizard.js') }}"></script>
    @endpush
</x-layout>
