<?php

namespace App\Http\Requests;

use App\Models\Interview;
use Illuminate\Foundation\Http\FormRequest;

class CancelInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        return $interview instanceof Interview && ($this->user()?->can('cancel', $interview) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'body' => ['nullable', 'string', 'max:20000'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'to_email' => ['prohibited'], 'recipient' => ['prohibited'],
            'cc' => ['prohibited'], 'bcc' => ['prohibited'], 'attachments' => ['prohibited'],
        ];
    }
}
