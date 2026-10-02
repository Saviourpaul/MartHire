<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="msapplication-TileColor" content="#000000">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000000">
    <meta name="robots" content="index, follow">

    <title>MartHire | Recruitment Management Platform</title>
    <meta name="title" content="MartHire | Recruitment Management Platform">
    <meta name="description" content="MartHire helps organizations manage vacancies, applications, applicant records, and candidate progression in one structured workspace.">
    <meta name="keywords" content="recruitment platform, recruitment management, applicant tracking, hiring workflow, candidate management, job listings">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="MartHire | Recruitment Management Platform">
    <meta property="og:description" content="Manage vacancies, applications, applicant records, and candidate progression in one structured workspace.">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:site_name" content="MartHire">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="MartHire | Recruitment Management Platform">
    <meta name="twitter:description" content="Manage vacancies, applications, applicant records, and candidate progression in one structured workspace.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/plugins/swiper/swiper-bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/brands.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/solid.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="header">
        <nav class="navbar container" aria-label="Primary navigation">
            <div class="order-0">
                <a href="{{ route('home') }}" aria-label="MartHire home">
                    <img src="{{ asset('assets/images/logo.png') }}" height="80" width="139" alt="MartHire">
                </a>
            </div>

            <input id="nav-toggle" type="checkbox" class="sr-only" aria-controls="nav-menu" aria-label="Toggle navigation">
            <label id="show-button" for="nav-toggle" class="text-black order-2 flex cursor-pointer items-center lg:order-1 lg:hidden">
                <svg class="h-6 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M0 3h20v2H0V3z m0 6h20v2H0V9z m0 6h20v2H0V0z"></path>
                </svg>
                <span class="sr-only">Open navigation</span>
            </label>
            <label id="hide-button" for="nav-toggle" class="text-black order-2 hidden cursor-pointer items-center lg:order-1">
                <svg class="h-6 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                    <polygon points="11 9 22 9 22 11 11 11 11 22 9 22 9 11 -2 11 -2 9 9 9 9 -2 11 -2" transform="rotate(45 10 10)"></polygon>
                </svg>
                <span class="sr-only">Close navigation</span>
            </label>

            <ul id="nav-menu" class="navbar-nav order-3 hidden w-full pb-3 lg:order-1 lg:flex lg:w-auto lg:items-center lg:space-x-2 lg:pb-0">
                <li class="nav-item"><a href="{{ route('about') }}" class="nav-link">About us</a></li>
                <li class="nav-item"><a href="{{ route('services') }}" class="nav-link">Services</a></li>
                <li class="nav-item"><a href="{{ route('pricing') }}" class="nav-link">Pricing</a></li>
                <li class="nav-item"><a href="{{ route('how-it-works') }}" class="nav-link">How it works</a></li>
                <li class="nav-item"><a href="{{ route('our-team') }}" class="nav-link">Our team</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link">Contact us</a></li>
                
                <li class="nav-item mt-3.5 lg:hidden">
                    <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Get started <i class="fa fa-chevron-right" aria-hidden="true"></i></a>
                </li>
            </ul>

            <div class="order-1 ml-auto hidden items-center lg:order-2 lg:ml-0 lg:flex">
                <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Get started</a>
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="footer bg-dark">
        <div class="container">
            <div class="row justify-center">
                <div class="footer-grid lg:col-10 pt-[100px] pb-16">
                    <div class="footer-col mb-10 lg:mb-0 lg:max-w-[270px]">
                        <a href="{{ route('home') }}" class="mb-4 inline-block">
                            <img src="{{ asset('assets/images/logo2.png') }}" height="100" width="300" alt="MartHire">
                        </a>
                        <p>One platform to manage recruitment across Africa.</p>
                    </div>

                    <div class="footer-col mb-10 lg:mb-0">
                        <h5>Platform</h5>
                        <ul class="footer-links">
                            <li><a class="footer-link" href="{{ route('services') }}">Services</a></li>
                            <li><a class="footer-link" href="{{ route('how-it-works') }}">How it works</a></li>
                            <li><a class="footer-link" href="{{ route('pricing') }}">pricing</a></li>
                            <li><a class="footer-link" href="{{ route('Browse-jobs') }}">Browse jobs</a></li>
                        </ul>
                    </div>

                    <div class="footer-col mb-10 lg:mb-0">
                        <h5>Company</h5>
                        <ul class="footer-links">
                            <li><a class="footer-link" href="{{ route('about') }}">About us</a></li>
                            <li><a class="footer-link" href="{{ route('our-team') }}">Our team</a></li>
                            <li><a class="footer-link" href="{{ route('contact') }}">Contact us</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h5>Applicants</h5>
                        <ul class="footer-links">
                            <li><a class="footer-link" href="{{ route('register') }}">Create an account</a></li>
                            <li><a class="footer-link" href="{{ route('login') }}">Sign in</a></li>
                            <li><a class="footer-link" href="{{ route('Browse-jobs') }}">Find opportunities</a></li>
                        </ul>
                    </div>
                </div>
                <div class="row border-[#4B4B4B] border-t py-8 text-center lg:col-10">
                    <p class="text-sm text-[#ABABAB]">Developed by <a class="underline hover:text-primary" href="https://saviourpaul.com/">Saviour Paul</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ asset('assets/plugins/swiper/swiper-bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/shuffle/shuffle.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
