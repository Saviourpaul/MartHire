<?php

namespace App\Http\Requests;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreOrganizationOnboardingInvitationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = app(OrganizationContext::class)->organization();

        return $organization instanceof Organization && ($this->user()?->can('manageTeam', $organization) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'invitees' => ['required', 'array', 'min:1', 'max:10'],
            'invitees.*.email' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
            'invitees.*.role' => ['required', Rule::enum(OrganizationRole::class)],
            'organization_id' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->input('invitees', []);
        $invitees = is_array($input) ? collect($input)->map(function ($invitee) {
            if (! is_array($invitee)) {
                return $invitee;
            }

            return [...$invitee, 'email' => Str::lower(trim((string) ($invitee['email'] ?? '')))];
        })->all() : $input;

        $this->merge(['invitees' => $invitees]);
    }
}
