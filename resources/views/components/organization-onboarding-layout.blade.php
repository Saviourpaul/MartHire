@props([
    'title' => __('Organization setup'),
    'currentStepLabel' => null,
    'currentStepNumber' => 0,
    'stepCount' => 0,
    'progressPercent' => 0,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="Marthire">
    <meta name="theme-color" content="#f7f8fd">
    <link rel="icon" type="image" href="{{ asset('asset/images/favicon.ico') }}">
    <title>{{ $title }} | Marthire</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'asset/marthire/build')
</head>

<body class="nw-onboarding-page">
    <a class="nw-skip-link" href="#main-content">{{ __('Skip to setup') }}</a>
    <div class="nw-onboarding-frame">
      <main id="main-content" class="nw-onboarding-shell">
        <header class="nw-onboarding-header">
            <a class="nw-onboarding-brand" href="{{ route('organisations.onboarding.show') }}" aria-label="{{ __('Marthire organization setup') }}">
                <x-application-logo />
            </a>
            <div class="nw-onboarding-header__right">
                @if ($currentStepLabel && $stepCount > 0)
                    <div class="nw-onboarding-progress-summary" data-onboarding-progress role="progressbar"
                        aria-label="{{ __('Organization setup progress') }}" aria-valuemin="1"
                        aria-valuemax="{{ $stepCount }}" aria-valuenow="{{ $currentStepNumber }}">
                        <span data-onboarding-progress-label>{{ __('Step :current of :total · :label', ['current' => $currentStepNumber, 'total' => $stepCount, 'label' => $currentStepLabel]) }}</span>
                        <div class="nw-onboarding-progress-summary__track" aria-hidden="true">
                            <span data-onboarding-progress-fill style="width: {{ $progressPercent }}%"></span>
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nw-onboarding-logout">{{ __('Sign out') }}</button>
                </form>
            </div>
        </header>

        <div class="nw-onboarding-fragment" data-onboarding-fragment>
            {{ $slot }}
        </div>
      </main>
    </div>

    <x-toast-notifications />
</body>

</html>
