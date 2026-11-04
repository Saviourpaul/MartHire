<x-app-layout>
    <x-slot name="title">{{ $user->isApplicant() ? __('Profile') : __('Profile & settings') }}</x-slot>

    <x-slot name="header">
        <span class="nw-eyebrow">{{ $user->isApplicant() ? __('Your account, your way') : __('Your account') }}</span>
        <h1>{{ $user->isApplicant() ? __('Profile') : __('Profile & settings') }}</h1>
        <p>{{ $user->isApplicant() ? __('Your details for every opportunity. Keep your experience and preferences up to date.') : __('Keep your details current and your account secure.') }}</p>
    </x-slot>

    @if ($user->isApplicant())
        <main class="nw-applicant-profile-main">
            @include('profile.partials.applicant-profile-sections')
            <section id="password" class="nw-panel nw-profile-card">
                @include('profile.partials.update-password-form')
            </section>
            <section id="delete-account" class="nw-panel nw-panel--danger nw-profile-card">
                @include('profile.partials.delete-user-form')
            </section>
            <p class="nw-form-hint">{{ __('Need a hand?') }} <a class="nw-link" href="{{ url('/ContactUs') }}">{{ __('Get in touch') }}</a></p>
        </main>
    @else
        <div class="nw-account-grid">
            <aside class="nw-account-sidebar" aria-label="{{ __('Settings sections') }}">
                <span class="nw-eyebrow">{{ __('On this page') }}</span>
                <nav class="nw-settings-nav">
                    <a href="#profile-information">{{ __('Personal details') }}</a>
                    <a href="#password">{{ __('Password') }}</a>
                    <a href="#delete-account">{{ __('Delete account') }}</a>
                </nav>
                <p class="nw-form-hint">{{ __('Need a hand?') }} <a class="nw-link" href="{{ url('/ContactUs') }}">{{ __('Get in touch') }}</a></p>
            </aside>

            <div class="nw-settings-panels">
                <div id="profile-information" class="nw-panel">
                    @include('profile.partials.update-profile-information-form')
                </div>
                <div id="password" class="nw-panel">
                    @include('profile.partials.update-password-form')
                </div>
                <div id="delete-account" class="nw-panel nw-panel--danger">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
