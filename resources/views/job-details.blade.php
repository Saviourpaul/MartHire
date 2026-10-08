<x-app>
    <section class="relative pt-28 lg:pt-[206px] lg:mb-0 pb-[120px] overflow-hidden">
        <div class="grained-bg absolute top-0 left-0 w-full h-full opacity-[0.36]">
            <img src="{{ asset('assets/images/banner.png') }}" alt="">
        </div>

        <div class="row justify-center">
           
            <div class="lg:col-10 text-center">
                 <a href={{ route('Browse-jobs') }} class="mb-4 text-sm font-semibold text-brand-500 hover:text-brand-600"><i
                    class="fa fa-arrow-left mr-1"></i> Back to all jobs</a>
                <h1 class="h1-lg mb-4">{{ $job->title }}</h1>
                <p class=""><i
                        class="fa fa-location-dot text-primary mr-1"></i>{{ $job->location ?: 'Not specified' }}<span
                        class="ml-1.5 text-primary">{{ $job->employmentTypeLabel() }}</span> </p>
            </div>
        </div>
    </section>

    <section class="section-bordered mt-0">
        
        <div class="container">
            <div class="row justify-center">
                <div class="col-11 lg:col-10 content">
                    <h3 class="mt-0">About The Role:</h3>
                    <p>{{ strip_tags($job->description) }}.</p>



                    <h3>Organization and Job Info </h3>
                    <h4>{{ $job->company }}</h4>
                    <ul class="mb-14">
                        <li>Start Date : {{ $job->start_date?->format('M j, Y') ?: 'Not specified' }}</li>
                        <li>Application deadline: {{ $job->due_date?->format('M j, Y') ?: 'Not specified' }}</li>
                        <div class="inline-flex items-center space-x-2">
                            <img class="w-6 h-6 rounded-full" src="{{ asset('assets/images/svgs/calender.svg') }}" alt="">
                            <span>{{ $job->created_at?->format('M j, Y') }}</span>
                        </div> 
                    </ul>
                    @if(!$job->AcceptingApplications())
                    <p>Applications are closed for this job.</p>
                    @elseif($existingApplication)
                    <a href="{{ route('client.applications.show', $existingApplication) }}"
                            class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/30">View
                            your application</a>
                    @else 
                       @auth
                       @if (auth()->user()->isApplicant())
                       <button  href="{{ route('applications.create', $job) }}" class="btn btn-primary job-apply-btn">Apply for this job</button>
                       @else
                                <p
                                    class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    Applications are available to applicant accounts.</p>
                            @endif
                            @else
                            <a href="{{ route('applications.create', $job) }}"
                                class="btn btn-primary job-apply-btn">Apply
                                now</a>
                          
                       @endauth
                    @endif

                     
                    
                </div>
                
            </div>
            
        </div>
        
    </section>
</x-app>
