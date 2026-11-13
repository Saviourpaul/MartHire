<x-guest-layout :title="__('Information received')" :description="__('Your organization demo request has been updated.')">
    <section class="nw-demo-card__success" role="status">
        <span class="nw-demo-card__success-icon"><x-icon name="check" /></span>
        <h1>{{ __('Thanks, we received your information') }}</h1>
        <p>{{ __('Our team will continue reviewing the request for :company and follow up by email.', ['company' => $companyName]) }}</p>
        <a class="nw-button nw-button--outline" href="{{ route('home') }}">{{ __('Return to MarHire') }}</a>
    </section>
</x-guest-layout>
