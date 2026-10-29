<?php

namespace App\Http\Requests;

use App\Enums\SecurityActionPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSecurityChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true && $this->user()->hasVerifiedEmail() && filled($this->user()->google_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'purpose' => ['required', Rule::enum(SecurityActionPurpose::class)],
        ];
    }
}
