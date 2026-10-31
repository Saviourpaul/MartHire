<?php

namespace App\Http\Requests;

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCandidatePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('applicationForm');

        return $application instanceof ApplicationForm
            && $this->user()?->can('managePipeline', $application)
            && ($this->input('stage') !== CandidatePipelineStage::Selected->value || $this->user()?->can('selectCandidates', $application));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(CandidatePipelineStage::class)],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'selection_confirmed' => ['exclude_unless:stage,selected', 'required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['selection_confirmed.accepted' => 'Explicitly confirm this candidate as Selected to continue.'];
    }
}
