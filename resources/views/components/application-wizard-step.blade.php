@props([
    'title',
    'index',
])

<section {{ $attributes->merge(['class' => 'application-wizard-step']) }} data-application-wizard-step="{{ $index }}" @if ($index > 0) hidden @endif>
    <div class="card mb-0">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <span class="text-muted small">Step {{ $index + 1 }}</span>
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                </div>
                {{ $actions ?? '' }}
            </div>

            {{ $slot }}
        </div>
    </div>
</section>
