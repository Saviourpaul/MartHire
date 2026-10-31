<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.site-head', ['title' => isset($title) ? (string) $title : 'Your account'])
    </head>
    <body>
        <a class="nw-skip-link" href="#main-content">{{ __('Skip to content') }}</a>
        <x-site-header />

        <main id="main-content" class="nw-auth-layout">
            <aside class="nw-auth-story" aria-label="{{ __('Welcome to Marthire') }}">
                <div class="nw-auth-story__content">
                    <span class="nw-eyebrow">{{ __('People. Possibility. Progress.') }}</span>
                    <h2>{{ __('Good people.') }}<br><em>{{ __('Great possibilities.') }}</em></h2>
                    <p>{{ __('A place for employers and job seekers to connect. Your next chapter starts with you.') }}</p>
                </div>
                <div class="nw-auth-orbit" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
                <div class="nw-auth-story__bottom">
                    <span>{{ __('Made for the people behind every opportunity.') }}</span>
                </div>
            </aside>

            <section class="nw-auth-main" aria-labelledby="auth-heading">
                <div class="nw-auth-card">
                    {{ $slot }}
                </div>
            </section>
        </main>

        <x-site-footer :compact="true" />
        <x-toast-notifications />
    </body>
</html>
