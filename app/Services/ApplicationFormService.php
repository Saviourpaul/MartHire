<?php

namespace App\Services;

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\ApplicantProfile;
use App\Models\Job;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationFormService
{
    private const RESUME_TYPES = ['pdf', 'doc', 'docx'];

    private const DOCUMENT_MAX_KB = 5120;

    public function __construct(
        private PipelineTransitionService $pipelineTransitions,
        private WorkflowNotificationService $notifications,
        private ApplicantProfileService $profiles,
        private ApplicantProfileChecklistService $profileChecklist
    ) {}

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
                $currentJob = Job::query()->lockForUpdate()->findOrFail($job->id);

                if (! $currentJob->isAcceptingApplications()) {
                    throw ValidationException::withMessages(['job' => 'This job is not accepting applications.']);
                }

                // The job lock serializes submissions. Repeat this check here
                // so concurrent attempts cannot race into the unique index.
                if ($currentJob->applications()->where('user_id', $applicant->id)->exists()) {
                    throw ValidationException::withMessages(['job' => 'You have already applied for this job.']);
                }

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
                        'phone_country',
                        'phone_dial_code',
                        'phone_national_number',
                        'date_of_birth',
                        'gender',
                        'marital_status',
                        'country_code',
                        'country',
                        'state',
                        'city',
                        'local_government_area',
                        'address',
                    ]),
                    'job_id' => $job->id,
                    'organization_id' => $job->organization_id,
                    'user_id' => $applicant->id,
                    'reference' => $this->generateReference(),
                    'status' => CandidatePipelineStage::Submitted,
                    'submitted_at' => now(),
                    'profile_image_path' => $profileImagePath,
                ]);

                if (($data['identification_document'] ?? null) instanceof UploadedFile) {
                    $identificationType = ApplicationDocumentType::from($data['identification_type']);
                    $identification = $this->storeIdentificationDocument(
                        $applicant,
                        $identificationType,
                        $data['identification_document'],
                        $storedFiles
                    );
                } else {
                    $identification = $this->profiles->availableIdentificationDocument($applicant);

                    if (! $identification) {
                        throw ValidationException::withMessages([
                            'identification_document' => 'Your saved identification document is no longer available. Upload it again to continue.',
                        ]);
                    }
                }

                $this->createIdentityDocument(
                    $application,
                    $identification
                );

                if (($data['resume'] ?? null) instanceof UploadedFile) {
                    $this->createDocument(
                        $application,
                        $data['resume'],
                        ApplicationDocumentType::Resume,
                        $data['resume']->getClientOriginalName(),
                        $storedFiles
                    );
                } else {
                    $profile = $this->profiles->forUser($applicant);
                    $this->createDocumentFromProfileResume($application, $profile, $storedFiles);
                }

                foreach ($data['education_documents'] as $document) {
                    $this->createDocument(
                        $application,
                        $document['file'],
                        ApplicationDocumentType::Education,
                        $document['name'],
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

                $this->syncApplicantProfile($applicant, $data, $profileImagePath, $storedFiles);
                $this->notifications->applicationSubmitted($application);

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

    public function moveCandidate(ApplicationForm $application, User $employer, CandidatePipelineStage $stage, ?string $remarks = null, bool $selectionConfirmed = false): ApplicationForm
    {
        return $this->pipelineTransitions->moveCandidate($application, $employer, $stage, $remarks, $selectionConfirmed);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncApplicantProfile(User $applicant, array $data, ?string $profileImagePath, array &$storedFiles): void
    {
        $profileData = Arr::only($data, [
            'first_name',
            'last_name',
            'date_of_birth',
            'phone',
            'phone_country',
            'phone_dial_code',
            'phone_national_number',
            'address',
            'country_code',
            'country',
            'state',
            'city',
            'local_government_area',
        ]);

        if ($profileImagePath) {
            $profileData['profile_image_path'] = $profileImagePath;
        }

        if (! empty($data['country_code'])) {
            $profileData['location_confirmed_at'] = now();
        }

        $applicant->fill($profileData)->save();

        if (($data['resume'] ?? null) instanceof UploadedFile) {
            $profile = $this->profiles->uploadResume($applicant, $data['resume']);
            $storedFiles[] = ['disk' => 'local', 'path' => $profile->resume_path];
        }

        $profileChecklistData = $data;
        $profileChecklistData['profile_contact'] = [
            ...($profileChecklistData['profile_contact'] ?? []),
            'gender_identity' => $data['gender'],
        ];
        $this->profiles->syncChecklistDetails($applicant, $profileChecklistData);

        $checklist = $this->profileChecklist->forUser($applicant->fresh());
        if ($checklist['completed'] < $checklist['total']) {
            throw ValidationException::withMessages([
                'profile' => 'Complete the required profile details before submitting your application.',
            ]);
        }
    }

    /**
     * Copy a private saved résumé into this application's private document
     * snapshot. Later profile edits must not change a submitted application.
     *
     * @param array<int, array{disk: string, path: string}> $storedFiles
     */
    private function createDocumentFromProfileResume(ApplicationForm $application, ApplicantProfile $profile, array &$storedFiles): ApplicationDocument
    {
        $sourcePath = trim((string) $profile->resume_path);
        $sourceDisk = Storage::disk('local');
        $extension = strtolower(pathinfo((string) $profile->resume_original_name, PATHINFO_EXTENSION));

        if (! $this->profiles->hasAvailableResume($profile)
            || ! in_array($extension, self::RESUME_TYPES, true)) {
            throw ValidationException::withMessages([
                'resume' => 'Your saved résumé is no longer available or supported. Upload it again to continue.',
            ]);
        }

        if ($sourceDisk->size($sourcePath) > self::DOCUMENT_MAX_KB * 1024) {
            throw ValidationException::withMessages(['resume' => 'Your saved résumé is larger than the 5 MB application limit. Upload a smaller file to continue.']);
        }

        $diskName = config('filesystems.application_documents_disk', 'local');
        $destination = 'application-documents/'.$application->id.'/resume-'.Str::uuid().'.'.$extension;
        $stream = $sourceDisk->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw ValidationException::withMessages(['resume' => 'Your saved résumé could not be read. Upload it again to continue.']);
        }

        try {
            $written = Storage::disk($diskName)->writeStream($destination, $stream);
        } finally {
            fclose($stream);
        }

        if (! $written) {
            throw ValidationException::withMessages(['resume' => 'Your saved résumé could not be copied. Upload it again to continue.']);
        }

        $storedFiles[] = ['disk' => $diskName, 'path' => $destination];

        return $application->documents()->create([
            'document_type' => ApplicationDocumentType::Resume,
            'document_name' => $profile->resume_original_name,
            'file_path' => $destination,
            'original_name' => $profile->resume_original_name,
            'mime_type' => $profile->resume_mime_type ?: Storage::disk($diskName)->mimeType($destination),
            'size' => Storage::disk($diskName)->size($destination),
        ]);
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    private function storeIdentificationDocument(
        User $applicant,
        ApplicationDocumentType $type,
        UploadedFile $file,
        array &$storedFiles
    ): UserIdentificationDocument {
        if (! $type->isIdentityType()) {
            throw ValidationException::withMessages([
                'identification_type' => 'Select a supported identification method.',
            ]);
        }

        $disk = config('filesystems.application_documents_disk', 'local');
        $path = $file->store('user-identification-documents/'.$applicant->id, $disk);
        $storedFiles[] = ['disk' => $disk, 'path' => $path];

        // Application snapshots may still reference a previously selected profile document.
        return $applicant->identificationDocument()->updateOrCreate([], [
            'document_type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: null,
            'size' => $file->getSize(),
        ]);
    }

    private function createIdentityDocument(
        ApplicationForm $application,
        UserIdentificationDocument $identification
    ): ApplicationDocument {
        return $application->documents()->create([
            'user_identification_document_id' => $identification->id,
            'document_type' => $identification->document_type,
            'document_name' => $identification->document_type->label(),
            'file_path' => $identification->file_path,
            'original_name' => $identification->original_name,
            'mime_type' => $identification->mime_type,
            'size' => $identification->size,
        ]);
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    private function createDocument(
        ApplicationForm $application,
        UploadedFile $file,
        ApplicationDocumentType $type,
        string $name,
        array &$storedFiles
    ): ApplicationDocument {
        $disk = config('filesystems.application_documents_disk', 'local');
        $path = $file->store('application-documents/'.$application->id, $disk);
        $storedFiles[] = ['disk' => $disk, 'path' => $path];

        return $application->documents()->create([
            'document_type' => $type,
            'document_name' => $name,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: null,
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
