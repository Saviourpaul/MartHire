<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchOrganizationRequest;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Http\RedirectResponse;

// invokable controller that switches the signed-in user’s active organization and then redirects them to the dashboard.
class OrganizationContextController extends Controller
{
    public function __invoke(SwitchOrganizationRequest $request): RedirectResponse
    {
        $organizationId = $request->integer('organization_id');
        abort_unless(OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)->where('user_id', $request->user()->id)
            ->where('status', 'active')->exists(), 403);
        $request->session()->put('active_organization_id', $organizationId);

        return redirect()->route('dashboard');
    }
}
