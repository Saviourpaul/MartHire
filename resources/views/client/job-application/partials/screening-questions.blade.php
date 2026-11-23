@use('App\Enums\JobScreeningQuestionType')

<fieldset class="nw-form-section nw-wizard__panel" data-wizard-step
    data-step-title="{{ __('Screening questions') }}"
    data-step-description="{{ __('Share job-specific answers with the hiring team.') }}"
    x-show="!ready || step === 2" :aria-hidden="ready && step !== 2">
    <legend data-wizard-heading tabindex="-1">{{ __('A few questions about this role') }}</legend>
    <p class="nw-wizard__description">{{ __('The hiring team selected these questions for this vacancy. Please answer every question.') }}</p>
    <div class="nw-form-section__grid">
        @foreach ($screeningQuestions as $question)
            @php
                $name = 'screening_answers.' . $question->question_key;
                $inputName = 'screening_answers[' . $question->question_key . ']';
                $value = old($name);
                $field = [
                    'name' => $name,
                    'input_name' => $inputName . ($question->type === JobScreeningQuestionType::MultiSelect ? '[]' : ''),
                    'label' => $question->prompt,
                    'required' => true,
                    'max_length' => $question->type === JobScreeningQuestionType::ShortText ? 250 : 5000,
                ];

                $field['type'] = match ($question->type) {
                    JobScreeningQuestionType::ShortText => 'short_text',
                    JobScreeningQuestionType::LongText => 'textarea',
                    JobScreeningQuestionType::SingleSelect => 'dropdown',
                    JobScreeningQuestionType::MultiSelect => 'multiple_choice',
                    JobScreeningQuestionType::YesNo => 'boolean',
                };

                if ($question->type === JobScreeningQuestionType::SingleSelect) {
                    $field['choices'] = collect($question->options ?? [])->map(fn (string $option): array => [
                        'id' => $option,
                        'body' => $option,
                    ])->all();
                }
                if ($question->type === JobScreeningQuestionType::MultiSelect) {
                    $field['single_answer'] = false;
                    $field['choices'] = collect($question->options ?? [])->map(fn (string $option): array => [
                        'id' => $option,
                        'body' => $option,
                    ])->all();
                }
            @endphp
            <div class="nw-form-section__item">
                <x-application-field :field="$field" :value="$value" />
            </div>
        @endforeach
    </div>
</fieldset>
