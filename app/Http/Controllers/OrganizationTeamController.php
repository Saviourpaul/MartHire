<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Http\Requests\StoreTeamInvitationRequest;
use App\Http\Requests\UpdateOrganizationMemberRequest;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Services\OrganizationTeamService;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationTeamController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OrganizationTeamService $teams,
    ) {}

    public function index(Request $request): View
    {
        $organization = $this->organization();
        Gate::authorize('manageTeam', $organization);
        $members = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->with('user')
            ->latest('id')
            ->paginate(15, ['*'], 'members_page');

        $assignmentCounts = JobPostMember::query()
            ->where('organization_id', $organization->id)
            ->whereIn('user_id', $members->getCollection()->pluck('user_id'))
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $members->getCollection()->each(fn (OrganizationMember $member) => $member->setAttribute('jobs_count', (int) ($assignmentCounts[$member->user_id] ?? 0)));
        $invitations = TeamInvitation::query()
            ->where('organization_id', $organization->id)
            ->with('invitedBy')
            ->latest('id')
            ->paginate(10, ['*'], 'invitations_page');

        $actorMembership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('organisations.team.index', [
            'organization' => $organization,
            'members' => $members,
            'invitations' => $invitations,
            'currentOrganizationRole' => $actorMembership->role,
            'roleOptions' => array_values(array_filter(OrganizationRole::cases(), fn (OrganizationRole $role): bool => $actorMembership->role === OrganizationRole::Owner || $role !== OrganizationRole::Owner)),
        ]);
    }

    public function storeInvitation(StoreTeamInvitationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $invitation = $this->teams->invite($this->organization(), $request->user(), $data['email'], OrganizationRole::from($data['role']));

        return to_route('organisations.team.index')->with('success', 'Invitation sent. It expires on '.$invitation->expires_at->format('M j, Y, g:i A').'.');
    }

    public function updateMember(UpdateOrganizationMemberRequest $request, OrganizationMember $organizationMember): RedirectResponse
    {
        $data = $request->validated();
        $this->teams->updateMember(
            $organizationMember,
            $request->user(),
            OrganizationRole::from($data['role']),
            isset($data['status']) ? OrganizationMemberStatus::from($data['status']) : $organizationMember->status,
        );

        return to_route('organisations.team.index')->with('success', 'Team member updated.');
    }

    public function destroyMember(Request $request, OrganizationMember $organizationMember): RedirectResponse
    {
        Gate::authorize('manageTeam', $organizationMember->organization);
        $this->teams->removeMember($organizationMember, $request->user());

        return to_route('organisations.team.index')->with('success', 'Team member removed and job assignments cleared.');
    }

    private function organization(): Organization
    {
        $organization = $this->context->organization();
        abort_unless($organization, 403);

        return $organization;
    }
}
