<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Services\OrganizationSettingsService;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;

class OrganizationSettingsController extends Controller
{
    public function __construct(private OrganizationSettingsService $settings, private OrganizationContext $context) {}

    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        $organization = $this->context->organization();
        abort_unless($organization, 404);

        $this->settings->update($organization, $request->user(), $request->validated());

        return to_route('settings.edit')->with('success', 'Organization settings saved.');
    }
}
