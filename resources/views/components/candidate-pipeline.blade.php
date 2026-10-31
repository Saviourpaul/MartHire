@props(['pipeline'])

<section class="nw-panel nw-review-progress" aria-labelledby="hiring-progress-heading">
    <header><div><h2 id="hiring-progress-heading">{{ __('Hiring progress') }}</h2><p>{{ __('Current stage: :stage', ['stage' => $pipeline['label']]) }}</p></div><x-icon name="layers" /></header>
    <ol class="nw-review-progress__steps" aria-label="{{ __('Candidate pipeline') }}">
        @foreach ($pipeline['steps'] as $step)
            <li class="is-{{ $step['state'] }}" @if($step['current']) aria-current="step" @endif>
                <span class="nw-review-progress__number" aria-hidden="true">@if($step['complete'])<x-icon name="check" />@elseif($step['state'] === 'rejected')<x-icon name="close" />@else{{ $loop->iteration }}@endif</span>
                <span>{{ __($step['label']) }}<small>{{ $step['current'] ? __('Current stage') : ($step['complete'] ? __('Completed') : ($pipeline['is_terminal'] ? __('Not reached') : __('Next stage'))) }}</small></span>
            </li>
        @endforeach
    </ol>
</section>
