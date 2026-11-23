@use('App\Models\Job')
@use('App\Support\JobDescription')

@php
    $isEdit = $job !== null;
    $initialScreeningQuestions = old('screening_questions', $screeningQuestions->map(fn ($question) => [
        'id' => $question->id,
        'prompt' => $question->prompt,
        'type' => $question->type->value,
        'required' => true,
        'options' => $question->options ?? [],
    ])->all());
@endphp
<form method="POST"
      action="{{ $isEdit ? route('organisations.jobs.update', $job) : route('organisations.jobs.store') }}"
      enctype="multipart/form-data"
      class="nw-form-stack"
      x-data="jobForm({ maxLength: {{ JobDescription::MAX_LENGTH }}, storageKey: '{{ $isEdit ? 'job-edit-'.$job->slug : 'job-create' }}', screeningQuestions: @js($initialScreeningQuestions), requiresIdentityDocument: @js((bool) old('requires_identity_document', $job?->requires_identity_document ?? false)) })"
      x-on:submit="allowSubmit()">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="title" :value="__('Job title')" />
            <x-text-input id="title" name="title" :value="old('title', $job?->title)" required autofocus />
            <x-input-error :messages="$errors->get('title')" />
        </div>
        <div>
            <x-input-label for="company" :value="__('Company')" />
            <x-text-input id="company" name="company" :value="$isEdit ? old('company', $job?->company) : $organization->name" :readonly="!$isEdit" required />
            <x-input-error :messages="$errors->get('company')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="category" :value="__('Category')" />
            <x-text-input id="category" name="category" list="job-categories" :value="old('category', $job?->category)" />
            <datalist id="job-categories">
                @foreach ($categories as $categoryOption)<option value="{{ $categoryOption }}"></option>@endforeach
            </datalist>
            <x-input-error :messages="$errors->get('category')" />
        </div>
        <div>
            <x-input-label for="country_code" :value="__('Country')" />
            <select id="country_code" name="country_code" class="nw-select" required>
                <option value="">{{ __('Select a country') }}</option>
                @foreach ($countries as $countryOption)
                    <option value="{{ $countryOption['value'] }}" @selected(old('country_code', $job?->country_code) === $countryOption['value'])>{{ $countryOption['label'] }}</option>
                @endforeach
            </select>
            @if (empty($countries))
                <p class="nw-form-hint">{{ __('Country options are temporarily unavailable. Please try again later.') }}</p>
            @endif
            <x-input-error :messages="$errors->get('country_code')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="employment_type" :value="__('Employment type')" />
            <select id="employment_type" name="employment_type" class="nw-select" required>
                @foreach ($employmentTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('employment_type', $job?->employment_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employment_type')" />
        </div>
        <div>
            <x-input-label for="workplace_type" :value="__('Workplace type')" />
            <select id="workplace_type" name="workplace_type" class="nw-select" required>
                <option value="">{{ __('Select a workplace type') }}</option>
                @foreach (Job::workplaceTypeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('workplace_type', $job?->workplace_type) === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('workplace_type')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div class="nw-organization-image-upload nw-job-logo-upload nw-job-logo-upload--full" x-data="organizationImageUpload({ savedUrl: @js($job?->logoUrl()), removeSavedImage: @js((bool) old('remove_logo')), maxBytes: 2097152, allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], allowedExtensions: ['jpg', 'jpeg', 'png', 'webp', 'gif'], messages: { invalidType: @js(__('Choose a JPG, PNG, WebP, or GIF image.')), tooLarge: @js(__('Choose an image smaller than 2 MB.')), previewFailed: @js(__('This image could not be previewed. Choose another file.')) } })">
            <x-input-label for="logo" :value="__('Logo (optional)')" />
            <div class="nw-avatar-upload nw-avatar-upload--apex nw-job-logo-upload__control">
                <span class="nw-avatar-upload__preview" aria-hidden="true">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="" class="nw-avatar-upload__image" x-on:error="imagePreviewFailed($refs.imageInput)">
                    </template>
                    <x-icon name="upload" x-show="!previewUrl" />
                </span>
                <div class="nw-avatar-upload__details">
                    <label for="logo" class="nw-button nw-button--outline nw-button--small">
                        <x-icon name="upload" /> {{ __('Upload logo') }}
                    </label>
                    <button type="button" class="nw-link nw-link--small" x-show="hasSelectedImage" x-cloak x-on:click="clearSelectedImage($refs.imageInput)">
                        {{ __('Undo change') }}
                    </button>
                    <p class="nw-form-hint" id="job-logo-hint">{{ __('JPG, PNG, WebP, or GIF. Up to 2 MB.') }}</p>
                    <p class="nw-avatar-upload__filename" x-show="hasSelectedImage" x-text="selectedImageName" x-cloak></p>
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="nw-sr-only" x-ref="imageInput"
                        x-on:change="previewImage($event)" aria-describedby="job-logo-hint job-logo-error" x-bind:aria-invalid="imageError ? 'true' : 'false'">
                </div>
            </div>
            <p class="nw-field__error" id="job-logo-error" x-show="imageError" x-cloak x-text="imageError" role="alert"></p>
            @if ($isEdit && $job?->logoUrl())
                <label class="nw-checkbox-label nw-settings-company-logo__remove">
                    <input class="nw-checkbox" type="checkbox" name="remove_logo" value="1" x-model="removeSavedImage" x-on:change="toggleSavedImageRemoval($event, $refs.imageInput)" x-bind:disabled="hasSelectedImage">
                    <span>{{ __('Remove current logo') }}</span>
                </label>
            @endif
            <x-input-error :messages="$errors->get('logo')" />
            <x-input-error :messages="$errors->get('remove_logo')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="start_date" :value="__('Start date')" />
            <x-text-input id="start_date" name="start_date" type="date" :value="old('start_date', $job?->start_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('start_date')" />
        </div>
        <div>
            <x-input-label for="due_date" :value="__('Application deadline')" />
            <x-text-input id="due_date" name="due_date" type="date" :value="old('due_date', $job?->due_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('due_date')" />
        </div>
    </div>

    <div class="nw-editor-field">
        <x-input-label for="description" :value="__('Job description')" />
        <div x-ref="editorShell">
            <div data-job-editor x-ignore class="nw-editor"></div>
        </div>
        <textarea id="description" name="description" x-ref="input" class="nw-input nw-editor__fallback" rows="10">{{ old('description', $job?->description) }}</textarea>
        <p class="nw-form-hint"><span x-text="count">0</span> / {{ JobDescription::MAX_LENGTH }} {{ __('characters') }}</p>
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <section class="nw-form-section nw-job-identity-requirement" aria-labelledby="identity-requirement-heading">
        <div>
            <h2 id="identity-requirement-heading">{{ __('Government identification') }}</h2>
            <p class="nw-form-hint">{{ __('Applicants must submit a government-issued identification document. Add a role-specific explanation when this vacancy has additional verification requirements.') }}</p>
        </div>
        <label class="nw-checkbox-label">
            <input class="nw-checkbox" type="checkbox" name="requires_identity_document" value="1" x-model="requiresIdentityDocument">
            <span>{{ __('This role has additional identity verification requirements') }}</span>
        </label>
        <div x-show="requiresIdentityDocument" x-cloak>
            <label class="nw-label" for="identity_requirement_reason">{{ __('Explain the additional verification need') }} <span class="nw-required" aria-hidden="true">*</span></label>
            <textarea class="nw-input nw-textarea" id="identity_requirement_reason" name="identity_requirement_reason" rows="3" minlength="10" maxlength="255" x-bind:required="requiresIdentityDocument" x-bind:disabled="!requiresIdentityDocument">{{ old('identity_requirement_reason', $job?->identity_requirement_reason) }}</textarea>
            <p class="nw-form-hint">{{ __('This explanation is shown to applicants before they submit their identification document.') }}</p>
            <x-input-error :messages="$errors->get('identity_requirement_reason')" />
        </div>
    </section>

    <section class="nw-form-section nw-screening-builder" aria-labelledby="screening-questions-heading">
        <div class="nw-screening-builder__heading">
            <div>
                <h2 id="screening-questions-heading">{{ __('Screening questions') }}</h2>
                <p class="nw-form-hint">{{ __('Ask focused questions that help assess this role. Applicants will be required to answer every question you add.') }}</p>
            </div>
            <span class="nw-screening-builder__count" x-text="`${screeningQuestions.length} / 15`"></span>
        </div>
        <input type="hidden" name="screening_questions_submitted" value="1">
        <div class="nw-screening-builder__list">
            <template x-for="(question, index) in screeningQuestions" :key="question.localKey">
                <article class="nw-screening-question">
                    <input type="hidden" x-bind:name="`screening_questions[${index}][id]`" x-bind:value="question.id || ''">
                    <div class="nw-screening-question__head">
                        <strong x-text="`Question ${index + 1}`"></strong>
                        <button type="button" class="nw-link nw-link--small" x-on:click="removeScreeningQuestion(index)">{{ __('Remove question') }}</button>
                    </div>
                    <div class="nw-form-row nw-form-row--fields">
                        <div>
                            <label class="nw-label" x-bind:for="`screening-question-${question.localKey}`">{{ __('Question prompt') }}</label>
                            <input class="nw-input" type="text" x-bind:id="`screening-question-${question.localKey}`" x-bind:name="`screening_questions[${index}][prompt]`" x-model="question.prompt" minlength="5" maxlength="500" required placeholder="{{ __('Example: Which relevant experience would you bring to this role?') }}">
                        </div>
                        <div>
                            <label class="nw-label" x-bind:for="`screening-type-${question.localKey}`">{{ __('Answer type') }}</label>
                            <select class="nw-select" x-bind:id="`screening-type-${question.localKey}`" x-bind:name="`screening_questions[${index}][type]`" x-model="question.type" x-on:change="ensureScreeningOptions(question)" required>
                                @foreach ($screeningQuestionTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <template x-if="['single_select', 'multi_select'].includes(question.type)">
                        <div class="nw-screening-question__options">
                            <span class="nw-label">{{ __('Answer options') }}</span>
                            <template x-for="(option, optionIndex) in question.options" :key="`${question.localKey}-option-${optionIndex}`">
                                <div class="nw-screening-option">
                                    <input class="nw-input" type="text" x-bind:name="`screening_questions[${index}][options][${optionIndex}]`" x-model="question.options[optionIndex]" maxlength="100" required placeholder="{{ __('Option') }}">
                                    <button type="button" class="nw-icon-button" x-on:click="removeScreeningOption(question, optionIndex)" x-bind:disabled="question.options.length <= 2" aria-label="{{ __('Remove answer option') }}"><x-icon name="trash" /></button>
                                </div>
                            </template>
                            <button type="button" class="nw-link nw-link--small" x-on:click="addScreeningOption(question)" x-bind:disabled="question.options.length >= 10">{{ __('Add option') }}</button>
                        </div>
                    </template>
                    <p class="nw-form-hint nw-screening-question__required">{{ __('Required for applicants') }}</p>
                </article>
            </template>
            <p class="nw-screening-builder__empty" x-show="screeningQuestions.length === 0">{{ __('No screening questions yet. Add questions that are specific to this vacancy.') }}</p>
        </div>
        <button type="button" class="nw-button nw-button--outline nw-button--small" x-on:click="addScreeningQuestion()" x-bind:disabled="screeningQuestions.length >= 15"><x-icon name="plus" /> {{ __('Add screening question') }}</button>
        @foreach ($errors->getMessages() as $key => $messages)
            @if (str_starts_with($key, 'screening_questions'))
                <p class="nw-field__error" role="alert">{{ $messages[0] }}</p>
            @endif
        @endforeach
    </section>

    <x-form.actions>
        <x-slot:cancel>
            <button type="submit" name="action" value="save_draft" class="nw-button nw-button--outline">{{ __('Save as draft') }}</button>
        </x-slot:cancel>
        <button type="submit" name="action" value="publish" class="nw-button nw-button--primary">{{ __('Publish') }}</button>
    </x-form.actions>
</form>
