<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>403 Forbidden</title><link href="{{ asset('assets/css/mains.css') }}" rel="stylesheet"></head>
<body class="bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center p-6"><section class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 text-center shadow-theme-sm"><p class="text-theme-sm font-medium text-error-600">403</p><h1 class="mt-2 text-2xl font-semibold">Access denied</h1><p class="mt-3 text-theme-sm text-gray-600">{{ $exception->getMessage() ?: 'You do not have permission to access this resource.' }}</p><a href="{{ route('home') }}" class="mt-6 inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white">Return home</a></section></main>
</body>
</html>
