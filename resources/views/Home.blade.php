<x-app>
      <section class="pt-28 lg:pt-[206px] pb-12 lg:pb-20 relative">
        <div class="grained-bg absolute top-0 left-0 w-full h-full opacity-[0.36]">
            <img src="{{ asset('assets/images/hp-banner.png') }}" alt="">
        </div>
        <div class="container">
            <div class="row justify-center text-center">
                <div class="lg:col-8 xl:col-6 mb-[72px] ">
                    <h1 class="h1-lg highlighted">One platform to <br>
                        manage <span>recruitment</span> across Africa</h1>
                    <p class="mt-8">Marthire helps government institutions and private organizations centralize
                        recruitment—from vacancy setup and applications to screening, shortlisting, interviews, and
                        hiring decisions.</p>
                    <div class="mt-14">
                        <a class="block sm:inline-block btn btn-primary sm:mx-2 mb-2 sm:mb-0 w-full sm:w-auto"
                            href="#">Join the waitlist</a>
                        <button id="modal-open-button"
                            class="block sm:inline-block btn btn-outline-dark sm:mx-2 w-full sm:w-auto">
                            See how it works
                        </button>
                    </div>
                </div>
                <div class="col-12">
                    <ul class="flex items-center flex-wrap justify-center">
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Centralized applicant tracking
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Structured shortlisting
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Hiring-team collaboration
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Clear recruitment oversight
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- brands -->
    <div class="brands py-10">
        <div class="container">
            <div class="text-center mb-14">
                <h2 class="h4 capitalize highlighted">built for better <span>recruitment operations</span></h2>
            </div>
            <!--div class="overflow-hidden">
                <div class="swiper brands-carousel cursor-pointer">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-1.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-2.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-2.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-3.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-4.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-5.png') }}" alt="">
                        </div>
                        <div class="swiper-slide">
                            <img src="{{ asset('assets/images/brands/company-logo-6.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div-->
        </div>
    </div>

    <!-- Video -->
    <section class="section-bordered mt-20">
        <div class="container">
            <div class="row justify-center">
                <div class="lg:col-12 text-center max-w-[1072px] px-8">
                    <div class="mb-20">
                        <h2 class="section-title">Recruitment without the <span>manual chase</span></h2>
                        <p>Marthire brings candidates, recruitment stages, and hiring activity into one organized
                            workflow—so your team can spend less time chasing updates across spreadsheets and emails.</p>
                    </div>
                    <div class="video-wrapper">
                        <div class="video-frame"></div>
                        <div class="video">
                            <img class="video-thumbnail" src="{{ asset('assets/images/video-thumbnail.png') }}" alt="">
                            <iframe class="video-iframe hidden" src="https://www.youtube.com/embed/ResipmZmpDU"
                                title="YouTube video player" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        </div>
                        <button class="video-play-btn">
                            <i class="fa-solid fa-play"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section />
    <!-- ./end Video -->

    <!-- Features -->
    <section class="features section-bordered">
        <div class="container">
            <div class="relative row justify-center">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-11 xl:col-10">
                    <h2 class="section-title text-center">A complete <span>recruitment workspace</span> <br>
                        for every hiring stage</h2>
                </div>
                <div class="lg:col-10 features-grid mb-3 relative">
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300 ">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Applicant Tracking</h5>
                                <p>Keep every application, profile, and supporting document connected to the right vacancy.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Screening Workflows</h5>
                                <p>Review candidates against role requirements using a clear, repeatable process.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Candidate Shortlisting</h5>
                                <p>Move qualified applicants forward with visible shortlists and organized candidate pools.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Interview Coordination</h5>
                                <p>Manage interview stages and candidate progression in the same recruitment workflow.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Team Collaboration</h5>
                                <p>Give authorized hiring stakeholders a shared view of vacancies, candidates, and progress.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Recruitment Oversight</h5>
                                <p>Maintain organized recruitment records and clearer visibility across every hiring process.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-10 text-center relative z-10">
                    <a class="btn btn-primary" href="#">Join the waitlist</a>
                </div>
            </div>
        </div>
    </section>
    <!-- ./end features -->

    <!-- services -->
    <section class="services section-bordered">
        <div class="container">
            <div class="relative row justify-center mb-20 lg:mb-[120px]">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="row items-center">
                        <div class="lg:col-7 lg:order-2 flex justify-center">
                            <img src="{{ asset('assets/images/service-img-1.png') }}" alt="">
                        </div>
                        <div class="lg:col-5 order-2 lg:order-1">
                            <h2 class="section-title">
                                Replace disconnected <span>recruitment</span>
                                administration
                            </h2>
                            <p class="mb-8">Bring vacancies, applications, documents, and recruitment decisions into
                                one organized workspace instead of relying on fragmented spreadsheets, email threads,
                                and manual follow-ups.</p>
                            <a class="btn btn-primary" href="#">Explore the platform</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative row justify-center mb-20 lg:mb-[120px]">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="row items-center">
                        <div class="lg:col-7  flex justify-center">
                            <img src="{{ asset('assets/images/service-img-1.png') }}" alt="">
                        </div>
                        <div class="lg:col-5">
                            <h2 class="section-title">
                                Keep candidate progression <span>clear</span>
                                and consistent
                            </h2>
                            <p class="mb-8">Move applicants through screening, shortlisting, interviews, and final
                                decisions with structured stages that help every authorized stakeholder understand what
                                happens next.</p>
                            <a class="btn btn-primary" href="#">Join the waitlist</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative row justify-center">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-6 mx-auto text-center">
                    <h2 class="section-title">Recruitment management <span>made for Africa</span></h2>
                    <p>Marthire is being built for the realities of government and private-sector recruitment across
                        African markets—where clarity, consistency, and accountable administration matter.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- end services -->

    <!-- Testimonials -->
    <section class="testimonials section-bordered">
        <div class="container">
            <div class="row justify-center">
                <div class="lg:col-6 mb-14 text-center">
                    <h2 class="section-title mb-0">Designed for <span>better recruitment</span></h2>
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="swiper testimonials-slider">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="testimonial-card">
                                    <div class="pb-6 mb-6 border-b border-border">
                                        <span class="inline-block mb-7">
                                            <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        </span>
                                        <p class="text-lg">Bring vacancies, applications, documents, and decisions into
                                            one organized recruitment process.
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">A single recruitment workspace</h4>
                                    <p class="text-lg italic text-[#85888C]">For every vacancy</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-card">
                                    <div class="pb-6 mb-6 border-b border-border">
                                        <span class="inline-block mb-7">
                                            <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        </span>
                                        <p class="text-lg">Make screening and shortlisting easier to manage with visible
                                            candidate stages and a clearer recruitment pipeline.
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/client-1.png') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">A clearer candidate pipeline</h4>
                                    <p class="text-lg italic text-[#85888C]">From application to decision</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-card">
                                    <div class="pb-6 mb-6 border-b border-border">
                                        <span class="inline-block mb-7">
                                            <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        </span>
                                        <p class="text-lg">Keep hiring teams informed without relying on scattered
                                            spreadsheets, inboxes, and disconnected systems.
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/client-1.png') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">A more accountable process</h4>
                                    <p class="text-lg italic text-[#85888C]">For authorized stakeholders</p>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-bullets testimonials-pagination text-center"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end Testimonials -->

    <!-- Systems -->
    <section class="systems section-bordered">
        <div class="container">
            <div class="relative row justify-center">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-6 mb-14 text-center">
                    <h2 class="section-title mb-0">One platform for every <br>
                        <span>recruitment workflow</span></h2>
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="row gy-4">
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/chat.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">vacancy
                                    planning</h5>
                                <p class="text-lg">Set up roles and define the workflow your organization will use to
                                    fill them.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/display.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">application
                                    management</h5>
                                <p class="text-lg">Receive and organize applicant information in one place from the
                                    moment a candidate applies.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/control-forward.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">screening and shortlisting</h5>
                                <p class="text-lg">Review applications, identify qualified candidates, and maintain
                                    structured shortlists.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">interview
                                    management</h5>
                                <p class="text-lg">Track interview stages and decisions so candidates continue moving
                                    through the process.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">candidate
                                    progression</h5>
                                <p class="text-lg">See where every candidate stands and what action is needed next.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">recruitment
                                    administration</h5>
                                <p class="text-lg">Maintain a clear record of activity and decisions across your
                                    recruitment workflow.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./end Systems -->

    <!-- faqs -->

    <!-- Faqs -->
    <section class="section-bordered faqs">
        <div class="container">
            <div class="row justify-center">
                <div class="col-12 lg:col-6 text-center">
                    <h2 class="section-title">Recruitment questions <br>
                        <span>answered</span>
                    </h2>
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="grid md:grid-cols-2 gap">
                        <div class="faqs-col">
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    What does Marthire help organizations manage?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Marthire centralizes vacancies, applications, candidate records, screening,
                                        shortlisting, interviews, and hiring administration in one workflow.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Who is Marthire built for?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        It is designed for government institutions and private organizations across
                                        Africa that need a more organized, transparent way to run recruitment.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How does Marthire reduce manual recruitment work?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        By replacing scattered spreadsheets, emails, and disconnected tools with a
                                        shared workspace that keeps recruitment activity and candidate statuses together.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Can teams collaborate on recruitment?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Authorized administrators and hiring stakeholders can work from the same view
                                        of vacancies, candidates, and recruitment progress.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="faqs-col">
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Can we use Marthire for government recruitment?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Yes. Marthire is being designed to support structured, accountable recruitment
                                        processes for public institutions and agencies.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    What happens to applicant information and documents?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Applicant profiles, qualifications, and supporting documents are organized with
                                        the relevant application, helping teams review complete information in context.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How do candidates move through the process?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Recruitment teams can move candidates through defined stages such as application
                                        review, screening, shortlisting, interviews, and final decisions.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Is job posting Marthire's main purpose?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        No. Job posting can be part of a recruitment workflow, but Marthire focuses on
                                        managing the complete process after and around a vacancy.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How can our organization join the waitlist?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Marthire is pre-launch. Join the waitlist to receive updates and learn when the
                                        platform becomes available for your organization.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end faqs -->

    <!-- Call to Action -->
    <section class="cta section-bordered">
        <div class="container">
            <div class="row mx-0 relative justify-center">
                <div class="col-12">
                    <img class="absolute -z-[1] top-0 left-0 w-full h-full" src="{{ asset('assets/images/cta-bg.png') }}" alt="">
                </div>
                <div class="lg:col-10 text-center">
                    <div class="shadow rounded-xl bg-white/40 py-20 border border-border">
                        <div class="md:max-w-[588px] mx-auto">
                            <h2 class="mb-6 highlighted">Ready to improve your <br>
                                <span>recruitment process</span>?</h2>
                            <p class="mb-6">Join the Marthire waitlist to receive launch updates and prepare your
                                organization for more organized, transparent recruitment.</p>
                            <a href="#" class="btn btn-primary">Join the waitlist</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <section>
        <div class="container">
            <div class="row mb-10">
                <div class="col-12">
                    <!--modal open button -->
                    <!-- <button id="modal-open-button" class="rounded bg-primary py-2 px-4 font-bold text-white hover:bg-primary/80">
                    Open Modal
                </button> -->
                    <!-- modal container -->
                    <div id="modal-container"
                        class="fixed inset-0 z-50 hidden h-screen w-screen bg-theme-dark bg-opacity-75"></div>
                    <!-- modal -->
                    <div id="modal"
                        class="fixed top-1/2 left-1/2 z-50 hidden -translate-x-1/2 -translate-y-1/2 transform rounded p-6 shadow-lg bg-transparent w-full max-w-[650px]">
                        <div class="rounded-xl overflow-hidden">
                            <iframe class="w-full" width="650" height="450"
                                src="https://www.youtube.com/embed/ResipmZmpDU" title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        </div>
                        <button id="modal-close-button"
                            class="border absolute -top-1.5 -right-1.5 text-primary w-8 h-8 rounded-full border-primary inline-flex items-center justify-center text-xl">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-app>
