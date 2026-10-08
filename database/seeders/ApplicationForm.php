<?php

namespace Database\Seeders;

use App\Enums\ApplicationDocumentType;
use App\Enums\ApplicationStatus;
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
            $status = match ($index % 3) {
                1 => ApplicationStatus::Approved,
                2 => ApplicationStatus::Rejected,
                default => ApplicationStatus::Pending,
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
                    'country_code' => $applicant->country_code,
                    'country' => $applicant->country,
                    'state' => $applicant->state,
                    'city' => $applicant->city,
                    'address' => $applicant->address,
                    'zipcode' => $applicant->zipcode,
                    'profile_image_path' => $applicant->profile_image_path,
                    'status' => $status,
                    'reviewed_by' => $status === ApplicationStatus::Pending ? null : $employer->id,
                    'reviewed_at' => $status === ApplicationStatus::Pending ? null : now(),
                    'employer_remarks' => $status === ApplicationStatus::Pending ? null : 'Seeded '.$status->label().' application.',
                ])
                ->create();

            $application->statusHistories()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::Pending,
                'changed_by' => $applicant->id,
                'remarks' => 'Application submitted.',
                'created_at' => $application->submitted_at,
            ]);

            if ($status !== ApplicationStatus::Pending) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Pending,
                    'to_status' => $status,
                    'changed_by' => $employer->id,
                    'remarks' => $application->employer_remarks,
                    'created_at' => now(),
                ]);
            }

            foreach (ApplicationDocumentType::cases() as $type) {
                ApplicationDocument::factory()
                    ->for($application, 'applicationForm')
                    ->type($type)
                    ->state([
                        'status' => $status,
                        'reviewed_by' => $status === ApplicationStatus::Pending ? null : $employer->id,
                        'reviewed_at' => $status === ApplicationStatus::Pending ? null : now(),
                        'employer_remarks' => $status === ApplicationStatus::Rejected ? 'Please upload a clearer copy.' : null,
                    ])
                    ->create();
            }
        }
    }
}
