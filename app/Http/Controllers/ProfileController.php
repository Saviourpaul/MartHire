<?php

namespace App\Http\Controllers;

use App\Enums\JobAlertFrequency;
use App\Enums\SecurityActionPurpose;
use App\Http\Controllers\Concerns\RespondsWithApplicantProfileChecklist;
use App\Http\Requests\CloseAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Services\AccountLifecycleService;
use App\Services\ApplicantAccountActivityService;
use App\Services\ApplicantProfileService;
use App\Services\CandidateJobPreferenceService;
use App\Services\LocationService;
use App\Services\SecurityChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use RespondsWithApplicantProfileChecklist;

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, ApplicantProfileService $profiles, CandidateJobPreferenceService $jobPreferences): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'countries' => app(LocationService::class)->countries(),
            'applicantProfile' => $user->isApplicant() ? $profiles->forUser($user) : null,
            'jobPreferences' => $user->isApplicant() ? $jobPreferences->forUser($user) : null,
            'jobTypes' => Job::EMPLOYMENT_TYPES,
            'jobAlertFrequencies' => JobAlertFrequency::cases(),
        ]);
    }

    public function showApplicant(Request $request, User $user): View|RedirectResponse
    {
        abort_unless($user->isApplicant(), 404);

        if ($request->user()->isOrganization()) {
            abort_unless($user->isActive(), 403);
            $application = ApplicationForm::query()->forEmployer($request->user())->where('user_id', $user->id)->latest('submitted_at')->first();
            abort_unless($application, 403);

            return redirect()->route('organisations.candidates.show', $application);
        }

        return view('profile.show', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, ApplicantProfileService $profiles, ApplicantAccountActivityService $activity): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $applicantProfile = $user->isApplicant() ? $profiles->forUser($user) : null;
        $data = $request->safe()->except(
            'profile_image',
            'email',
            'gender_identity',
            'disability_status',
            'disability_details',
            'professional_headline',
            'about',
        );

        if ($request->hasFile('profile_image')) {
            $uploadedImagePath = $request->file('profile_image')->store('profile-images', 'public');

            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $data['profile_image_path'] = $uploadedImagePath;
        }

        $user->fill($data);
        $changedFields = array_keys($user->getDirty());
        if ($request->hasFile('profile_image')) {
            $changedFields[] = 'profile_image';
        }

        if ($applicantProfile) {
            foreach ($request->safe()->only(['professional_headline', 'about']) as $field => $value) {
                if ($applicantProfile->getAttribute($field) !== $value) {
                    $changedFields[] = $field;
                }
            }

            $identification = $applicantProfile->selfIdentification;
            $identityChanged = collect($request->safe()->only([
                'gender_identity', 'disability_status', 'disability_details',
            ]))->contains(fn ($value, string $field): bool => $identification?->getAttribute($field) !== $value);
            if ($identityChanged) {
                $changedFields[] = 'self_identification';
            }
        }

        // A successful save with a validated country selection confirms the
        // applicant's canonical location.
        if ($request->filled('country_code')) {
            $user->location_confirmed_at = now();
        }

        $user->save();

        if ($user->isApplicant()) {
            $profiles->updateDetails($user, $request->safe()->only(['professional_headline', 'about']));
            $profiles->updateContactIdentification($user, $request->safe()->only([
                'gender_identity',
                'disability_status',
                'disability_details',
            ]));
            $activity->profileUpdated($user, $changedFields);
        }

        if ($user->isApplicant() && $request->expectsJson()) {
            return $this->applicantProfileChecklistResponse($user, 'Profile updated successfully.');
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(CloseAccountRequest $request, SecurityChallengeService $challenges, AccountLifecycleService $accounts): RedirectResponse
    {
        $challenges->confirm($request, SecurityActionPurpose::AccountClose, fn (User $user) => $accounts->close($user));
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Your account has been closed. Recruitment records may be retained.');
    }
}
