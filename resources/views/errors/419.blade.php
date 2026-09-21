<html lang="en">

<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        404 Error Page | MartHire 
    </title>
    <link rel="icon" href="favicon.ico">
    <style data-fullcalendar=""></style>
    <link href="{{ asset('assets/css/mains.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <!-- ===== Page Wrapper Start ===== -->
    <div class="relative z-1 flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
        <!-- ===== Common Grid Shape Start ===== -->
        <div class="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
            <img src="{{ asset('assets/images/svgs/grid-01.svg') }}" alt="grid">
        </div>
        <div class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
            <img src="{{ asset('assets/images/svgs/grid-01.svg') }}" alt="grid">
        </div>
        <!-- ===== Common Grid Shape End ===== -->
        <!-- Centered Content -->
        <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
            <h1 class="text-title-md xl:text-title-2xl mb-8 font-bold text-gray-800 dark:text-white/90">
                ERROR
            </h1>
            <img src="{{ asset('assets/images/svgs/419.svg') }}" alt="419" class="dark:hidden">
            <img src="{{ asset('assets/images/svgs/419-dark.svg') }}" alt="419" class="hidden dark:block">
            <p class="mt-10 mb-6 text-base text-gray-700 sm:text-lg dark:text-gray-400">
                Your Session Has Expired Please Login again!
            </p>

            <a href="{{ route('home') }}"
                class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200" style="margin-bottom: 20px">
                Go to Home Page
            </a>
			<a href="{{route('login')}}"
                class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                Login 
            </a>
        </div>
        <!-- Footer -->
        <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-center text-sm text-gray-500 dark:text-gray-400">
			
            © <span id="year"></span> - MartHire
        </p>
		<script>
		document.getElementById("year").textContent = new Date().getFullYear();
		</script>
    </div>
    <!-- ===== Page Wrapper End ===== -->
    <script defer="" src="{{ asset('assets/js/bundle.js') }}"></script>
</body>
</html>
