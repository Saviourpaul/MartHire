<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInternationalPhone;
use App\Rules\InternationalPhoneNumber;
use App\Services\OrganizationDemoCaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrganizationDemoRequestRequest extends FormRequest
{
    use NormalizesInternationalPhone;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_email' => Str::lower(trim((string) $this->input('business_email'))),
        ]);
        $this->normalizePhoneInput();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_first_name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'contact_last_name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'company_name' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f\x7f]/'],
            'business_email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['bail', 'required', 'string', 'max:80', new InternationalPhoneNumber($this->input('phone_country'))],
            'industry' => ['nullable', 'string', 'max:100', 'not_regex:/[\x00-\x1f\x7f]/'],
            'organization_size' => ['required', Rule::in(['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'])],
            'website' => ['nullable', 'url', 'max:255'],
            'g-recaptcha-response' => [app(OrganizationDemoCaptchaService::class)->enabled() ? 'required' : 'nullable', 'string', 'max:4096'],
            ...$this->phonePartRules(),
            'organization_id' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! app(OrganizationDemoCaptchaService::class)->verify(
                $this->input('g-recaptcha-response'),
                $this,
            )) {
                $validator->errors()->add('captcha', 'Please complete the CAPTCHA challenge and try again.');
            }
        });
    }
}
