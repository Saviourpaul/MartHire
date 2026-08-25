<!DOCTYPE html>

<html lang="zxx">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <!-- favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />
    <!-- theme meta -->
    <meta name="msapplication-TileColor" content="#000000" />
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#fff" />
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000" />
    <meta name="generator" content="gulp" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<!-- Primary Meta Tags -->
	<title>MartHire — Recruitment Management Platform for Employers & Recruiters</title>
	<meta name="title" content="MarHire — Recruitment Management Platform for Employers & Recruiters">
	<meta name="description" content="Marthire is a recruitment technology platform that helps organizations manage the full hiring lifecycle — from job vacancies to candidate selection — through structured digital workflows. Join the waitlist.">

<!-- Keywords (low SEO value, harmless to include) -->
<meta name="keywords" content="recruitment platform, recruitment management system, hiring software, applicant tracking, recruitment technology, HR software, candidate management, recruitment workflow, employer platform, recruitment agency software">

<!-- Canonical -->
<link rel="canonical" href="https://marthire.com/">

<!-- Open Graph / Facebook / LinkedIn -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://marthire.com/">
<meta property="og:title" content="MarHire — Recruitment Management Platform">
<meta property="og:description" content="Manage hiring from vacancy creation to candidate selection in one structured platform. Built for employers, recruiters, and organizations. Join the waitlist.">
<meta property="og:image" content="https://marthire.com/og-image.png">
<meta property="og:site_name" content="MarHire">

<!-- Twitter / X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://marthire.com/">
<meta name="twitter:title" content="MarHire — Recruitment Management Platform">
<meta name="twitter:description" content="Manage hiring from vacancy creation to candidate selection in one structured platform. Join the waitlist.">
<meta name="twitter:image" content="https://marthire.com/og-image.png">

<!-- Robots -->
<meta name="robots" content="index, follow">
    <!-- responsive meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
    <!-- google font css -->
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&amp;display=swap"
        rel="stylesheet" />

    <!-- styles -->

    <!-- Swiper slider -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/swiper/swiper-bundle.css') }}" />

    <!-- Fontawesome -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/brands.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/solid.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/font-awesome/v6/fontawesome.css') }}" />

    <!-- Main Stylesheet -->
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet" />
</head>

<header class="header">
    <nav class="navbar container">
        <!-- logo -->
        <div class="order-0">
            <a href="{{ route('home') }}">
                <img src="{{ asset('assets/images/logo.png') }}" height="80" width="139" alt="" />
            </a>
        </div>
        <!-- navbar toggler -->
        <input id="nav-toggle" type="checkbox" class="hidden" />
        <label id="show-button" for="nav-toggle"
            class="text-black order-2 flex cursor-pointer items-center lg:order-1 lg:hidden">
            <svg class="h-6 fill-current" viewBox="0 0 20 20">
                <title>Menu Open</title>
                <path d="M0 3h20v2H0V3z m0 6h20v2H0V9z m0 6h20v2H0V0z"></path>
            </svg>
        </label>
        <label id="hide-button" for="nav-toggle"
            class="text-black order-2 hidden cursor-pointer items-center lg:order-1">
            <svg class="h-6 fill-current" viewBox="0 0 20 20">
                <title>Menu Close</title>
                <polygon points="11 9 22 9 22 11 11 11 11 22 9 22 9 11 -2 11 -2 9 9 9 9 -2 11 -2"
                    transform="rotate(45 10 10)"></polygon>
            </svg>
        </label>
        <!-- /navbar toggler -->
        <ul id="nav-menu"
            class="navbar-nav order-3 lg:items-center hidden w-full lg:order-1 lg:flex lg:w-auto lg:space-x-2 pb-3 lg:pb-0">
            <li class="nav-item">
                <a href="{{ route ("about") }}" class="nav-link">
                    About Us
                </a>
            </li>
            <li class="nav-item nav-dropdown group relative">
                <a href="{{ route("services") }}" class="nav-link">
                    services
                </a>
            </li>
            <li class="nav-item nav-dropdown group relative">
                <a href="{{ route("pricing") }}" class="nav-link">
                    Pricing
                </a>
            </li>
			<li class="nav-item nav-dropdown group relative">
				<a href="{{ route("how-it-works") }}" class="nav-link"> How it works </a>
			</li>
			<li class="nav-item nav-dropdown group relative">
				<a href="{{ route("our-team") }}" class="nav-link"> Our Team </a>
			</li>
            <li class="nav-item nav-dropdown group relative">
                <a href="{{ route("contact") }}" class="nav-link">
                    Contact Us
                </a>
            </li>
           
			
            <li class="nav-item lg:hidden mt-3.5">
                <a class="btn btn-outline-dark btn-sm" href="{{route("login")}}">
                    Get Started
                    <i class="fa fa-chevron-right"></i>
                </a>
                </div>
            </li>
        </ul>
        <div class="order-1 ml-auto items-center lg:order-2 lg:ml-0 hidden lg:flex">
            <a class="btn btn-outline-dark btn-sm" href="{{ route("login") }}">
                Start journey
                <i class="fa fa-chevron-right"></i>
            </a>
        </div>
    </nav>
</header>

<body><!-- banner -->
    {{ $slot }}
    <footer class="footer bg-dark">
        <div class="container">
            <div class="row justify-center">
                <div class="lg:col-10 footer-grid pt-[100px] pb-16">
                    <div class="footer-col lg:max-w-[270px] mb-10 lg:mb-0">
                        <a href="{{ route('home') }}" class="mb-4 inline-block">
                            <img src="{{ asset('assets/images/logo2.png') }}" alt="">
                        </a>
                        <p class="mb-10">Lorem ipsum dolor sit sed dmi amet
                            consectetur adipiscing. Cdo tellus
                            sed condimentum volutpat. </p>
                        <span class="inline-block font-semibold text-lg font-primary mb-2">Follow us</span>
                        <ul class="social-icons footer-social-icons">
                            <li>
                                <a href="#">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <i class="fa-brands fa-twitter"></i>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <i class="fa-brands fa-instagram"></i>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <i class="fa-brands fa-linkedin-in"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-col mb-10 lg:mb-0">
                        <h5>Products</h5>
                        <ul class="footer-links">
                            <li>
                                <a class="footer-link" href="#">The Platform</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Partner</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Information</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">About</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Take the Tour</a>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-col mb-10 lg:mb-0">
                        <h5>About</h5>
                        <ul class="footer-links">
                            <li>
                                <a class="footer-link" href="#">The Platform</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Webinars/Events</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Leadership</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Privacy</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Cookie Preferences</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Legal</a>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h5>What We Offer</h5>
                        <ul class="footer-links">
                            <li>
                                <a class="footer-link" href="#">Products</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Webinars/Events</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Support</a>
                            </li>
                            <li>
                                <a class="footer-link" href="#">Migration Services</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="lg:col-10 row py-8 text-center border-[#4B4B4B] border-t">
                    <p class="text-sm text-[#ABABAB]"> Developed by <a class="underline hover:text-pri
                    " href="https://saviourpaul.com/">Saviour Paul</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Swiper JS -->
    <script src="{{ asset('assets/plugins/swiper/swiper-bundle.js') }}"></script>
    <!-- Shuffle JS -->
    <script src="{{ asset('assets/plugins/shuffle/shuffle.js') }}"></script>

    <!-- Main Script -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>