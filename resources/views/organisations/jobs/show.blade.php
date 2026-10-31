<x-app-layout>
    <x-slot name="title">{{ $job->title }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ $job->title }}</h1>
        <p>{{ $job->company }}</p>
    </x-slot>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="nw-form-bar" style="margin-bottom:20px">
        <div class="nw-form-bar__grow">
            <x-status-badge :status="$job->status" />
        </div>
        @can('update', $job)
            <a href="{{ route('organisations.jobs.edit', $job) }}" class="nw-button nw-button--outline nw-button--small">
                <x-icon name="pencil" /> {{ __('Edit') }}
            </a>
        @endcan
        @can('update', $job)
            @unless ($job->isClosed())
                <form method="POST" action="{{ route('organisations.jobs.close', $job) }}" data-confirm="{{ __('Close this job to new applications?') }}" data-confirm-label="{{ __('Close job') }}" data-confirm-type="warning">
                    @csrf
                    <input type="hidden" name="confirm_closure" value="1">
                    <button type="submit" class="nw-button nw-button--outline nw-button--small">{{ __('Close') }}</button>
                </form>
            @endunless
        @endcan
        @can('delete', $job)
            <form method="POST" action="{{ route('organisations.jobs.destroy', $job) }}" data-confirm="{{ __('Delete this job? This cannot be undone.') }}" data-confirm-label="{{ __('Delete job') }}" data-confirm-type="error">
                @csrf
                @method('DELETE')
                <button type="submit" class="nw-button nw-button--danger nw-button--small">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>

    <section class="nw-panel">
        <dl class="nw-detail-list">
            <div><dt>{{ __('Country') }}</dt><dd>
                @if ($country)
                    <x-job-country :job="['country' => $country['name'], 'flag_url' => $country['flag_url'], 'flag_emoji' => $country['flag_emoji']]" />
                @else
                    {{ $job->country_code ?: '—' }}
                @endif
            </dd></div>
            <div><dt>{{ __('Employment type') }}</dt><dd>{{ \App\Models\Job::employmentTypeOptions()[$job->employment_type] ?? $job->employment_type }}</dd></div>
            <div><dt>{{ __('Category') }}</dt><dd>{{ $job->category ?: '—' }}</dd></div>
            <div><dt>{{ __('Start date') }}</dt><dd>{{ $job->start_date?->format('j M Y') }}</dd></div>
            <div><dt>{{ __('Deadline') }}</dt><dd>{{ $job->due_date?->format('j M Y') }}</dd></div>
            <div><dt>{{ __('Applications') }}</dt><dd>{{ $job->applications_count ?? $job->applications()->count() }}</dd></div>
        </dl>

        <div class="nw-rich-content">
            {!! app(\App\Support\JobDescription::class)->toHtml($job->description) !!}
        </div>
    </section>
</x-app-layout>
