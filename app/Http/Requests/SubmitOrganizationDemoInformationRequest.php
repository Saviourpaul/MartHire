<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOrganizationDemoInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:4000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/'],
        ];
    }
}
