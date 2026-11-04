
<x-layout :title="__('Apply for :title', ['title' => $job->title])">
    <section class="nw-section">
        <div class="nw-container nw-native-apply">
            <nav class="nw-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('jobs.show', $job) }}"><x-icon name="arrow-left" /> {{ __('Back to the role') }}</a>
            </nav>
            <header class="nw-section-heading">
                <div><span class="nw-eyebrow">{{ $job->company }}</span>
                    <h1 class="nw-heading">{{ __('Apply for :title', ['title' => $job->title]) }}</h1>
                </div>
                <p>{{ __('A few focused steps to introduce yourself and share your documents.') }}</p>
            </header>
            <p class="nw-job__meta"><x-job-country :job="$publicJob" /></p>

            <div class="nw-wizard" x-data="nativeApplicationForm({
                errors: @js($serverErrors),
                documents: @js($educationDocuments),
                profileDisabilityStatus: @js(old('profile_contact.disability_status', $applicantProfile->selfIdentification?->disability_status ?? '')),
                profileEmploymentCurrent: @js((bool) old('profile_employment.is_current', false)),
                location: {
                    countries: @js($countries),
                    countryCode: @js($initialCountry),
                    state: @js($initialState),
                    city: @js($initialCity),
                    lga: @js($initialLga),
                    states: @js($states),
                    cities: @js($cities),
                    statesUrl: @js(route('locations.states')),
                    citiesUrl: @js(route('locations.cities')),
                    nigeriaUsesLga: true
                }
            })">
                <x-form.wizard-progress />
                <div class="nw-error-summary" x-ref="summary" tabindex="-1" role="alert" x-show="summary.length"
                    x-cloak>
                    <h2>{{ __('Please check these details') }}</h2>
                    <ul><template x-for="item in summary" :key="item.name">
                            <li><button type="button" class="nw-link" x-on:click="goTo(item)"><span
                                        x-text="item.label"></span>: <span x-text="item.message"></span></button></li>
                        </template></ul>
                </div>
                <div class="nw-error-summary" x-ref="submissionError" tabindex="-1" role="alert"
                    x-show="submissionError" x-text="submissionError" x-cloak></div>
                @if ($errors->any())
                    <noscript>
                        <div class="nw-notice nw-notice--error" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <p>{{ __('Please select your files again after reloading the page.') }}</p>
                        </div>
                    </noscript>
                @endif

                <form method="POST" action="{{ route('applications.store', $job) }}" enctype="multipart/form-data"
                    class="nw-wizard__form" novalidate x-ref="form" x-on:submit="submit($event)"
                    x-on:input="handleInput($event)" x-on:change="handleInput($event)">
                    @csrf
                    @include('client.job-application.partials.personal-details', [
                        'personalFields' => $personalFields,
                        'personalFieldValues' => $personalFieldValues,
                        'user' => $user,
                        'applicantProfile' => $applicantProfile,
                    ])

                    @include('client.job-application.partials.address', [
                        'initialCountry' => $initialCountry,
                        'initialState' => $initialState,
                        'initialCity' => $initialCity,
                        'initialLga' => $initialLga,
                        'countries' => $countries,
                        'states' => $states,
                        'cities' => $cities,
                        'user' => $user,
                    ])

                    @include('client.job-application.partials.profile', [
                        'profileStepEnabled' => $profileStepEnabled,
                        'profileRequirements' => $profileRequirements,
                        'applicantProfile' => $applicantProfile,
                        'jobPreferences' => $jobPreferences,
                        'employmentTypeChoices' => $employmentTypeChoices,
                    ])

                    @include('client.job-application.partials.documents', [
                        'profileStepEnabled' => $profileStepEnabled,
                        'savedIdentityDocument' => $savedIdentityDocument,
                        'identityField' => $identityField,
                        'profileRequirements' => $profileRequirements,
                        'applicantProfile' => $applicantProfile,
                    ])

                    @include('client.job-application.partials.review', [
                        'profileStepEnabled' => $profileStepEnabled,
                    ])

                    <x-form.wizard-actions :cancel-url="route('jobs.show', $job)" :submit-label="__('Submit application')" />
                </form>
            </div>
        </div>
    </section>
</x-layout>
