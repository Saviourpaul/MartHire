<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default system interview email templates
    |--------------------------------------------------------------------------
    |
    | Seeded (as organization_id = null, is_system = true) by the
    | install_system_interview_email_templates migration, and used as the
    | fallback by EmailTemplateService::resolve() whenever an organization
    | hasn't configured its own override for a given interview email type.
    | Keys must match App\Enums\InterviewTemplateType values. Bodies may use
    | the placeholders listed in InterviewTemplateRenderer::PLACEHOLDERS.
    |
    */

    'defaults' => [
        'interview_online' => [
            'name' => 'Online interview invitation',
            'subject' => 'Interview invitation: {{ job_title }} at {{ organization_name }}',
            'body' => "Hi {{ candidate_name }},\n\nThank you for applying for {{ job_title }} at {{ organization_name }}. We'd like to invite you to an online interview on {{ interview_date }} at {{ interview_time }} ({{ timezone }}).\n\nJoin here: {{ meeting_link }}\n\nIf you have any questions, please contact {{ contact_name }} at {{ contact_phone }}.\n\n{{ instructions }}\n\nWe look forward to speaking with you.",
        ],
        'interview_onsite' => [
            'name' => 'Onsite interview invitation',
            'subject' => 'Interview invitation: {{ job_title }} at {{ organization_name }}',
            'body' => "Hi {{ candidate_name }},\n\nThank you for applying for {{ job_title }} at {{ organization_name }}. We'd like to invite you to an onsite interview on {{ interview_date }} at {{ interview_time }} ({{ timezone }}).\n\nLocation: {{ venue_name }}, {{ address }}\n\nIf you have any questions, please contact {{ contact_name }} at {{ contact_phone }}.\n\n{{ instructions }}\n\nWe look forward to meeting you.",
        ],
        'interview_reschedule' => [
            'name' => 'Interview rescheduled',
            'subject' => 'Your interview for {{ job_title }} has been rescheduled',
            'body' => "Hi {{ candidate_name }},\n\nYour interview for {{ job_title }} at {{ organization_name }} has been rescheduled to {{ interview_date }} at {{ interview_time }} ({{ timezone }}).\n\nIf you have any questions, please contact {{ contact_name }} at {{ contact_phone }}.\n\n{{ instructions }}",
        ],
        'interview_cancel' => [
            'name' => 'Interview cancelled',
            'subject' => 'Your interview for {{ job_title }} has been cancelled',
            'body' => "Hi {{ candidate_name }},\n\nYour interview for {{ job_title }} at {{ organization_name }} scheduled for {{ interview_date }} at {{ interview_time }} ({{ timezone }}) has been cancelled.\n\nIf you have any questions, please contact {{ contact_name }} at {{ contact_phone }}.",
        ],
    ],

    'subject_max_length' => 255,
    'body_max_length' => 20000,

];
