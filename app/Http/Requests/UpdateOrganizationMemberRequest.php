<?php

namespace App\Http\Requests;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $member = $this->route('organizationMember');

        return $member instanceof OrganizationMember && ($this->user()?->can('manageTeam', $member->organization) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(OrganizationRole::class)],
            'status' => ['sometimes', 'required', Rule::enum(OrganizationMemberStatus::class)],
        ];
    }
}
