<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email | MartHire</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
   <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 dark:bg-gray-900">
    <div class="w-full max-w-md"> 
        {{-- Card --}}
        <main class="rounded-2xl bg-white p-8 shadow-xl shadow-[#022A5E]/5 ring-1 ring-slate-200 sm:p-10 dark:bg-gray-800 dark:ring-gray-700">

            {{-- Icon --}}
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#9788FD]/15">
               <svg xmlns="http://www.w3.org/2000/svg" width="136" height="136" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail preview-icon"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
            </div>

            {{-- Heading --}}
            <div class="mt-6 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-[#022A5E] dark:text-white">
                    Check your inbox
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    We sent a verification link to
                </p>
                <p class="mt-2 inline-block max-w-full break-all rounded-full bg-[#9788FD]/10 px-4 py-1.5 text-sm font-semibold text-[#022A5E] dark:text-[#c9c1ff]">
                    {{ auth()->user()->email }}
                </p>
                <p class="mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    Click the link in that email to activate your account.
                </p>
            </div>
            {{-- Status message after resend --}}
            @if (session('status') === 'verification-link-sent')
                <div role="status"
                     class="mt-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                         fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <p>A new verification link has been queued. Please check your inbox shortly.</p>
                </div>
            @endif
            @if (session('status') === 'verification-required')
                <div role="status"
                     class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
                    Verify your email address before accessing your account.
                </div>
            @endif
            @if ($errors->has('verification'))
                <div role="alert"
                     class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    {{ $errors->first('verification') }}
                </div>
            @endif
            {{-- Resend --}}
            <form method="POST" action="{{ route('verification.send') }}" class="mt-8"
                  onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg  px-6 py-3 text-sm font-semibold text-white transition-colors duration-200  focus:outline-none focus:ring-4 disabled:cursor-allowed disabled:opacity-60 dark:bg-[#9788FD] dark:text-[#022A5E] dark:hover:bg-[#a99dff]" style="background-color: #022a5e">
                    Resend verification email
                </button>
            </form>
            {{-- Help --}}
        </main>

        {{-- Footer --}}
        <footer class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400">
            &copy; {{ date('Y') }} MartHire. All rights reserved.
        </footer>
    </div>
</div>
</body>
</html>
