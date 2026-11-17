<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationSlugAlias;
use App\Services\PublicJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicOrganizationCareerController extends Controller
{
    public function __invoke(string $slug, PublicJobService $jobs): View|RedirectResponse
    {
        $organization = Organization::query()->where('slug', $slug)->first();

        if (! $organization && Schema::hasTable('organization_slug_aliases')) {
            $organization = OrganizationSlugAlias::query()
                ->with('organization')
                ->where('slug', $slug)
                ->first()?->organization;
        }

        abort_unless($organization, 404);

        if ($organization->slug !== $slug) {
            return redirect()->route('organizations.careers.show', ['slug' => $organization->slug], 301);
        }

        abort_unless(filled($organization->slug), 404);
        $organization->load('settings');

        return view('organisations.careers.show', [
            'organization' => $organization,
            'settings' => $organization->settings,
            'jobs' => $jobs->forOrganization($organization),
        ]);
    }
}
