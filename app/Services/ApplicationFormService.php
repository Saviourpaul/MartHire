<?php

namespace App\Services;

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationFormService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function submit(Job $job, User $applicant, array $data): ApplicationForm
    {
        if ($job->applications()->where('user_id', $applicant->id)->exists()) {
            throw ValidationException::withMessages([
                'job' => 'You have already applied for this job.',
            ]);
        }

        $storedFiles = [];
        $oldProfileImagePath = $applicant->profile_image_path;
        $newProfileImagePath = null;

        try {
            $application = DB::transaction(function () use ($job, $applicant, $data, &$storedFiles, &$newProfileImagePath): ApplicationForm {
                $profileImagePath = $applicant->profile_image_path;

                if (($data['profile_image'] ?? null) instanceof UploadedFile) {
                    $profileImagePath = $data['profile_image']->store('profile-images', 'public');
                    $storedFiles[] = ['disk' => 'public', 'path' => $profileImagePath];
                    $newProfileImagePath = $profileImagePath;
                }

                $application = ApplicationForm::create([
                    ...Arr::only($data, [
                        'first_name',
                        'middle_name',
                        'last_name',
                        'email',
                        'phone',
                        'nationality',
                        'date_of_birth',
                        'gender',
                        'marital_status',
                        'state_of_origin',
                        'local_government_area',
                        'address',
                        'zipcode',
                    ]),
                    'job_id' => $job->id,
                    'user_id' => $applicant->id,
                    'reference' => $this->generateReference(),
                    'status' => CandidatePipelineStage::Submitted,
                    'submitted_at' => now(),
                    'profile_image_path' => $profileImagePath,
                ]);

                $this->createDocument(
                    $application,
                    $data['nin_document'],
                    ApplicationDocumentType::Nin,
                    ApplicationDocumentType::Nin->label(),
                    $data['nin_number'],
                    $storedFiles
                );

                $this->createDocument(
                    $application,
                    $data['bvn_document'],
                    ApplicationDocumentType::Bvn,
                    ApplicationDocumentType::Bvn->label(),
                    $data['bvn_number'],
                    $storedFiles
                );

                foreach ($data['education_documents'] as $document) {
                    $this->createDocument(
                        $application,
                        $document['file'],
                        ApplicationDocumentType::Education,
                        Str::headline($document['type']),
                        null,
                        $storedFiles
                    );
                }

                $application->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => CandidatePipelineStage::Submitted,
                    'changed_by' => $applicant->id,
                    'remarks' => 'Application submitted.',
                    'created_at' => now(),
                ]);

                $this->syncApplicantProfile($applicant, $data, $profileImagePath);

                return $application->load(['job', 'documents']);
            });
        } catch (Throwable $throwable) {
            $this->deleteStoredFiles($storedFiles);

            throw $throwable;
        }

        if ($newProfileImagePath && $oldProfileImagePath && $oldProfileImagePath !== $newProfileImagePath) {
            Storage::disk('public')->delete($oldProfileImagePath);
        }

        return $application;
    }

    public function moveCandidate(ApplicationForm $application, User $employer, CandidatePipelineStage $stage, ?string $remarks = null): ApplicationForm
    {
        return DB::transaction(function () use ($application, $employer, $stage, $remarks): ApplicationForm {
            $application = ApplicationForm::query()->lockForUpdate()->findOrFail($application->id);
            $currentStage = $application->status;

            if (! $currentStage->canTransitionTo($stage)) {
                throw ValidationException::withMessages([
                    'stage' => "A candidate cannot move from {$currentStage->label()} to {$stage->label()}.",
                ]);
            }

            $movedAt = now();
            $application->update(['status' => $stage, 'reviewed_by' => $employer->id, 'reviewed_at' => $movedAt, 'employer_remarks' => $remarks]);
            $application->statusHistories()->create(['from_status' => $currentStage, 'to_status' => $stage, 'changed_by' => $employer->id, 'remarks' => $remarks, 'created_at' => $movedAt]);

            return $application->fresh(['job', 'applicant', 'documents', 'statusHistories.changedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncApplicantProfile(User $applicant, array $data, ?string $profileImagePath): void
    {
        $profileData = Arr::only($data, [
            'first_name',
            'last_name',
            'date_of_birth',
            'phone',
            'address',
            'nationality',
            'state_of_origin',
            'local_government_area',
            'zipcode',
        ]);

        if ($profileImagePath) {
            $profileData['profile_image_path'] = $profileImagePath;
        }

        $applicant->fill($profileData)->save();
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    private function createDocument(
        ApplicationForm $application,
        UploadedFile $file,
        ApplicationDocumentType $type,
        string $name,
        ?string $number,
        array &$storedFiles
    ): ApplicationDocument {
        $path = $file->store('application-documents/'.$application->id, 'local');
        $storedFiles[] = ['disk' => 'local', 'path' => $path];

        return $application->documents()->create([
            'document_type' => $type,
            'document_name' => $name,
            'document_number' => $number,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    private function deleteStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as $file) {
            Storage::disk($file['disk'])->delete($file['path']);
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'APP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (ApplicationForm::where('reference', $reference)->exists());

        return $reference;
    }
}
