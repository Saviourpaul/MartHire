<x-app-layout>
    <x-slot name="title">{{ $job->title }}</x-slot>

    @fragment('admin-job-detail')
    <div data-live-table="admin-job-detail" data-live-topics="jobs,applications,candidates" data-live-url="{{ request()->fullUrl() }}">
        <header class="nw-page-heading nw-page-heading--app">
            <div>
                <a class="nw-link nw-link--small" href="{{ route('admin.jobs.index') }}">{{ __('Back to job moderation') }}</a>
                <h1 class="nw-heading">{{ $job->title }}</h1>
                <p>{{ $job->organization?->name ?? $job->company }}</p>
            </div>
            <x-status-badge :status="$job->status" />
        </header>

        @if ($errors->any())
            <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="nw-panel" aria-labelledby="job-summary-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="job-summary-heading">{{ __('Job overview') }}</h2>
                    <p>{{ __('Review the organization posting and its application activity.') }}</p>
                </div>
                @if ($job->isAcceptingApplications())
                    <a class="nw-button nw-button--outline nw-button--small" href="{{ route('jobs.show', $job) }}" target="_blank" rel="noopener noreferrer">
                        {{ __('View public listing') }}
                    </a>
                @endif
            </header>

            <dl class="nw-detail-list">
                <div><dt>{{ __('Organization') }}</dt><dd>{{ $job->organization?->name ?? $job->company }}</dd></div>
                <div>
                    <dt>{{ __('Employer') }}</dt>
                    <dd>
                        @if ($job->employer)
                            {{ trim($job->employer->first_name.' '.$job->employer->last_name) ?: $job->employer->email }}
                            <small>{{ $job->employer->email }}</small>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div><dt>{{ __('Country') }}</dt><dd>
                    @if ($country)
                        <x-job-country :job="['country' => $country['name'], 'flag_url' => $country['flag_url'], 'flag_emoji' => $country['flag_emoji']]" />
                    @else
                        {{ $job->country_code ?: '—' }}
                    @endif
                </dd></div>
                <div><dt>{{ __('Category') }}</dt><dd>{{ $job->category ?: '—' }}</dd></div>
                <div><dt>{{ __('Employment type') }}</dt><dd>{{ $employmentTypeLabel }}</dd></div>
                <div><dt>{{ __('Applications') }}</dt><dd>{{ number_format($job->applications_count) }}</dd></div>
                <div><dt>{{ __('Published') }}</dt><dd>{{ $job->start_date?->format('j M Y') ?? '—' }}</dd></div>
                <div><dt>{{ __('Deadline') }}</dt><dd>{{ $job->due_date?->format('j M Y') ?? '—' }}</dd></div>
                <div><dt>{{ __('Created') }}</dt><dd>{{ $job->created_at?->format('j M Y, H:i') }}</dd></div>
            </dl>

            <div class="nw-rich-content">{!! $descriptionHtml !!}</div>
        </section>

        @if ($job->isSuspended())
            <section class="nw-panel" aria-labelledby="suspension-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="suspension-heading">{{ __('Suspension details') }}</h2>
                        <p>{{ __('This job is hidden from public job discovery and cannot be edited by the organization.') }}</p>
                    </div>
                </header>
                <dl class="nw-detail-list">
                    <div><dt>{{ __('Suspended on') }}</dt><dd>{{ $job->suspended_at?->format('j M Y, H:i') ?? '—' }}</dd></div>
                    <div><dt>{{ __('Suspended by') }}</dt><dd>{{ $job->suspendedBy ? trim($job->suspendedBy->first_name.' '.$job->suspendedBy->last_name) : '—' }}</dd></div>
                    <div><dt>{{ __('Reason') }}</dt><dd>{{ $job->suspension_reason ?: '—' }}</dd></div>
                </dl>
            </section>
        @endif

        @if ($job->status === \App\Enums\JobStatus::Open)
            <section class="nw-panel" aria-labelledby="moderation-actions-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="moderation-actions-heading">{{ __('Moderation action') }}</h2>
                        <p>{{ __('Suspending immediately removes the job from public listings and blocks new applications.') }}</p>
                    </div>
                </header>
                <form method="POST" action="{{ route('admin.jobs.suspend', $job) }}" class="nw-form-stack" data-confirm="{{ __('Suspend this job? Applicants already in the pipeline will remain available.') }}" data-confirm-label="{{ __('Suspend job') }}" data-confirm-type="warning">
                    @csrf
                    <div>
                        <x-input-label for="suspension_reason" :value="__('Reason for suspension')" />
                        <textarea id="suspension_reason" class="nw-input" name="suspension_reason" rows="4" maxlength="2000" required aria-describedby="suspension-reason-hint">{{ old('suspension_reason') }}</textarea>
                        <p id="suspension-reason-hint" class="nw-form-hint">{{ __('This reason is retained with the moderation record.') }}</p>
                        @error('suspension_reason')<p class="nw-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="nw-form-actions">
                        <button type="submit" class="nw-button nw-button--danger">{{ __('Suspend job') }}</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="nw-panel" aria-labelledby="delete-job-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="delete-job-heading">{{ __('Permanent deletion') }}</h2>
                    @if ($job->applications_count > 0)
                        <p>{{ __('This job has applications and cannot be permanently deleted. Keep it closed or suspended to preserve candidate history.') }}</p>
                    @else
                        <p>{{ __('Jobs without applications can be permanently removed.') }}</p>
                    @endif
                </div>
            </header>
            @if ((int) $job->applications_count === 0)
                <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" data-live-ignore data-confirm="{{ __('Permanently delete this job? This cannot be undone.') }}" data-confirm-label="{{ __('Delete job') }}" data-confirm-type="error" data-confirm-field="confirm_deletion">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nw-button nw-button--danger nw-button--small"><x-icon name="trash" /> {{ __('Delete job permanently') }}</button>
                </form>
            @else
                <button type="button" class="nw-button nw-button--danger nw-button--small" disabled aria-describedby="delete-job-heading">{{ __('Deletion unavailable') }}</button>
            @endif
        </section>
    </div>
    @endfragment
</x-app-layout>
