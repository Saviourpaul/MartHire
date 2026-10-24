@php
    $groups = [
        __('Overview') => [
            ['label' => __('Dashboard'), 'href' => route('dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('dashboard')],
            ['label' => __('My applications'), 'href' => route('dashboard').'#applications', 'icon' => 'file'],
            ['label' => __('New roles'), 'href' => route('dashboard').'#recommended', 'icon' => 'spark'],
        ],
        __('Find work') => [
            ['label' => __('Browse jobs'), 'href' => route('jobs.index'), 'icon' => 'briefcase'],
            ['label' => __('Job categories'), 'href' => url('/').'#categories', 'icon' => 'globe'],
        ],
        __('Account') => [
            ['label' => __('Profile & settings'), 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
            ['label' => __('Help & support'), 'href' => url('/ContactUs'), 'icon' => 'help'],
        ],
    ];
@endphp

<nav class="nw-sidebar__nav" aria-label="{{ __('Your account') }}">
    @foreach ($groups as $heading => $links)
        <p class="nw-sidebar__label">{{ $heading }}</p>
        @foreach ($links as $link)
            <a class="nw-side-link{{ ! empty($link['active']) ? ' is-active' : '' }}" href="{{ $link['href'] }}" @if (! empty($link['active'])) aria-current="page" @endif x-on:click="sidebar = false">
                <x-icon :name="$link['icon']" /> {{ $link['label'] }}
            </a>
        @endforeach
    @endforeach
</nav>
