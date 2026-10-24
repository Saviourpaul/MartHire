@php
    $shellUser = auth()->user();
    // The users table is moving from `name` to first/last name columns, so accept any of those spellings.
    $shellFirst = $shellUser->getAttribute('firstname') ?? $shellUser->getAttribute('firstName') ?? '';
    $shellLast = $shellUser->getAttribute('lastname') ?? $shellUser->getAttribute('lastName') ?? '';
    $shellName = trim($shellFirst.' '.$shellLast) ?: ($shellUser->getAttribute('name') ?: $shellUser->email);
    $shellInitial = mb_strtoupper(mb_substr($shellName, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.site-head', ['title' => isset($title) ? (string) $title : 'Your account'])
    </head>
    <body class="nw-app"
        x-data="{
            sidebar: false,
            mobile: window.matchMedia('(max-width: 1023px)').matches,
            init() {
                window.matchMedia('(max-width: 1023px)').addEventListener('change', (event) => {
                    this.mobile = event.matches;
                    if (! event.matches) this.sidebar = false;
                });
            },
        }"
        x-on:keydown.escape.window="sidebar = false"
        x-on:keydown.window="if ($event.key === '/' && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.search.focus(); }">
        <a class="nw-skip-link" href="#main-content">{{ __('Skip to content') }}</a>

        <aside id="app-sidebar" class="nw-sidebar" x-bind:class="{ 'is-open': sidebar }" x-bind:inert="mobile && ! sidebar" aria-label="{{ __('Account navigation') }}">
            <div class="nw-sidebar__brand">
                <a class="nw-brand" href="{{ url('/') }}" aria-label="{{ __('NearbyWorks home') }}"><x-application-logo /></a>
                <button type="button" class="nw-sidebar__close" x-on:click="sidebar = false" aria-label="{{ __('Close menu') }}"><x-icon name="close" /></button>
            </div>

            @include('layouts.navigation')

            <div class="nw-sidebar__user">
                <span class="nw-avatar" aria-hidden="true">{{ $shellInitial }}</span>
                <div class="nw-sidebar__who">
                    <strong>{{ $shellName }}</strong>
                    <small>{{ $shellUser->email }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nw-sidebar__logout" title="{{ __('Log out') }}"><x-icon name="logout" /><span class="nw-sr-only">{{ __('Log out') }}</span></button>
                </form>
            </div>
        </aside>
        <div class="nw-app__scrim" x-show="sidebar" x-cloak x-on:click="sidebar = false"></div>

        <div class="nw-app__main">
            <header class="nw-topbar">
                <button type="button" class="nw-topbar__menu" aria-controls="app-sidebar" x-bind:aria-expanded="sidebar.toString()" x-on:click="sidebar = true">
                    <x-icon name="menu" /><span class="nw-sr-only">{{ __('Open menu') }}</span>
                </button>

                <form class="nw-topbar__search" role="search" method="get" action="{{ route('jobs.index') }}">
                    <x-icon name="search" />
                    <label class="nw-sr-only" for="app-search">{{ __('Search jobs') }}</label>
                    <input id="app-search" x-ref="search" type="search" name="q" placeholder="{{ __('Search jobs') }}" autocomplete="off">
                    <kbd aria-hidden="true">/</kbd>
                </form>

                <div class="nw-topbar__actions">
                    <a class="nw-button nw-button--primary nw-button--small nw-topbar__cta" href="{{ route('jobs.index') }}">{{ __('Find jobs') }} <x-icon name="arrow-up-right" /></a>

                    <button type="button" class="nw-icon-button" data-theme-toggle title="{{ __('Switch light or dark') }}">
                        <x-icon name="moon" class="nw-icon--to-dark" />
                        <x-icon name="sun" class="nw-icon--to-light" />
                        <span class="nw-sr-only">{{ __('Switch between light and dark') }}</span>
                    </button>

                    <div class="nw-user-menu" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
                        <button type="button" class="nw-user-menu__button" aria-haspopup="true" x-bind:aria-expanded="open.toString()" x-on:click="open = ! open">
                            <span class="nw-avatar" aria-hidden="true">{{ $shellInitial }}</span>
                            <x-icon name="chevron-down" />
                            <span class="nw-sr-only">{{ __('Your account menu') }}</span>
                        </button>
                        <div class="nw-user-menu__panel" x-show="open" x-cloak>
                            <div class="nw-user-menu__who"><strong>{{ $shellName }}</strong><small>{{ $shellUser->email }}</small></div>
                            <a href="{{ route('dashboard') }}"><x-icon name="grid" /> {{ __('Dashboard') }}</a>
                            <a href="{{ route('profile.edit') }}"><x-icon name="user" /> {{ __('Profile & settings') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"><x-icon name="logout" /> {{ __('Log out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="nw-app__content">
                @isset($header)
                    <header class="nw-page-heading nw-page-heading--app">
                        <div>{{ $header }}</div>
                        @isset($actions)
                            <div class="nw-page-heading__actions">{{ $actions }}</div>
                        @endisset
                    </header>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
