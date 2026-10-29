@props(['account' => false])

<header class="nw-header{{ $account ? ' nw-header--account' : '' }}" x-data="{ open: false }" x-on:keydown.escape.window="if (open) { open = false; $refs.navigationToggle.focus(); }">
    <div class="nw-container nw-header__inner">
        <a class="nw-brand" href="{{ url('/') }}" aria-label="{{ __('NearbyWorks home') }}">
            <x-application-logo />
        </a>

        <nav class="nw-desktop-nav" aria-label="{{ __('Main navigation') }}">
            <a href="{{ route('workable-jobs.index') }}" @if (request()->routeIs('workable-jobs.*')) aria-current="page" @endif>{{ __('Browse jobs') }}</a>
            <a href="{{ url('/') }}#for-employers">{{ __('For employers') }}</a>
            <a href="{{ url('/') }}#for-job-seekers">{{ __('For job seekers') }}</a>
            <a href="{{ url('/About') }}" @if (request()->is('About')) aria-current="page" @endif>{{ __('About') }}</a>
            <a href="{{ url('/ContactUs') }}" @if (request()->is('ContactUs')) aria-current="page" @endif>{{ __('Contact') }}</a>
        </nav>

        <div class="nw-header__actions">
            @auth
                <a class="nw-button nw-button--primary nw-button--small" href="{{ route('dashboard') }}">{{ __('Your workspace') }} <x-icon name="arrow-up-right" /></a>
            @else
                <a class="nw-header__login" href="{{ route('login') }}">{{ __('Log in') }}</a>
                <a class="nw-button nw-button--primary nw-button--small" href="{{ route('register') }}">{{ __('Get started') }} <x-icon name="arrow-up-right" /></a>
            @endauth
        </div>

        <button x-ref="navigationToggle" class="nw-menu-toggle" type="button" x-on:click="open = ! open" aria-controls="site-mobile-navigation" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-label="{{ __('Open navigation') }}" x-bind:aria-label="open ? '{{ __('Close navigation') }}' : '{{ __('Open navigation') }}'">
            <span x-show="! open"><x-icon name="menu" /></span>
            <span x-show="open" x-cloak><x-icon name="close" /></span>
        </button>
    </div>

    <nav id="site-mobile-navigation" class="nw-mobile-nav" aria-label="{{ __('Mobile navigation') }}" x-show="open" x-cloak x-on:click="if ($event.target.closest('a')) open = false">
        <div class="nw-container">
            <a href="{{ route('workable-jobs.index') }}">{{ __('Browse jobs') }}</a>
            <a href="{{ url('/') }}#for-employers">{{ __('For employers') }}</a>
            <a href="{{ url('/') }}#for-job-seekers">{{ __('For job seekers') }}</a>
            <a href="{{ url('/About') }}">{{ __('About') }}</a>
            <a href="{{ url('/ContactUs') }}">{{ __('Contact') }}</a>
            @auth
                <a class="nw-button nw-button--primary" href="{{ route('dashboard') }}">{{ __('Your workspace') }} <x-icon name="arrow-up-right" /></a>
            @else
                <a href="{{ route('login') }}">{{ __('Log in') }}</a>
                <a class="nw-button nw-button--primary" href="{{ route('register') }}">{{ __('Get started') }} <x-icon name="arrow-up-right" /></a>
            @endauth
        </div>
    </nav>
</header>
