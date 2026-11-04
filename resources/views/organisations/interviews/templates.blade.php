<x-app-layout>
    <x-slot name="title">{{ __('Interview email templates') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Interview email templates') }}</h1>
        <p>{{ __('Set the messages candidates receive when interviews are scheduled, changed, or cancelled.') }}</p>
    </x-slot>

    <div class="nw-settings-page">
        <section class="nw-panel nw-settings-card" aria-labelledby="template-guidance-heading">
            <header class="nw-settings-card__head">
                <h2 id="template-guidance-heading">{{ __('Personalize candidate communication') }}</h2>
                <p>{{ __('Use placeholders to insert interview details. System defaults remain in use until you save an organization template.') }}</p>
            </header>
            <p class="nw-hint">
                <strong>{{ __('Available placeholders:') }}</strong>
                @foreach ($placeholderTokens as $placeholderToken)
                    <code>{{ $placeholderToken }}</code>
                    @unless ($loop->last)
                        <span aria-hidden="true">, </span>
                    @endunless
                @endforeach
            </p>
        </section>

        @foreach ($templates as $template)
            @php
                $type = $template->type;
                $isSystemDefault = $template->is_system;
                $subjectId = 'template-'.$type->value.'-subject';
                $bodyId = 'template-'.$type->value.'-body';
                $enabledId = 'template-'.$type->value.'-enabled';
            @endphp
            <section class="nw-panel nw-settings-card" aria-labelledby="template-{{ $type->value }}-heading">
                <header class="nw-settings-card__head">
                    <div>
                        <h2 id="template-{{ $type->value }}-heading">{{ $type->label() }}</h2>
                        <p>{{ $isSystemDefault ? __('Using the system default. Save to create your organization override.') : __('Organization-specific template.') }}</p>
                    </div>
                    <x-status-badge :status="$isSystemDefault ? 'default' : 'custom'" />
                </header>

                <form method="POST" action="{{ route('organisations.email-templates.update', $type) }}" class="nw-settings-form">
                    @csrf
                    @method('PUT')
                    <div class="nw-field">
                        <label class="nw-label" for="{{ $subjectId }}">{{ __('Email subject') }}</label>
                        <input class="nw-input" id="{{ $subjectId }}" name="subject" type="text" maxlength="255" required value="{{ old('subject', $template->subject) }}">
                        <p class="nw-hint">{{ __('Keep the subject on one line.') }}</p>
                        @error('subject')
                            <p class="nw-field__error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="nw-field">
                        <label class="nw-label" for="{{ $bodyId }}">{{ __('Email message') }}</label>
                        <textarea class="nw-input" id="{{ $bodyId }}" name="body" rows="12" maxlength="20000" required>{{ old('body', $template->body) }}</textarea>
                        @if (isset($requiredPlaceholderTokens[$type->value]))
                            <p class="nw-hint">{{ __('This template must include :placeholder.', ['placeholder' => $requiredPlaceholderTokens[$type->value]]) }}</p>
                        @endif
                        @error('body')
                            <p class="nw-field__error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="nw-field">
                        <label for="{{ $enabledId }}" class="nw-checkbox-label">
                            <input name="is_active" type="hidden" value="0">
                            <input id="{{ $enabledId }}" class="nw-checkbox" name="is_active" type="checkbox" value="1" @checked(old('is_active', $template->is_active))>
                            <span>{{ __('Use this organization template') }}</span>
                        </label>
                        <p class="nw-hint">{{ __('When disabled, the system default is used for new messages.') }}</p>
                    </div>
                    <div class="nw-settings-form__actions">
                        <button class="nw-button nw-button--primary" type="submit">{{ $isSystemDefault ? __('Save organization template') : __('Save template') }}</button>
                    </div>
                </form>
            </section>
        @endforeach
    </div>
</x-app-layout>
