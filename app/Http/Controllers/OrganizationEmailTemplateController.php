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

        return view('organisations.interviews.templates', [
            'templates' => $this->templates->forOrganization($organization->id),
            'placeholders' => InterviewTemplateRenderer::PLACEHOLDERS,
        ]);
    }

    public function update(SaveInterviewEmailTemplateRequest $request, InterviewTemplateType $type): RedirectResponse
    {
        $this->templates->saveOverride($this->context->organization(), $request->user(), $type, $request->validated());

        return to_route('organisations.email-templates.index')->with('success', 'Organization email template saved.');
    }
}
