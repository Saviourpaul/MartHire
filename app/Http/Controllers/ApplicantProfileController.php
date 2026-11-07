<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApplicantProfileChecklist;
use App\Http\Requests\SaveCandidateEducationRequest;
use App\Http\Requests\SaveCandidateEmploymentRequest;
use App\Http\Requests\StoreApplicantResumeRequest;
use App\Http\Requests\UpdateApplicantProfileDetailsRequest;
use App\Http\Requests\UpdateCandidateSkillsRequest;
use App\Services\ApplicantAccountActivityService;
use App\Services\ApplicantProfileService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantProfileController extends Controller
{
    use RespondsWithApplicantProfileChecklist;

    public function __construct(
        private ApplicantProfileService $profiles,
        private ApplicantAccountActivityService $activity,
    ) {}

    public function updateDetails(UpdateApplicantProfileDetailsRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $profile = $this->profiles->forUser($user);
        $changed = collect(['professional_headline', 'about'])
            ->contains(fn (string $field): bool => $profile->getAttribute($field) !== ($data[$field] ?? null));
        $links = $profile->links->keyBy('platform');
        foreach (['linkedin', 'github', 'website'] as $platform) {
            $existing = $links->get($platform)?->url;
            $requested = $data['links'][$platform] ?? null;
            if (filled($requested) ? $existing !== $requested : $links->has($platform)) {
                $changed = true;
                break;
            }
        }

        if ($changed) {
            $this->profiles->updateDetails($user, $data);
        }

        return $this->profileSaved($request, 'online-profiles', 'Profile details saved.', $changed ? ['online-profiles'] : []);
    }

    public function updateSkills(UpdateCandidateSkillsRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $existing = $this->profiles->forUser($user)->skills->pluck('name')
            ->map(fn (string $skill): string => mb_strtolower(trim($skill)))->unique()->sort()->values()->all();
        $requested = collect($request->validated('skills'))
            ->map(fn (string $skill): string => trim($skill))->filter()
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->map(fn (string $skill): string => mb_strtolower($skill))->sort()->values()->all();
        $changed = $existing !== $requested;

        if ($changed) {
            $this->profiles->updateSkills($user, $request->validated('skills'));
        }

        return $this->profileSaved($request, 'skills', 'Skills saved.', $changed ? ['skills'] : []);
    }

    public function storeResume(StoreApplicantResumeRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->uploadResume($request->user(), $request->file('resume'));

        return $this->profileSaved($request, 'resume', 'Résumé uploaded.', ['resume']);
    }

    public function removeResume(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $hasResume = filled($this->profiles->forUser($request->user())->resume_path);
        $this->profiles->deleteResume($request->user());

        return $this->profileSaved($request, 'resume', 'Résumé removed.', $hasResume ? ['resume'] : []);
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

        return $this->profileSaved($request, 'employment', 'Employment details added.', ['employment']);
    }

    public function updateEmployment(SaveCandidateEmploymentRequest $request, int $employment): RedirectResponse|JsonResponse
    {
        $record = $this->profiles->forUser($request->user())->employments->firstWhere('id', $employment);
        abort_unless($record, 404);
        $data = $request->validated();
        $changed = $this->hasChangedAttributes($record, $data);
        if ($changed) {
            $this->profiles->updateEmployment($request->user(), $employment, $data);
        }

        return $this->profileSaved($request, 'employment', 'Employment details updated.', $changed ? ['employment'] : []);
    }

    public function destroyEmployment(Request $request, int $employment): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $this->profiles->deleteEmployment($request->user(), $employment);

        return $this->profileSaved($request, 'employment', 'Employment details removed.', ['employment']);
    }

    public function storeEducation(SaveCandidateEducationRequest $request): RedirectResponse|JsonResponse
    {
        $this->profiles->addEducation($request->user(), $request->validated());

        return $this->profileSaved($request, 'education', 'Education details added.', ['education']);
    }

    public function updateEducation(SaveCandidateEducationRequest $request, int $education): RedirectResponse|JsonResponse
    {
        $record = $this->profiles->forUser($request->user())->educations->firstWhere('id', $education);
        abort_unless($record, 404);
        $data = $request->validated();
        $changed = $this->hasChangedAttributes($record, $data);
        if ($changed) {
            $this->profiles->updateEducation($request->user(), $education, $data);
        }

        return $this->profileSaved($request, 'education', 'Education details updated.', $changed ? ['education'] : []);
    }

    public function destroyEducation(Request $request, int $education): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->isApplicant(), 403);
        $this->profiles->deleteEducation($request->user(), $education);

        return $this->profileSaved($request, 'education', 'Education details removed.', ['education']);
    }

    /** @param array<string, mixed> $values */
    private function hasChangedAttributes(Model $model, array $values): bool
    {
        foreach ($values as $field => $value) {
            $current = $model->getAttribute($field);
            if ($current instanceof DateTimeInterface) {
                $current = $current->format('Y-m-d');
            }
            if ($field === 'is_current') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($value === '') {
                $value = null;
            }
            if ($current !== $value) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $changedFields */
    private function profileSaved(Request $request, string $section, string $message, array $changedFields = []): RedirectResponse|JsonResponse
    {
        if ($changedFields !== []) {
            $this->activity->profileUpdated($request->user(), $changedFields);
        }

        if ($request->expectsJson()) {
            return $this->applicantProfileChecklistResponse($request->user(), $message);
        }

        return redirect()->to(route('profile.edit').'#'.$section)->with('success', $message);
    }
}
