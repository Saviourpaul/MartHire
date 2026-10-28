@use('Illuminate\Support\Str')

@php
    // Real data: pass $jobs and $form (the job's own application form) in from the route/controller.
    // Until then, sample listings and a sample form are shown and nothing is sent anywhere.
    $catalog = \App\Support\JobCatalog::make($jobs ?? null);
    $job = $job ?? $catalog->jobs->firstWhere('id', $id) ?? abort(404);
    $company = $job['company'] ?? __('the employer');
    $form = $form ?? require resource_path('data/sample-application-form.php');
    $fill = fn (?string $text) => $text === null ? null : str_replace(':company', $company, $text);

    // The backend switches this off simply by defining a POST route named workable-jobs.apply.store.
    $preview = ! \Illuminate\Support\Facades\Route::has('workable-jobs.apply.store');
    $action = $preview ? route('workable-jobs.apply', $job['id']) : route('workable-jobs.apply.store', $job['id']);

    $account = auth()->user();
    $given = $account->getAttribute('firstname') ?? $account->getAttribute('first_name') ?? '';
    $family = $account->getAttribute('lastname') ?? $account->getAttribute('last_name') ?? '';
    if ($given === '' && $account->getAttribute('name')) {
        [$given, $family] = array_pad(explode(' ', trim($account->getAttribute('name')), 2), 2, '');
    }
    $prefill = ['firstname' => $given, 'lastname' => $family, 'email' => $account->email];

    // (Values on the left win in PHP's `+`, so the filled-in label and hint go first.)
    $standard = collect($form['fields'] ?? [])->map(fn ($field) => ['name' => $field['key'], 'label' => $fill($field['label']), 'hint' => $fill($field['hint'] ?? null)] + $field);
    $questions = collect($form['questions'] ?? [])->map(function ($question) use ($fill) {
        $isFile = $question['type'] === 'file';
        $isGroup = $question['type'] === 'multiple_choice' && ! ($question['single_answer'] ?? true);

        return [
            'name' => 'answers['.$question['id'].']',
            'input_name' => $isFile ? 'answer_files['.$question['id'].']' : 'answers['.$question['id'].']'.($isGroup ? '[]' : ''),
            'label' => $fill($question['body']),
            'hint' => $fill($question['hint'] ?? null),
            'type' => $question['type'],
            'required' => $question['required'] ?? false,
            'choices' => $question['choices'] ?? [],
            'single_answer' => $question['single_answer'] ?? true,
            'accept' => $question['supported_file_types'] ?? [],
            'max_file_size' => $question['max_file_size'] ?? null,
        ];
    });
    // For a file question the field is tracked under its answers[...] name, but the file itself is posted as answer_files[...].
    $sections = [
        ['title' => __('About you'), 'fields' => $standard->where('section', 'details')->values()],
        ['title' => __('Your CV'), 'fields' => $standard->where('section', 'documents')->values()],
        ['title' => __('A few questions from :company', ['company' => $company]), 'fields' => $questions],
    ];
    $serverErrors = collect($errors->messages())->map(fn ($messages) => $messages[0])->all();
    $alreadySent = session('applied') === true;
@endphp

<x-layout :title="__('Apply for :title', ['title' => $job['title']])" :description="__('Apply for :title at :company on NearbyWorks.', ['title' => $job['title'], 'company' => $company])">
    <div class="nw-apply">
        <div class="nw-container">
            <nav class="nw-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('workable-jobs.show', $job['id']) }}"><x-icon name="arrow-left" /> {{ __('Back to the role') }}</a>
            </nav>

            <div class="nw-apply__grid">
                <div class="nw-apply__main" x-data="applicationForm({ preview: @js($preview), errors: @js($serverErrors) })" x-init="@if ($alreadySent) done = true @endif">
                    <header class="nw-apply__head" x-show="! done">
                        <span class="nw-eyebrow">{{ __('Your application') }}</span>
                        <h1>{{ __('Apply for :title', ['title' => $job['title']]) }}</h1>
                        <p class="nw-copy">{{ __('Tell :company a little about you. Fields marked * are required.', ['company' => $company]) }}</p>
                    </header>

                    <div x-show="! done">
                        @if ($preview)
                            <div class="nw-notice nw-notice--sample" role="note">
                                <x-icon name="spark" />
                                <span>{{ __('This is a preview. The form isn’t connected to Workable yet, so nothing is sent when you finish.') }}</span>
                            </div>
                        @endif

                        <div class="nw-error-summary" x-ref="summary" tabindex="-1" role="alert" x-show="summary.length" x-cloak>
                            <h2>{{ __('Please check these first') }}</h2>
                            <ul>
                                <template x-for="item in summary" x-bind:key="item.name">
                                    <li><button type="button" class="nw-link" x-on:click="goTo(item)"><span x-text="item.label"></span>: <span x-text="item.message"></span></button></li>
                                </template>
                            </ul>
                        </div>

                        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate x-ref="form" x-on:submit="submit($event)">
                            @csrf

                            @foreach ($sections as $section)
                                @if ($section['fields']->isNotEmpty())
                                    <fieldset class="nw-form-section">
                                        <legend>{{ $section['title'] }}</legend>
                                        <div class="nw-form-section__grid">
                                            @foreach ($section['fields'] as $field)
                                                <div class="nw-form-section__item {{ in_array($field['name'], ['firstname', 'lastname'], true) ? 'is-half' : '' }}">
                                                    <x-application-field :field="$field" :value="old(preg_replace('/\[([^\]]+)\]/', '.$1', $field['name']), $prefill[$field['name']] ?? null)" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif
                            @endforeach

                            <div class="nw-form-section nw-form-section--confirm">
                                <div class="nw-field" data-field="confirm" data-type="boolean" data-label="{{ __('Confirmation') }}" data-required data-required-message="{{ __('Please tick this box to send your application.') }}">
                                    <label class="nw-choice nw-choice--check">
                                        <input type="checkbox" name="confirm" value="1" @checked(old('confirm')) aria-describedby="field-confirm-error" x-on:change="clear('confirm')">
                                        <span>{{ __('I confirm these details are correct, and I’m happy for NearbyWorks to share them with :company for this application.', ['company' => $company]) }}</span>
                                    </label>
                                    <p class="nw-field__error" id="field-confirm-error" role="alert" x-show="error('confirm')" x-text="error('confirm')" x-cloak></p>
                                </div>

                                <x-form.actions>
                                    <x-slot:cancel>
                                        <a class="nw-link" href="{{ route('workable-jobs.show', $job['id']) }}">{{ __('Cancel') }}</a>
                                    </x-slot:cancel>
                                    <button type="submit" class="nw-button nw-button--primary" x-bind:disabled="submitting">
                                        <span x-text="submitting ? @js(__('Sending…')) : @js(__('Send application'))">{{ __('Send application') }}</span>
                                        <x-icon name="send" />
                                    </button>
                                </x-form.actions>
                            </div>
                        </form>
                    </div>

                    <section class="nw-apply__done" x-ref="success" tabindex="-1" x-show="done" x-cloak aria-labelledby="done-heading">
                        <span class="nw-apply__done-icon" aria-hidden="true"><x-icon name="check" /></span>
                        <h2 id="done-heading">{{ __('Your application is on its way.') }}</h2>
                        <p class="nw-copy">{{ __('Thank you for applying for :title. :company will take a look.', ['title' => $job['title'], 'company' => $company]) }}</p>
                        @if ($preview)
                            <p class="nw-apply__done-note"><x-icon name="spark" /> {{ __('Preview only: nothing was sent, because the Workable connection isn’t set up yet.') }}</p>
                        @endif
                        <div class="nw-button-row">
                            <a class="nw-button nw-button--primary" href="{{ route('dashboard') }}">{{ __('Go to your dashboard') }} <x-icon name="arrow-up-right" /></a>
                            <a class="nw-button nw-button--outline" href="{{ route('workable-jobs.index') }}">{{ __('Keep browsing jobs') }}</a>
                        </div>
                    </section>
                </div>

                <aside class="nw-apply__aside" aria-label="{{ __('About this role') }}">
                    <div class="nw-summary-card">
                        <div class="nw-summary-card__top">
                            <x-company-mark :company="$company" />
                            <div>
                                <p class="nw-job__company">{{ $company }}</p>
                                <h2>{{ $job['title'] }}</h2>
                            </div>
                        </div>
                        <ul class="nw-job__meta">
                            @if (! empty($job['location']))<li><x-icon name="map-pin" /> {{ $job['location'] }}</li>@endif
                            @if (! empty($job['type']))<li><x-icon name="briefcase" /> {{ $job['type'] }}@if (! empty($job['workplace'])), {{ $job['workplace'] }}@endif</li>@endif
                            @if (! empty($job['salary']))<li><x-icon name="wallet" /> {{ $job['salary'] }}</li>@endif
                        </ul>
                        <a class="nw-link nw-link--small" href="{{ route('workable-jobs.show', $job['id']) }}">{{ __('Read the full description') }} <x-icon name="arrow-up-right" /></a>
                    </div>

                    @if (! empty($job['apply_url']))
                        <p class="nw-form-hint">
                            {{ __('Prefer to apply on :company’s own page?', ['company' => $company]) }}
                            <a class="nw-link" href="{{ $job['apply_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('Continue there') }}</a>
                        </p>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-layout>
