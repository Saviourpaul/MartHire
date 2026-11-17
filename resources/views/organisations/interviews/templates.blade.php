@use('App\Enums\InterviewTemplateType')
@use('Illuminate\Support\Js')
@use('Illuminate\Support\Str')

<x-app-layout>
    <x-slot name="title">{{ __('Candidate email studio') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Candidate email studio') }}</h1>
        <p>{{ __('Review and personalize the interview emails your candidates receive.') }}</p>
    </x-slot>

    @php
        $studioState = [
            'templates' => $templatePreviewData,
            'sample' => $previewValues,
            'editor' => $editorValues,
            'placeholders' => $placeholderTokens,
            'selected' => $selectedTemplateType,
            'organizationName' => $organization->name,
            'requiredPlaceholder' => $selectedTemplate && isset($requiredPlaceholderTokens[$selectedTemplate->type->value])
                ? $requiredPlaceholderTokens[$selectedTemplate->type->value]
                : null,
        ];
    @endphp

    <div class="nw-email-studio" x-data='organizationEmailTemplateStudio({{ Js::from($studioState)->toHtml() }})'>
        @if (session('success'))
            <p class="nw-notice nw-notice--success" role="status">{{ session('success') }}</p>
        @endif

        @if (! $selectedTemplate)
            <section class="nw-email-studio__intro" aria-labelledby="email-studio-intro-heading">
                <div>
                    <h2 id="email-studio-intro-heading">{{ __('Every interview message, ready to make your own.') }}</h2>
                    <p>{{ __('Choose a message to edit its subject and wording, then preview exactly how your sample email reads.') }}</p>
                </div>
                <span class="nw-email-studio__count">{{ trans_choice(':count template|:count templates', $templates->count(), ['count' => $templates->count()]) }}</span>
            </section>

            <section class="nw-email-studio__library" aria-labelledby="email-studio-library-heading">
                <div class="nw-email-studio__section-heading">
                    <div>
                        <h2 id="email-studio-library-heading">{{ __('Interview messages') }}</h2>
                        <p>{{ __('Organization templates override the system version when enabled.') }}</p>
                    </div>
                </div>

                <div class="nw-email-studio__grid">
                    @foreach ($templates as $template)
                        @php
                            $typeValue = $template->type->value;
                            $templateStatus = $template->is_system
                                ? __('System default')
                                : ($template->is_active ? __('Organization active') : __('Disabled · system default used'));
                            $templateDescription = match ($template->type) {
                                InterviewTemplateType::InterviewOnline => __('Invite a candidate to a remote interview.'),
                                InterviewTemplateType::InterviewOnsite => __('Share the time and location for an onsite interview.'),
                                InterviewTemplateType::InterviewReschedule => __('Explain a new time or meeting destination.'),
                                InterviewTemplateType::InterviewCancel => __('Let a candidate know an interview has been cancelled.'),
                            };
                        @endphp
                        <article class="nw-email-studio-card">
                            <div class="nw-email-studio-card__sample" aria-hidden="true">
                                <div class="nw-email-studio-card__mail">
                                    <span>{{ $organization->name }}</span>
                                    <strong x-text="previewSubject('{{ $typeValue }}')">{{ $template->subject }}</strong>
                                    <p x-text="previewExcerpt('{{ $typeValue }}')">{{ Str::limit($template->body, 110) }}</p>
                                </div>
                            </div>
                            <div class="nw-email-studio-card__content">
                                <div class="nw-email-studio-card__title">
                                    <h3>{{ $template->type->label() }}</h3>
                                    <span class="nw-email-studio__status {{ $template->is_system ? 'is-default' : ($template->is_active ? 'is-active' : 'is-disabled') }}">
                                        <span aria-hidden="true"></span>{{ $templateStatus }}
                                    </span>
                                </div>
                                <p>{{ $templateDescription }}</p>
                                <a class="nw-button nw-button--secondary nw-button--small" href="{{ route('organisations.email-templates.index', ['template' => $typeValue]) }}">
                                    {{ __('Customize message') }}
                                    <x-icon name="arrow-right" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @else
            @php
                $type = $selectedTemplate->type;
                $typeValue = $type->value;
                $subjectId = 'template-'.$typeValue.'-subject';
                $bodyId = 'template-'.$typeValue.'-body';
                $enabledId = 'template-'.$typeValue.'-enabled';
                $subjectHintId = $subjectId.'-hint';
                $subjectErrorId = $subjectId.'-error';
                $bodyHintId = $bodyId.'-hint';
                $bodyErrorId = $bodyId.'-error';
                $requiredToken = $requiredPlaceholderTokens[$typeValue] ?? null;
            @endphp

            <div class="nw-email-studio__editor-heading">
                <a class="nw-email-studio__back" href="{{ route('organisations.email-templates.index') }}" @click="confirmLeave($event)">
                    <x-icon name="arrow-left" /> {{ __('All templates') }}
                </a>
                <div class="nw-email-studio__editor-title">
                    <div>
                        <h2>{{ $type->label() }}</h2>
                        <p>{{ __('Edit the message, then review the preview before saving.') }}</p>
                    </div>
                    <div class="nw-email-studio__editor-states">
                        <span class="nw-email-studio__status {{ $selectedTemplate->is_system ? 'is-default' : ($selectedTemplate->is_active ? 'is-active' : 'is-disabled') }}">
                            <span aria-hidden="true"></span>
                            {{ $selectedTemplate->is_system ? __('System default') : ($selectedTemplate->is_active ? __('Organization active') : __('Disabled · system default used')) }}
                        </span>
                        <span class="nw-email-studio__unsaved" x-show="isDirty" x-cloak>{{ __('Unsaved changes') }}</span>
                    </div>
                </div>
            </div>

            <div class="nw-email-studio__workspace">
                <aside class="nw-email-studio__sidebar" aria-label="{{ __('Email content and sample details') }}">
                    <form method="POST" action="{{ route('organisations.email-templates.update', $type) }}" class="nw-email-studio__form" x-on:submit="submitting = true">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template_type" value="{{ $typeValue }}">

                        <div class="nw-email-studio__form-heading">
                            <h3>{{ __('Message content') }}</h3>
                            <p>{{ __('Your edits are saved for this message only.') }}</p>
                        </div>

                        <div class="nw-field">
                            <label class="nw-label" for="{{ $subjectId }}">{{ __('Email subject') }}</label>
                            <input
                                class="nw-input"
                                id="{{ $subjectId }}"
                                name="subject"
                                type="text"
                                maxlength="255"
                                required
                                x-model="editor.subject"
                                value="{{ $editorValues['subject'] }}"
                                aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}"
                                aria-describedby="{{ $subjectHintId }}{{ $errors->has('subject') ? ' '.$subjectErrorId : '' }}"
                            >
                            <p class="nw-hint" id="{{ $subjectHintId }}">{{ __('Keep the subject on one line. Use a placeholder to personalize it.') }}</p>
                            @error('subject')
                                <p class="nw-field__error" id="{{ $subjectErrorId }}" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="nw-field nw-email-studio__field--body">
                            <div class="nw-email-studio__field-title">
                                <label class="nw-label" for="{{ $bodyId }}">{{ __('Email message') }}</label>
                                <span class="nw-email-studio__field-meta">{{ __('Plain text') }}</span>
                            </div>
                            <textarea
                                class="nw-input nw-email-studio__body-input"
                                id="{{ $bodyId }}"
                                name="body"
                                rows="12"
                                maxlength="20000"
                                required
                                x-model="editor.body"
                                aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}"
                                aria-describedby="{{ $bodyHintId }}{{ $errors->has('body') ? ' '.$bodyErrorId : '' }}"
                            >{{ $editorValues['body'] }}</textarea>
                            <p class="nw-hint" id="{{ $bodyHintId }}">
                                @if ($requiredToken)
                                    {{ __('Include :placeholder. Add other details from the list below.', ['placeholder' => $requiredToken]) }}
                                @else
                                    {{ __('Add a supported placeholder where you want interview details to appear.') }}
                                @endif
                            </p>
                            @error('body')
                                <p class="nw-field__error" id="{{ $bodyErrorId }}" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <fieldset class="nw-email-studio__tokens">
                            <legend>{{ __('Insert a detail') }}</legend>
                            <p>{{ __('Select a placeholder to add it at your cursor.') }}</p>
                            <div class="nw-email-studio__token-list">
                                @foreach ($placeholderTokens as $placeholderToken)
                                    <button class="nw-email-studio__token" type="button" data-token="{{ $placeholderToken }}" x-on:click="insertPlaceholder($event.currentTarget.dataset.token)">
                                        {{ $placeholderToken }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="nw-email-studio__toggle">
                            <label for="{{ $enabledId }}">
                                <strong>{{ __('Use this organization template') }}</strong>
                                <span>{{ __('When off, new emails use the system default.') }}</span>
                            </label>
                            <input name="is_active" type="hidden" value="0">
                            <input id="{{ $enabledId }}" class="nw-checkbox" name="is_active" type="checkbox" value="1" x-model="editor.is_active" @checked($editorValues['is_active'])>
                        </div>

                        <div class="nw-email-studio__save">
                            <button class="nw-button nw-button--primary" type="submit" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                                <x-icon name="check" />
                                <span x-text="submitting ? '{{ __('Saving…') }}' : '{{ __('Save template') }}'">{{ __('Save template') }}</span>
                            </button>
                            <p>{{ __('Preview values are examples only and are not saved.') }}</p>
                        </div>
                    </form>

                    <details class="nw-email-studio__sample-details" open>
                        <summary>{{ __('Sample information') }}</summary>
                        <p>{{ __('Change these example details to see how placeholders read. They never update candidate records.') }}</p>
                        <div class="nw-email-studio__sample-fields">
                            <div class="nw-field">
                                <label class="nw-label" for="sample-candidate">{{ __('Candidate name') }}</label>
                                <input class="nw-input" id="sample-candidate" type="text" x-model="sample.candidate_name">
                            </div>
                            <div class="nw-field">
                                <label class="nw-label" for="sample-job">{{ __('Job title') }}</label>
                                <input class="nw-input" id="sample-job" type="text" x-model="sample.job_title">
                            </div>
                            <div class="nw-field">
                                <label class="nw-label" for="sample-date">{{ __('Interview date') }}</label>
                                <input class="nw-input" id="sample-date" type="text" x-model="sample.interview_date">
                            </div>
                            <div class="nw-field">
                                <label class="nw-label" for="sample-time">{{ __('Interview time') }}</label>
                                <input class="nw-input" id="sample-time" type="text" x-model="sample.interview_time">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('timezone')">
                                <label class="nw-label" for="sample-timezone">{{ __('Time zone') }}</label>
                                <input class="nw-input" id="sample-timezone" type="text" x-model="sample.timezone">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('meeting_link')">
                                <label class="nw-label" for="sample-meeting-link">{{ __('Meeting link') }}</label>
                                <input class="nw-input" id="sample-meeting-link" type="url" x-model="sample.meeting_link">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('venue_name')">
                                <label class="nw-label" for="sample-venue">{{ __('Venue name') }}</label>
                                <input class="nw-input" id="sample-venue" type="text" x-model="sample.venue_name">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('address')">
                                <label class="nw-label" for="sample-address">{{ __('Interview location') }}</label>
                                <input class="nw-input" id="sample-address" type="text" x-model="sample.address">
                            </div>
                            <div class="nw-field">
                                <label class="nw-label" for="sample-contact">{{ __('Contact name') }}</label>
                                <input class="nw-input" id="sample-contact" type="text" x-model="sample.contact_name">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('contact_phone')">
                                <label class="nw-label" for="sample-phone">{{ __('Contact phone') }}</label>
                                <input class="nw-input" id="sample-phone" type="tel" x-model="sample.contact_phone">
                            </div>
                            <div class="nw-field" x-show="usesPlaceholder('instructions')">
                                <label class="nw-label" for="sample-instructions">{{ __('Instructions') }}</label>
                                <textarea class="nw-input" id="sample-instructions" rows="3" x-model="sample.instructions"></textarea>
                            </div>
                        </div>
                    </details>
                </aside>

                <section class="nw-email-studio__preview-area" aria-labelledby="email-preview-heading" x-on:keydown="handleTabKeydown($event)">
                    <div class="nw-email-studio__preview-toolbar">
                        <div class="nw-email-studio__tabs" role="tablist" aria-label="{{ __('Email preview format') }}">
                            <button type="button" role="tab" id="email-tab-preview" aria-controls="email-panel-preview" x-bind:aria-selected="view === 'preview'" x-bind:tabindex="view === 'preview' ? 0 : -1" x-on:click="setView('preview')">{{ __('Preview') }}</button>
                            <button type="button" role="tab" id="email-tab-html" aria-controls="email-panel-html" x-bind:aria-selected="view === 'html'" x-bind:tabindex="view === 'html' ? 0 : -1" x-on:click="setView('html')">{{ __('HTML') }}</button>
                            <button type="button" role="tab" id="email-tab-text" aria-controls="email-panel-text" x-bind:aria-selected="view === 'text'" x-bind:tabindex="view === 'text' ? 0 : -1" x-on:click="setView('text')">{{ __('Plain text') }}</button>
                            <button type="button" role="tab" id="email-tab-usage" aria-controls="email-panel-usage" x-bind:aria-selected="view === 'usage'" x-bind:tabindex="view === 'usage' ? 0 : -1" x-on:click="setView('usage')">{{ __('Usage') }}</button>
                        </div>
                        <div class="nw-email-studio__device-controls" role="group" aria-label="{{ __('Preview width') }}">
                            <button type="button" aria-label="{{ __('Desktop preview') }}" x-bind:aria-pressed="device === 'desktop'" x-on:click="device = 'desktop'">
                                <x-icon name="monitor" />
                            </button>
                            <button type="button" aria-label="{{ __('Mobile preview') }}" x-bind:aria-pressed="device === 'mobile'" x-on:click="device = 'mobile'">
                                <x-icon name="smartphone" />
                            </button>
                        </div>
                    </div>

                    <div class="nw-email-studio__canvas" x-bind:class="{ 'is-mobile': device === 'mobile' }">
                        <section id="email-panel-preview" role="tabpanel" aria-labelledby="email-tab-preview" x-show="view === 'preview'" x-cloak>
                            <div class="nw-email-studio__mail-client" aria-label="{{ __('Sample candidate email preview') }}">
                                <p><span>{{ __('From') }}</span><strong>{{ $organization->name }} {{ __('via Marthire') }}</strong></p>
                                <p><span>{{ __('To') }}</span><strong x-text="sample.candidate_name">{{ $previewValues['candidate_name'] }}</strong></p>
                            </div>
                            <div class="nw-email-studio__mail">
                                <div class="nw-email-studio__mail-content">
                                    <p class="nw-email-studio__mail-organization">{{ $organization->name }}</p>
                                    <h3 id="email-preview-heading" x-text="renderedSubject">{{ $selectedTemplate->subject }}</h3>
                                    <div class="nw-email-studio__mail-body" x-text="renderedBody">{{ $selectedTemplate->body }}</div>
                                    <div class="nw-email-studio__mail-actions" x-show="selected !== 'interview_cancel'" x-cloak>
                                        <span>{{ __('Confirm attendance') }}</span>
                                        <span>{{ __('Decline invitation') }}</span>
                                    </div>
                                    <p class="nw-email-studio__mail-link-note" x-show="selected !== 'interview_cancel'" x-cloak>
                                        {{ __('Signed response links are added for each interview when the email is sent.') }}
                                    </p>
                                </div>
                            </div>
                            <p class="nw-email-studio__preview-note">{{ __('Example details only. Save your edits before they are used. Disabled templates use the system default.') }}</p>
                        </section>

                        <section id="email-panel-html" class="nw-email-studio__code-panel" role="tabpanel" aria-labelledby="email-tab-html" x-show="view === 'html'" x-cloak>
                            <p>{{ __('Sample HTML only. Signed response-link placeholders are replaced for each interview when the email is sent.') }}</p>
                            <pre><code x-text="htmlPreview"></code></pre>
                        </section>

                        <section id="email-panel-text" class="nw-email-studio__text-panel" role="tabpanel" aria-labelledby="email-tab-text" x-show="view === 'text'" x-cloak>
                            <pre x-text="textPreview"></pre>
                        </section>

                        <section id="email-panel-usage" class="nw-email-studio__usage-panel" role="tabpanel" aria-labelledby="email-tab-usage" x-show="view === 'usage'" x-cloak>
                            <h3>{{ __('Personalize with placeholders') }}</h3>
                            <p>{{ __('Placeholders are replaced with interview details when the email is sent. Click one in the editor to insert it at your cursor.') }}</p>
                            <ul>
                                @foreach ($placeholderTokens as $placeholderToken)
                                    <li><code>{{ $placeholderToken }}</code><span>{{ Str::headline(trim($placeholderToken, '{} ')) }}</span></li>
                                @endforeach
                            </ul>
                            @if ($requiredToken)
                                <p class="nw-email-studio__required-note"><x-icon name="info" /> {{ __('This message must include :placeholder.', ['placeholder' => $requiredToken]) }}</p>
                            @endif
                        </section>
                    </div>

                    <div class="nw-email-studio__export-bar">
                        <p class="nw-email-studio__live-status" role="status" aria-live="polite" x-text="statusMessage"></p>
                        <div>
                            <button class="nw-button nw-button--primary nw-button--small" type="button" x-on:click="copyHtml()">
                                <x-icon name="copy" /> {{ __('Copy HTML') }}
                            </button>
                            <button class="nw-button nw-button--secondary nw-button--small" type="button" x-on:click="download('html')">
                                <x-icon name="download" /> {{ __('Download HTML') }}
                            </button>
                            <button class="nw-button nw-button--secondary nw-button--small" type="button" x-on:click="download('text')">
                                <x-icon name="download" /> {{ __('Download text') }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-app-layout>
