@php
    $features = [
        [
            'title' => __('Build a structured workflow'),
            'eyebrow' => __('Start with clarity'),
            'description' => __('Bring your vacancies, job requirements and hiring stages into one workspace. Set out the role, choose its country and publish when your team is ready to receive applications.'),
            'image' => 'how-it-works-img-1.png', 'width' => 875, 'height' => 533,
            'alt' => __('Illustration of a dashboard with activity charts'),
            'link' => '#hiring-process', 'action' => __('Explore the workflow'),
        ],
        [
            'title' => __('Centralize every application'),
            'eyebrow' => __('One candidate record'),
            'description' => __('Keep application details and supporting documents together. Your team can review a candidate, preview their files and follow their progress without piecing information together across spreadsheets and email.'),
            'image' => 'how-it-works-img-2.png', 'width' => 874, 'height' => 526,
            'alt' => __('Illustration of information organized in a shared dashboard'),
            'link' => '#process-review', 'action' => __('See how candidates are reviewed'),
        ],
        [
            'title' => __('Guide candidates forward'),
            'eyebrow' => __('Keep the next step clear'),
            'description' => __('Move from screening to shortlisting and interviews with a shared view of each application. Connect interview arrangements, candidate responses and recorded decisions so your team knows what happens next.'),
            'image' => 'how-it-works-img-3.png', 'width' => 862, 'height' => 526,
            'alt' => __('Illustration of connected activity and progress summaries'),
            'link' => '#process-interviews', 'action' => __('Explore interview coordination'),
        ],
    ];
    $steps = [
        [
            'id' => 'process-applications', 'title' => __('Receive and organize applications'),
            'description' => __('Publish your vacancy and give applicants a clear way to apply. Their submitted details and documents stay connected to the right job, ready for your hiring team to review.'),
            'image' => 'process-img-1.png', 'alt' => __('Illustrative activity chart'),
        ],
        [
            'id' => 'process-review', 'title' => __('Review and shortlist'),
            'description' => __('Read the application and preview supporting files in the candidate workspace. Add context to stage changes and move suitable candidates into your shortlist.'),
            'image' => 'process-img-2.png', 'alt' => __('Illustration of an organized dashboard summary'),
        ],
        [
            'id' => 'process-interviews', 'title' => __('Coordinate interviews and decisions'),
            'description' => __('Schedule the conversation, share the arrangements and track the candidate’s response. Mark the interview completed before an authorized team member explicitly confirms selection.'),
            'image' => 'process-img-3.png', 'alt' => __('Illustrative progress and activity chart'),
        ],
        [
            'id' => 'process-decisions', 'title' => __('Confirm selection and close the role'),
            'description' => __('Record the final decision and close the vacancy when recruitment is complete. Keep the application, interview details and activity history together for a clear record of the process.'),
            'image' => 'process-img-4.png', 'alt' => __('Illustration of a completed activity summary'),
        ],
    ];
@endphp

<x-layout :title="__('How it works')" :description="__('See how Marthire connects vacancies, applications, document review, interviews and hiring decisions in one recruitment workspace.')">
    <div class="nw-how">
        <section class="nw-how-hero" aria-labelledby="how-title">
            <div class="nw-container">
                <p class="nw-eyebrow">{{ __('From vacancy to decision') }}</p>
                <h1 id="how-title">{{ __('How Marthire') }}<br><em>{{ __('works.') }}</em></h1>
                <p class="nw-copy">{{ __('A clearer recruitment journey for African government institutions, businesses and international organizations. Keep every stage connected, from the first application to your final decision.') }}</p>
                <a class="nw-link" href="#hiring-process">{{ __('Follow the hiring journey') }} <x-icon name="arrow-down" /></a>
            </div>
        </section>

        <section class="nw-container nw-how-features" aria-label="{{ __('A connected recruitment workspace') }}">
            @foreach ($features as $feature)
                <article class="nw-how-feature{{ $loop->even ? ' nw-how-feature--reverse' : '' }}" aria-labelledby="how-feature-{{ $loop->iteration }}">
                    <div class="nw-how-feature__copy">
                        <p class="nw-eyebrow">{{ $feature['eyebrow'] }}</p>
                        <h2 id="how-feature-{{ $loop->iteration }}" class="nw-heading">{{ $feature['title'] }}</h2>
                        <p class="nw-copy">{{ $feature['description'] }}</p>
                        <a class="nw-button nw-button--primary" href="{{ $feature['link'] }}">{{ $feature['action'] }} <x-icon name="arrow-right" /></a>
                    </div>
                    <figure class="nw-how-feature__visual">
                        <img src="{{ asset('asset/marthire/how-it-works/'.$feature['image']) }}" alt="{{ $feature['alt'] }}" width="{{ $feature['width'] }}" height="{{ $feature['height'] }}" loading="lazy" decoding="async">
                        <figcaption>{{ __('Workflow illustration') }}</figcaption>
                    </figure>
                </article>
            @endforeach
        </section>

        <section id="hiring-process" class="nw-container nw-how-process" aria-labelledby="how-process-title">
            <header class="nw-how-process__heading">
                <p class="nw-eyebrow">{{ __('The hiring journey') }}</p>
                <h2 id="how-process-title" class="nw-heading">{{ __('One clear process,') }}<br><em>{{ __('from application to decision.') }}</em></h2>
                <p class="nw-copy">{{ __('Four connected stages help your team work consistently and give every application a clear place in the process.') }}</p>
            </header>
            <ol class="nw-how-steps">
                @foreach ($steps as $step)
                    <li id="{{ $step['id'] }}" class="nw-how-step{{ $loop->even ? ' nw-how-step--reverse' : '' }}">
                        <span class="nw-how-step__number" aria-hidden="true">{{ $loop->iteration }}</span>
                        <div class="nw-how-step__copy">
                            <p class="nw-eyebrow">{{ __('Step :number', ['number' => $loop->iteration]) }}</p>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['description'] }}</p>
                        </div>
                        <img class="nw-how-step__image" src="{{ asset('asset/marthire/how-it-works/'.$step['image']) }}" alt="{{ $step['alt'] }}" width="400" height="270" loading="lazy" decoding="async">
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="nw-container nw-how-contact" aria-labelledby="how-contact-title">
            <div class="nw-how-contact__card">
                <p class="nw-eyebrow">{{ __('Bring your hiring together') }}</p>
                <h2 id="how-contact-title" class="nw-heading">{{ __('Ready for a more') }}<br><em>{{ __('connected recruitment process?') }}</em></h2>
                <p class="nw-copy">{{ __('Talk to us about bringing vacancies, applicant records and candidate progression into one organized workspace for your team.') }}</p>
                <div class="nw-how-contact__actions">
                    <a class="nw-button nw-button--primary" href="{{ route('contact') }}">{{ __('Contact us') }} <x-icon name="arrow-up-right" /></a>
                    <a class="nw-link" href="{{ route('workable-jobs.index') }}">{{ __('Looking for your next role?') }} <x-icon name="arrow-right" /></a>
                </div>
            </div>
        </section>
    </div>
</x-layout>
