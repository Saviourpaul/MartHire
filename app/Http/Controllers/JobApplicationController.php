<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationDocumentType;
use App\Http\Requests\StoreApplicationFormRequest;
use App\Models\ApplicantProfile;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use App\Services\ApplicantProfileChecklistService;
use App\Services\ApplicantProfileService;
use App\Services\ApplicationFormService;
use App\Services\LocationService;
use App\Services\PublicJobService;
use App\Support\ApplicationUploadLimits;
use App\Support\LiveTables;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $applications = $request->user()
            ->applications()
            ->with(['job', 'documents'])
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('client.jobs', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request, Job $job): View|RedirectResponse
    {
        abort_unless($job->isAcceptingApplications(), 404);

        $user = $request->user();

        if (! $user) {
            $request->session()->put('url.intended', route('applications.create', $job));

            return redirect()
                ->route('login')
                ->with('info', 'Log in or Register to continue with job application.');
        }

        abort_unless($user->isApplicant(), 403);

        if (! $user->hasVerifiedEmail()) {
            $request->session()->put('url.intended', route('applications.create', $job));

            return redirect()->route('verification.notice');
        }

        $existingApplication = $user
            ->applications()
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            return redirect()
                ->route('client.applications.show', $existingApplication)
                ->with('info', 'You have already applied for this job.');
        }

        $locations = app(LocationService::class);
        $profiles = app(ApplicantProfileService::class);
        $applicantProfile = $profiles->forUser($user);
        $savedIdentityDocument = $profiles->availableIdentificationDocument($user);
        $profileRequirements = app(ApplicantProfileChecklistService::class)->applicationRequirements(
            $user,
            $applicantProfile,
        );
        $screeningQuestions = $job->screeningQuestions;
        $countryCode = $request->old('country_code', $user->country_code ?? '');
        $state = $request->old('state', $user->state ?? '');

        return view('client.job-application', [
            'job' => $job,
            'publicJob' => app(PublicJobService::class)->native($job),
            'user' => $user,
            'applicantProfile' => $applicantProfile,
            'savedIdentityDocument' => $savedIdentityDocument,
            'profileRequirements' => $profileRequirements,
            'screeningQuestions' => $screeningQuestions,
            'screeningStepEnabled' => $screeningQuestions->isNotEmpty(),
            'documentsStepIndex' => 2 + ($screeningQuestions->isNotEmpty() ? 1 : 0),
            'reviewStepIndex' => 3 + ($screeningQuestions->isNotEmpty() ? 1 : 0),
            'countries' => $locations->countries(),
            'states' => is_string($countryCode) ? $locations->states($countryCode) : [],
            'cities' => is_string($countryCode) && is_string($state) ? $locations->cities($countryCode, $state) : [],
            ...$this->applicationFormViewData(
                $request,
                $user,
                $applicantProfile,
                $profileRequirements,
                $savedIdentityDocument,
                $job,
                $locations->countries(),
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $profileRequirements
     * @return array<string, mixed>
     */
    private function applicationFormViewData(
        Request $request,
        User $user,
        ApplicantProfile $applicantProfile,
        array $profileRequirements,
        ?UserIdentificationDocument $savedIdentityDocument,
        Job $job,
        array $countries,
    ): array {
        $errorBag = $request->session()->get('errors');
        $serverErrors = collect($errorBag?->getBag('default')->messages() ?? [])
            ->map(fn (array $messages): string => $messages[0])
            ->all();

        $identityChoices = collect(ApplicationDocumentType::identityTypes())
            ->map(fn (ApplicationDocumentType $type): array => [
                'id' => $type->value,
                'body' => $type->label(),
            ]);

        if ($savedIdentityDocument && ! $identityChoices->contains('id', $savedIdentityDocument->document_type->value)) {
            $identityChoices->push([
                'id' => $savedIdentityDocument->document_type->value,
                'body' => $savedIdentityDocument->document_type->label(),
            ]);
        }

        $savedIssuerCountry = collect($countries)->firstWhere('value', $savedIdentityDocument?->issuing_country_code);

        $viewData = [
            'initialCountry' => $request->old('country_code', $user->country_code ?? ''),
            'initialState' => $request->old('state', $user->state ?? ''),
            'initialCity' => $request->old('city', $user->city ?? ''),
            'initialLga' => $request->old('local_government_area', $user->local_government_area ?? ''),
            'serverErrors' => $serverErrors,
            'maxTotalUploadBytes' => ApplicationUploadLimits::maxTotalFileBytes(),
            'maxSingleUploadBytes' => ApplicationUploadLimits::maxSingleFileBytes(),
            'maxUploadFiles' => ApplicationUploadLimits::maxFileCount(),
            'personalFields' => [
                [
                    'name' => 'first_name',
                    'label' => __('First name'),
                    'type' => 'text',
                    'required' => true,
                    'min_length' => 2,
                    'max_length' => 100,
                    'autocomplete' => 'given-name',
                ],
                [
                    'name' => 'middle_name',
                    'label' => __('Middle name'),
                    'type' => 'text',
                    'required' => false,
                    'max_length' => 100,
                    'autocomplete' => 'additional-name',
                ],
                [
                    'name' => 'last_name',
                    'label' => __('Last name'),
                    'type' => 'text',
                    'required' => true,
                    'min_length' => 2,
                    'max_length' => 100,
                    'autocomplete' => 'family-name',
                ],
                [
                    'name' => 'email',
                    'label' => __('Email address'),
                    'type' => 'email',
                    'required' => true,
                    'max_length' => 255,
                    'autocomplete' => 'email',
                ],
                [
                    'name' => 'phone',
                    'label' => __('Phone number'),
                    'type' => 'tel',
                    'required' => true,
                    'autocomplete' => 'tel',
                ],
            ],
            'identityField' => [
                'name' => 'identification_type',
                'label' => __('Identification document type'),
                'type' => 'dropdown',
                'required' => true,
                'choices' => $identityChoices->all(),
            ],
            'identityCountryChoices' => collect($countries)->map(fn (array $country): array => [
                'id' => $country['value'],
                'body' => $country['label'],
            ])->values()->all(),
            'identityRequired' => true,
            'identityRequirementReason' => $job->identity_requirement_reason,
            'savedIdentityIssuingCountry' => is_array($savedIssuerCountry) ? $savedIssuerCountry['label'] : null,
            'profilePhotoRequired' => $profileRequirements['profile_image'],
        ];

        $viewData['personalFieldValues'] = collect($viewData['personalFields'])
            ->mapWithKeys(function (array $field) use ($request, $user): array {
                $name = $field['name'];
                $default = $user->getAttribute($name);

                return [$name => $request->old($name, $default)];
            })
            ->all();

        return $viewData;
    }

    /**
     * @throws Throwable
     */
    public function store(StoreApplicationFormRequest $request, Job $job, ApplicationFormService $service): RedirectResponse|JsonResponse
    {
        // Applicants have no active organization. Bind every submission and its
        // documents to the job's organization while the service writes them.
        $application = app(OrganizationContext::class)->runInOrganization(
            $job->organization,
            fn () => $service->submit($job, $request->user(), $request->validated())
        );

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Your application has been submitted successfully.');

            return response()->json([
                'submitted' => true,
                'message' => 'Your application has been submitted successfully.',
                'redirect' => route('client.applications.show', $application),
            ], 201);
        }

        return redirect()
            ->route('client.applications.show', $application)
            ->with('success', 'Your application has been submitted successfully.');
    }

    public function show(Request $request, ApplicationForm $applicationForm): View|JsonResponse|RedirectResponse
    {
        $this->authorize('view', $applicationForm);

        $identifier = $request->route()?->originalParameter('applicationForm');
        if (is_string($identifier) && ctype_digit($identifier)) {
            return redirect()->route('client.applications.show', $applicationForm, 301);
        }

        return LiveTables::view($request, view('client.application-show', [
            'application' => $applicationForm->load(['job', 'applicant', 'documents', 'statusHistories.changedBy', 'interviews']),
            'publicJob' => $applicationForm->job ? app(PublicJobService::class)->native($applicationForm->job) : null,
        ]), ['client-application']);
    }

    public function documents(Request $request): View
    {
        $documents = ApplicationDocument::query()
            ->with('applicationForm.job')
            ->whereHas('applicationForm', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.documents', [
            'documents' => $documents,
        ]);
    }

    public function notifications(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.notifications', [
            'notifications' => $notifications,
        ]);
    }
}
