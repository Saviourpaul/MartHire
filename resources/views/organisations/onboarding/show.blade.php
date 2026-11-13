<x-organization-onboarding-layout
    :title="__('Set up :organization', ['organization' => $organization->name])"
    :current-step-label="$stepLabels[$currentStep] ?? __('Organization setup')"
    :current-step-number="$currentStepNumber"
    :step-count="$stepCount"
    :progress-percent="$progressPercent">
    @include('organisations.onboarding.partials.stage')
</x-organization-onboarding-layout>
