<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\StoreOrganizationOnboardingInvitationsRequest;
use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Models\Job;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\OrganizationDemoRequestService;
use App\Services\OrganizationOnboardingService;
use App\Services\OrganizationSettingsService;
use App\Services\OrganizationTeamService;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationOnboardingController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OrganizationPermissions $permissions,
        private readonly OrganizationOnboardingService $onboarding,
    ) {}

    public function show(Request $request): View|RedirectResponse|JsonResponse
    {
        [$organization, $membership] = $this->activeOrganization($request);
        $progress = $this->onboarding->reconcile($membership, $this->onboarding->initialize($membership));
        if ($progress->isComplete()) {
            if ($request->expectsJson()) {
                return response()->json(['redirectUrl' => route('organisations.dashboard')]);
            }

            return to_route('organisations.dashboard');
        }

        $settings = app(OrganizationSettingsService::class)->forOrganization($organization);
        $stepLabels = [
            'welcome' => __('Welcome'),
            'profile' => __('Company profile'),
            'team' => __('Invite team'),
            'finish' => __('Ready'),
        ];
        $steps = $this->onboarding->stepsFor($membership);
        $currentStepIndex = array_search($progress->current_step, $steps, true);
        $stepCount = count($steps);
        $roles = OrganizationRole::cases();
        if ($membership->role !== OrganizationRole::Owner) {
            $roles = array_values(array_filter($roles, fn (OrganizationRole $role): bool => $role !== OrganizationRole::Owner));
        }

        $viewData = [
            'organization' => $organization,
            'membership' => $membership,
            'progress' => $progress,
            'steps' => $steps,
            'currentStep' => $progress->current_step,
            'stepLabels' => $stepLabels,
            'currentStepNumber' => $currentStepIndex === false ? 1 : $currentStepIndex + 1,
            'stepCount' => $stepCount,
            'progressPercent' => $currentStepIndex === false || $stepCount === 0
                ? 0
                : (int) round(($currentStepIndex / $stepCount) * 100),
            'focusOptions' => $this->onboarding->focusOptions(),
            'settings' => $settings,
            'roleOptions' => $roles,
            'canCreateJob' => $request->user()->can('create', Job::class),
            'story' => $this->storyFor($progress->current_step),
            'onboardingInitialState' => [
                'companyName' => old('name', $organization->name),
                'about' => old('about', $settings->about),
                'industry' => old('industry', $settings->industry),
                'size' => old('organization_size', $settings->organization_size),
                'primary' => $settings->brand_primary_color ?: '#006b43',
                'accent' => $settings->brand_accent_color ?: '#c9f542',
                'logo' => $settings->companyLogoUrl(),
                'removeSavedImage' => (bool) old('remove_company_logo'),
                'imageMessages' => [
                    'invalidType' => __('Choose a JPG, PNG, or WebP image.'),
                    'tooLarge' => __('Choose an image smaller than 2 MB.'),
                    'previewFailed' => __('This image could not be previewed. Choose another file.'),
                ],
                'invitees' => old('invitees', [['email' => '', 'role' => 'recruiter']]),
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'stageHtml' => view('organisations.onboarding.partials.stage', $viewData)->render(),
                'step' => $progress->current_step,
                'progressLabel' => __('Step :current of :total · :label', [
                    'current' => $viewData['currentStepNumber'],
                    'total' => $stepCount,
                    'label' => $stepLabels[$progress->current_step] ?? __('Organization setup'),
                ]),
                'progressPercent' => $viewData['progressPercent'],
                'progressValue' => $viewData['currentStepNumber'],
                'progressMax' => $stepCount,
            ]);
        }

        return view('organisations.onboarding.show', $viewData);
    }

    public function saveWelcome(Request $request): RedirectResponse|JsonResponse
    {
        [$organization, $membership] = $this->activeOrganization($request);
        $data = $request->validate(['recruitment_focus' => ['required', 'string', 'in:'.implode(',', array_keys($this->onboarding->focusOptions()))]]);
        $progress = $this->onboarding->initialize($membership);
        abort_unless($progress->current_step === 'welcome', 409);
        $this->onboarding->updateFocus($progress, $data['recruitment_focus'], $this->onboarding->focusOptions());
        $this->onboarding->advance($membership, 'welcome');

        return $this->transitionResponse($request, 'Your workspace preferences are saved.');
    }

    public function saveProfile(UpdateOrganizationSettingsRequest $request, OrganizationSettingsService $settings): RedirectResponse|JsonResponse
    {
        [$organization, $membership] = $this->activeOrganization($request);
        $progress = $this->onboarding->initialize($membership);
        abort_unless($progress->current_step === 'profile', 409);
        $settings->update($organization, $request->user(), $request->validated());
        $this->onboarding->advance($membership, 'profile');

        return $this->transitionResponse($request, 'Your organization profile is saved.');
    }

    public function inviteTeam(StoreOrganizationOnboardingInvitationsRequest $request, OrganizationTeamService $teams): RedirectResponse|JsonResponse
    {
        [$organization, $membership] = $this->activeOrganization($request);
        $progress = $this->onboarding->initialize($membership);
        abort_unless($progress->current_step === 'team', 409);
        $results = [];

        foreach ($request->validated('invitees') as $invitee) {
            try {
                $invitation = $teams->invite($organization, $request->user(), $invitee['email'], OrganizationRole::from($invitee['role']));
                $results[] = ['email' => $invitee['email'], 'success' => true, 'message' => 'Invitation sent; expires '.$invitation->expires_at->format('M j, Y').'.'];
            } catch (ValidationException $exception) {
                $results[] = ['email' => $invitee['email'], 'success' => false, 'message' => Arr::flatten($exception->errors())[0] ?? 'This invitation could not be sent.'];
            } catch (\Throwable $exception) {
                report($exception);
                $results[] = ['email' => $invitee['email'], 'success' => false, 'message' => 'The invitation could not be sent. Please retry this address.'];
            }
        }

        $failed = collect($results)->contains(fn (array $result): bool => ! $result['success']);
        if (! $failed) {
            $this->onboarding->advance($membership, 'team');
        }

        if ($request->expectsJson()) {
            $retryInvitees = $failed
                ? collect($request->validated('invitees'))
                    ->filter(fn (array $invitee): bool => collect($results)->contains(
                        fn (array $result): bool => $result['email'] === $invitee['email'] && ! $result['success'],
                    ))
                    ->values()
                    ->all()
                : [];

            if ($failed) {
                $request->session()->flash('inviteResults', $results);
                $request->session()->flashInput(['invitees' => $retryInvitees]);
            }

            return response()->json([
                'transitioned' => ! $failed,
                'showUrl' => $failed ? null : route('organisations.onboarding.show'),
                'message' => $failed ? 'Some invitations need attention. Successful invitations were kept.' : 'Hiring team invitations sent.',
                'type' => $failed ? 'warning' : 'success',
                'inviteResults' => $results,
                'retryInvitees' => $retryInvitees,
            ]);
        }

        $response = to_route('organisations.onboarding.show')
            ->with('inviteResults', $results)
            ->with($failed ? 'warning' : 'success', $failed ? 'Some invitations need attention. Successful invitations were kept.' : 'Hiring team invitations sent.');

        if ($failed) {
            $retryEmails = collect($results)->where('success', false)->pluck('email')->all();
            $retryInvitees = collect($request->validated('invitees'))
                ->filter(fn (array $invitee): bool => in_array($invitee['email'], $retryEmails, true))->values()->all();
            $response->withInput(['invitees' => $retryInvitees]);
        }

        return $response;
    }

    public function skip(Request $request, string $step): RedirectResponse|JsonResponse
    {
        [, $membership] = $this->activeOrganization($request);
        $this->onboarding->advance($membership, $step, skipped: true);

        return $this->transitionResponse($request, 'You can complete this step later in organization settings.', 'info');
    }

    public function back(Request $request): RedirectResponse|JsonResponse
    {
        [, $membership] = $this->activeOrganization($request);
        $progress = $this->onboarding->initialize($membership);
        $steps = $this->onboarding->stepsFor($membership);
        $index = array_search($progress->current_step, $steps, true);
        abort_unless($index !== false && $index > 0, 409);
        $this->onboarding->goBack($membership, $steps[$index - 1]);

        return $this->transitionResponse($request);
    }

    public function finish(Request $request, OrganizationDemoRequestService $demoRequests): RedirectResponse|JsonResponse
    {
        [$organization, $membership] = $this->activeOrganization($request);
        $data = $request->validate(['next_action' => ['required', 'in:dashboard,job']]);

        if ($data['next_action'] === 'job') {
            abort_unless($request->user()->can('create', Job::class), 403);
        }

        DB::transaction(function () use ($membership, $request, $organization, $demoRequests): void {
            $this->onboarding->complete($membership);
            $demoRequests->markOnboardingCompleted($request->user(), $organization);
        });

        if ($data['next_action'] === 'job') {
            if ($request->expectsJson()) {
                return response()->json(['redirectUrl' => route('organisations.jobs.create')]);
            }

            return to_route('organisations.jobs.create')->with('success', 'Your workspace is ready. Create your first job.');
        }

        if ($request->expectsJson()) {
            return response()->json(['redirectUrl' => route('organisations.dashboard')]);
        }

        return to_route('organisations.dashboard')->with('success', 'Your workspace is ready.');
    }

    /** @param array<string, mixed> $extra */
    private function transitionResponse(Request $request, ?string $message = null, string $type = 'success', array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'showUrl' => route('organisations.onboarding.show'),
                'message' => $message,
                'type' => $type,
            ], $extra));
        }

        $response = to_route('organisations.onboarding.show');

        return $message === null ? $response : $response->with($type, $message);
    }

    /** @return array{eyebrow: string, title: string, description: string, caption: string, illustration: string} */
    private function storyFor(string $step): array
    {
        return match ($step) {
            'profile' => [
                'eyebrow' => __('Your company, clearly presented'),
                'title' => __('Give your hiring workspace a home.'),
                'description' => __('Add the details candidates and teammates need to recognize your organization.'),
                'caption' => __('Your organization profile is saved to this workspace.'),
                'illustration' => 'asset/marthire/onboarding/organization-profile.svg',
            ],
            'team' => [
                'eyebrow' => __('Hiring works better together'),
                'title' => __('Bring the right people into the process.'),
                'description' => __('Invite teammates with the access they need, then review candidates together.'),
                'caption' => __('You can change team access any time.'),
                'illustration' => 'asset/marthire/onboarding/hiring-team.svg',
            ],
            'finish' => [
                'eyebrow' => __('Workspace ready'),
                'title' => __('Make your next great hire together.'),
                'description' => __('Your team can move from a new opening to a confident hiring decision in one place.'),
                'caption' => __('Your workspace is ready for its first job.'),
                'illustration' => 'asset/marthire/onboarding/workspace-ready.svg',
            ],
            default => [
                'eyebrow' => __('Marthire for organizations'),
                'title' => __('Build a team that moves your mission forward.'),
                'description' => __('Set up your hiring workspace, bring your team together, and keep every candidate moving with clarity.'),
                'caption' => __('One clear workspace for every hiring decision.'),
                'illustration' => 'asset/marthire/onboarding/hiring-workspace.svg',
            ],
        };
    }

    /** @return array{Organization, OrganizationMember} */
    private function activeOrganization(Request $request): array
    {
        $organization = $this->context->organization();
        $membership = $this->permissions->membership($request->user());
        abort_unless($organization instanceof Organization && $membership instanceof OrganizationMember, 403);
        abort_unless($membership->organization_id === $organization->id, 403);

        return [$organization, $membership];
    }
}
