@props(['title' => 'MartHire'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/mains.css') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100" x-data="{ darkMode: false }"
    x-init="try { darkMode = localStorage.getItem('darkMode') === 'true' } catch (error) {} document.documentElement.classList.toggle('dark', darkMode)">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-lg space-y-8">
            <a href="{{ route('home') }}" class="flex justify-center" aria-label="MartHire home">
                <img src="{{ asset('assets/images/logo.png') }}" alt="MartHire" width="150" class="dark:hidden">
                <img src="{{ asset('assets/images/logo2.png') }}" alt="MartHire" width="150" class="hidden dark:block">
            </a>
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
                {{ $slot }}
            </section>
        </div>
    </main>
    <script defer src="{{ asset('assets/js/bundle.js') }}"></script>
</body>
</html>
