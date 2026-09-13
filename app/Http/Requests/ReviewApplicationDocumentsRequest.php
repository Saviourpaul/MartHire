<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewApplicationDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('applicationForm');
        $user = $this->user();

        if (! $application instanceof ApplicationForm || ! $user?->isEmployer()) {
            return false;
        }

        $application->loadMissing('job');

        return $application->job->employer_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ApplicationStatus::values())],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
