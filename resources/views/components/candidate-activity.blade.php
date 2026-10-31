@props(['activity'])

<section aria-labelledby="candidate-activity-heading">
    <header class="nw-panel__head"><div><h2 id="candidate-activity-heading">{{ __('Candidate activity') }}</h2><p>{{ __('Stage changes, interviews, communication and document access.') }}</p></div></header>
    @if (empty($activity['entries']))
        <div class="nw-empty"><x-icon name="clock" /><h3>{{ __('No activity recorded yet.') }}</h3></div>
    @else
        <ol class="nw-review-timeline">
            @foreach ($activity['entries'] as $entry)
                <li><span class="nw-review-timeline__icon" aria-hidden="true"><x-icon :name="match($entry['type']) { 'interview' => 'calendar', 'document_view' => 'file', 'email' => 'mail', default => 'layers' }" /></span>
                    <div><strong>{{ __($entry['title']) }}</strong>@if($entry['detail'])<p>{{ $entry['detail'] }}</p>@endif
                        @if($entry['remarks'])<blockquote>{{ $entry['remarks'] }}</blockquote>@endif
                        <small>{{ $entry['actor'] }} @if($entry['at'])<span aria-hidden="true">·</span> <time datetime="{{ $entry['at']->toIso8601String() }}">{{ $entry['at']->format('j M Y, g:i A') }}</time>@endif</small>
                    </div>
                </li>
            @endforeach
        </ol>
        @if($activity['has_more'])<p class="nw-hint">{{ __('Showing the latest :count activities. Earlier records remain in the audit history.', ['count' => $activity['limit']]) }}</p>@endif
    @endif
</section>
