<?php

namespace App\Services;

use App\Models\ApplicantProfile;
use App\Models\CandidateProfileEducation;
use App\Models\CandidateProfileEmployment;
use App\Models\CandidateSelfIdentification;
use App\Models\CandidateJobPreference;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ApplicantProfileService
{
    public function forUser(User $user): ApplicantProfile
    {
        return $user->applicantProfile()->with([
            'employments', 'educations', 'skills', 'links', 'selfIdentification',
        ])->first() ?? new ApplicantProfile(['user_id' => $user->id]);
    }

    public function hasAvailableResume(ApplicantProfile $profile): bool
    {
        $path = trim((string) $profile->resume_path);

        return $this->pathIsInsideDirectory($path, 'candidate-resumes')
            && Storage::disk('local')->exists($path);
    }

    public function availableIdentificationDocument(User $user): ?UserIdentificationDocument
    {
        $document = $user->identificationDocument()->first();

        if (! $document) {
            return null;
        }

        $directory = 'user-identification-documents/'.$user->id;
        $path = trim((string) $document->file_path);
        $disk = config('filesystems.application_documents_disk', 'local');

        return $this->pathIsInsideDirectory($path, $directory) && Storage::disk($disk)->exists($path)
            ? $document
            : null;
    }

    /** @param array<string, mixed> $data */
    public function addEmployment(User $user, array $data): CandidateProfileEmployment
    {
        return DB::transaction(function () use ($user, $data): CandidateProfileEmployment {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrCreate(['user_id' => $user->id]);

            return $profile->employments()->create($data);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateEmployment(User $user, int $employmentId, array $data): CandidateProfileEmployment
    {
        return DB::transaction(function () use ($user, $employmentId, $data): CandidateProfileEmployment {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->where('user_id', $user->id)->firstOrFail();
            $employment = $profile->employments()->whereKey($employmentId)->firstOrFail();
            $employment->update($data);

            return $employment->refresh();
        });
    }

    public function deleteEmployment(User $user, int $employmentId): void
    {
        DB::transaction(function () use ($user, $employmentId): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->where('user_id', $user->id)->firstOrFail();
            $profile->employments()->whereKey($employmentId)->firstOrFail()->delete();
        });
    }

    /** @param array<string, mixed> $data */
    public function addEducation(User $user, array $data): CandidateProfileEducation
    {
        return DB::transaction(function () use ($user, $data): CandidateProfileEducation {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrCreate(['user_id' => $user->id]);

            return $profile->educations()->create($data);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateEducation(User $user, int $educationId, array $data): CandidateProfileEducation
    {
        return DB::transaction(function () use ($user, $educationId, $data): CandidateProfileEducation {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->where('user_id', $user->id)->firstOrFail();
            $education = $profile->educations()->whereKey($educationId)->firstOrFail();
            $education->update($data);

            return $education->refresh();
        });
    }

    public function deleteEducation(User $user, int $educationId): void
    {
        DB::transaction(function () use ($user, $educationId): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->where('user_id', $user->id)->firstOrFail();
            $profile->educations()->whereKey($educationId)->firstOrFail()->delete();
        });
    }

    /** @param array<string, mixed> $data */
    public function updateDetails(User $user, array $data): ApplicantProfile
    {
        return DB::transaction(function () use ($user, $data): ApplicantProfile {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrNew(['user_id' => $user->id]);
            $profile->fill(Arr::only($data, ['professional_headline', 'about']));
            $profile->save();

            if (array_key_exists('links', $data)) {
                foreach (['linkedin', 'github', 'website'] as $platform) {
                    $url = $data['links'][$platform] ?? null;
                    if (filled($url)) {
                        $profile->links()->updateOrCreate(['platform' => $platform], ['url' => $url]);
                    } else {
                        $profile->links()->where('platform', $platform)->delete();
                    }
                }
            }

            return $profile->load(['employments', 'educations', 'skills', 'links', 'selfIdentification']);
        });
    }

    /**
     * Save only checklist details supplied by the job application form. Existing
     * profile rows are retained, so submitting another application cannot
     * duplicate the applicant's employment or education history.
     *
     * @param array<string, mixed> $data
     */
    public function syncChecklistDetails(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrCreate(['user_id' => $user->id]);

            $contact = $data['profile_contact'] ?? [];
            $profileFields = Arr::only($contact, ['professional_headline', 'about']);
            if ($profileFields !== []) {
                $profile->fill($profileFields)->save();
            }

            $identityFields = Arr::only($contact, ['gender_identity', 'disability_status', 'disability_details']);
            if (($identityFields['disability_status'] ?? null) === 'no') {
                $identityFields['disability_details'] = null;
            }
            if ($identityFields !== []) {
                $profile->selfIdentification()->updateOrCreate([], $identityFields);
            }

            $employmentFields = Arr::only($data['profile_employment'] ?? [], [
                'company', 'title', 'location', 'employment_type', 'start_date', 'end_date', 'is_current', 'description',
            ]);
            if ($employmentFields !== [] && ! $profile->employments()->exists()) {
                $profile->employments()->create($employmentFields);
            }

            if (is_array($data['profile_skills'] ?? null)) {
                $existingSkills = $profile->skills()->pluck('name')->map(fn (string $name): string => mb_strtolower($name))->all();
                foreach ($data['profile_skills'] as $skill) {
                    $normalised = mb_strtolower(trim((string) $skill));
                    if ($normalised !== '' && ! in_array($normalised, $existingSkills, true)) {
                        $profile->skills()->create(['name' => trim((string) $skill)]);
                        $existingSkills[] = $normalised;
                    }
                }
            }

            $educationFields = Arr::only($data['profile_education'] ?? [], [
                'institution', 'qualification', 'field_of_study', 'start_date', 'end_date', 'description',
            ]);
            if ($educationFields !== [] && ! $profile->educations()->exists()) {
                $profile->educations()->create($educationFields);
            }

            foreach (['linkedin', 'github', 'website'] as $platform) {
                $url = $data['profile_links'][$platform] ?? null;
                if (filled($url)) {
                    $profile->links()->updateOrCreate(['platform' => $platform], ['url' => $url]);
                }
            }

            $preferenceFields = Arr::only($data['profile_preferences'] ?? [], [
                'preferred_roles', 'preferred_locations', 'preferred_job_type', 'preferred_workplace', 'salary_min', 'salary_max',
            ]);
            if ($preferenceFields !== []) {
                $preferences = CandidateJobPreference::query()->firstOrNew(['user_id' => $user->id]);
                $preferences->fill($preferenceFields)->save();
            }
        });
    }

    /** @param list<string> $skills */
    public function updateSkills(User $user, array $skills): ApplicantProfile
    {
        return DB::transaction(function () use ($user, $skills): ApplicantProfile {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrNew(['user_id' => $user->id]);
            $profile->save();

            $normalised = collect($skills)
                ->map(fn (string $skill): string => trim($skill))
                ->filter()
                ->unique(fn (string $skill): string => Str::lower($skill))
                ->values();

            if ($normalised->isEmpty()) {
                $profile->skills()->delete();
            } else {
                $profile->skills()->whereNotIn('name', $normalised)->delete();
                foreach ($normalised as $name) {
                    $profile->skills()->firstOrCreate(['name' => $name]);
                }
            }

            return $profile->load(['employments', 'educations', 'skills', 'links', 'selfIdentification']);
        });
    }

    public function uploadResume(User $user, UploadedFile $resume): ApplicantProfile
    {
        $newPath = $resume->store('candidate-resumes', 'local');
        abort_if($newPath === false, 422, 'The résumé could not be saved. Please try again.');

        try {
            return DB::transaction(function () use ($user, $resume, $newPath): ApplicantProfile {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $profile = ApplicantProfile::query()->firstOrNew(['user_id' => $user->id]);
                $oldPath = $profile->resume_path;
                $profile->fill([
                    'resume_path' => $newPath,
                    'resume_original_name' => Str::limit(basename($resume->getClientOriginalName()), 255, ''),
                    'resume_mime_type' => $resume->getMimeType(),
                    'resume_size' => $resume->getSize(),
                ])->save();

                if ($oldPath) {
                    DB::afterCommit(fn () => Storage::disk('local')->delete($oldPath));
                }

                return $profile->load(['employments', 'educations', 'skills', 'links', 'selfIdentification']);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }
    }

    public function deleteResume(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $oldPath = $profile->resume_path;
            $profile->forceFill([
                'resume_path' => null,
                'resume_original_name' => null,
                'resume_mime_type' => null,
                'resume_size' => null,
            ])->save();

            if ($oldPath) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($oldPath));
            }
        });
    }

    private function pathIsInsideDirectory(string $path, string $directory): bool
    {
        return str_starts_with($path, $directory.'/')
            && dirname($path) === $directory
            && ! str_contains($path, '\\');
    }

    /** @param array<string, mixed> $data */
    public function updateContactIdentification(User $user, array $data): CandidateSelfIdentification
    {
        return DB::transaction(function () use ($user, $data): CandidateSelfIdentification {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = ApplicantProfile::query()->firstOrNew(['user_id' => $user->id]);
            $profile->save();
            $identification = $profile->selfIdentification()->firstOrNew();
            $genderIdentity = array_key_exists('gender_identity', $data)
                ? $data['gender_identity']
                : $identification->gender_identity;
            $disabilityStatus = array_key_exists('disability_status', $data)
                ? $data['disability_status']
                : $identification->disability_status;
            $disabilityDetails = $disabilityStatus === 'yes'
                ? (array_key_exists('disability_details', $data) ? $data['disability_details'] : $identification->disability_details)
                : null;

            $identification->fill([
                'gender_identity' => $genderIdentity,
                'disability_status' => $disabilityStatus,
                'disability_details' => $disabilityDetails,
            ]);

            $identification->save();

            return $identification;
        });
    }
}
