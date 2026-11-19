<x-app-layout>
    <x-slot name="header">
        <h1 class="nw-heading">{{ $greeting }} <span aria-hidden="true">{{ $greetingEmoji }}</span></h1>
        <p>{{ __('Track your applications and find your next role.') }}</p>
    </x-slot>

    <div class="nw-applicant-dashboard" x-data="applicantProfileChecklistEditor" x-on:open-applicant-profile-editor.window="open($event.detail)" x-on:submit="submitForm($event)" x-on:input="clearFieldError($event)" x-on:change="clearFieldError($event)">
    @fragment('client-dashboard')
    <div class="nw-applicant-dashboard-grid">
        <main class="nw-applicant-dashboard-main">

    <section id="applications" class="nw-panel" aria-labelledby="applications-heading">
        <header class="nw-panel__head">
            <div>
                <h2 id="applications-heading">{{ __('Your applications') }}</h2>
                <p>{{ __('The roles you\'ve applied for, newest first.') }}</p>
            </div>
        </header>

        @if ($applications->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No applications yet.') }}</h3>
                <p>{{ __('When you apply for a role, you\'ll be able to follow it here.') }}</p>
                <a class="nw-button nw-button--primary" href="{{ route('workable-jobs.index') }}">{{ __('Find a role') }} <x-icon name="arrow-up-right" /></a>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Applied') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <div>
                                            <strong>{{ $application->job?->title }}</strong>
                                            <small>{{ $application->job?->company }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $application->submitted_at?->format('j M Y') }}</td>
                                <td><x-status-badge :status="$application->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="nw-pagination">{{ $applications->onEachSide(1)->links() }}</div>
        @endif
    </section>

    
        </main>

        <aside class="nw-dashboard-profile-sidebar" aria-label="{{ __('Applicant profile progress') }}">
            <x-applicant-profile-checklist :checklist="$profileChecklist" />
        </aside>
    </div>
    </div>
    @endfragment
    @unless ($isLiveTableRequest)
    <x-modal name="applicant-profile-checklist-editor" maxWidth="2xl">
        @include('profile.partials.applicant-profile-checklist-editor', ['deferPhoneEnhancement' => true])
    </x-modal>
    @endunless
    </div>
</x-app-layout>
