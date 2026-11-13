<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestOrganizationDemoInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('demo_requests.review') === true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/'],
        ];
    }
}
