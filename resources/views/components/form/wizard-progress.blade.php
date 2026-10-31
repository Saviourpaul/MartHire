<nav class="nw-wizard-progress" aria-label="{{ __('Application progress') }}" x-show="ready" x-cloak>
    <div class="nw-wizard-progress__caption" role="status" aria-live="polite" aria-atomic="true">
        <span>{{ __('Step') }} <strong x-text="step + 1"></strong> {{ __('of') }} <span x-text="steps.length"></span></span>
        <span x-text="steps[step]?.title"></span>
    </div>
    <div class="nw-wizard-progress__track" role="progressbar" aria-label="{{ __('Application progress') }}" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress" x-bind:aria-valuetext="'Step ' + (step + 1) + ' of ' + steps.length">
        <span x-bind:style="{ width: progress + '%' }"></span>
    </div>
    <ol class="nw-wizard-progress__steps">
        <template x-for="item in steps" x-bind:key="item.index">
            <li x-bind:class="{ 'is-current': item.index === step, 'is-complete': item.index < step }" x-bind:aria-current="item.index === step ? 'step' : null">
                <span class="nw-wizard-progress__number" aria-hidden="true"><span x-show="item.index >= step" x-text="item.index + 1"></span><x-icon name="check" x-show="item.index < step" /></span>
                <span x-text="item.title"></span>
            </li>
        </template>
    </ol>
</nav>
