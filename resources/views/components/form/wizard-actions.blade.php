@props(['cancelUrl', 'submitLabel' => __('Submit application')])

<div class="nw-wizard-actions">
    <div class="nw-wizard-actions__back">
        <button type="button" class="nw-button nw-button--outline" x-show="ready && step > 0" x-cloak x-on:click="back()" x-bind:disabled="submitting"><x-icon name="arrow-left" /> {{ __('Back') }}</button>
        <a class="nw-link nw-link--small" href="{{ $cancelUrl }}">{{ __('Cancel') }}</a>
    </div>
    <button type="button" class="nw-button nw-button--primary" x-show="ready && !isLastStep" x-cloak x-on:click="next()" x-bind:disabled="submitting || stepBusy()">{{ __('Next') }} <x-icon name="arrow-right" /></button>
    <button type="submit" class="nw-button nw-button--primary" x-show="!ready || isLastStep" x-bind:disabled="submitting">
        <span x-text="submitting ? @js(__('Sending…')) : @js($submitLabel)">{{ $submitLabel }}</span><x-icon name="send" />
    </button>
</div>
