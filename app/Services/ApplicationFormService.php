<?php

namespace App\Services;

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Enums\JobScreeningQuestionType;
use App\Models\ApplicantProfile;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobScreeningQuestion;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ApplicationFormService
{
    private const RESUME_TYPES = ['pdf', 'doc', 'docx'];

    private const DOCUMENT_MAX_KB = 5120;

    public function __construct(
        private PipelineTransitionService $pipelineTransitions,
        private WorkflowNotificationService $notifications,
        private ApplicantProfileService $profiles
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function submit(Job $job, User $applicant, array $data): ApplicationForm
    {
        $storedFiles = [];
        $oldProfileImagePath = $applicant->profile_image_path;
        $newProfileImagePath = null;

        try {
            $application = DB::transaction(function () use ($job, $applicant, $data, &$storedFiles, &$newProfileImagePath): ApplicationForm {
                $currentJob = Job::query()->sharedLock()->findOrFail($job->id);
                $currentJob->load('screeningQuestions');

                if (! $currentJob->isAcceptingApplications()) {
                    throw ValidationException::withMessages(['job' => 'This job is not accepting applications.']);
                }

                $this->validateScreeningAnswers($currentJob, $data['screening_answers'] ?? []);

                // Shared locks let applicants proceed in parallel, while a
                // concurrent job closure waits until this submission commits.
                // The unique (job_id, user_id) index handles duplicate races.
                $profileImagePath = $applicant->profile_image_path;

                if (($data['profile_image'] ?? null) instanceof UploadedFile) {
                    $profileImagePath = $data['profile_image']->store('profile-images', 'public');
                    $storedFiles[] = ['disk' => 'public', 'path' => $profileImagePath];
                    $newProfileImagePath = $profileImagePath;
                }

                $applicationProfileImagePath = $this->snapshotProfileImage($profileImagePath, $storedFiles);

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
                    'profile_image_path' => $applicationProfileImagePath,
                ]);

                if (($data['identification_document'] ?? null) instanceof UploadedFile) {
                    $identificationType = ApplicationDocumentType::from($data['identification_type']);
                    $this->createDocument(
                        $application,
                        $data['identification_document'],
                        $identificationType,
                        $identificationType->label(),
                        $storedFiles,
                        $data['identity_issuing_country_code'] ?? null,
                    );
                } else {
                    $identification = $this->profiles->availableIdentificationDocument($applicant);
                    if (! $identification) {
                        throw ValidationException::withMessages([
                            'identification_document' => 'Upload a government-issued identification document to apply.',
                        ]);
                    }
                    $this->copyIdentityDocumentToApplication(
                        $application,
                        $identification,
                        $data['identity_issuing_country_code'] ?? $identification->issuing_country_code,
                        $storedFiles,
                    );
                }

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

                $this->storeScreeningAnswerSnapshots($application, $currentJob, $data['screening_answers'] ?? []);

                $application->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => CandidatePipelineStage::Submitted,
                    'changed_by' => $applicant->id,
                    'remarks' => 'Application submitted.',
                    'created_at' => now(),
                ]);

                $this->syncApplicantProfile($applicant, $data, $profileImagePath, $storedFiles);
                $this->notifications->applicationSubmitted($application);

                return $application->load(['job', 'documents', 'screeningAnswers']);
            });
        } catch (Throwable $throwable) {
            $this->deleteStoredFiles($storedFiles);

            if ($throwable instanceof QueryException && $this->isDuplicateApplicationConflict($throwable)) {
                throw ValidationException::withMessages([
                    'job' => 'You have already applied for this job.',
                ]);
            }

            throw $throwable;
        }

        if ($newProfileImagePath && $oldProfileImagePath && $oldProfileImagePath !== $newProfileImagePath) {
            Storage::disk('public')->delete($oldProfileImagePath);
        }

        return $application;
    }

    private function isDuplicateApplicationConflict(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'application_forms')
            && (str_contains($message, 'job_id_user_id_unique')
                || str_contains($message, 'unique constraint failed: application_forms.job_id, application_forms.user_id'));
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

        if (array_intersect(array_keys($data), [
            'profile_contact', 'profile_employment', 'profile_education', 'profile_skills', 'profile_links', 'profile_preferences',
        ]) !== []) {
            $this->profiles->syncChecklistDetails($applicant, $data);
        }
    }

    /**
     * Copy a private saved résumé into this application's private document
     * snapshot. Later profile edits must not change a submitted application.
     *
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
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
    /**
     * Copy a previously saved private identity file into the application so a
     * future profile replacement cannot change the evidence that was reviewed.
     *
     * @param array<int, array{disk: string, path: string}> $storedFiles
     */
    private function copyIdentityDocumentToApplication(
        ApplicationForm $application,
        UserIdentificationDocument $identification,
        ?string $issuingCountryCode,
        array &$storedFiles,
    ): ApplicationDocument {
        $diskName = config('filesystems.application_documents_disk', 'local');
        $extension = strtolower(pathinfo($identification->original_name, PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)
            || ! Storage::disk($diskName)->exists($identification->file_path)) {
            throw ValidationException::withMessages([
                'identification_document' => 'Your saved identification document is no longer available or supported. Upload it again to continue.',
            ]);
        }

        if (Storage::disk($diskName)->size($identification->file_path) > self::DOCUMENT_MAX_KB * 1024) {
            throw ValidationException::withMessages([
                'identification_document' => 'Your saved identification document is larger than the 5 MB application limit. Upload a smaller file to continue.',
            ]);
        }

        $destination = 'application-documents/'.$application->id.'/identity-'.Str::uuid().'.'.$extension;
        $stream = Storage::disk($diskName)->readStream($identification->file_path);
        if (! is_resource($stream)) {
            throw ValidationException::withMessages([
                'identification_document' => 'Your saved identification document could not be read. Upload it again to continue.',
            ]);
        }

        try {
            $written = Storage::disk($diskName)->writeStream($destination, $stream);
        } finally {
            fclose($stream);
        }

        if (! $written) {
            throw ValidationException::withMessages([
                'identification_document' => 'Your saved identification document could not be copied. Upload it again to continue.',
            ]);
        }

        $storedFiles[] = ['disk' => $diskName, 'path' => $destination];

        return $application->documents()->create([
            'user_identification_document_id' => $identification->id,
            'document_type' => $identification->document_type,
            'document_name' => $identification->document_type->label(),
            'file_path' => $destination,
            'original_name' => $identification->original_name,
            'mime_type' => $identification->mime_type,
            'size' => Storage::disk($diskName)->size($destination),
            'issuing_country_code' => $issuingCountryCode,
        ]);
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    /**
     * Keep the image attached to this submission stable when the applicant
     * later replaces their account photo.
     *
     * @param  array<int, array{disk: string, path: string}>  $storedFiles
     */
    private function snapshotProfileImage(?string $profileImagePath, array &$storedFiles): string
    {
        if (! filled($profileImagePath)) {
            throw ValidationException::withMessages([
                'profile_image' => 'Upload a valid profile photo to apply.',
            ]);
        }

        $path = ltrim($profileImagePath, '/');
        foreach (['storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        $publicPath = app(PublicMediaUrl::class)->publicPath($path);
        $extension = strtolower(pathinfo((string) $publicPath, PATHINFO_EXTENSION));
        if ($publicPath === null || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            || ! Storage::disk('public')->exists($publicPath)) {
            throw ValidationException::withMessages([
                'profile_image' => 'Upload a valid profile photo to apply.',
            ]);
        }

        $snapshotPath = 'profile-images/application-'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->copy($publicPath, $snapshotPath)) {
            throw new RuntimeException('Unable to preserve the applicant photo for this application.');
        }

        $storedFiles[] = ['disk' => 'public', 'path' => $snapshotPath];

        return $snapshotPath;
    }

    private function createDocument(
        ApplicationForm $application,
        UploadedFile $file,
        ApplicationDocumentType $type,
        string $name,
        array &$storedFiles,
        ?string $issuingCountryCode = null,
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
            'issuing_country_code' => $issuingCountryCode,
        ]);
    }

    /** @param array<string, mixed> $answers */
    private function validateScreeningAnswers(Job $job, array $answers): void
    {
        $questions = $job->screeningQuestions;
        $knownKeys = $questions->pluck('question_key')->all();
        foreach (array_keys($answers) as $key) {
            if (! in_array((string) $key, $knownKeys, true)) {
                throw ValidationException::withMessages([
                    'screening_answers' => 'One of the screening questions has changed. Refresh the application and try again.',
                ]);
            }
        }

        /** @var JobScreeningQuestion $question */
        foreach ($questions as $question) {
            $key = $question->question_key;
            $answer = $answers[$key] ?? null;
            if ($answer === null || $answer === '' || $answer === []) {
                throw ValidationException::withMessages(['screening_answers.'.$key => 'Answer this required screening question.']);
            }

            $options = $question->options ?? [];
            $valid = match ($question->type) {
                JobScreeningQuestionType::ShortText => is_string($answer) && mb_strlen($answer) <= 250,
                JobScreeningQuestionType::LongText => is_string($answer) && mb_strlen($answer) <= 5000,
                JobScreeningQuestionType::SingleSelect => is_string($answer) && in_array($answer, $options, true),
                JobScreeningQuestionType::MultiSelect => is_array($answer)
                    && count($answer) <= count($options)
                    && count($answer) === count(array_unique($answer))
                    && collect($answer)->every(fn ($option): bool => is_string($option) && in_array($option, $options, true)),
                JobScreeningQuestionType::YesNo => in_array((string) $answer, ['yes', 'no', '1', '0'], true),
            };

            if (! $valid) {
                throw ValidationException::withMessages(['screening_answers.'.$key => 'Provide an answer that matches this question.']);
            }
        }
    }

    /** @param array<string, mixed> $answers */
    private function storeScreeningAnswerSnapshots(ApplicationForm $application, Job $job, array $answers): void
    {
        /** @var JobScreeningQuestion $question */
        foreach ($job->screeningQuestions as $question) {
            $answer = $answers[$question->question_key] ?? null;
            if ($question->type === JobScreeningQuestionType::YesNo && $answer !== null) {
                $answer = in_array((string) $answer, ['yes', '1'], true) ? 'Yes' : 'No';
            }

            $application->screeningAnswers()->create([
                'question_key' => $question->question_key,
                'type' => $question->type->value,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'is_required' => true,
                'position' => $question->position,
                'answer' => $answer,
            ]);
        }
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
