<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>
        MartHire
    </title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="{{ asset('assets/css/mains.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css'])



</head>

<body
    @php
$currentUser = auth()->user();
		$searchRoute = match(true) {
			request()->routeIs('dashboard') => route('dashboard'),
			default => route('dashboard'),
		};
        $recentNotifications = $currentUser ? $currentUser->notifications()->latest()->limit(4)->get() : collect();
        $unreadNotificationCount = $currentUser ? $currentUser->unreadNotifications()->count() : 0;
        $notificationsUrl = $currentUser?->isEmployer() ? route('employer.notifications') : ($currentUser?->isApplicant() ? route('client.notifications') : route('notifications'));
        $notificationsReadAllUrl = $currentUser?->isEmployer() ? route('employer.notifications.read-all') : (\Illuminate\Support\Facades\Route::has('notifications.read-all') ? route('notifications.read-all') : null);
    @endphp
    x-data="{
        loaded: true,
        darkMode: false,
        stickyMenu: false,
        sidebarToggle: false,
        desktopSidebarCollapsed: false,
        isDesktop: false,
        scrollTop: false,
        sidebarTrigger: null,
        init() {
            this.syncSidebarViewport();
            try {
                this.darkMode = window.localStorage.getItem('darkMode') === 'true';
            } catch (error) {
                this.darkMode = false;
            }

            this.applyTheme();
            this.$watch('darkMode', (value) => {
                try {
                    window.localStorage.setItem('darkMode', JSON.stringify(value));
                } catch (error) {
                    // Theme changes still work when browser storage is unavailable.
                }

                this.applyTheme();
            });
        },
        applyTheme() {
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        syncSidebarViewport() {
            const isDesktop = window.matchMedia('(min-width: 1280px)').matches;

            if (this.isDesktop !== isDesktop) {
                this.sidebarToggle = false;
                this.sidebarTrigger = null;
            }

            this.isDesktop = isDesktop;
        },
        toggleSidebar(trigger) {
            if (this.isDesktop) {
                this.desktopSidebarCollapsed = !this.desktopSidebarCollapsed;
                return;
            }

            if (this.sidebarToggle) {
                this.closeSidebar();
                return;
            }

            this.sidebarTrigger = trigger;
            this.sidebarToggle = true;
            this.$nextTick(() => this.$refs.sidebar.querySelector('a, button')?.focus());
        },
        closeSidebar(restoreFocus = true) {
            if (this.isDesktop || !this.sidebarToggle) {
                return;
            }

            this.sidebarToggle = false;

            if (restoreFocus && this.sidebarTrigger) {
                this.$nextTick(() => this.sidebarTrigger.focus());
            }
        }
    }" @resize.window.debounce.150ms="syncSidebarViewport()"
    @keydown.escape.window="closeSidebar()" :class="{ 'dark bg-gray-900': darkMode }">
    <!-- ===== Preloader Start ===== -->
    <div x-show="loaded" x-init="window.addEventListener('DOMContentLoaded', () => { setTimeout(() => loaded = false, 500) })"
        class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black">
        <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent">
        </div>
    </div>

    <!-- ===== Preloader End ===== -->

    <!-- ===== Page Wrapper Start ===== -->
    <div class="dashboard-shell flex overflow-hidden">
        <!-- ===== Sidebar Start ===== -->
        <aside id="application-sidebar" x-ref="sidebar" :data-open="sidebarToggle"
            :data-collapsed="desktopSidebarCollapsed" :aria-hidden="!isDesktop && !sidebarToggle"
            :inert="!isDesktop && !sidebarToggle"
            class="sidebar fixed top-0 left-0 flex flex-col overflow-hidden border-r border-gray-200 bg-white px-5 xl:static dark:border-gray-800 dark:bg-black">
            <!-- SIDEBAR HEADER -->
            <div class="sidebar-header flex items-center justify-between gap-2 pt-8 pb-7">
                <a href="{{ route('dashboard') }}">
                    <span class="logo">
                        <img class="dark:hidden"src="{{ asset('assets/images/logo.png') }}" alt="Logo"
                            width="150" height="100" />
                        <img class="hidden dark:block" src="{{ asset('assets/images/logo2.png') }}" alt="Logo"
                            width="150" height="100" />
                    </span>
                    <img class="logo-icon" src="{{ asset('assets/images/4.png') }} " width="50" height="80"
                        alt="Logo" />

                </a>
                <button type="button"
                    class="sidebar-close flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 xl:hidden dark:text-gray-400"
                    aria-label="Close navigation" @click="closeSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        focusable="false" data-lucide="circle-x">
                        <use href="#sidebar-icon-circle-x" />
                    </svg>
                </button>
            </div>
            <!-- SIDEBAR HEADER -->

            <div class="sidebar-navigation-scroll no-scrollbar flex flex-col">
                <!-- Sidebar Menu -->

                <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" class="absolute"
                    aria-hidden="true" focusable="false">
                    <defs>
                        <symbol id="sidebar-icon-layout-dashboard" viewBox="0 0 24 24">
                            <rect width="7" height="9" x="3" y="3" rx="1" />
                            <rect width="7" height="5" x="14" y="3" rx="1" />
                            <rect width="7" height="9" x="14" y="12" rx="1" />
                            <rect width="7" height="5" x="3" y="16" rx="1" />
                        </symbol>
                        <symbol id="sidebar-icon-circle-user-round" viewBox="0 0 24 24">
                            <path d="M18 20a6 6 0 0 0-12 0" />
                            <circle cx="12" cy="10" r="4" />
                            <circle cx="12" cy="12" r="10" />
                        </symbol>
                        <symbol id="sidebar-icon-users-round" viewBox="0 0 24 24">
                            <path d="M18 21a8 8 0 0 0-16 0" />
                            <circle cx="10" cy="8" r="5" />
                            <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                        </symbol>
                        <symbol id="sidebar-icon-briefcase-business" viewBox="0 0 24 24">
                            <path d="M12 12h.01" />
                            <path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                            <path d="M22 13a18.15 18.15 0 0 1-20 0" />
                            <rect width="20" height="14" x="2" y="6" rx="2" />
                        </symbol>
                        <symbol id="sidebar-icon-clipboard-list" viewBox="0 0 24 24">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                            <path d="M12 11h4" />
                            <path d="M12 16h4" />
                            <path d="M8 11h.01" />
                            <path d="M8 16h.01" />
                        </symbol>
                        <symbol id="sidebar-icon-chart-no-axes-combined" viewBox="0 0 24 24">
                            <path d="M12 16v5" />
                            <path d="M16 14v7" />
                            <path d="M20 10v11" />
                            <path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15" />
                            <path d="M4 18v3" />
                            <path d="M8 14v7" />
                        </symbol>
                        <symbol id="sidebar-icon-settings-2" viewBox="0 0 24 24">
                            <path d="M20 7h-9" />
                            <path d="M14 17H5" />
                            <circle cx="17" cy="17" r="3" />
                            <circle cx="7" cy="7" r="3" />
                        </symbol>
                        <symbol id="sidebar-icon-bell" viewBox="0 0 24 24">
                            <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                            <path
                                d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                        </symbol>
                        <symbol id="sidebar-icon-user-round" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="5" />
                            <path d="M20 21a8 8 0 0 0-16 0" />
                        </symbol>
                        <symbol id="sidebar-icon-building-2" viewBox="0 0 24 24">
                            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                            <path d="M10 6h4" />
                            <path d="M10 10h4" />
                            <path d="M10 14h4" />
                            <path d="M10 18h4" />
                        </symbol>
                        <symbol id="sidebar-icon-shield-check" viewBox="0 0 24 24">
                            <path
                                d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                            <path d="m9 12 2 2 4-4" />
                        </symbol>
                        <symbol id="sidebar-icon-user-round-x" viewBox="0 0 24 24">
                            <path d="M2 21a8 8 0 0 1 11.873-7" />
                            <circle cx="10" cy="8" r="5" />
                            <path d="m17 17 5 5" />
                            <path d="m22 17-5 5" />
                        </symbol>
                        <symbol id="sidebar-icon-badge-check" viewBox="0 0 24 24">
                            <path
                                d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                            <path d="m9 12 2 2 4-4" />
                        </symbol>
                        <symbol id="sidebar-icon-circle-x" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m15 9-6 6" />
                            <path d="m9 9 6 6" />
                        </symbol>
                        <symbol id="sidebar-icon-clock" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </symbol>
                        <symbol id="sidebar-icon-clipboard-check" viewBox="0 0 24 24">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                            <path d="m9 14 2 2 4-4" />
                        </symbol>
                        <symbol id="sidebar-icon-message-square-text" viewBox="0 0 24 24">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            <path d="M13 8H7" />
                            <path d="M17 12H7" />
                        </symbol>
                        <symbol id="sidebar-icon-mail" viewBox="0 0 24 24">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </symbol>
                        <symbol id="sidebar-icon-list-checks" viewBox="0 0 24 24">
                            <path d="m3 17 2 2 4-4" />
                            <path d="m3 7 2 2 4-4" />
                            <path d="M13 6h8" />
                            <path d="M13 12h8" />
                            <path d="M13 18h8" />
                        </symbol>
                        <symbol id="sidebar-icon-user-check" viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <polyline points="16 11 18 13 22 9" />
                        </symbol>
                        <symbol id="sidebar-icon-calendar-days" viewBox="0 0 24 24">
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M3 10h18" />
                            <path d="M8 14h.01" />
                            <path d="M12 14h.01" />
                            <path d="M16 14h.01" />
                            <path d="M8 18h.01" />
                            <path d="M12 18h.01" />
                            <path d="M16 18h.01" />
                        </symbol>
                        <symbol id="sidebar-icon-file-check-2" viewBox="0 0 24 24">
                            <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4" />
                            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                            <path d="m3 15 2 2 4-4" />
                        </symbol>
                        <symbol id="sidebar-icon-ellipsis" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="1" />
                            <circle cx="19" cy="12" r="1" />
                            <circle cx="5" cy="12" r="1" />
                        </symbol>
                        <symbol id="sidebar-icon-chevron-down" viewBox="0 0 24 24">
                            <path d="m6 9 6 6 6-6" />
                        </symbol>
                    </defs>
                </svg>

                <nav aria-label="Sidebar navigation" x-data="{ openGroup: null }"
                    x-init="openGroup = $el.querySelector('button[aria-expanded=true]')?.getAttribute('aria-controls') ?? null">
                    @if ($currentUser?->isAdmin() || $currentUser?->isEmployer() || $currentUser?->isApplicant())
                        <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                            <span class="menu-group-title">MENU</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-ellipsis menu-group-icon mx-auto"
                                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" data-lucide="ellipsis"
                                aria-hidden="true" focusable="false">
                                <use href="#sidebar-icon-ellipsis" />
                            </svg>
                        </h3>
                        <ul class="mb-6 flex flex-col gap-1">
                            <li>
                                <a href="{{ route('dashboard') }}"
                                    class="menu-item group {{ request()->routeIs('dashboard', 'client.Job-Application.') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                    aria-label="Dashboard" title="Dashboard"
                                    @if (request()->routeIs('dashboard', 'client.Job-Application.')) aria-current="page" @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="lucide lucide-layout-dashboard shrink-0 {{ request()->routeIs('dashboard', 'client.Job-Application.') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                        width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" data-lucide="layout-dashboard"
                                        aria-hidden="true" focusable="false">
                                        <use href="#sidebar-icon-layout-dashboard" />
                                    </svg>
                                    <span class="menu-item-text">Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}"
                                    class="menu-item group {{ request()->routeIs('profile.*', 'client.profile', 'employer.profile') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                    aria-label="User Profile" title="User Profile"
                                    @if (request()->routeIs('profile.*', 'client.profile', 'employer.profile')) aria-current="page" @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="lucide lucide-circle-user-round shrink-0 {{ request()->routeIs('profile.*', 'client.profile', 'employer.profile') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                        width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" data-lucide="circle-user-round"
                                        aria-hidden="true" focusable="false">
                                        <use href="#sidebar-icon-circle-user-round" />
                                    </svg>
                                    <span class="menu-item-text">User Profile</span>
                                </a>
                            </li>

                            @if ($currentUser->isAdmin())
                                <li>
                                    <button type="button"
                                        class="menu-item group w-full {{ request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        @click="openGroup = isDesktop && desktopSidebarCollapsed ? 'user-management' : (openGroup === 'user-management' ? null : 'user-management');
                                            if (isDesktop) { desktopSidebarCollapsed = false; }"
                                        aria-label="User Management" title="User Management" aria-controls="user-management"
                                        aria-expanded="{{ request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show') ? 'true' : 'false' }}"
                                        :aria-expanded="openGroup === 'user-management'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-users-round shrink-0 {{ request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="users-round"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-users-round" />
                                        </svg>
                                        <span class="menu-item-text">User Management</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chevron-down menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2"
                                            :class="openGroup === 'user-management' ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'"
                                            width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chevron-down"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chevron-down" />
                                        </svg>
                                    </button>
                                    <div id="user-management" class="sidebar-submenu"
                                        data-open="{{ request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show') ? 'true' : 'false' }}"
                                        :data-open="openGroup === 'user-management'"
                                        aria-hidden="{{ request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show') ? 'false' : 'true' }}"
                                        :aria-hidden="openGroup !== 'user-management'"
                                        @unless (request()->routeIs('applicants', 'Employers', 'administrators', 'suspended-accounts', 'applicants.profile.show')) inert @endunless
                                        :inert="openGroup !== 'user-management'">
                                        <div class="sidebar-submenu-content">
                                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                                <li>
                                                    <a href="{{ route('applicants') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('applicants', 'applicants.profile.show') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Applicants" title="Applicants"
                                                        @if (request()->routeIs('applicants', 'applicants.profile.show')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-user-round shrink-0 {{ request()->routeIs('applicants', 'applicants.profile.show') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="user-round"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-user-round" />
                                                        </svg>
                                                        <span class="menu-item-text">Applicants</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('Employers') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('Employers') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Employers" title="Employers"
                                                        @if (request()->routeIs('Employers')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-building-2 shrink-0 {{ request()->routeIs('Employers') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="building-2"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-building-2" />
                                                        </svg>
                                                        <span class="menu-item-text">Employers</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('administrators') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('administrators') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Administrators" title="Administrators"
                                                        @if (request()->routeIs('administrators')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-shield-check shrink-0 {{ request()->routeIs('administrators') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="shield-check"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-shield-check" />
                                                        </svg>
                                                        <span class="menu-item-text">Administrators</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('suspended-accounts') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('suspended-accounts') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Suspended Accounts" title="Suspended Accounts"
                                                        @if (request()->routeIs('suspended-accounts')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-user-round-x shrink-0 {{ request()->routeIs('suspended-accounts') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="user-round-x"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-user-round-x" />
                                                        </svg>
                                                        <span class="menu-item-text">Suspended Accounts</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <button type="button"
                                        class="menu-item group w-full {{ request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        @click="openGroup = isDesktop && desktopSidebarCollapsed ? 'job-management' : (openGroup === 'job-management' ? null : 'job-management');
                                            if (isDesktop) { desktopSidebarCollapsed = false; }"
                                        aria-label="Job Moderation" title="Job Moderation" aria-controls="job-management"
                                        aria-expanded="{{ request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs') ? 'true' : 'false' }}"
                                        :aria-expanded="openGroup === 'job-management'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-briefcase-business shrink-0 {{ request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="briefcase-business"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-briefcase-business" />
                                        </svg>
                                        <span class="menu-item-text">Job Moderation</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chevron-down menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2"
                                            :class="openGroup === 'job-management' ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'"
                                            width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chevron-down"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chevron-down" />
                                        </svg>
                                    </button>
                                    <div id="job-management" class="sidebar-submenu"
                                        data-open="{{ request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs') ? 'true' : 'false' }}"
                                        :data-open="openGroup === 'job-management'"
                                        aria-hidden="{{ request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs') ? 'false' : 'true' }}"
                                        :aria-hidden="openGroup !== 'job-management'"
                                        @unless (request()->routeIs('admin.jobs.*', 'open-jobs', 'suspended-jobs', 'closed-jobs')) inert @endunless
                                        :inert="openGroup !== 'job-management'">
                                        <div class="sidebar-submenu-content">
                                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                                <li>
                                                    <a href="{{ route('admin.jobs.index') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('admin.jobs.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="All Jobs" title="All Jobs"
                                                        @if (request()->routeIs('admin.jobs.*')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-briefcase-business shrink-0 {{ request()->routeIs('admin.jobs.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="briefcase-business"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-briefcase-business" />
                                                        </svg>
                                                        <span class="menu-item-text">All Jobs</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('open-jobs') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('open-jobs') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Open Jobs" title="Open Jobs"
                                                        @if (request()->routeIs('open-jobs')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-briefcase-business shrink-0 {{ request()->routeIs('open-jobs') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="briefcase-business"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-briefcase-business" />
                                                        </svg>
                                                        <span class="menu-item-text">Open Jobs</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('suspended-jobs') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('suspended-jobs') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Suspended Jobs" title="Suspended Jobs"
                                                        @if (request()->routeIs('suspended-jobs')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-circle-x shrink-0 {{ request()->routeIs('suspended-jobs') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="circle-x"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-circle-x" />
                                                        </svg>
                                                        <span class="menu-item-text">Suspended Jobs</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('closed-jobs') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('closed-jobs') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Closed Jobs" title="Closed Jobs"
                                                        @if (request()->routeIs('closed-jobs')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-circle-x shrink-0 {{ request()->routeIs('closed-jobs') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="circle-x"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-circle-x" />
                                                        </svg>
                                                        <span class="menu-item-text">Closed Jobs</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <button type="button"
                                        class="menu-item group w-full {{ request()->routeIs('assessment-templates', 'interview-templates', 'email-templates') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        @click="openGroup = isDesktop && desktopSidebarCollapsed ? 'recruitment-tools' : (openGroup === 'recruitment-tools' ? null : 'recruitment-tools');
                                            if (isDesktop) { desktopSidebarCollapsed = false; }"
                                        aria-label="Recruitment Tools" title="Recruitment Tools" aria-controls="recruitment-tools"
                                        aria-expanded="{{ request()->routeIs('assessment-templates', 'interview-templates', 'email-templates') ? 'true' : 'false' }}"
                                        :aria-expanded="openGroup === 'recruitment-tools'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-clipboard-list shrink-0 {{ request()->routeIs('assessment-templates', 'interview-templates', 'email-templates') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="clipboard-list"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-clipboard-list" />
                                        </svg>
                                        <span class="menu-item-text">Recruitment Tools</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chevron-down menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2"
                                            :class="openGroup === 'recruitment-tools' ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'"
                                            width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chevron-down"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chevron-down" />
                                        </svg>
                                    </button>
                                    <div id="recruitment-tools" class="sidebar-submenu"
                                        data-open="{{ request()->routeIs('assessment-templates', 'interview-templates', 'email-templates') ? 'true' : 'false' }}"
                                        :data-open="openGroup === 'recruitment-tools'"
                                        aria-hidden="{{ request()->routeIs('assessment-templates', 'interview-templates', 'email-templates') ? 'false' : 'true' }}"
                                        :aria-hidden="openGroup !== 'recruitment-tools'"
                                        @unless (request()->routeIs('assessment-templates', 'interview-templates', 'email-templates')) inert @endunless
                                        :inert="openGroup !== 'recruitment-tools'">
                                        <div class="sidebar-submenu-content">
                                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                                <li>
                                                    <a href="{{ route('assessment-templates') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('assessment-templates') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Assessment Templates" title="Assessment Templates"
                                                        @if (request()->routeIs('assessment-templates')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-clipboard-check shrink-0 {{ request()->routeIs('assessment-templates') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="clipboard-check"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-clipboard-check" />
                                                        </svg>
                                                        <span class="menu-item-text">Assessment Templates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('interview-templates') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('interview-templates') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Interview Templates" title="Interview Templates"
                                                        @if (request()->routeIs('interview-templates')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-message-square-text shrink-0 {{ request()->routeIs('interview-templates') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="message-square-text"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-message-square-text" />
                                                        </svg>
                                                        <span class="menu-item-text">Interview Templates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('email-templates') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('email-templates') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Email Templates" title="Email Templates"
                                                        @if (request()->routeIs('email-templates')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-mail shrink-0 {{ request()->routeIs('email-templates') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="mail"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-mail" />
                                                        </svg>
                                                        <span class="menu-item-text">Email Templates</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <a href="{{ route('Reports') }}"
                                        class="menu-item group {{ request()->routeIs('Reports', 'Reports.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        aria-label="Reports" title="Reports"
                                        @if (request()->routeIs('Reports', 'Reports.*')) aria-current="page" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chart-no-axes-combined shrink-0 {{ request()->routeIs('Reports', 'Reports.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chart-no-axes-combined"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chart-no-axes-combined" />
                                        </svg>
                                        <span class="menu-item-text">Reports</span>
                                    </a>
                                </li>
                            @elseif ($currentUser->isEmployer())
                                <li>
                                    <button type="button"
                                        class="menu-item group w-full {{ request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        @click="openGroup = isDesktop && desktopSidebarCollapsed ? 'candidates' : (openGroup === 'candidates' ? null : 'candidates');
                                            if (isDesktop) { desktopSidebarCollapsed = false; }"
                                        aria-label="Candidate" title="Candidate" aria-controls="candidates"
                                        aria-expanded="{{ request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show') ? 'true' : 'false' }}"
                                        :aria-expanded="openGroup === 'candidates'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-users-round shrink-0 {{ request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="users-round"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-users-round" />
                                        </svg>
                                        <span class="menu-item-text">Candidate</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chevron-down menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2"
                                            :class="openGroup === 'candidates' ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'"
                                            width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chevron-down"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chevron-down" />
                                        </svg>
                                    </button>
                                    <div id="candidates" class="sidebar-submenu"
                                        data-open="{{ request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show') ? 'true' : 'false' }}"
                                        :data-open="openGroup === 'candidates'"
                                        aria-hidden="{{ request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show') ? 'false' : 'true' }}"
                                        :aria-hidden="openGroup !== 'candidates'"
                                        @unless (request()->routeIs('employer.Applied-Candidates', 'employer.applications.*', 'applicants.profile.show')) inert @endunless
                                        :inert="openGroup !== 'candidates'">
                                        <div class="sidebar-submenu-content">
                                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                                <li>
                                                    <a href="{{ route('employer.Applied-Candidates') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('employer.Applied-Candidates') && (request()->query('stage', '') === '' || request()->query('stage') === null) ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Applied Candidates" title="Applied Candidates"
                                                        @if (request()->routeIs('employer.Applied-Candidates') && (request()->query('stage', '') === '' || request()->query('stage') === null)) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-users-round shrink-0 {{ request()->routeIs('employer.Applied-Candidates') && (request()->query('stage', '') === '' || request()->query('stage') === null) ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="users-round"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-users-round" />
                                                        </svg>
                                                        <span class="menu-item-text">Applied Candidates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('employer.Applied-Candidates', ['stage' => 'shortlisted']) }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'shortlisted' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="shortlisted Candidates" title="shortlisted Candidates"
                                                        @if (request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'shortlisted') aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-list-checks shrink-0 {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'shortlisted' ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="list-checks"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-list-checks" />
                                                        </svg>
                                                        <span class="menu-item-text">shortlisted Candidates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('employer.Applied-Candidates', ['stage' => 'interview']) }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'interview' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Interviewed Candidates" title="Interviewed Candidates"
                                                        @if (request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'interview') aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-calendar-days shrink-0 {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'interview' ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="calendar-days"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-calendar-days" />
                                                        </svg>
                                                        <span class="menu-item-text">Interviewed Candidates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('employer.Applied-Candidates', ['stage' => 'selected']) }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'selected' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Selected Candidates" title="Selected Candidates"
                                                        @if (request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'selected') aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-user-check shrink-0 {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'selected' ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="user-check"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-user-check" />
                                                        </svg>
                                                        <span class="menu-item-text">Selected Candidates</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('employer.Applied-Candidates', ['stage' => 'rejected']) }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'rejected' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Rejected Candidates" title="Rejected Candidates"
                                                        @if (request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'rejected') aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-user-round-x shrink-0 {{ request()->routeIs('employer.Applied-Candidates') && request()->query('stage') === 'rejected' ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="user-round-x"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-user-round-x" />
                                                        </svg>
                                                        <span class="menu-item-text">Rejected Candidates</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <a href="{{ route('jobs') }}"
                                        class="menu-item group {{ request()->routeIs('jobs', 'jobs.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        aria-label="Manage Jobs" title="Manage Jobs"
                                        @if (request()->routeIs('jobs', 'jobs.*')) aria-current="page" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-briefcase-business shrink-0 {{ request()->routeIs('jobs', 'jobs.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="briefcase-business"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-briefcase-business" />
                                        </svg>
                                        <span class="menu-item-text">Manage Jobs</span>
                                    </a>
                                </li>
                                @isset($currentOrganization)
                                    @can('manageTeam', $currentOrganization)
                                        <li>
                                            <a href="{{ route('employer.team.index') }}"
                                                class="menu-item group {{ request()->routeIs('employer.team.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                                aria-label="Hiring Team" title="Hiring Team"
                                                @if (request()->routeIs('employer.team.*')) aria-current="page" @endif>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="lucide lucide-users-round shrink-0 {{ request()->routeIs('employer.team.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                    width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" data-lucide="users-round"
                                                    aria-hidden="true" focusable="false">
                                                    <use href="#sidebar-icon-users-round" />
                                                </svg>
                                                <span class="menu-item-text">Hiring Team</span>
                                            </a>
                                        </li>
                                    @endcan
                                @endisset
                            @elseif ($currentUser->isApplicant())
                                <li>
                                    <a href="{{ route('Client.Application') }}"
                                        class="menu-item group {{ request()->routeIs('Client.Application', 'client.jobs', 'client.applications.*', 'applications.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        aria-label="Applications" title="Applications"
                                        @if (request()->routeIs('Client.Application', 'client.jobs', 'client.applications.*', 'applications.*')) aria-current="page" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-file-check-2 shrink-0 {{ request()->routeIs('Client.Application', 'client.jobs', 'client.applications.*', 'applications.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="file-check-2"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-file-check-2" />
                                        </svg>
                                        <span class="menu-item-text">Applications</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('client.jobs-listings') }}"
                                        class="menu-item group {{ request()->routeIs('client.jobs-listings', 'job-details') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        aria-label="Jobs" title="Jobs"
                                        @if (request()->routeIs('client.jobs-listings', 'job-details')) aria-current="page" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-briefcase-business shrink-0 {{ request()->routeIs('client.jobs-listings', 'job-details') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="briefcase-business"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-briefcase-business" />
                                        </svg>
                                        <span class="menu-item-text">Jobs</span>
                                    </a>
                                </li>
                            @endif
                        </ul>

                        <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                            <span class="menu-group-title">System</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-ellipsis menu-group-icon mx-auto"
                                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" data-lucide="ellipsis"
                                aria-hidden="true" focusable="false">
                                <use href="#sidebar-icon-ellipsis" />
                            </svg>
                        </h3>
                        <ul class="mb-6 flex flex-col gap-1">
                            @if ($currentUser->isAdmin())
                                <li>
                                    <button type="button"
                                        class="menu-item group w-full {{ request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        @click="openGroup = isDesktop && desktopSidebarCollapsed ? 'settings' : (openGroup === 'settings' ? null : 'settings');
                                            if (isDesktop) { desktopSidebarCollapsed = false; }"
                                        aria-label="Settings" title="Settings" aria-controls="settings"
                                        aria-expanded="{{ request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings') ? 'true' : 'false' }}"
                                        :aria-expanded="openGroup === 'settings'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-settings-2 shrink-0 {{ request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="settings-2"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-settings-2" />
                                        </svg>
                                        <span class="menu-item-text">Settings</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-chevron-down menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2"
                                            :class="openGroup === 'settings' ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive'"
                                            width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="chevron-down"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-chevron-down" />
                                        </svg>
                                    </button>
                                    <div id="settings" class="sidebar-submenu"
                                        data-open="{{ request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings') ? 'true' : 'false' }}"
                                        :data-open="openGroup === 'settings'"
                                        aria-hidden="{{ request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings') ? 'false' : 'true' }}"
                                        :aria-hidden="openGroup !== 'settings'"
                                        @unless (request()->routeIs('general-settings', 'email-configuration', 'permission-management', 'employer.settings')) inert @endunless
                                        :inert="openGroup !== 'settings'">
                                        <div class="sidebar-submenu-content">
                                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                                <li>
                                                    <a href="{{ route('general-settings') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('general-settings', 'employer.settings') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="General Settings" title="General Settings"
                                                        @if (request()->routeIs('general-settings', 'employer.settings')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-settings-2 shrink-0 {{ request()->routeIs('general-settings', 'employer.settings') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="settings-2"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-settings-2" />
                                                        </svg>
                                                        <span class="menu-item-text">General Settings</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('email-configuration') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('email-configuration') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Email Configuration" title="Email Configuration"
                                                        @if (request()->routeIs('email-configuration')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-mail shrink-0 {{ request()->routeIs('email-configuration') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="mail"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-mail" />
                                                        </svg>
                                                        <span class="menu-item-text">Email Configuration</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('permission-management') }}"
                                                        class="menu-dropdown-item group {{ request()->routeIs('permission-management') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                        aria-label="Permission Management" title="Permission Management"
                                                        @if (request()->routeIs('permission-management')) aria-current="page" @endif>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="lucide lucide-shield-check shrink-0 {{ request()->routeIs('permission-management') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                            width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="shield-check"
                                                            aria-hidden="true" focusable="false">
                                                            <use href="#sidebar-icon-shield-check" />
                                                        </svg>
                                                        <span class="menu-item-text">Permission Management</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                            @elseif ($currentUser->isEmployer())
                                <li>
                                    <a href="{{ route('profile.edit') }}"
                                        class="menu-item group menu-item-inactive"
                                        aria-label="Account Settings" title="Account Settings">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-settings-2 shrink-0 menu-item-icon-inactive"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="settings-2"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-settings-2" />
                                        </svg>
                                        <span class="menu-item-text">Account Settings</span>
                                    </a>
                                </li>
                            @elseif ($currentUser->isApplicant())
                                <li>
                                    <a href="#"
                                        class="menu-item group {{ request()->routeIs('client.settings') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                        aria-label="Settings" title="Settings"
                                        @if (request()->routeIs('client.settings')) aria-current="page" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="lucide lucide-settings-2 shrink-0 {{ request()->routeIs('client.settings') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" data-lucide="settings-2"
                                            aria-hidden="true" focusable="false">
                                            <use href="#sidebar-icon-settings-2" />
                                        </svg>
                                        <span class="menu-item-text">Settings</span>
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ $notificationsUrl }}"
                                    class="menu-item group {{ request()->routeIs('notifications', 'notifications.*', 'client.notifications', 'employer.notifications') ? 'menu-item-active' : 'menu-item-inactive' }}"
                                    aria-label="Notifications" title="Notifications"
                                    @if (request()->routeIs('notifications', 'notifications.*', 'client.notifications', 'employer.notifications')) aria-current="page" @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="lucide lucide-bell shrink-0 {{ request()->routeIs('notifications', 'notifications.*', 'client.notifications', 'employer.notifications') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                        width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" data-lucide="bell"
                                        aria-hidden="true" focusable="false">
                                        <use href="#sidebar-icon-bell" />
                                    </svg>
                                    <span class="menu-item-text">Notifications</span>
                                </a>
                            </li>
                        </ul>
                    @endif
                </nav>
                <!-- Sidebar Menu -->


            </div>
        </aside>

        <!-- ===== Sidebar End ===== -->

        <!-- ===== Content Area Start ===== -->
        <!-- Small Device Overlay Start -->
        <div x-cloak x-show="sidebarToggle && !isDesktop" @click="closeSidebar()"
            class="sidebar-backdrop fixed inset-0 bg-gray-900/50" aria-hidden="true">
        </div>
        <!-- Small Device Overlay End -->
        <div :inert="!isDesktop && sidebarToggle"
            class="relative flex min-w-0 flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <!-- ===== Header Start ===== -->
            <header x-data="{ menuToggle: false }"
                class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white xl:border-b dark:border-gray-800 dark:bg-gray-900">
                <div class="flex grow flex-col items-center justify-between xl:flex-row xl:px-6">
                    <div
                        class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:py-4 xl:justify-normal xl:border-b-0 xl:px-0 dark:border-gray-800">
                        <!-- Hamburger Toggle BTN -->
                        <button type="button"
                            :class="sidebarToggle ? 'xl:bg-transparent dark:xl:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
                            class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 xl:h-11 xl:w-11 xl:border dark:border-gray-800 dark:text-gray-400"
                            aria-controls="application-sidebar"
                            :aria-expanded="(isDesktop ? !desktopSidebarCollapsed : sidebarToggle).toString()"
                            :aria-label="isDesktop ? (desktopSidebarCollapsed ? 'Expand navigation' : 'Collapse navigation') : (
                                sidebarToggle ? 'Close navigation' : 'Open navigation')"
                            @click.stop="toggleSidebar($el)">
                            <svg class="hidden fill-current xl:block" width="16" height="12"
                                viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                                    fill="" />
                            </svg>

                            <svg :class="sidebarToggle ? 'hidden' : 'block xl:hidden'" class="fill-current xl:hidden"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M3.25 6C3.25 5.58579 3.58579 5.25 4 5.25L20 5.25C20.4142 5.25 20.75 5.58579 20.75 6C20.75 6.41421 20.4142 6.75 20 6.75L4 6.75C3.58579 6.75 3.25 6.41422 3.25 6ZM3.25 18C3.25 17.5858 3.58579 17.25 4 17.25L20 17.25C20.4142 17.25 20.75 17.5858 20.75 18C20.75 18.4142 20.4142 18.75 20 18.75L4 18.75C3.58579 18.75 3.25 18.4142 3.25 18ZM4 11.25C3.58579 11.25 3.25 11.5858 3.25 12C3.25 12.4142 3.58579 12.75 4 12.75L12 12.75C12.4142 12.75 12.75 12.4142 12.75 12C12.75 11.5858 12.4142 11.25 12 11.25L4 11.25Z"
                                    fill="" />
                            </svg>

                            <!-- cross icon -->
                            <svg :class="sidebarToggle ? 'block xl:hidden' : 'hidden'" class="fill-current"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                                    fill="" />
                            </svg>
                        </button>
                        <!-- Hamburger Toggle BTN -->

                        <a href="{{ route('dashboard') }}" class="xl:hidden">
                            <img class="dark:hidden" src="{{ asset('assets/images/logo.png') }}" alt="Logo"
                                width="70" height="70" />
                            <img class="hidden dark:block" src="{{ asset('assets/images/logo.png') }}"
                                alt="Logo" width="70" height="70" />
                        </a>

                        <!-- Application nav menu button -->
                        <button type="button"
                            class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 xl:hidden dark:text-gray-400 dark:hover:bg-gray-800"
                            :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''"
                            aria-controls="application-header-menu" :aria-expanded="menuToggle.toString()"
                            aria-label="Toggle account navigation" @click.stop="menuToggle = !menuToggle">
                            <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
                                    fill="" />
                            </svg>
                        </button>
                        <!-- Application nav menu button -->
                    </div>

                    <div id="application-header-menu" :class="menuToggle ? 'flex' : 'hidden'"
                        class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 xl:flex xl:justify-end xl:px-0 xl:shadow-none">


                        <!-- Notification Area -->
                        <div class="relative" x-data="{ dropdownOpen: false, notifying: true }" @click.outside="dropdownOpen = false">
                            @if ($unreadNotificationCount > 0)
                                <span
                                    class="absolute -top-0.5 -right-0.5 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-orange-400 text-[10px] font-bold text-white">
                                    {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                </span>
                            @endif

                            <button
                                class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                @click.prevent="dropdownOpen = !dropdownOpen; notifying = false">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                                        fill=""></path>
                                </svg>

                                <span :class="!notifying ? 'hidden' : 'flex'"
                                    class="absolute top-0.5 right-0 z-1 h-2 w-2 rounded-full bg-orange-400 flex">
                                    <span
                                        class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                                </span>

                            </button>

                            <!-- Dropdown Start -->
                            <div x-show="dropdownOpen"
                                class="shadow-theme-lg dark:bg-gray-dark absolute -left-15 mt-4.25 flex h-120 w-87.5 flex-col rounded-2xl border border-gray-200 bg-white p-3 sm:w-90.25 xl:right-0 xl:left-auto dark:border-gray-800"
                                style="display: none;">
                                <div
                                    class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                    <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                        Notification
                                    </h5>

                                    <button @click="dropdownOpen = false" class="text-gray-500 dark:text-gray-400">
                                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                                                fill=""></path>
                                        </svg>
                                    </button>
                                </div>

                                @if ($recentNotifications->isNotEmpty())
                                    <ul class="custom-scrollbar flex h-auto flex-col overflow-y-auto">
                                        @foreach ($recentNotifications as $notification)
                                            <li>
                                                <a href="{{ $notification->data['action_url'] ?? $notificationsUrl }}"
                                                    class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5">
                                                    <span
                                                        class="relative z-1 flex h-10 w-full max-w-10 items-center justify-center rounded-full {{ ($notification->data['category'] ?? null) === 'security' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300' : '' }}">
                                                        @if (($notification->data['category'] ?? null) === 'security')
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5"
                                                                viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                aria-hidden="true">
                                                                <path
                                                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                                                <path d="m9 12 2 2 4-4" />
                                                            </svg>
                                                        @else
                                                            <img src="{{ asset('admin/assets/img/Avatar.png') }}"
                                                                alt="User" class="overflow-hidden rounded-full">
                                                        @endif
                                                        @if (is_null($notification->read_at))
                                                            <span
                                                                class="bg-orange-400 absolute right-0 bottom-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white dark:border-gray-900"></span>
                                                        @else
                                                            <span
                                                                class="bg-success-500 absolute right-0 bottom-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white dark:border-gray-900"></span>
                                                        @endif
                                                    </span>

                                                    <span class="block">
                                                        <span
                                                            class="text-theme-sm mb-1.5 block text-gray-500 dark:text-gray-400">
                                                            <span class="font-medium text-gray-800 dark:text-white/90">
                                                                {{ $notification->data['title'] ?? 'Notification' }}
                                                            </span>
                                                            {{ $notification->data['message'] ?? '' }}
                                                        </span>

                                                        <span
                                                            class="text-theme-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div
                                        class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                                        @if ($notificationsReadAllUrl)
                                        <form method="POST" action="{{ $notificationsReadAllUrl }}">
                                            @csrf
                                            @unless ($currentUser?->isEmployer()) @method('PATCH') @endunless
                                            <button type="submit"
                                                class="text-theme-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                                Mark All as Read
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ $notificationsUrl }}"
                                            class="text-theme-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                            View All
                                        </a>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center py-10">
                                        <svg class="mb-3 fill-gray-400" width="48" height="48"
                                            viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M24 4C12.954 4 4 12.954 4 24s8.954 20 20 20 20-8.954 20-20S35.046 4 24 4zm0 36c-8.837 0-16-7.163-16-16S15.163 8 24 8s16 7.163 16 16-7.163 16-16 16zm1-25h-2v6h2v-6zm0 8h-2v2h2v-2z"
                                                fill=""></path>
                                        </svg>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No
                                            notifications yet</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">You're all caught up.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <!-- Dropdown End -->
                        </div>
                        <!-- Notification Area -->

                        <!-- User Area -->
                        @auth
                            <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                                <a class="flex items-center text-gray-700 dark:text-gray-400" href="#"
                                    @click.prevent="dropdownOpen = ! dropdownOpen">
                                    <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
                                        <img src="{{ $currentUser->profileImageUrl() }}"
                                            alt="{{ $currentUser->first_name }}">

                                    </span>

                                    <span class="text-theme-sm mr-1 block font-medium">{{ $currentUser->first_name }}
                                        {{ $currentUser->last_name }}</span>

                                    <svg :class="dropdownOpen && 'rotate-180'"
                                        class="stroke-gray-500 dark:stroke-gray-400" width="18" height="20"
                                        viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.3125 8.65625L9 13.3437L13.6875 8.65625" stroke=""
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>

                                <!-- Dropdown Start -->
                                <div x-show="dropdownOpen"
                                    class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800">
                                    <div>

                                        <span class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400">
                                            <a href="#" class="__cf_email__">
                                                {{ $currentUser->email }}</a>
                                        </span>
                                    </div>

                                    <ul
                                        class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800">
                                        <li>
                                            <a href="{{ route('profile.edit') }}"
                                                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                                <svg class="fill-gray-500 group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z"
                                                        fill="" />
                                                </svg>
                                                Edit profile
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#"
                                                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                                <svg class="fill-gray-500 group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M10.4858 3.5L13.5182 3.5C13.9233 3.5 14.2518 3.82851 14.2518 4.23377C14.2518 5.9529 16.1129 7.02795 17.602 6.1682C17.9528 5.96567 18.4014 6.08586 18.6039 6.43667L20.1203 9.0631C20.3229 9.41407 20.2027 9.86286 19.8517 10.0655C18.3625 10.9253 18.3625 13.0747 19.8517 13.9345C20.2026 14.1372 20.3229 14.5859 20.1203 14.9369L18.6039 17.5634C18.4013 17.9142 17.9528 18.0344 17.602 17.8318C16.1129 16.9721 14.2518 18.0471 14.2518 19.7663C14.2518 20.1715 13.9233 20.5 13.5182 20.5H10.4858C10.0804 20.5 9.75182 20.1714 9.75182 19.766C9.75182 18.0461 7.88983 16.9717 6.40067 17.8314C6.04945 18.0342 5.60037 17.9139 5.39767 17.5628L3.88167 14.937C3.67903 14.586 3.79928 14.1372 4.15026 13.9346C5.63949 13.0748 5.63946 10.9253 4.15025 10.0655C3.79926 9.86282 3.67901 9.41401 3.88165 9.06303L5.39764 6.43725C5.60034 6.08617 6.04943 5.96581 6.40065 6.16858C7.88982 7.02836 9.75182 5.9539 9.75182 4.23399C9.75182 3.82862 10.0804 3.5 10.4858 3.5ZM13.5182 2L10.4858 2C9.25201 2 8.25182 3.00019 8.25182 4.23399C8.25182 4.79884 7.64013 5.15215 7.15065 4.86955C6.08213 4.25263 4.71559 4.61859 4.0986 5.68725L2.58261 8.31303C1.96575 9.38146 2.33183 10.7477 3.40025 11.3645C3.88948 11.647 3.88947 12.3531 3.40026 12.6355C2.33184 13.2524 1.96578 14.6186 2.58263 15.687L4.09863 18.3128C4.71562 19.3814 6.08215 19.7474 7.15067 19.1305C7.64015 18.8479 8.25182 19.2012 8.25182 19.766C8.25182 20.9998 9.25201 22 10.4858 22H13.5182C14.7519 22 15.7518 20.9998 15.7518 19.7663C15.7518 19.2015 16.3632 18.8487 16.852 19.1309C17.9202 19.7476 19.2862 19.3816 19.9029 18.3134L21.4193 15.6869C22.0361 14.6185 21.6701 13.2523 20.6017 12.6355C20.1125 12.3531 20.1125 11.647 20.6017 11.3645C21.6701 10.7477 22.0362 9.38152 21.4193 8.3131L19.903 5.68667C19.2862 4.61842 17.9202 4.25241 16.852 4.86917C16.3632 5.15138 15.7518 4.79856 15.7518 4.23377C15.7518 3.00024 14.7519 2 13.5182 2ZM9.6659 11.9999C9.6659 10.7103 10.7113 9.66493 12.0009 9.66493C13.2905 9.66493 14.3359 10.7103 14.3359 11.9999C14.3359 13.2895 13.2905 14.3349 12.0009 14.3349C10.7113 14.3349 9.6659 13.2895 9.6659 11.9999ZM12.0009 8.16493C9.88289 8.16493 8.1659 9.88191 8.1659 11.9999C8.1659 14.1179 9.88289 15.8349 12.0009 15.8349C14.1189 15.8349 15.8359 14.1179 15.8359 11.9999C15.8359 9.88191 14.1189 8.16493 12.0009 8.16493Z"
                                                        fill="" />
                                                </svg>
                                                Account settings
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('profile.edit') }}"
                                                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                                <svg class="fill-gray-500 group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M3.5 12C3.5 7.30558 7.30558 3.5 12 3.5C16.6944 3.5 20.5 7.30558 20.5 12C20.5 16.6944 16.6944 20.5 12 20.5C7.30558 20.5 3.5 16.6944 3.5 12ZM12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM11.0991 7.52507C11.0991 8.02213 11.5021 8.42507 11.9991 8.42507H12.0001C12.4972 8.42507 12.9001 8.02213 12.9001 7.52507C12.9001 7.02802 12.4972 6.62507 12.0001 6.62507H11.9991C11.5021 6.62507 11.0991 7.02802 11.0991 7.52507ZM12.0001 17.3714C11.5859 17.3714 11.2501 17.0356 11.2501 16.6214V10.9449C11.2501 10.5307 11.5859 10.1949 12.0001 10.1949C12.4143 10.1949 12.7501 10.5307 12.7501 10.9449V16.6214C12.7501 17.0356 12.4143 17.3714 12.0001 17.3714Z"
                                                        fill="" />
                                                </svg>
                                                Support
                                            </a>
                                        </li>
                                    </ul>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button :href="('logout')"
                                            class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                            <svg class="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497L14.3507 14.245H12.8507V18.497C12.8507 19.7396 13.8581 20.747 15.1007 20.747H18.5007C19.7434 20.747 20.7507 19.7396 20.7507 18.497L20.7507 5.49609C20.7507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007C13.8581 3.24609 12.8507 4.25345 12.8507 5.49609V9.74501L14.3507 9.74501V5.49609C14.3507 5.08188 14.6865 4.74609 15.1007 4.74609L18.5007 4.74609C18.9149 4.74609 19.2507 5.08188 19.2507 5.49609L19.2507 18.497C19.2507 18.9112 18.9149 19.247 18.5007 19.247H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559C9.44848 16.8631 9.44863 16.3882 9.15583 16.0952L5.81116 12.7484L16.0007 12.7484C16.4149 12.7484 16.7507 12.4127 16.7507 11.9984C16.7507 11.5842 16.4149 11.2484 16.0007 11.2484L5.81528 11.2484L9.15585 7.90554C9.44864 7.61255 9.44847 7.13767 9.15547 6.84488C8.86248 6.55209 8.3876 6.55226 8.09481 6.84525L3.52309 11.4202C3.35673 11.5577 3.25073 11.7657 3.25073 11.9984Z"
                                                    fill="" />
                                            </svg>
                                            {{ __('Log Out') }}

                                        </button>

                                    </form>
                                </div>
                                <!-- Dropdown End -->
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <a href="{{ route('login') }}"
                                    class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Login</a>
                                <a href="{{ route('register') }}"
                                    class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Register</a>
                            </div>
                        @endauth
                        <!-- User Area -->
                    </div>
                </div>
            </header>
            <!-- ===== Header End ===== -->

            <!-- ===== Main Content Start ===== -->
            {{ $slot }}
            <!-- ===== Main Content End ===== -->
        </div>
        <!-- ===== Content Area End ===== -->
    </div>
    <!-- ===== Page Wrapper End ===== -->
    <div id="request-recovery" role="alert" aria-live="assertive" hidden
        class="fixed inset-x-4 top-4 z-50 mx-auto hidden max-w-lg rounded-xl border border-gray-200 bg-white p-5 shadow-lg dark:border-gray-700 dark:bg-gray-900">
        <p data-recovery-message class="text-sm leading-6 text-gray-700 dark:text-gray-300"></p>
        <div class="mt-4 flex items-center justify-between gap-4">
            <a data-recovery-action href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">Continue</a>
            <button data-recovery-dismiss type="button" aria-label="Dismiss recovery notice" class="min-h-[44px] px-3 text-sm text-gray-500 dark:text-gray-400">Dismiss</button>
        </div>
    </div>
    <script src="{{ asset('assets/js/request-recovery.js') }}"></script>
    <script defer src="{{ asset('assets/js/bundle.js') }}"></script>
    <script src="{{ asset('admin/assets/js/Application.js') }}"></script>
    @include('components.sweet-alerts')
    <script src="{{ asset('assets/js/sweetalert.js') }}"></script>
    <script src="{{ asset('assets/js/app-alerts.js') }}"></script>
    <script src="{{ asset('assets/js/table-refresh.js') }}"></script>
    @stack('page-scripts')
</body>


</html>
