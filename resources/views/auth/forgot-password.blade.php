<!doctype html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<head>
  <meta charset="UTF-8" />
  <meta name="viewport"
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>forgot-password — MartHire</title>
<meta name="title" content="forgot-password — MartHire">
<meta name="description" content="Log in to your MartHire account to manage recruitment campaigns, job vacancies, applications, and candidates.">

<link rel="canonical" href="https://marthire.com/forgot-password">

<!-- Do NOT index auth pages -->
<meta name="robots" content="noindex, nofollow">

<!-- Open Graph (minimal — these won't be shared, but keep consistent) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://marthire.com/forgot-password">
<meta property="og:title" content="forgot-password — MartHire">
<meta property="og:description" content="Log in to your MartHire account to manage recruitment campaigns, job vacancies, applications, and candidates.">
<meta property="og:image" content="https://marthire.com/og-image.png">
<meta property="og:site_name" content="MartHire">

<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="forgot-password — MartHire">
<meta name="twitter:description" content="Log in to your MartHire account.">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link href="{{ asset('assets/css/mains.css') }}" rel="stylesheet">
</head>

<body>
  <!-- ===== Preloader Start ===== -->


  <!-- ===== Preloader End ===== -->

  <!-- ===== Page Wrapper Start ===== -->
  <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
    <div class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
      <!-- Form -->
      <div class="flex w-full flex-1 flex-col lg:w-1/2">
        <div class="mx-auto w-full max-w-md pt-10">
          <a href="{{ route('home') }}"
            class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            <svg class="stroke-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
              fill="none">
              <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke="" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
            Back to Home
          </a>
        </div>
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
          <div>
            <div class="mb-5 sm:mb-8">
              <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                Forgot Your Password?
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                No problem. Just let us know your email address and we will email you a password reset link.
              </p>
            </div>
            @if (session('success'))
              <div x-data="{ show: true }" x-show="show" x-transition
                x-init="setTimeout(() => show = false, 6000)"
                class="mb-5 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                {{ session('success') }}
              </div>
            @endif
            <div>
              
              <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="space-y-5">
                  <!-- Email -->
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                      Email<span class="text-error-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" placeholder="info@gmail.com" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" 
                      class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                      <x-input-error :messages="$errors->get('email')" />
                  </div>
                 
                  <div>
                    <button
                      class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                      Email Password Reset Link
                    </button>
                  </div>
                </div>
              </form>
              <div class="mt-5">
                <p class="text-center text-sm font-normal text-gray-700 sm:text-start dark:text-gray-400">
                  Remembered your password?
                  <a href="{{ route('login') }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400">login</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-brand-950 relative hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5">
        <div class="z-1 flex items-center justify-center">
          <div class="flex max-w-xs flex-col items-center">
            <a href="Home" class="mb-4 block">
              <img src="{{ asset('assets/images/logo2.png') }}" alt="Logo" />
            </a>
            
          </div>
        </div>
      </div>
      <!-- Toggler -->
      
    </div>
  </div>
  <!-- ===== Page Wrapper End ===== -->
  <script defer src="{{ asset('assets/js/bundle.js') }}"></script>
</body>

</html>
