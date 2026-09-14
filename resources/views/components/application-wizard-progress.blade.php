@props(['steps'])

<ol class="application-wizard-progress" data-application-wizard-progress>
    @foreach ($steps as $index => $step)
        <li class="{{ $index === 0 ? 'is-active' : '' }}" data-application-wizard-progress-item>
            <span class="application-wizard-progress-number">{{ $index + 1 }}</span>
            <span class="application-wizard-progress-label">{{ $step }}</span>
        </li>
    @endforeach
</ol>
