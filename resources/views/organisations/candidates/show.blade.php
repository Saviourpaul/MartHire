<x-app-layout>
    <x-slot name="title">{{ $application->applicant?->first_name }} {{ $application->applicant?->last_name }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ trim(($application->applicant?->first_name ?? '').' '.($application->applicant?->last_name ?? '')) }}</h1>
        <p>{{ __('Applied for :job', ['job' => $application->job?->title]) }}</p>
    </x-slot>
    <x-slot name="actions">
        <a class="nw-link" href="{{ route('organisations.candidates.index') }}">{{ __('Back to candidates') }}</a>
    </x-slot>

    @if (session('success'))
        <div class="nw-notice" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="nw-dash-grid">
        <div class="nw-dash-stack">
            <section class="nw-panel" aria-labelledby="candidate-details-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="candidate-details-heading">{{ __('Candidate') }}</h2>
                    </div>
                    <x-status-badge :status="$application->status" />
                </header>

                <dl class="nw-detail-list">
                    <div><dt>{{ __('Email') }}</dt><dd>{{ $application->applicant?->email }}</dd></div>
                    <div><dt>{{ __('Phone') }}</dt><dd>{{ $application->applicant?->phone ?: '—' }}</dd></div>
                    <div><dt>{{ __('Reference') }}</dt><dd>{{ $application->reference }}</dd></div>
                    <div><dt>{{ __('Applied') }}</dt><dd>{{ $application->submitted_at?->format('j M Y, g:ia') }}</dd></div>
                </dl>
            </section>

            @can('viewDocuments', $application)
                <section class="nw-panel" aria-labelledby="documents-heading">
                    <header class="nw-panel__head">
                        <div>
                            <h2 id="documents-heading">{{ __('Documents') }}</h2>
                        </div>
                    </header>

                    @if ($application->documents->isEmpty())
                        <div class="nw-empty"><h3>{{ __('No documents submitted.') }}</h3></div>
                    @else
                        <ul class="nw-ranked-list">
                            @foreach ($application->documents as $document)
                                <li>
                                    <strong>{{ $document->document_type?->label() ?? $document->document_name ?? __('Document') }}</strong>
                                    <span>{{ $document->original_name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endcan

            <section class="nw-panel" aria-labelledby="history-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="history-heading">{{ __('Pipeline history') }}</h2>
                    </div>
                </header>

                @if ($application->statusHistories->isEmpty())
                    <div class="nw-empty"><h3>{{ __('No stage changes yet.') }}</h3></div>
                @else
                    <ul class="nw-ranked-list">
                        @foreach ($application->statusHistories->sortByDesc('created_at') as $history)
                            <li>
                                <strong>
                                    {{ $history->from_status?->label() ?? __('Applied') }}
                                    &rarr;
                                    {{ $history->to_status->label() }}
                                </strong>
                                <span>
                                    {{ $history->created_at?->format('j M Y, g:ia') }}
                                    @if ($history->changedBy)
                                        &middot; {{ trim($history->changedBy->first_name.' '.$history->changedBy->last_name) }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <section class="nw-panel" aria-labelledby="move-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="move-heading">{{ __('Move candidate') }}</h2>
                    <p>{{ __('Currently: :stage', ['stage' => $application->status->label()]) }}</p>
                </div>
            </header>

            @if (empty($allowedStages))
                <div class="nw-empty">
                    <h3>{{ __('No further moves available.') }}</h3>
                    <p>{{ __('This candidate has reached a final stage, or you don\'t have permission to move them.') }}</p>
                </div>
            @else
                <div class="nw-button-row nw-button-row--stack">
                    @foreach ($allowedStages as $stage)
                        <form method="POST" action="{{ route('organisations.candidates.move', $application) }}"
                            onsubmit="return confirm('{{ __('Move this candidate to :stage?', ['stage' => $stage->label()]) }}');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="stage" value="{{ $stage->value }}">
                            <button type="submit" class="nw-button {{ $stage->value === 'rejected' ? 'nw-button--danger' : 'nw-button--primary' }} nw-button--small">
                                {{ __('Move to :stage', ['stage' => $stage->label()]) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
