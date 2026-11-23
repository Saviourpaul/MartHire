@props(['cancelUrl', 'submitLabel' => __('Submit application')])

<div class="nw-wizard-actions">
    <div class="nw-wizard-actions__progress" x-ref="progressStatus" x-show="submitting" x-cloak role="status" aria-live="polite"
        data-progress-preparing="{{ __('Preparing your application…') }}"
        data-progress-uploading="{{ __('Uploading your application…') }}"
        data-progress-finishing="{{ __('Files uploaded. Finishing your submission…') }}"
        data-error-upload-count="{{ __('You selected :actual files. This form can send up to :limit files at once. Remove a file and try again.', ['actual' => '__ACTUAL__', 'limit' => '__LIMIT__']) }}"
        data-error-upload-total="{{ __('Your selected files total :selected. This form can send up to :limit at once. Remove a file or choose smaller files, then try again.', ['selected' => '__SELECTED__', 'limit' => '__LIMIT__']) }}"
        data-error-no-file-uploads="{{ __('The server is not configured to accept file uploads. Try again later.') }}"
        data-error-file-size="{{ __('That file is :selected. Please choose one under :limit.', ['selected' => '__SELECTED__', 'limit' => '__LIMIT__']) }}"
        data-error-file-type="{{ __('That file type is not supported. Please choose a :types file.', ['types' => '__TYPES__']) }}"
        data-error-upload-large="{{ __('The application upload is larger than the server allows. Remove a file or choose smaller files, then try again.') }}"
        data-error-connection="{{ __('We could not connect. Your entries are still here; please try again.') }}"
        data-error-timeout="{{ __('The upload took too long. Check your connection and try again.') }}"
        data-error-session="{{ __('Please refresh this page and sign in again if your session has expired.') }}"
        data-error-permission="{{ __('Your account does not have permission to submit this application.') }}"
        data-error-not-found="{{ __('This job is no longer accepting applications.') }}"
        data-error-rate-limit="{{ __('Too many attempts. Wait a moment, then try again.') }}"
        data-error-server="{{ __('The server could not complete your application. Your entries are still here; please try again.') }}"
        data-error-generic="{{ __('We could not submit your application. Your entries are still here; please try again.') }}">
        <span x-text="submissionProgressLabel()">{{ __('Preparing your application…') }}</span>
        <progress x-show="uploadProgress === null" max="100" aria-label="{{ __('Application upload progress') }}"></progress>
        <progress x-show="uploadProgress !== null" max="100" x-bind:value="uploadProgress" aria-label="{{ __('Application upload progress') }}"></progress>
    </div>
    <div class="nw-wizard-actions__back">
        <button type="button" class="nw-button nw-button--outline" x-show="ready && step > 0" x-cloak x-on:click="back()"
            x-bind:disabled="submitting"><x-icon name="arrow-left" /> {{ __('Back') }}</button>
        <a class="nw-link nw-link--small" href="{{ $cancelUrl }}">{{ __('Cancel') }}</a>
    </div>
    <button type="button" class="nw-button nw-button--primary" x-show="ready && !isLastStep" x-cloak x-on:click="next()"
        x-bind:disabled="submitting || stepBusy()">{{ __('Next') }} <x-icon name="arrow-right" /></button>
    <button type="submit" class="nw-button nw-button--primary" x-show="!ready || isLastStep"
        x-bind:disabled="submitting">
        <span
            x-text="submitting ? @js(__('Sending…')) : @js($submitLabel)">{{ $submitLabel }}</span><x-icon
            name="send" />
    </button>
</div>
