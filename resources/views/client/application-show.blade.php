<x-app-layout>
    <x-slot name="title">{{ __('Application :reference', ['reference' => $application->reference]) }}</x-slot>
    <x-slot name="header"><h1 class="nw-heading">{{ __('Your application') }}</h1><p>{{ $application->job?->title }} · {{ $application->job?->company }}</p></x-slot>

    <section class="nw-panel nw-form-stack">
        <header class="nw-panel-heading"><span class="nw-eyebrow">{{ $application->reference }}</span><h2 class="nw-heading">{{ $application->status->label() }}</h2><p>{{ __('Submitted :date', ['date' => $application->submitted_at?->format('j F Y, H:i')]) }}</p></header>
        @if ($publicJob)<div><span class="nw-label">{{ __('Job country') }}</span><x-job-country :job="$publicJob" /></div>@endif
        <dl class="nw-form-stack">
            <div><dt class="nw-label">{{ __('Applicant') }}</dt><dd>{{ trim($application->first_name.' '.$application->last_name) }}</dd></div>
            <div><dt class="nw-label">{{ __('Email address') }}</dt><dd>{{ $application->email }}</dd></div>
            <div><dt class="nw-label">{{ __('Applicant country') }}</dt><dd><x-country-display :code="$application->country_code" :name="$application->country" /></dd></div>
        </dl>
        <div><h3>{{ __('Documents received') }}</h3><ul>@foreach ($application->documents as $document)<li>{{ $document->displayLabel() }}</li>@endforeach</ul></div>
        <div class="nw-actions">
            @if ($application->job?->isAcceptingApplications())<a class="nw-button nw-button--outline" href="{{ route('jobs.show', $application->job) }}">{{ __('View job') }}</a>@endif
            <a class="nw-button nw-button--primary" href="{{ route('workable-jobs.index') }}">{{ __('Browse jobs') }}</a>
        </div>
    </section>
</x-app-layout>
