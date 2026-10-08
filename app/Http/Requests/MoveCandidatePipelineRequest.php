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
            && $this->user()?->can('managePipeline', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(CandidatePipelineStage::class)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
