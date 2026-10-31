@props([
    'title' => 'Hiring starts with a connection',
    'description' => 'Marthire brings employers and job seekers together. Discover a more connected way to approach hiring and your next opportunity.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.site-head', ['title' => $title, 'description' => $description])
    </head>
    <body>
        <a class="nw-skip-link" href="#main-content">{{ __('Skip to content') }}</a>
        <x-site-header />

        <main id="main-content">
            {{ $slot }}
        </main>

        <x-site-footer />
        <x-toast-notifications />
    </body>
</html>
