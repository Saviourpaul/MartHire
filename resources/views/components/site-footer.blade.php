@props(['compact' => false])

<footer class="nw-footer{{ $compact ? ' nw-footer--compact' : '' }}">
    <div class="nw-container">
        @unless ($compact)
            <div class="nw-footer__top">
                <div class="nw-footer__brand">
                    <a class="nw-brand" href="{{ url('/') }}" aria-label="{{ __('MartHire home') }}"><x-application-logo /></a>
                    <p>{{ __('Good people. Great possibilities.') }}</p>
                </div>

                <div class="nw-footer__group">
                    <h2>{{ __('Find your next') }}</h2>
                    <a href="{{ route('workable-jobs.index') }}">{{ __('Browse jobs') }}</a>
                    <a href="{{ url('/') }}#categories">{{ __('Job categories') }}</a>
                    <a href="{{ url('/') }}#for-employers">{{ __('For employers') }}</a>
                    <a href="{{ url('/') }}#for-job-seekers">{{ __('For job seekers') }}</a>
                    <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
                    <a href="{{ route('login') }}">{{ __('Log in') }}</a>
                </div>

                <div class="nw-footer__group">
                    <h2>{{ __('NearbyWorks') }}</h2>
                    <a href="{{ url('/About') }}">{{ __('About us') }}</a>
                    <a href="{{ url('/ContactUs') }}">{{ __('Contact us') }}</a>
                    @auth
                        <a href="{{ route('dashboard') }}">{{ __('Your workspace') }}</a>
                        <a href="{{ route('profile.edit') }}">{{ __('Profile & settings') }}</a>
                    @endauth
                </div>

                <div class="nw-footer__note">
                    <span class="nw-eyebrow">{{ __('Built around people') }}</span>
                    <p>{{ __('Because the right connection can change everything.') }}</p>
                    <a class="nw-link" href="{{ url('/About') }}">{{ __('Get to know us') }} <x-icon name="arrow-up-right" /></a>
                </div>
            </div>
        @endunless

        <div class="nw-footer__bottom">
            <p>&copy; {{ date('Y') }} NearbyWorks. {{ __('All rights reserved.') }}</p>
            <span class="nw-footer__people">{{ __('People first. Always.') }}</span>
            <div class="nw-theme-control">
                <x-icon name="sun" />
                <label for="theme-preference">{{ __('Appearance') }}</label>
                <select id="theme-preference" name="theme-preference" data-theme-control>
                    <option value="system">{{ __('System') }}</option>
                    <option value="light">{{ __('Light') }}</option>
                    <option value="dark">{{ __('Dark') }}</option>
                </select>
            </div>
        </div>
    </div>

    @unless ($compact)
        <div class="nw-footer-art" aria-hidden="true">
            <span class="nw-footer-art__wordmark" style="--nw-footer-photo: url('{{ asset('asset/nearbyworks/conversation.png') }}'); padding-inline-end: .1em">MartHire</span>
        </div>
    @endunless
</footer>
