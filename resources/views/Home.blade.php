
<x-app>

      <section class="pt-28 lg:pt-[206px] pb-12 lg:pb-20 relative">
        <div class="grained-bg absolute top-0 left-0 w-full h-full opacity-[0.36]">
            <img src="{{ asset('assets/images/hp-banner.png') }}" alt="">
        </div>
        <div class="container">
            <div class="row justify-center text-center">
                <div class="lg:col-8 xl:col-6 mb-[72px]">
                    <h1 class="h1-lg highlighted">The Future of Work - <br>
                        and <span>Extended</span> Workforce Planning - is Here</h1>
                    <p class="mt-8">The choice of font and font size with which Lorem ipsum is reproduced answers to
                        specific needs that go beyond the simple and simple filling of spaces dedicated to accepting
                        real texts and allowing to have hands an advertising/publishing product,</p>
                    <div class="mt-14">
                        <a class="block sm:inline-block btn btn-primary sm:mx-2 mb-2 sm:mb-0 w-full sm:w-auto"
                            href="#">Stay in Touch</a>
                        <button id="modal-open-button"
                            class="block sm:inline-block btn btn-outline-dark sm:mx-2 w-full sm:w-auto">
                            watch a video
                        </button>
                    </div>
                </div>
                <div class="col-12">
                    <ul class="flex items-center flex-wrap justify-center">
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Engagement Footprint
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Cost Savings
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Operational Agility
                        </li>
                        <li class="bg-white rounded-lg mx-2 my-2 lg:mx-5 py-1 px-2 lg:my-5">
                            <i class="fa fa-check text-[#15B400] mr-2"></i>Increase Candidate Quality
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
                <h2 class="h4 capitalize highlighted">best <span>customer experiences</span> with DataSource</h2>
            </div>
            <div class="overflow-hidden">
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
            </div>
        </div>
    </div>

    <!-- Video -->
    <section class="section-bordered mt-20">
        <div class="container">
            <div class="row justify-center">
                <div class="lg:col-12 text-center max-w-[1072px] px-8">
                    <div class="mb-20">
                        <h2 class="section-title">Get the <span>fastest time</span> to hire</h2>
                        <p>suitable full-time and temporary candidates. Publish job vacancies all major
                            sites to broaden your reach – and access a global candidate warehouse that offers thousands
                            of pre-vetted technical, professional, and scientific candidates.</p>
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
                    <h2 class="section-title text-center"><span>ultimate</span> platform for creating, <br>
                        sharing, and executing</h2>
                </div>
                <div class="lg:col-10 features-grid mb-3 relative">
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300 ">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                        <div class="md:col-6 py-10 px-6 lg:px-14 hover:bg-white transition-all duration-300">
                            <div class="pl-10 relative">
                                <span class="icon absolute left-0 -top-2">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-2">Centralised Repository</h5>
                                <p>Enhance how you work with assets/images in bulk & raise the bar for your team’s potential
                                    with the most simplified image library.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-10 text-center relative z-10">
                    <a class="btn btn-primary" href="#">learn more</a>
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
                                Avoid falling foul to
                                <span>employment</span> complian
                                & regulatory matters
                            </h2>
                            <p class="mb-8">suitable fulltime and temporary candidates. Publish job vacancies all major
                                sites to broaden your reach – and access a global candidate warehouse offers thousands
                                of pre-vetted technical, professional, and scientific candidates.</p>
                            <a class="btn btn-primary" href="#">learn more</a>
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
                                Avoid falling foul to
                                <span>employment</span> complian
                                & regulatory matters
                            </h2>
                            <p class="mb-8">suitable fulltime and temporary candidates. Publish job vacancies all major
                                sites to broaden your reach – and access a global candidate warehouse offers thousands
                                of pre-vetted technical, professional, and scientific candidates.</p>
                            <a class="btn btn-primary" href="#">learn more</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative row justify-center">
                <div class="absolute left-0 top-0 h-full w-full grained-bg opacity-[0.16]">
                    <img src="{{ asset('assets/images/common-bg.png') }}" alt="">
                </div>
                <div class="lg:col-6 mx-auto text-center">
                    <h2 class="section-title">Next-Gen VMS <span>Software</span></h2>
                    <p>Explore why so many Fortune 500 businesses are ditching their 1st gen SaaS platform VMS systems
                        in favor of Total Talent Management VMS Software.</p>
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
                    <h2 class="section-title mb-0">Hear from our <span>clients</span></h2>
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
                                        <p class="text-lg">But I must explain to you how all
                                            this mistaken idea of denouncing of a pleasure and praising pain was born
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">Dianne Russell</h4>
                                    <p class="text-lg italic text-[#85888C]">content analizer</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-card">
                                    <div class="pb-6 mb-6 border-b border-border">
                                        <span class="inline-block mb-7">
                                            <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        </span>
                                        <p class="text-lg">But I must explain to you how all
                                            this mistaken idea of denouncing of a pleasure and praising pain was born
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/client-1.png') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">Dianne Russell</h4>
                                    <p class="text-lg italic text-[#85888C]">content analizer</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-card">
                                    <div class="pb-6 mb-6 border-b border-border">
                                        <span class="inline-block mb-7">
                                            <img src="{{ asset('assets/images/svgs/quote.svg') }}" alt="">
                                        </span>
                                        <p class="text-lg">But I must explain to you how all
                                            this mistaken idea of denouncing of a pleasure and praising pain was born
                                        </p>
                                    </div>
                                    <a class="avatar" href="#">
                                        <img src="{{ asset('assets/images/client-1.png') }}" alt="">
                                        <span>
                                            <img src="{{ asset('assets/images/linkedin.png') }}" alt="">
                                        </span>
                                    </a>
                                    <h4 class="font-primary font-medium">Dianne Russell</h4>
                                    <p class="text-lg italic text-[#85888C]">content analizer</p>
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
                    <h2 class="section-title mb-0"><span>find</span> Which system is <br>
                        right for you</h2>
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="row gy-4">
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/chat.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">total talent
                                    management</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/display.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">diversity in
                                    system</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/control-forward.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">shift management system</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/lock.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">Vendor Management
                                    System</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/magnet.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">Direct Sourcing and
                                    Talent Pools</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
                            </div>
                        </div>
                        <div class="md:col-6 lg:col-4">
                            <div
                                class="px-8 py-10 text-center bg-white/40 rounded-xl border border-border h-full hover:bg-white transition-all duration-300 hover:shadow">
                                <span class="inline-block mb-6">
                                    <img src="{{ asset('assets/images/svgs/link.svg') }}" alt="">
                                </span>
                                <h5 class="mb-6 capitalize">Statement of
                                    all Work</h5>
                                <p class="text-lg">Lorem ipsum dolor sit amet, consect
                                    adipisci elit, sed eiusmod tempor incidunt
                                    ut labore et dolore magna aliqua.</p>
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
                    <h2 class="section-title">Popular questions <br>
                        <span>answered</span>
                    </h2>
                </div>
                <div class="lg:col-11 xl:col-10">
                    <div class="grid md:grid-cols-2 gap">
                        <div class="faqs-col">
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Hyper-personalize, engage, and convert candidates
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Develop and retain your employees with intelligence
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Improve recruiter productivity through automation
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    Hire more talent, faster with Al
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="faqs-col">
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How Can I Manage Transactions?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How Many Should I Pay?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How does app work?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How can I manage income?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
                                    </p>
                                </div>
                            </div>
                            <div class="accordion">
                                <div class="accordion-header" data-accordion>
                                    How to invest using this app?
                                    <svg class="accordion-icon" x="0px" y="0px" viewBox="0 0 512 512"
                                        xmlspace="preserve">
                                        <path fill="currentColor"
                                            d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi
                                        quaerat veritatis necessitatibus nemo ullam dolores aut veniam
                                        officiis asperiores, unde quo magni repudiandae impedit iusto
                                        voluptatum eos, aliquam, consectetur aliquid.
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
                            <h2 class="mb-6 highlighted">Do you want to be a part <br>
                                of our <span>team</span>?</h2>
                            <p class="mb-6">All our premium themes are designed elegantly with blazing speed
                                themes even score above% in Google page speed.</p>
                            <a href="#" class="btn btn-primary">Start journey</a>
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
