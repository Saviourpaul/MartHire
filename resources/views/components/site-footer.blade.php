@props(['compact' => false])

<footer class="nw-footer{{ $compact ? ' nw-footer--compact' : '' }}">
    <div class="nw-container">
        @unless ($compact)
            <div class="nw-footer__top">
                <div class="nw-footer__brand">
                    <a class="nw-brand" href="{{ url('/') }}" aria-label="{{ __('Marthire home') }}"><x-application-logo /></a>
                    <p>One platform to manageRecruitment across African businesses, and international organisations.</p>
                    @if (! empty($platformBrand['social_links']))
                        <nav class="nw-footer__socials" aria-label="{{ __('Follow us on social media') }}">
                            @foreach ($platformBrand['social_links'] as $socialLink)
                                <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Follow :brand on :platform', ['brand' => $platformBrand['display_name'] ?? 'Marthire', 'platform' => $socialLink['label']]) }}" title="{{ $socialLink['label'] }}">
                                    @if ($socialLink['key'] === 'x_url')
                                        <svg class="nw-footer__social-x" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.64 7.58H.48l8.6-9.83L0 1.15h7.6l5.24 6.93 6.06-6.93Zm-1.29 19.49h2.04L6.49 3.24H4.3l13.31 17.4Z" /></svg>
                                    @else
                                        <span class="nw-footer__social-icon fa-brands {{ $socialLink['icon'] }}" aria-hidden="true"></span>
                                    @endif
                                    <span class="nw-sr-only">{{ $socialLink['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    @endif
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
                    <h2>{{ __('Marthire') }}</h2>
                    <a href="{{ route('how-it-works') }}">{{ __('How it works') }}</a>
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
            <p>&copy; {{ date('Y') }} Marthire. {{ __('All rights reserved.') }}</p>
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
            <span class="nw-footer-art__wordmark" style="--nw-footer-photo: url('{{ asset('asset/marthire/conversation.png') }}'); padding-inline-end: .1em">Marthire</span>
        </div>
    @endunless
</footer>
