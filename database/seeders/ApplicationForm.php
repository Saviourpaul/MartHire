<?php

namespace Database\Seeders;

use App\Enums\ApplicationDocumentType;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm as ApplicationFormModel;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationForm extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employer = User::where('email', 'test@example.com')->first();

        if (! $employer) {
            return;
        }

        $jobs = Job::query()
            ->where('employer_id', $employer->id)
            ->active()
            ->take(5)
            ->get();

        if ($jobs->isEmpty()) {
            return;
        }

        $applicants = User::factory()
            ->count(8)
            ->completeApplicantProfile()
            ->create();

        foreach ($applicants as $index => $applicant) {
            $job = $jobs[$index % $jobs->count()];
            $status = match ($index % 5) {
                1 => CandidatePipelineStage::Shortlisted,
                2 => CandidatePipelineStage::Interview,
                3 => CandidatePipelineStage::Selected,
                4 => CandidatePipelineStage::Rejected,
                default => CandidatePipelineStage::Submitted,
            };

            $application = ApplicationFormModel::factory()
                ->for($job, 'job')
                ->for($applicant, 'applicant')
                ->state([
                    'email' => $applicant->email,
                    'first_name' => $applicant->first_name,
                    'last_name' => $applicant->last_name,
                    'date_of_birth' => $applicant->date_of_birth,
                    'phone' => $applicant->phone,
                    'nationality' => $applicant->nationality,
                    'state_of_origin' => $applicant->state_of_origin,
                    'local_government_area' => $applicant->local_government_area,
                    'address' => $applicant->address,
                    'zipcode' => $applicant->zipcode,
                    'profile_image_path' => $applicant->profile_image_path,
                    'status' => $status,
                    'reviewed_by' => $status === CandidatePipelineStage::Submitted ? null : $employer->id,
                    'reviewed_at' => $status === CandidatePipelineStage::Submitted ? null : now(),
                    'employer_remarks' => $status === CandidatePipelineStage::Submitted ? null : 'Seeded '.$status->label().' application.',
                ])
                ->create();

            $application->statusHistories()->create([
                'from_status' => null,
                'to_status' => CandidatePipelineStage::Submitted,
                'changed_by' => $applicant->id,
                'remarks' => 'Application submitted.',
                'created_at' => $application->submitted_at,
            ]);

            if ($status !== CandidatePipelineStage::Submitted) {
                $application->statusHistories()->create([
                    'from_status' => CandidatePipelineStage::Submitted,
                    'to_status' => $status,
                    'changed_by' => $employer->id,
                    'remarks' => $application->employer_remarks,
                    'created_at' => now(),
                ]);
            }

            $identificationType = fake()->randomElement(ApplicationDocumentType::identityTypes());
            $identification = $applicant->identificationDocument()->create([
                'document_type' => $identificationType,
                'file_path' => "user-identification-documents/{$applicant->id}/seeded-identity.pdf",
                'original_name' => 'seeded-identity.pdf',
                'mime_type' => 'application/pdf',
                'size' => 102400,
            ]);

            ApplicationDocument::factory()
                ->for($application, 'applicationForm')
                ->type($identificationType)
                ->create([
                    'user_identification_document_id' => $identification->id,
                    'file_path' => $identification->file_path,
                    'original_name' => $identification->original_name,
                    'mime_type' => $identification->mime_type,
                    'size' => $identification->size,
                ]);

            ApplicationDocument::factory()
                ->for($application, 'applicationForm')
                ->type(ApplicationDocumentType::Education)
                ->create();
        }
    }
}
