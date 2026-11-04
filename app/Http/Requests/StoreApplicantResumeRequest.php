<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreApplicantResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'resume' => ['required', File::types(['doc', 'docx', 'txt', 'rtf', 'pdf'])->max(10240)],
            'user_id' => ['prohibited'],
        ];
    }
}
