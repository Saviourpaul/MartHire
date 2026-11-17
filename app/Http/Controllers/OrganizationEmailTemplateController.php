<?php

namespace App\Http\Controllers;

use App\Enums\InterviewTemplateType;
use App\Http\Requests\SaveInterviewEmailTemplateRequest;
use App\Services\EmailTemplateService;
use App\Services\InterviewTemplateRenderer;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationEmailTemplateController extends Controller
{
    public function __construct(private EmailTemplateService $templates, private OrganizationContext $context) {}

    public function index(): View
    {
        $organization = $this->context->organization();
        abort_unless($organization, 403);
        Gate::authorize('manage_email_templates', $organization);

        $templates = $this->templates->forOrganization($organization->id);
        $selectedType = old('template_type', request()->query('template'));
        if (! in_array($selectedType, InterviewTemplateType::values(), true)) {
            $selectedType = null;
        }
        $selectedTemplate = $selectedType
            ? $templates->first(fn ($template): bool => $template->type->value === $selectedType)
            : null;

        return view('organisations.interviews.templates', [
            'organization' => $organization,
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'selectedTemplateType' => $selectedType,
            'editorValues' => [
                'subject' => old('subject', $selectedTemplate?->subject ?? ''),
                'body' => old('body', $selectedTemplate?->body ?? ''),
                'is_active' => filter_var(old('is_active', $selectedTemplate?->is_active ?? true), FILTER_VALIDATE_BOOLEAN),
            ],
            'templatePreviewData' => $templates->mapWithKeys(fn ($template): array => [$template->type->value => [
                'label' => $template->type->label(),
                'subject' => $template->subject,
                'body' => $template->body,
                'is_default' => $template->is_system,
                'is_active' => $template->is_active,
            ]])->all(),
            'previewValues' => [
                'candidate_name' => 'Alex Morgan',
                'job_title' => 'Senior Product Designer',
                'organization_name' => $organization->name,
                'interview_date' => 'Tuesday, December 1, 2026',
                'interview_time' => '10:30 AM - 11:15 AM',
                'timezone' => 'WAT',
                'venue_name' => 'Marthire Office',
                'address' => '14 Kingsway Road, Ikoyi, Lagos',
                'meeting_link' => 'https://meet.example.com/interview',
                'contact_name' => 'Jordan Lee',
                'contact_phone' => '+234 800 000 0000',
                'instructions' => 'Please have a portfolio sample ready to discuss.',
            ],
            'placeholderTokens' => array_map(
                static fn (string $placeholder): string => '{{ '.$placeholder.' }}',
                InterviewTemplateRenderer::PLACEHOLDERS,
            ),
            'requiredPlaceholderTokens' => [
                InterviewTemplateType::InterviewOnline->value => '{{ meeting_link }}',
                InterviewTemplateType::InterviewOnsite->value => '{{ address }}',
            ],
        ]);
    }

    public function update(SaveInterviewEmailTemplateRequest $request, InterviewTemplateType $type): RedirectResponse
    {
        $this->templates->saveOverride($this->context->organization(), $request->user(), $type, $request->validated());

        return to_route('organisations.email-templates.index', ['template' => $type->value])
            ->with('success', 'Organization email template saved.');
    }
}
