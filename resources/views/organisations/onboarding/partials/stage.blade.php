<div class="nw-onboarding-stage" data-organization-onboarding data-step="{{ $currentStep }}"
    data-show-url="{{ route('organisations.onboarding.show') }}"
    data-validation-title="{{ __('Please review the highlighted fields.') }}"
    data-request-error="{{ __('We could not save this step. Your entries are still here; please try again.') }}"
    data-saving-message="{{ __('Saving your progress…') }}"
    x-data="organizationOnboarding(@js($onboardingInitialState))"
    x-on:organization-onboarding-retry-invitees.window="replaceInvitees($event.detail)">
    <aside class="nw-onboarding-visual" aria-labelledby="onboarding-visual-title">
        <div class="nw-onboarding-visual__copy">
            <h1 id="onboarding-visual-title" data-onboarding-story-title>{{ $story['title'] }}</h1>
            <p data-onboarding-story-description>{{ $story['description'] }}</p>
        </div>
        <img class="nw-onboarding-illustration" src="{{ asset($story['illustration']) }}" width="720" height="520"
            alt="" fetchpriority="high" decoding="async">
        <div class="nw-onboarding-visual__caption">
            <span aria-hidden="true"><x-icon name="check" /></span>
            <span>{{ $story['caption'] }}</span>
        </div>
    </aside>

    <section class="nw-onboarding-workflow" aria-label="{{ __('Organization setup steps') }}">
        <div class="nw-onboarding" data-onboarding-workflow>
            <p class="nw-onboarding-pending" data-onboarding-pending role="status" aria-live="polite"></p>

            <div class="nw-onboarding-feedback" data-onboarding-feedback role="alert" tabindex="-1" hidden>
                <strong data-onboarding-feedback-title></strong>
                <ul data-onboarding-feedback-list></ul>
            </div>

            @if ($errors->any())
                <div class="nw-alert nw-alert--error nw-onboarding-server-errors" role="alert" tabindex="-1">
                    <strong>{{ __('Please review the highlighted fields.') }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($currentStep === 'welcome')
                <section class="nw-onboarding-card nw-onboarding-card--step" aria-labelledby="onboarding-welcome-title">
                    <h2 id="onboarding-welcome-title" data-onboarding-heading tabindex="-1">
                        {{ __('Welcome to your hiring workspace, :name', ['name' => auth()->user()->first_name]) }}
                    </h2>
                    <p>{{ __('Tell us how you work so we can tailor setup suggestions.') }}</p>
                    <form method="POST" action="{{ route('organisations.onboarding.welcome') }}" class="nw-form-stack">
                        @csrf
                        <fieldset class="nw-onboarding-focus" aria-describedby="onboarding-focus-hint">
                            <legend class="nw-label">{{ __('How will you use Marthire?') }} <span aria-hidden="true">*</span></legend>
                            <p id="onboarding-focus-hint" class="nw-hint">{{ __('Choose the role that best matches your day-to-day work.') }}</p>
                            @foreach ($focusOptions as $value => $label)
                                <label class="nw-onboarding-choice">
                                    <input type="radio" name="recruitment_focus" value="{{ $value }}"
                                        @checked(old('recruitment_focus', $progress->recruitment_focus) === $value) required>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <x-input-error :messages="$errors->get('recruitment_focus')" />
                        <div class="nw-onboarding__actions">
                            <button type="submit" class="nw-button nw-button--primary">
                                {{ __('Continue') }} <x-icon name="arrow-right" />
                            </button>
                        </div>
                    </form>
                </section>
            @elseif ($currentStep === 'profile')
                <section class="nw-onboarding-card nw-onboarding-card--step" aria-labelledby="onboarding-profile-title">
                    <h2 id="onboarding-profile-title" data-onboarding-heading tabindex="-1">{{ __('Create your organization profile') }}</h2>
                    <p>{{ __('Add the name candidates will recognize. You can add more company details now or whenever you’re ready.') }}</p>
                    <form id="onboarding-profile-form" method="POST" action="{{ route('organisations.onboarding.profile') }}"
                        enctype="multipart/form-data" class="nw-form-stack">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="version" value="{{ $settings->version }}">
                        <div class="nw-field">
                            <label class="nw-label" for="onboarding-name">{{ __('Organization name') }} <span aria-hidden="true">*</span></label>
                            <input class="nw-input" id="onboarding-name" name="name" maxlength="255" required x-model="companyName" autocomplete="organization">
                            <x-input-error :messages="$errors->get('name')" />
                        </div>
                        <details class="nw-onboarding-optional-details" @if (old('industry', $settings->industry) || old('organization_size', $settings->organization_size) || old('about', $settings->about) || $settings->companyLogoUrl() || $errors->hasAny(['industry', 'organization_size', 'about', 'company_logo', 'remove_company_logo'])) open @endif>
                            <summary>{{ __('Add company details (optional)') }}</summary>
                            <div class="nw-form-grid">
                            <div class="nw-field">
                                <label class="nw-label" for="onboarding-industry">{{ __('Industry (optional)') }}</label>
                                <input class="nw-input" id="onboarding-industry" name="industry" maxlength="100" x-model="industry" placeholder="{{ __('e.g. Technology') }}">
                                <x-input-error :messages="$errors->get('industry')" />
                            </div>
                            <div class="nw-field nw-field--full">
                                <label class="nw-label" for="onboarding-size">{{ __('Organization size (optional)') }}</label>
                                <select class="nw-input" id="onboarding-size" name="organization_size">
                                    <option value="">{{ __('Choose a range') }}</option>
                                    @foreach (['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'] as $size)
                                        <option value="{{ $size }}" @selected(old('organization_size', $settings->organization_size) === $size)>
                                            {{ $size }} {{ __('employees') }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('organization_size')" />
                            </div>
                            <div class="nw-field nw-field--full">
                                <label class="nw-label" for="onboarding-about">{{ __('About your organization (optional)') }}</label>
                                <textarea class="nw-input" id="onboarding-about" name="about" rows="4" maxlength="2000" x-model="about"></textarea>
                                <x-input-error :messages="$errors->get('about')" />
                            </div>
                            <div class="nw-field nw-field--full nw-organization-image-upload">
                                <label class="nw-label" for="onboarding-logo">{{ __('Organization logo (optional)') }}</label>
                                <div class="nw-avatar-upload nw-avatar-upload--apex nw-organization-image-upload__avatar">
                                    <span class="nw-avatar-upload__preview" aria-hidden="true">
                                        <template x-if="previewUrl">
                                            <img :src="previewUrl" alt="" class="nw-avatar-upload__image" x-on:error="imagePreviewFailed($refs.imageInput)">
                                        </template>
                                        <x-icon name="upload" x-show="!previewUrl" />
                                    </span>
                                    <div class="nw-avatar-upload__details">
                                        <label for="onboarding-logo" class="nw-button nw-button--outline nw-button--small">
                                            <x-icon name="upload" /> {{ __('Upload company logo') }}
                                        </label>
                                        <button type="button" class="nw-link nw-link--small" x-show="hasSelectedImage" x-cloak
                                            x-on:click="clearSelectedImage($refs.imageInput)">
                                            {{ __('Undo change') }}
                                        </button>
                                        <p class="nw-form-hint" id="onboarding-logo-hint">{{ __('JPG, PNG, or WebP. Up to 2 MB. Choose another file to replace the preview.') }}</p>
                                        <p class="nw-avatar-upload__filename" x-show="hasSelectedImage" x-text="selectedImageName" x-cloak></p>
                                        <input id="onboarding-logo" name="company_logo" x-ref="imageInput" class="nw-sr-only" type="file"
                                            accept="image/png,image/jpeg,image/webp" aria-describedby="onboarding-logo-hint onboarding-logo-error"
                                            x-on:change="previewImage($event)">
                                    </div>
                                </div>
                                <p class="nw-field__error" id="onboarding-logo-error" x-show="imageError" x-cloak x-text="imageError" role="alert"></p>
                                <button class="nw-button nw-button--outline nw-button--small nw-organization-image-upload__remove"
                                    type="button" x-show="hasSelectedImage" x-cloak x-on:click="clearSelectedImage($refs.imageInput)">
                                    {{ __('Remove selected image') }}
                                </button>
                                @if ($settings->companyLogoUrl())
                                    <label class="nw-checkbox-label nw-settings-company-logo__remove">
                                        <input class="nw-checkbox" type="checkbox" name="remove_company_logo" value="1"
                                            x-model="removeSavedImage" x-on:change="toggleSavedImageRemoval($event, $refs.imageInput)"
                                            x-bind:disabled="hasSelectedImage">
                                        <span>{{ __('Remove current logo') }}</span>
                                    </label>
                                @endif
                                <x-input-error :messages="$errors->get('company_logo')" />
                                <x-input-error :messages="$errors->get('remove_company_logo')" />
                            </div>
                            </div>
                        </details>
                    </form>
                    <div class="nw-onboarding-actions-row">
                        <form method="POST" action="{{ route('organisations.onboarding.back') }}" class="nw-onboarding-actions-row__back">
                            @csrf
                            <button type="submit" class="nw-button nw-button--outline"><x-icon name="arrow-left" /> {{ __('Back') }}</button>
                        </form>
                        <div class="nw-onboarding-actions-row__right">
                            <form method="POST" action="{{ route('organisations.onboarding.skip', 'profile') }}">
                                @csrf
                                <button type="submit" class="nw-button nw-button--outline">{{ __('Skip') }}</button>
                            </form>
                            <button type="submit" form="onboarding-profile-form" class="nw-button nw-button--primary">
                                {{ __('Continue') }} <x-icon name="arrow-right" />
                            </button>
                        </div>
                    </div>
                </section>
            @elseif ($currentStep === 'team')
                <section class="nw-onboarding-card nw-onboarding-card--step" aria-labelledby="onboarding-team-title">
                    <h2 id="onboarding-team-title" data-onboarding-heading tabindex="-1">{{ __('Bring your hiring team in') }}</h2>
                    <p>{{ __('Invite teammates with the roles they need. You can skip this step and invite them later.') }}</p>

                    <ul class="nw-onboarding-invite-results" data-invite-results aria-live="polite" @if (!session('inviteResults')) hidden @endif>
                        @foreach (session('inviteResults', []) as $result)
                            <li class="{{ $result['success'] ? 'is-success' : 'is-error' }}">
                                <strong>{{ $result['email'] }}</strong><span>{{ $result['message'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <form id="onboarding-team-form" method="POST" action="{{ route('organisations.onboarding.invitations') }}"
                        class="nw-form-stack" x-ref="inviteForm">
                        @csrf
                        <div class="nw-onboarding-invitees">
                            <template x-for="(invitee, index) in invitees" :key="invitee.key">
                                <div class="nw-onboarding-invitee">
                                    <div class="nw-field">
                                        <label class="nw-label" :for="'invite-email-' + index">{{ __('Email address') }}</label>
                                        <input class="nw-input" type="email" :id="'invite-email-' + index"
                                            :name="'invitees[' + index + '][email]'" x-model="invitee.email"
                                            autocomplete="email" required>
                                    </div>
                                    <div class="nw-field">
                                        <label class="nw-label" :for="'invite-role-' + index">{{ __('Organization role') }}</label>
                                        <select class="nw-input" :id="'invite-role-' + index"
                                            :name="'invitees[' + index + '][role]'" x-model="invitee.role" required>
                                            @foreach ($roleOptions as $role)
                                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="nw-button nw-button--outline nw-button--small"
                                        @click="removeInvitee(index)" :disabled="invitees.length === 1"
                                        aria-label="{{ __('Remove invitation') }}"><x-icon name="close" /></button>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="nw-link nw-onboarding-add" @click="addInvitee()">
                            + {{ __('Add another teammate') }}
                        </button>
                        <x-input-error :messages="$errors->get('invitees')" />
                    </form>

                    <div class="nw-onboarding-actions-row">
                        <form method="POST" action="{{ route('organisations.onboarding.back') }}" class="nw-onboarding-actions-row__back">
                            @csrf
                            <button type="submit" class="nw-button nw-button--outline"><x-icon name="arrow-left" /> {{ __('Back') }}</button>
                        </form>
                        <div class="nw-onboarding-actions-row__right">
                            <form method="POST" action="{{ route('organisations.onboarding.skip', 'team') }}">
                                @csrf
                                <button type="submit" class="nw-button nw-button--outline">{{ __('Skip') }}</button>
                            </form>
                            <button type="submit" form="onboarding-team-form" class="nw-button nw-button--primary">
                                {{ __('Continue') }} <x-icon name="arrow-right" />
                            </button>
                        </div>
                    </div>
                </section>
            @else
                <section class="nw-onboarding-card nw-onboarding-card--finish" aria-labelledby="onboarding-finish-title">
                    <h2 id="onboarding-finish-title" data-onboarding-heading tabindex="-1">{{ __('Your hiring workspace is ready') }}</h2>
                    <p>{{ __('Your organization context and team permissions are in place. Choose where you want to go next.') }}</p>
                    <div class="nw-onboarding-finish-summary" aria-label="{{ __('Workspace summary') }}">
                        <span class="nw-onboarding-finish-icon" aria-hidden="true"><x-icon name="check" /></span>
                        <span><strong>{{ $organization->name }}</strong><small>{{ __('Organization workspace') }}</small></span>
                    </div>
                    <div class="nw-onboarding-actions-row nw-onboarding-actions-row--finish">
                        <form method="POST" action="{{ route('organisations.onboarding.back') }}" class="nw-onboarding-actions-row__back">
                            @csrf
                            <button class="nw-button nw-button--outline" type="submit"><x-icon name="arrow-left" /> {{ __('Back') }}</button>
                        </form>
                        <form method="POST" action="{{ route('organisations.onboarding.finish') }}" class="nw-onboarding-actions-row__right">
                            @csrf
                            @if ($canCreateJob)
                                <button class="nw-button nw-button--primary" type="submit" name="next_action" value="job">
                                    {{ __('Create Your First Job') }} <x-icon name="arrow-right" />
                                </button>
                            @endif
                            <button class="nw-button nw-button--outline" type="submit" name="next_action" value="dashboard">
                                {{ __('Go to Dashboard') }}
                            </button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </section>
</div>
