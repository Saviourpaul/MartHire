@php
    $pageTitle = html_entity_decode(trim((string) ($title ?? 'Hiring starts with a connection')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pageDescription = $description ?? 'Marthire brings employers and job seekers together. Discover a more connected way to approach hiring and your next opportunity.';
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $pageDescription }}">
<meta name="color-scheme" content="light dark">
<meta name="application-name" content="Marthire">
<meta property="og:site_name" content="Marthire">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }} | Marthire">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('asset/images/marthire-logo.png') }}">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" type="image/png" href="{{ asset('asset/images/marthire-logo.png') }}">
<title>{{ $pageTitle }} | Marthire</title>

<script>
    (() => {
        let preference = 'system';
        try {
            // Read the old preference once, then keep only the Marthire key.
            const legacyThemeKey = 'nearbyworks-theme';
            let savedPreference = window.localStorage.getItem('marthire-theme');
            if (savedPreference === null) {
                savedPreference = window.localStorage.getItem(legacyThemeKey);
                if (['system', 'light', 'dark'].includes(savedPreference)) {
                    window.localStorage.setItem('marthire-theme', savedPreference);
                }
            }
            window.localStorage.removeItem(legacyThemeKey);
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
