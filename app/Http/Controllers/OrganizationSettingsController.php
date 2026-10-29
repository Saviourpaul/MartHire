<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Models\Job;
use App\Models\Organization;
use App\Services\OrganizationSettingsService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function __construct(private OrganizationSettingsService $settings) {}

    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('settings.organization', [
            'organization' => $organization,
            'organizationSettings' => $this->settings->forOrganization($organization),
            'timezones' => DateTimeZone::listIdentifiers(),
            'jobTypes' => Job::EMPLOYMENT_TYPES,
        ]);
    }

    public function update(UpdateOrganizationSettingsRequest $request, Organization $organization): RedirectResponse
    {
        $this->settings->update($organization, $request->user(), $request->validated());

        return to_route('employer.organization-settings.edit', $organization)->with('success', 'Organization settings saved.');
    }
}
