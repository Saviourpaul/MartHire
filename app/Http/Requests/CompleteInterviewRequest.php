<?php

namespace App\Http\Requests;

use App\Models\Interview;
use Illuminate\Foundation\Http\FormRequest;

class CompleteInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        return $interview instanceof Interview && ($this->user()?->can('manageCompletion', $interview) ?? false);
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'completion_confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['completion_confirmed.accepted' => 'Confirm that this interview took place before marking it completed.'];
    }
}
