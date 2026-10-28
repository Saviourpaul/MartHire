@php
    $navUser = auth()->user();

    $groups = match (true) {
        $navUser->isAdmin() => [
            __('Overview') => [
                ['label' => __('Dashboard'), 'href' => route('admin.dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('admin.dashboard')],
            ],
            __('Manage') => [
                ['label' => __('Organizations'), 'href' => route('admin.users.organizations'), 'icon' => 'briefcase', 'active' => request()->routeIs('admin.users.organizations')],
                ['label' => __('Applicants'), 'href' => route('admin.users.applicants'), 'icon' => 'user', 'active' => request()->routeIs('admin.users.applicants')],
                ['label' => __('Admins'), 'href' => route('admin.users.administrators'), 'icon' => 'grid', 'active' => request()->routeIs('admin.users.administrators')],
                ['label' => __('Suspended'), 'href' => route('admin.users.suspended'), 'icon' => 'close', 'active' => request()->routeIs('admin.users.suspended')],
            ],
            __('Account') => [
                ['label' => __('Profile & settings'), 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
                ['label' => __('Help & support'), 'href' => url('/ContactUs'), 'icon' => 'help'],
            ],
        ],
        $navUser->isOrganization() => [
            __('Overview') => [
                ['label' => __('Dashboard'), 'href' => route('organisations.dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('organisations.dashboard')],
            ],
            __('Recruitment') => [
                ['label' => __('Jobs'), 'href' => route('organisations.jobs.index'), 'icon' => 'briefcase', 'active' => request()->routeIs('organisations.jobs.*')],
                ['label' => __('Candidates'), 'href' => route('organisations.candidates.index'), 'icon' => 'file', 'active' => request()->routeIs('organisations.candidates.*')],
                ['label' => __('Team'), 'href' => route('organisations.team.index'), 'icon' => 'users', 'active' => request()->routeIs('organisations.team.*')],
            ],
            __('Account') => [
                ['label' => __('Profile & settings'), 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
                ['label' => __('Help & support'), 'href' => url('/ContactUs'), 'icon' => 'help'],
            ],
        ],
        default => [
            __('Overview') => [
                ['label' => __('Dashboard'), 'href' => route('client.dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('client.dashboard')],
                ['label' => __('My applications'), 'href' => route('client.dashboard').'#applications', 'icon' => 'file'],
                ['label' => __('New roles'), 'href' => route('client.dashboard').'#recommended', 'icon' => 'spark'],
            ],
            __('Find work') => [
                ['label' => __('Browse jobs'), 'href' => route('workable-jobs.index'), 'icon' => 'briefcase'],
                ['label' => __('Job categories'), 'href' => url('/').'#categories', 'icon' => 'globe'],
            ],
            __('Account') => [
                ['label' => __('Profile & settings'), 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
                ['label' => __('Help & support'), 'href' => url('/ContactUs'), 'icon' => 'help'],
            ],
        ],
    };
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
