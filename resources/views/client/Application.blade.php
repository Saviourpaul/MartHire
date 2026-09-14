@use('App\Http\Requests\StoreApplicationFormRequest')

<x-layout title="Job Application">
    @php
        $wizardSteps = ['Personal Information', 'Identification', 'Educational Qualification', 'Application Summary'];

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
            ->map(fn($type) => '.'.$type)
            ->implode(',');
        $documentAccept = collect(StoreApplicationFormRequest::DOCUMENT_TYPES)
            ->map(fn($type) => '.'.$type)
            ->implode(',');
        $profileImageMaxMb = StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB / 1024;
        $documentMaxMb = StoreApplicationFormRequest::DOCUMENT_MAX_KB / 1024;
    @endphp

   

    <div class="application-wizard-shell">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Apply for {{ $job->title }}</h3>
                    <p class="text-muted mb-0">{{ $job->company }}</p>
                </div>
    
                 <div>
                <a href="{{ route('job-details', $job) }}" class="mb-3 inline-flex items-center gap-2 text-theme-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">
                    <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7908 5.23017C13.0837 5.52306 13.0837 5.99794 12.7908 6.29083L9.08167 10L12.7908 13.7092C13.0837 14.0021 13.0837 14.4769 12.7908 14.7698C12.4979 15.0627 12.0231 15.0627 11.7302 14.7698L7.49017 10.5298C7.19728 10.2369 7.19728 9.76206 7.49017 9.46917L11.7302 5.23017C12.0231 4.93728 12.4979 4.93728 12.7908 5.23017Z" />
                    </svg>
                    Back to jobs
                </a>
            </div>
            </div>
        </div>

        <x-application-wizard-progress :steps="$wizardSteps" />

        <form action="{{ route('applications.store', $job) }}" method="POST" enctype="multipart/form-data"
            data-application-wizard data-confirm data-confirm-title="Submit application?"
            data-confirm-text="Please confirm your information and uploaded documents are correct."
            data-confirm-icon="question" data-confirm-button="Submit Application" novalidate>
            @csrf

            <div class="alert alert-danger d-none" data-validation-summary role="alert" tabindex="-1">
                Please correct the highlighted fields before continuing.
            </div>

            <x-application-wizard-step title="Personal Information" :index="0">
                <div class="application-form-panel">
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="pro-form-img profile-upload-preview">
                                <div class="profile-pic">

                                    <img id="profile-image-preview" class="rounded-circle"
                                        src="{{ $user->profileImageUrl() }}" alt="Profile image" width="100"
                                        height="100" style="object-fit: cover;">
                                </div>

                                <div class="upload-files">
                                    <label class="file-upload image-upbtn">
                                        <i class="feather-upload me-2"></i>Upload Photo
                                        <input id="profile-image-input" type="file" name="profile_image"
                                            class="form-control @error('profile_image') is-invalid @enderror"
                                            accept="{{ $profileImageAccept }}" @required(blank($user->profile_image_path))
                                            data-file-input data-file-kind="profile-image"
                                            data-max-kb="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_KB }}"
                                            data-allowed-types='@json(StoreApplicationFormRequest::PROFILE_IMAGE_TYPES)'
                                            data-min-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_WIDTH }}"
                                            data-min-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_HEIGHT }}"
                                            data-max-width="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_WIDTH }}"
                                            data-max-height="{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_HEIGHT }}"
                                            aria-describedby="profile-image-help profile-image-feedback">
                                    </label>
                                    <span id="profile-image-help">
                                        JPG, PNG, or WebP. Max {{ $profileImageMaxMb }}MB. Dimensions
                                        {{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_WIDTH }}x{{ StoreApplicationFormRequest::PROFILE_IMAGE_MIN_HEIGHT }}
                                        to
                                        {{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_WIDTH }}x{{ StoreApplicationFormRequest::PROFILE_IMAGE_MAX_HEIGHT }}
                                        pixels.
                                    </span>
                                    <div id="profile-image-preview-status" class="profile-preview-status" aria-live="polite">
                                        Choose a photo to preview it before submission.
                                    </div>
                                    <div id="profile-image-feedback"
                                        class="validation-feedback @error('profile_image') is-visible @enderror"
                                        data-validation-message aria-live="polite">
                                        @error('profile_image')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name"
                                class="form-control @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name', $user->first_name) }}" minlength="2" maxlength="100"
                                autocomplete="given-name" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name"
                                class="form-control @error('middle_name') is-invalid @enderror"
                                value="{{ old('middle_name') }}" maxlength="100" autocomplete="additional-name">
                            @error('middle_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name"
                                class="form-control @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name', $user->last_name) }}" minlength="2" maxlength="100"
                                autocomplete="family-name" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                                pattern="\+?[0-9\s().-]{7,20}" maxlength="20" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality"
                                class="form-control @error('nationality') is-invalid @enderror"
                                value="{{ old('nationality', $user->nationality ?? 'Nigeria') }}" maxlength="100"
                                autocomplete="country-name" required>
                            @error('nationality')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                min="1900-01-01" max="{{ now()->subDay()->toDateString() }}" required>
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                <option value="">Select gender</option>
                                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status"
                                class="form-control @error('marital_status') is-invalid @enderror" required>
                                <option value="">Select status</option>
                                @foreach (['single' => 'Single', 'married' => 'Married', 'Other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('marital_status') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            @error('marital_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Zipcode</label>
                            <input type="text" name="zipcode"
                                class="form-control @error('zipcode') is-invalid @enderror"
                                value="{{ old('zipcode', $user->zipcode) }}" minlength="3" maxlength="20"
                                pattern="[A-Za-z0-9\s-]{3,20}" autocomplete="postal-code" required>
                            @error('zipcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">State of Origin</label>
                            <select name="state_of_origin"
                                class="form-control @error('state_of_origin') is-invalid @enderror" required
                                data-state-of-origin>
                                <option value="">Select state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->name }}"
                                        data-lga-url="{{ route('locations.states.local-government-areas', $state) }}"
                                        @selected($selectedState === $state->name)>
                                        {{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('state_of_origin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Local Government Area</label>
                            <select name="local_government_area"
                                class="form-control @error('local_government_area') is-invalid @enderror" required
                                data-local-government-area data-selected-lga="{{ $selectedLga }}">
                                <option value="">Select LGA</option>
                                @if ($selectedLga)
                                    <option value="{{ $selectedLga }}" selected>{{ $selectedLga }}</option>
                                @endif
                            </select>
                            @error('local_government_area')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address', $user->address) }}" minlength="5" maxlength="255"
                                autocomplete="street-address" required>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="wizard-actions">
                    <span></span>
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Next
                        <i data-feather="arrow-right"></i>
                    </button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step title="Identification" :index="1">
                <div class="application-form-panel">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIN Number</label>
                            <input type="text" name="nin_number"
                                class="form-control @error('nin_number') is-invalid @enderror"
                                value="{{ old('nin_number') }}" inputmode="numeric" pattern="[0-9]{11}"
                                minlength="11" maxlength="11" required>
                            @error('nin_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIN Document</label>
                            <input type="file" name="nin_document"
                                class="form-control @error('nin_document') is-invalid @enderror"
                                accept="{{ $documentAccept }}" required data-file-input data-file-kind="document"
                                data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                                data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'
                                aria-describedby="nin-document-help nin-document-feedback">
                            <div id="nin-document-help" class="form-text">
                                PDF, JPG, or PNG. Max {{ $documentMaxMb }}MB.
                            </div>
                            <div id="nin-document-feedback"
                                class="validation-feedback @error('nin_document') is-visible @enderror"
                                data-validation-message aria-live="polite">
                                @error('nin_document')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">BVN Number</label>
                            <input type="text" name="bvn_number"
                                class="form-control @error('bvn_number') is-invalid @enderror"
                                value="{{ old('bvn_number') }}" inputmode="numeric" pattern="[0-9]{11}"
                                minlength="11" maxlength="11" required>
                            @error('bvn_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">BVN Document</label>
                            <input type="file" name="bvn_document"
                                class="form-control @error('bvn_document') is-invalid @enderror"
                                accept="{{ $documentAccept }}" required data-file-input data-file-kind="document"
                                data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                                data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'
                                aria-describedby="bvn-document-help bvn-document-feedback">
                            <div id="bvn-document-help" class="form-text">
                                PDF, JPG, or PNG. Max {{ $documentMaxMb }}MB.
                            </div>
                            <div id="bvn-document-feedback"
                                class="validation-feedback @error('bvn_document') is-visible @enderror"
                                data-validation-message aria-live="polite">
                                @error('bvn_document')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-previous>
                        <i data-feather="arrow-left"></i>
                        Back
                    </button>
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Next
                        <i data-feather="arrow-right"></i>
                    </button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step title="Educational Qualification" :index="2">
                <div class="application-form-panel">
                    <div id="education-documents" data-education-documents>
                        @foreach ($educationRows as $index => $row)
                            <div class="qualification-document" data-education-document>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <h6 class="mb-0" data-document-title>Qualification Document {{ $index + 1 }}
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-remove-document>
                                        <i data-feather="x"></i>
                                        Remove
                                    </button>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            for="education-document-type-{{ $index }}">Document Type</label>
                                        <select id="education-document-type-{{ $index }}"
                                            name="education_documents[{{ $index }}][type]"
                                            class="form-control @error('education_documents.' . $index . '.type') is-invalid @enderror"
                                            required data-document-type>
                                            <option value="">Select document type</option>
                                            @foreach ($educationDocumentTypes as $value => $label)
                                                <option value="{{ $value }}" @selected(($row['type'] ?? '') === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('education_documents.' . $index . '.type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            for="education-document-file-{{ $index }}">Upload Document</label>
                                        <input id="education-document-file-{{ $index }}" type="file"
                                            name="education_documents[{{ $index }}][file]"
                                            class="form-control @error('education_documents.' . $index . '.file') is-invalid @enderror"
                                            accept="{{ $documentAccept }}" required data-document-file data-file-input
                                            data-file-kind="document"
                                            data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                                            data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'
                                            aria-describedby="education-document-file-help-{{ $index }} education-document-file-feedback-{{ $index }}">
                                        <div id="education-document-file-help-{{ $index }}" class="form-text"
                                            data-document-file-help>
                                            PDF, JPG, or PNG. Max {{ $documentMaxMb }}MB.
                                        </div>
                                        <div id="education-document-file-feedback-{{ $index }}"
                                            class="validation-feedback @error('education_documents.' . $index . '.file') is-visible @enderror"
                                            data-validation-message aria-live="polite">
                                            @error('education_documents.' . $index . '.file')
                                                {{ $message }}
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('education_documents')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @enderror

                    <button type="button" class="btn btn-outline-primary" data-add-document>
                        <i data-feather="plus"></i>
                        Add another document
                    </button>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-previous>
                        <i data-feather="arrow-left"></i>
                        Back
                    </button>
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Next
                        <i data-feather="arrow-right"></i>
                    </button>
                </div>
            </x-application-wizard-step>

            <x-application-wizard-step title="Application Summary" :index="3">
                <div class="application-form-panel">
                    <dl class="application-summary-list">
                        <div>
                            <dt>Job</dt>
                            <dd>{{ $job->title }}</dd>
                        </div>
                        <div>
                            <dt>Company</dt>
                            <dd>{{ $job->company }}</dd>
                        </div>
                        <div>
                            <dt>Deadline</dt>
                            <dd>{{ $job->due_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt>Applicant</dt>
                            <dd data-summary-full-name>
                                {{ trim(old('first_name', $user->first_name) . ' ' . old('last_name', $user->last_name)) ?: 'Not provided' }}
                            </dd>
                        </div>
                        <div>
                            <dt>Contact</dt>
                            <dd data-summary-contact>{{ old('phone', $user->phone) ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt>Origin</dt>
                            <dd data-summary-origin>
                                {{ collect([$selectedLga, $selectedState])->filter()->implode(', ') ?:'Not provided' }}
                            </dd>
                        </div>
                        <div>
                            <dt>Qualification Documents</dt>
                            <dd data-summary-documents>{{ $educationRows->count() }}</dd>
                        </div>
                        <div>
                            <dt>Nationality</dt>
                            <dd data-summary-nationality>
                                {{ old('nationality', $user->nationality ?? 'Nigeria') ?: 'Not provided' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-previous>
                        <i data-feather="arrow-left"></i>
                        Back
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="send"></i>
                        Submit Application
                    </button>
                </div>
            </x-application-wizard-step>
        </form>

        <template id="education-document-template">
            <div class="qualification-document" data-education-document>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h6 class="mb-0" data-document-title></h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-document>
                        <i data-feather="x"></i>
                        Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" data-document-type-label>Document Type</label>
                        <select class="form-control" required data-document-type>
                            <option value="">Select document type</option>
                            @foreach ($educationDocumentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" data-document-file-label>Upload Document</label>
                        <input type="file" class="form-control" accept="{{ $documentAccept }}" required
                            data-document-file data-file-input data-file-kind="document"
                            data-max-kb="{{ StoreApplicationFormRequest::DOCUMENT_MAX_KB }}"
                            data-allowed-types='@json(StoreApplicationFormRequest::DOCUMENT_TYPES)'>
                        <div class="form-text" data-document-file-help>PDF, JPG, or PNG. Max {{ $documentMaxMb }}MB.</div>
                        <div class="validation-feedback" data-validation-message aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            (function() {
                const form = document.querySelector('[data-application-wizard]');

                if (!form) {
                    return;
                }

                const steps = Array.from(form.querySelectorAll('[data-application-wizard-step]'));
                const progressItems = Array.from(document.querySelectorAll('[data-application-wizard-progress-item]'));
                const validationSummary = form.querySelector('[data-validation-summary]');
                const profileImageInput = document.getElementById('profile-image-input');
                const profileImagePreview = document.getElementById('profile-image-preview');
                const profilePreviewStatus = document.getElementById('profile-image-preview-status');
                const namePattern = /^[\p{L}\s'-]+$/u;
                const phonePattern = /^\+?[0-9\s().-]{7,20}$/;
                const zipcodePattern = /^[A-Za-z0-9\s-]{3,20}$/;
                let profilePreviewUrl = null;
                let currentStep = 0;

                const formatMegabytes = (kilobytes) => `${Number(kilobytes / 1024).toFixed(kilobytes % 1024 === 0 ? 0 : 1)}MB`;

                const parseAllowedTypes = (field) => {
                    try {
                        return JSON.parse(field.dataset.allowedTypes || '[]');
                    } catch (error) {
                        return [];
                    }
                };

                const extensionFor = (file) => {
                    const parts = file.name.toLowerCase().split('.');

                    return parts.length > 1 ? parts.pop() : '';
                };

                const labelFor = (field) => {
                    if (field.id) {
                        const explicitLabel = form.querySelector(`label[for="${CSS.escape(field.id)}"]`);

                        if (explicitLabel) {
                            return explicitLabel.textContent.trim();
                        }
                    }

                    return field.closest('.mb-3, .upload-files')?.querySelector('label')?.textContent.trim() ||
                        field.name.replace(/[_\[\].]/g, ' ');
                };

                const feedbackFor = (field) => {
                    const wrapper = field.closest('.mb-3, .upload-files') || field.parentElement;
                    let feedback = wrapper?.querySelector('[data-validation-message]');

                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'validation-feedback';
                        feedback.dataset.validationMessage = '';
                        feedback.setAttribute('aria-live', 'polite');
                        field.insertAdjacentElement('afterend', feedback);
                    }

                    if (!feedback.id && field.id) {
                        feedback.id = `${field.id}-feedback`;
                    }

                    if (feedback.id) {
                        const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/)
                            .filter(Boolean));
                        descriptions.add(feedback.id);
                        field.setAttribute('aria-describedby', Array.from(descriptions).join(' '));
                    }

                    return feedback;
                };

                const setFieldError = (field, message) => {
                    const feedback = feedbackFor(field);

                    field.classList.add('is-invalid');
                    field.setAttribute('aria-invalid', 'true');
                    feedback.textContent = message;
                    feedback.classList.add('is-visible');
                };

                const clearFieldError = (field) => {
                    const feedback = feedbackFor(field);

                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');
                    feedback.textContent = '';
                    feedback.classList.remove('is-visible');
                };

                const showValidationSummary = (message) => {
                    if (!validationSummary) {
                        return;
                    }

                    validationSummary.textContent = message || 'Please correct the highlighted fields before continuing.';
                    validationSummary.classList.remove('d-none');
                    validationSummary.focus({ preventScroll: true });
                };

                const hideValidationSummary = () => {
                    validationSummary?.classList.add('d-none');
                };

                const loadImageDimensions = (file) => new Promise((resolve, reject) => {
                    const objectUrl = URL.createObjectURL(file);
                    const image = new Image();

                    image.onload = () => {
                        const dimensions = {
                            width: image.naturalWidth,
                            height: image.naturalHeight,
                        };

                        URL.revokeObjectURL(objectUrl);
                        resolve(dimensions);
                    };

                    image.onerror = () => {
                        URL.revokeObjectURL(objectUrl);
                        reject(new Error('Unable to read image dimensions.'));
                    };

                    image.src = objectUrl;
                });

                const setProfilePreviewStatus = (message, state = '') => {
                    if (!profilePreviewStatus) {
                        return;
                    }

                    profilePreviewStatus.textContent = message;
                    profilePreviewStatus.classList.toggle('is-valid', state === 'valid');
                    profilePreviewStatus.classList.toggle('is-invalid', state === 'invalid');
                };

                const validateFileField = async (field) => {
                    const label = labelFor(field);
                    const file = field.files?.[0] || null;
                    const allowedTypes = parseAllowedTypes(field);
                    const maxKb = Number(field.dataset.maxKb || 0);

                    if (!file) {
                        if (field.required) {
                            setFieldError(field, `${label} is required.`);
                            return false;
                        }

                        clearFieldError(field);
                        return true;
                    }

                    const extension = extensionFor(file);

                    if (allowedTypes.length > 0 && !allowedTypes.includes(extension)) {
                        setFieldError(field, `${label} must be a ${allowedTypes.map((type) => type.toUpperCase()).join(', ')} file.`);
                        return false;
                    }

                    if (maxKb > 0 && file.size > maxKb * 1024) {
                        setFieldError(field, `${label} must not be larger than ${formatMegabytes(maxKb)}.`);
                        return false;
                    }

                    if (field.dataset.fileKind === 'profile-image') {
                        try {
                            const dimensions = await loadImageDimensions(file);
                            const minWidth = Number(field.dataset.minWidth || 0);
                            const minHeight = Number(field.dataset.minHeight || 0);
                            const maxWidth = Number(field.dataset.maxWidth || Infinity);
                            const maxHeight = Number(field.dataset.maxHeight || Infinity);

                            if (
                                dimensions.width < minWidth ||
                                dimensions.height < minHeight ||
                                dimensions.width > maxWidth ||
                                dimensions.height > maxHeight
                            ) {
                                setFieldError(
                                    field,
                                    `${label} must be between ${minWidth}x${minHeight} and ${maxWidth}x${maxHeight} pixels.`
                                );
                                setProfilePreviewStatus('The selected image dimensions are not allowed.', 'invalid');
                                return false;
                            }

                            if (profileImagePreview) {
                                if (profilePreviewUrl) {
                                    URL.revokeObjectURL(profilePreviewUrl);
                                }

                                profilePreviewUrl = URL.createObjectURL(file);
                                profileImagePreview.src = profilePreviewUrl;
                            }

                            setProfilePreviewStatus(
                                `${file.name} selected - ${dimensions.width}x${dimensions.height}px, ${(file.size / 1024 / 1024).toFixed(2)}MB.`,
                                'valid'
                            );
                        } catch (error) {
                            setFieldError(field, 'Choose a valid image that can be previewed.');
                            setProfilePreviewStatus('The selected file could not be previewed.', 'invalid');
                            return false;
                        }
                    }

                    clearFieldError(field);
                    return true;
                };

                const validateStandardField = (field) => {
                    const label = labelFor(field);
                    const value = field.value.trim();

                    if (field.required && value === '') {
                        setFieldError(field, `${label} is required.`);
                        return false;
                    }

                    if (value === '') {
                        clearFieldError(field);
                        return true;
                    }

                    if (field.name === 'first_name' || field.name === 'middle_name' || field.name === 'last_name' || field.name === 'nationality') {
                        if (!namePattern.test(value)) {
                            setFieldError(field, `${label} may only contain letters, spaces, hyphens, and apostrophes.`);
                            return false;
                        }
                    }

                    if (field.name === 'phone' && !phonePattern.test(value)) {
                        setFieldError(field, 'Enter a valid phone number using 7 to 20 digits, with an optional leading plus sign.');
                        return false;
                    }

                    if (field.name === 'zipcode' && !zipcodePattern.test(value)) {
                        setFieldError(field, 'The zipcode may only contain letters, numbers, spaces, and hyphens.');
                        return false;
                    }

                    if (!field.checkValidity()) {
                        setFieldError(field, field.validationMessage || `${label} is invalid.`);
                        return false;
                    }

                    clearFieldError(field);
                    return true;
                };

                const validateField = async (field) => {
                    if (field.disabled) {
                        return true;
                    }

                    return field.type === 'file' ? validateFileField(field) : validateStandardField(field);
                };

                const validateFields = async (fields) => {
                    let firstInvalidField = null;

                    for (const field of fields) {
                        const isValid = await validateField(field);

                        if (!isValid && !firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }

                    if (firstInvalidField) {
                        firstInvalidField.focus({ preventScroll: false });
                        return false;
                    }

                    hideValidationSummary();
                    return true;
                };

                const showStep = (index) => {
                    currentStep = Math.max(0, Math.min(index, steps.length - 1));

                    steps.forEach((step, stepIndex) => {
                        step.hidden = stepIndex !== currentStep;
                    });

                    progressItems.forEach((item, itemIndex) => {
                        item.classList.toggle('is-active', itemIndex === currentStep);
                        item.classList.toggle('is-complete', itemIndex < currentStep);
                    });
                };

                const validateCurrentStep = async () => {
                    const fields = Array.from(steps[currentStep].querySelectorAll('input, select, textarea'));
                    const isValid = await validateFields(fields);

                    if (!isValid) {
                        showValidationSummary('Please correct the highlighted fields in this step before continuing.');
                    }

                    return isValid;
                };

                const stateSelect = form.querySelector('[data-state-of-origin]');
                const lgaSelect = form.querySelector('[data-local-government-area]');

                const resetLgaOptions = (placeholder = 'Select LGA') => {
                    if (!lgaSelect) {
                        return;
                    }

                    lgaSelect.innerHTML = '';
                    lgaSelect.append(new Option(placeholder, ''));
                };

                const populateLgas = async (selectedLga = '') => {
                    if (!stateSelect || !lgaSelect) {
                        return;
                    }

                    const selectedOption = stateSelect.selectedOptions[0];
                    const lgaUrl = selectedOption?.dataset.lgaUrl || '';

                    resetLgaOptions(lgaUrl ? 'Loading LGAs...' : 'Select state first');
                    lgaSelect.disabled = true;

                    if (!lgaUrl) {
                        return;
                    }

                    try {
                        const response = await fetch(lgaUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load LGAs.');
                        }

                        const payload = await response.json();
                        const localGovernmentAreas = Array.isArray(payload.data) ? payload.data : [];

                        resetLgaOptions(localGovernmentAreas.length > 0 ? 'Select LGA' : 'No LGAs available');

                        localGovernmentAreas.forEach((lga) => {
                            const option = new Option(lga.name, lga.name, false, lga.name === selectedLga);
                            option.dataset.lgaId = lga.id;
                            option.dataset.lgaSlug = lga.slug;
                            lgaSelect.append(option);
                        });

                        lgaSelect.disabled = localGovernmentAreas.length === 0;
                    } catch (error) {
                        resetLgaOptions('Unable to load LGAs');
                        lgaSelect.disabled = true;
                        console.error(error);
                    } finally {
                        updateSummary();
                    }
                };

                if (stateSelect && lgaSelect) {
                    populateLgas(lgaSelect.dataset.selectedLga || lgaSelect.value || '');

                    stateSelect.addEventListener('change', () => {
                        lgaSelect.dataset.selectedLga = '';
                        populateLgas();
                        updateSummary();
                    });

                    lgaSelect.addEventListener('change', updateSummary);
                }

                const documentsContainer = form.querySelector('[data-education-documents]');
                const addDocumentButton = form.querySelector('[data-add-document]');
                const documentTemplate = document.getElementById('education-document-template');

                const refreshDocumentRows = () => {
                    if (!documentsContainer) {
                        return;
                    }

                    const rows = Array.from(documentsContainer.querySelectorAll('[data-education-document]'));

                    rows.forEach((row, index) => {
                        const number = index + 1;
                        const type = row.querySelector('[data-document-type]');
                        const file = row.querySelector('[data-document-file]');
                        const typeLabel = row.querySelector('[data-document-type-label]');
                        const fileLabel = row.querySelector('[data-document-file-label]');
                        const fileHelp = row.querySelector('[data-document-file-help]');
                        const fileFeedback = row.querySelector('[data-validation-message]');
                        const removeButton = row.querySelector('[data-remove-document]');
                        const title = row.querySelector('[data-document-title]');

                        if (title) {
                            title.textContent = `Qualification Document ${number}`;
                        }

                        if (type) {
                            type.name = `education_documents[${index}][type]`;
                            type.id = `education-document-type-${index}`;
                        }

                        if (file) {
                            file.name = `education_documents[${index}][file]`;
                            file.id = `education-document-file-${index}`;
                        }

                        if (typeLabel) {
                            typeLabel.setAttribute('for', `education-document-type-${index}`);
                        }

                        if (fileLabel) {
                            fileLabel.setAttribute('for', `education-document-file-${index}`);
                        }

                        if (fileHelp) {
                            fileHelp.id = `education-document-file-help-${index}`;
                        }

                        if (fileFeedback) {
                            fileFeedback.id = `education-document-file-feedback-${index}`;
                        }

                        if (file && fileHelp && fileFeedback) {
                            file.setAttribute('aria-describedby', `${fileHelp.id} ${fileFeedback.id}`);
                        }

                        if (removeButton) {
                            removeButton.hidden = rows.length === 1;
                        }
                    });

                    updateSummary();

                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                };

                addDocumentButton?.addEventListener('click', () => {
                    if (!documentTemplate || !documentsContainer) {
                        return;
                    }

                    const currentRows = documentsContainer.querySelectorAll('[data-education-document]').length;

                    if (currentRows >= 10) {
                        return;
                    }

                    documentsContainer.append(documentTemplate.content.firstElementChild.cloneNode(true));
                    refreshDocumentRows();
                    documentsContainer.lastElementChild?.querySelector('select')?.focus();
                });

                documentsContainer?.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-document]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = documentsContainer.querySelectorAll('[data-education-document]');

                    if (rows.length <= 1) {
                        return;
                    }

                    removeButton.closest('[data-education-document]')?.remove();
                    refreshDocumentRows();
                });

                function fieldValue(name) {
                    return form.elements[name]?.value?.trim() || '';
                }

                function updateSummary() {
                    const fullName = [fieldValue('first_name'), fieldValue('last_name')].filter(Boolean).join(' ');
                    const origin = [fieldValue('local_government_area'), fieldValue('state_of_origin')].filter(Boolean)
                        .join(', ');
                    const documentCount = documentsContainer?.querySelectorAll('[data-education-document]').length || 0;

                    const summary = {
                        '[data-summary-full-name]': fullName,
                        '[data-summary-contact]': fieldValue('phone'),
                        '[data-summary-origin]': origin,
                        '[data-summary-nationality]': fieldValue('nationality'),
                        '[data-summary-documents]': String(documentCount),
                    };

                    Object.entries(summary).forEach(([selector, value]) => {
                        const target = document.querySelector(selector);

                        if (target) {
                            target.textContent = value || 'Not provided';
                        }
                    });
                }

                form.addEventListener('click', async (event) => {
                    if (event.target.closest('[data-wizard-next]')) {
                        if (await validateCurrentStep()) {
                            showStep(currentStep + 1);
                            updateSummary();
                        }
                    }

                    if (event.target.closest('[data-wizard-previous]')) {
                        showStep(currentStep - 1);
                        updateSummary();
                    }
                });

                form.addEventListener('input', (event) => {
                    delete form.dataset.validationPassed;
                    updateSummary();

                    if (event.target.matches('input:not([type="file"]), select, textarea')) {
                        validateField(event.target);
                    }
                });

                form.addEventListener('change', async (event) => {
                    delete form.dataset.validationPassed;
                    updateSummary();

                    if (event.target.matches('input, select, textarea')) {
                        await validateField(event.target);
                    }
                });

                profileImageInput?.addEventListener('change', async () => {
                    if (!profileImageInput.files?.length) {
                        setProfilePreviewStatus('Choose a photo to preview it before submission.');
                        return;
                    }

                    await validateFileField(profileImageInput);
                });

                form.addEventListener('submit', async (event) => {
                    if (form.dataset.confirmed === 'true' || form.dataset.validationPassed === 'true') {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    const allFields = steps.flatMap((step) => Array.from(step.querySelectorAll('input, select, textarea')));
                    const isValid = await validateFields(allFields);

                    if (!isValid) {
                        const invalidStepIndex = steps.findIndex((step) => step.querySelector('.is-invalid'));

                        if (invalidStepIndex >= 0) {
                            showStep(invalidStepIndex);
                        }

                        showValidationSummary('Please correct the highlighted fields before submitting your application.');
                        return;
                    }

                    form.dataset.validationPassed = 'true';
                    form.requestSubmit();
                });

                refreshDocumentRows();
                updateSummary();
                showStep(0);
            })();
        </script>
    @endpush
</x-layout>
