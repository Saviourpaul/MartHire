@php
    $pageTitle = html_entity_decode(trim((string) ($title ?? 'Hiring starts with a connection')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pageDescription = $description ?? 'NearbyWorks brings employers and job seekers together. Discover a more connected way to approach hiring and your next opportunity.';
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $pageDescription }}">
<meta name="color-scheme" content="light dark">
<title>{{ $pageTitle }} | NearbyWorks</title>

<script>
    (() => {
        let preference = 'system';
        try {
            const savedPreference = window.localStorage.getItem('nearbyworks-theme');
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
@vite(['resources/css/app.css', 'resources/js/app.js'], 'asset/nearbyworks/build')