@props([
    'title' => 'Hiring starts with a connection',
    'description' => 'MartHire brings organizations and job seekers together. Discover a more connected way to approach hiring and your next opportunity.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
    $pageTitle = html_entity_decode(trim((string) ($title ?? 'Hiring starts with a connection')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pageDescription = $description ?? 'MartHire brings organizations and job seekers together. Discover a more connected way to approach hiring and your next opportunity.';
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $pageDescription }}">
<meta name="color-scheme" content="light dark">
<meta name="application-name" content="MartHire">
<meta property="og:site_name" content="MartHire">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }} | MartHire">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('asset/images/favicon.ico') }}">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" type="image" href="{{ asset('asset/images/favicon.ico') }}">
<title>{{ $pageTitle }} | MartHire</title>

<script>
    (() => {
        let preference = 'system';
        try {
            const themeKey = 'marthire-theme';
            const legacyThemeKeys = ['MartHire-theme', 'nearbyworks-theme'];
            let savedPreference = window.localStorage.getItem(themeKey);
            if (!['system', 'light', 'dark'].includes(savedPreference)) {
                for (const legacyThemeKey of legacyThemeKeys) {
                    const legacyPreference = window.localStorage.getItem(legacyThemeKey);
                    if (['system', 'light', 'dark'].includes(legacyPreference)) {
                        savedPreference = legacyPreference;
                        window.localStorage.setItem(themeKey, legacyPreference);
                        break;
                    }
                }
            }
            legacyThemeKeys.forEach(key => window.localStorage.removeItem(key));
            if (['system', 'light', 'dark'].includes(savedPreference)) {
                preference = savedPreference;
            }
        } catch (_) {}

        const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.dataset.themePreference = preference;
        document.documentElement.dataset.theme = preference === 'system'
            ? (systemIsDark ? 'dark' : 'light')
            : preference;
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'], 'asset/marthire/build')

    </head>
    <body>
        <x-site-header />

        <main id="main-content">
            {{ $slot }}
        </main>

        <x-site-footer />
        <x-toast-notifications />
    </body>
</html>
