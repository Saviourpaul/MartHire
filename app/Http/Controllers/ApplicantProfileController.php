<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCandidateEducationRequest;
use App\Http\Requests\SaveCandidateEmploymentRequest;
use App\Http\Requests\StoreApplicantResumeRequest;
use App\Http\Requests\UpdateApplicantProfileDetailsRequest;
use App\Http\Requests\UpdateCandidateSkillsRequest;
use App\Http\Controllers\Concerns\RespondsWithApplicantProfileChecklist;
use App\Models\User;
use App\Services\ApplicantProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantProfileController extends Controller
{
    use RespondsWithApplicantProfileChecklist;

    public function __construct(private ApplicantProfileService $profiles) {}

    public function updateDetails(UpdateApplicantProfileDetailsRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->updateDetails($request->user(), $request->validated());

        return $this->profileSaved($request, 'online-profiles', 'Profile details saved.');
    }

    public function updateSkills(UpdateCandidateSkillsRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->updateSkills($request->user(), $request->validated('skills'));

        return $this->profileSaved($request, 'skills', 'Skills saved.');
    }

    public function storeResume(StoreApplicantResumeRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->uploadResume($request->user(), $request->file('resume'));

        return $this->profileSaved($request, 'resume', 'Résumé uploaded.');
    }

    public function removeResume(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $this->profiles->deleteResume($request->user());

        return $this->profileSaved($request, 'resume', 'Résumé removed.');
    }

    public function downloadResume(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $profile = $this->profiles->forUser($request->user());
        abort_unless($profile->resume_path && Storage::disk('local')->exists($profile->resume_path), 404);

        return Storage::disk('local')->download($profile->resume_path, basename($profile->resume_original_name ?: 'resume'));
    }

    public function storeEmployment(SaveCandidateEmploymentRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->addEmployment($request->user(), $request->validated());

        return $this->profileSaved($request, 'employment', 'Employment details added.');
    }

    public function updateEmployment(SaveCandidateEmploymentRequest $request, int $employment): RedirectResponse|JsonResponse
    {
        $this->profiles->updateEmployment($request->user(), $employment, $request->validated());

        return $this->profileSaved($request, 'employment', 'Employment details updated.');
    }

    public function destroyEmployment(Request $request, int $employment): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $this->profiles->deleteEmployment($request->user(), $employment);

        return $this->profileSaved($request, 'employment', 'Employment details removed.');
    }

    public function storeEducation(SaveCandidateEducationRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->addEducation($request->user(), $request->validated());

        return $this->profileSaved($request, 'education', 'Education details added.');
    }

    public function updateEducation(SaveCandidateEducationRequest $request, int $education): RedirectResponse|JsonResponse
    {
        $this->profiles->updateEducation($request->user(), $education, $request->validated());

        return $this->profileSaved($request, 'education', 'Education details updated.');
    }

    public function destroyEducation(Request $request, int $education): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $this->profiles->deleteEducation($request->user(), $education);

        return $this->profileSaved($request, 'education', 'Education details removed.');
    }

    private function profileSaved(Request $request, string $section, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->applicantProfileChecklistResponse($request->user(), $message);
        }

        return redirect()->to(route('profile.edit').'#'.$section)->with('success', $message);
    }
}
