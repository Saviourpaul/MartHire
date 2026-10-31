<section aria-labelledby="candidate-interviews-heading" id="candidate-interviews">
    <header class="nw-panel__head"><div><h2 id="candidate-interviews-heading">{{ __('Interviews') }}</h2><p>{{ __('Schedule conversations and follow candidate responses.') }}</p></div></header>
    @can('create', [\App\Models\Interview::class, $application])
        <details class="nw-review-disclosure" id="schedule-interview" x-ref="scheduleInterview" @if(old('_interview_form') === 'schedule') open @endif>
            <summary>{{ __('Schedule interview') }}</summary>
            @include('organisations.interviews._form', ['interview' => null])
        </details>
    @else
        @if ($canSendCandidateEmail && $application->status !== \App\Enums\CandidatePipelineStage::Interview)
            <p class="nw-hint">{{ __('Move this candidate to the Interview stage to schedule an interview.') }}</p>
        @endif
    @endcan
    @forelse ($application->interviews as $interview)
        <article class="nw-review-interview" aria-labelledby="interview-heading-{{ $interview->id }}">
            <header class="nw-panel__head"><div><h3 id="interview-heading-{{ $interview->id }}">{{ $interview->mode->label() }} {{ __('interview') }}</h3><p>{{ $interview->start_at->setTimezone($interview->timezone)->format('D, j M Y · g:i A') }} – {{ $interview->end_at->setTimezone($interview->timezone)->format('g:i A') }} ({{ $interview->timezone }})</p></div><x-status-badge :status="$interview->status" /></header>
            <dl class="nw-detail-list">
                <div><dt>{{ __('Candidate response') }}</dt><dd>{{ $interview->candidate_response->label() }}</dd></div>
                @if ($interview->mode === \App\Enums\InterviewMode::Online)
                    <div><dt>{{ __('Platform') }}</dt><dd>{{ $interview->platform?->label() }}</dd></div>
                    <div><dt>{{ __('Meeting') }}</dt><dd><a class="nw-link" href="{{ $interview->meeting_link }}" target="_blank" rel="noopener noreferrer">{{ __('Join meeting') }}</a></dd></div>
                @else
                    <div><dt>{{ __('Location') }}</dt><dd>{{ $interview->venue_name }} · {{ $interview->address }}</dd></div>
                @endif
                @if ($interview->instructions)<div><dt>{{ __('Instructions') }}</dt><dd>{{ $interview->instructions }}</dd></div>@endif
            </dl>
            @can('complete', $interview)
                <form method="POST" action="{{ route('organisations.interviews.complete', $interview) }}" class="nw-form" data-confirm="{{ __('Mark this interview as completed? You can then review the candidate for selection. This will not select the candidate automatically.') }}" data-confirm-title="{{ __('Complete interview') }}" data-confirm-label="{{ __('Mark completed') }}" data-confirm-type="info" data-confirm-field="completion_confirmed">
                    @csrf @method('PATCH')
                    <input type="hidden" name="version" value="{{ $interview->version }}">
                    <input type="hidden" name="_interview_form" value="complete-{{ $interview->id }}">
                    <button type="submit" class="nw-button nw-button--primary nw-button--small">{{ __('Mark interview completed') }}</button>
                </form>
            @endcan
            @if ($interview->status->isActive())
                @can('update', $interview)
                    <details class="nw-review-disclosure" @if(old('_interview_form') === 'edit-'.$interview->id) open @endif><summary>{{ __('Reschedule or edit') }}</summary>@include('organisations.interviews._form', ['interview' => $interview])</details>
                @endcan
                @can('cancel', $interview)
                    <details class="nw-review-disclosure" @if(old('_interview_form') === 'cancel-'.$interview->id) open @endif>
                        <summary>{{ __('Cancel interview') }}</summary>
                        <form method="POST" action="{{ route('organisations.interviews.cancel', $interview) }}" class="nw-form">
                            @csrf <input type="hidden" name="version" value="{{ $interview->version }}"><input type="hidden" name="_interview_form" value="cancel-{{ $interview->id }}">
                            <p class="nw-hint">{{ __('The candidate will receive a cancellation email. This does not change their pipeline stage.') }}</p>
                            <button class="nw-button nw-button--danger nw-button--small" type="submit">{{ __('Cancel and notify candidate') }}</button>
                        </form>
                    </details>
                @endcan
            @endif
            @if ($canSendCandidateEmail && $interview->relationLoaded('messages'))
                <details class="nw-review-disclosure"><summary>{{ __('Invitation delivery') }}</summary>
                    @forelse ($interview->messages as $message)
                        <div class="nw-review-delivery"><strong>{{ $message->rendered_subject }}</strong><p>{{ $message->status->label() }} · {{ ($message->sent_at ?? $message->queued_at ?? $message->created_at)?->format('j M Y, g:i A') }}</p>
                            @if ($message->error_message)<p class="nw-hint">{{ $message->error_message }}</p>@endif
                            @can('retry', $message)<form method="POST" action="{{ route('organisations.interviews.retry', $message) }}">@csrf<button type="submit" class="nw-button nw-button--small">{{ __('Retry email') }}</button></form>@endcan
                        </div>
                    @empty<p class="nw-hint">{{ __('No invitation emails recorded.') }}</p>@endforelse
                </details>
            @endif
        </article>
    @empty<div class="nw-empty"><h3>{{ __('No interviews scheduled') }}</h3><p>{{ __('Interview details and candidate responses will appear here.') }}</p></div>@endforelse
</section>
