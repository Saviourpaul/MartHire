@php
    $profile = $review['profile'];
    $initialTab = old('_interview_form') || request('tab') === 'interviews' ? 'interviews' : (request('tab') === 'activity' ? 'activity' : 'overview');
@endphp
<x-app-layout>
    <x-slot name="title">{{ $profile['name'] }} · {{ __('Candidate review') }}</x-slot>
    <x-slot name="header"><h1 class="nw-heading">{{ __('Candidate review') }}</h1><p>{{ __('Review the application, documents and hiring activity in one place.') }}</p></x-slot>
    <x-slot name="actions"><a class="nw-button nw-button--outline nw-button--small" href="{{ route('organisations.candidates.index') }}"><x-icon name="arrow-left" />{{ __('All candidates') }}</a></x-slot>

    @fragment('candidate-review')
    <div class="nw-review" data-live-table="candidate-review" data-live-topics="candidates,interviews,team,jobs" data-live-pages="" data-live-interval="0" data-live-url="{{ request()->fullUrl() }}" x-data="candidateReview({ documents: @js($reviewDocuments), tab: @js($initialTab) })" x-bind:data-current-review-tab="reviewTab" x-bind:data-current-document="documentId">
        @if ($errors->any())<div class="nw-notice nw-notice--error" role="alert"><strong>{{ __('Please review the highlighted fields.') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <x-candidate-pipeline :pipeline="$review['pipeline']" />

        <div class="nw-review__workspace">
            <div class="nw-review__information">
                <section class="nw-panel nw-review-profile" aria-labelledby="candidate-name">
                    <div class="nw-review-profile__identity">
                        <div class="nw-review-avatar" x-data="{ avatarFailed: false }">@if($profile['avatar_url'])<img src="{{ $profile['avatar_url'] }}" alt="" width="80" height="80" x-show="!avatarFailed" x-init="if ($el.complete && !$el.naturalWidth) avatarFailed = true" x-on:error="avatarFailed = true"><span aria-hidden="true" x-show="avatarFailed" x-cloak>{{ $profile['initials'] }}</span>@else<span aria-hidden="true">{{ $profile['initials'] }}</span>@endif</div>
                        <div><span class="nw-eyebrow">{{ __('Applicant') }}</span><h2 id="candidate-name">{{ $profile['name'] }}</h2><x-status-badge :status="$application->status" /></div>
                    </div>
                    <div class="nw-review-profile__role"><x-icon name="briefcase" /><div><span>{{ __('Applied for') }}</span><strong>{{ $application->job?->title ?? __('Job unavailable') }}</strong></div></div>
                    <div class="nw-review-profile__contacts">
                        @if($profile['email'])<a href="mailto:{{ $profile['email'] }}"><x-icon name="mail" /><span>{{ $profile['email'] }}</span></a>@endif
                        @if($profile['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/', '', $profile['phone']) }}"><x-icon name="headset" /><span>{{ $profile['phone'] }}</span></a>@endif
                        @if($profile['location_without_country'] || $profile['country_data'])<p><x-icon name="map-pin" /><span>@if($profile['location_without_country']){{ $profile['location_without_country'] }}<br>@endif<x-country-display :country="$profile['country_data']" /></span></p>@endif
                    </div>
                    <div class="nw-review-profile__meta"><span><x-icon name="calendar" />{{ $application->submitted_at?->format('j M Y') ?? __('Date unavailable') }}</span><span>{{ $application->reference }}</span></div>
                    @can('create', [\App\Models\Interview::class, $application])
                        <a class="nw-button nw-button--primary nw-review-profile__schedule" href="?tab=interviews#schedule-interview" x-on:click.prevent="openSchedule()"><x-icon name="calendar" />{{ __('Schedule interview') }}</a>
                    @endcan
                </section>

                <section class="nw-panel nw-review-detail" aria-label="{{ __('Candidate information') }}">
                    <div class="nw-review-tabs" role="tablist" aria-label="{{ __('Candidate sections') }}" x-on:keydown.arrow-right.prevent="moveReviewTab(1)" x-on:keydown.arrow-left.prevent="moveReviewTab(-1)" x-on:keydown.home.prevent="selectReviewTab('overview', true)" x-on:keydown.end.prevent="selectReviewTab('activity', true)">
                        @foreach (['overview' => __('Overview'), 'interviews' => __('Interviews'), 'activity' => __('Activity')] as $tab => $label)
                            <button type="button" id="review-tab-{{ $tab }}" role="tab" aria-controls="review-panel-{{ $tab }}" x-ref="reviewTab{{ $tab }}" x-bind:aria-selected="reviewTab === @js($tab) ? 'true' : 'false'" x-bind:tabindex="reviewTab === @js($tab) ? 0 : -1" x-bind:class="{ 'is-active': reviewTab === @js($tab) }" x-on:click="selectReviewTab(@js($tab))">{{ $label }}@if($tab === 'interviews')<span>{{ $application->interviews->count() }}</span>@endif</button>
                        @endforeach
                    </div>

                    <div id="review-panel-overview" class="nw-review-tab-panel" role="tabpanel" aria-labelledby="review-tab-overview" tabindex="0" x-show="reviewTab === 'overview'">
                        <h3>{{ __('Personal information') }}</h3><p class="nw-hint">{{ __('Details supplied with this application.') }}</p>
                        <dl class="nw-detail-list nw-review-facts">
                            @foreach ([__('Date of birth') => $profile['date_of_birth'], __('Gender') => $profile['gender'], __('Marital status') => $profile['marital_status'], __('State') => $profile['state'], __('City / LGA') => $profile['city'] ?: $profile['local_government_area'], __('Address') => $profile['address']] as $label => $value)
                                @if($value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endif
                            @endforeach
                            <div><dt>{{ __('Country') }}</dt><dd><x-country-display :country="$profile['country_data']" /></dd></div>
                            <div><dt>{{ __('Application reference') }}</dt><dd>{{ $application->reference }}</dd></div>
                            <div><dt>{{ __('Submitted') }}</dt><dd>{{ $application->submitted_at?->format('j M Y, g:i A') ?? '—' }}</dd></div>
                        </dl>
                        @if($application->employer_remarks)<div class="nw-review-remarks"><h3>{{ __('Latest stage remarks') }}</h3><p>{{ $application->employer_remarks }}</p></div>@endif
                        <div class="nw-review-pipeline-actions" aria-labelledby="move-heading">
                            <h3 id="move-heading">{{ __('Move candidate') }}</h3>
                            @if($application->status === \App\Enums\CandidatePipelineStage::Interview)
                                @can('selectCandidates', $application)
                                    @cannot('select', $application)<p class="nw-hint">{{ __('Complete an interview before selecting this candidate. Completing an interview does not select them automatically.') }}</p>@endcannot
                                @endcan
                            @endif
                            @if(empty($allowedStages))
                                <p class="nw-hint">{{ $review['pipeline']['is_terminal'] ? __('This application has reached a final stage.') : __('You do not have permission to change this candidate’s stage.') }}</p>
                            @else
                                <form method="POST" action="{{ route('organisations.candidates.move', $application) }}" class="nw-form">
                                    @csrf @method('PATCH')
                                    <div class="nw-field"><label class="nw-label" for="stage-remarks">{{ __('Stage remarks') }} <span class="nw-optional">{{ __('Optional') }}</span></label><textarea class="nw-input" id="stage-remarks" name="remarks" rows="3" maxlength="2000" placeholder="{{ __('Add context for your team…') }}" aria-describedby="stage-remarks-hint @error('remarks') stage-remarks-error @enderror">{{ old('remarks') }}</textarea><p class="nw-hint" id="stage-remarks-hint">{{ __('Saved with this stage change in the activity history.') }}</p>@error('remarks')<p id="stage-remarks-error" class="nw-field__error">{{ $message }}</p>@enderror</div>
                                    <div class="nw-button-row">@foreach($allowedStages as $stage)<button type="submit" name="stage" value="{{ $stage->value }}" class="nw-button nw-button--small {{ $stage->value === 'rejected' ? 'nw-button--danger' : 'nw-button--primary' }}"
                                        @if($stage === \App\Enums\CandidatePipelineStage::Selected) data-confirm="{{ __('Confirm this candidate as Selected? This is a final pipeline stage.') }}" data-confirm-title="{{ __('Select candidate') }}" data-confirm-label="{{ __('Confirm selection') }}" data-confirm-type="success" data-confirm-field="selection_confirmed"
                                        @elseif($stage === \App\Enums\CandidatePipelineStage::Rejected) data-confirm="{{ __('Reject this candidate? This is a final pipeline stage.') }}" data-confirm-title="{{ __('Reject candidate') }}" data-confirm-label="{{ __('Confirm rejection') }}" data-confirm-type="warning" @endif>{{ __('Move to :stage', ['stage' => $stage->label()]) }}</button>@endforeach</div>
                                    @error('stage')<p class="nw-field__error">{{ $message }}</p>@enderror
                                </form>
                            @endif
                        </div>
                    </div>
                    <div id="review-panel-interviews" class="nw-review-tab-panel" role="tabpanel" aria-labelledby="review-tab-interviews" tabindex="0" x-show="reviewTab === 'interviews'" x-cloak>@include('organisations.candidates.partials.interviews')</div>
                    <div id="review-panel-activity" class="nw-review-tab-panel" role="tabpanel" aria-labelledby="review-tab-activity" tabindex="0" x-show="reviewTab === 'activity'" x-cloak><x-candidate-activity :activity="$review['activity']" /></div>
                </section>
            </div>
            @can('viewDocuments', $application)
                <x-candidate-document-viewer :documents="$reviewDocuments" />
            @else
                <section class="nw-panel nw-review-documents" aria-label="{{ __('Document access') }}"><div class="nw-review-document-empty"><x-icon name="lock" /><h2>{{ __('Document access restricted') }}</h2><p>{{ __('Your team role does not include access to candidate files.') }}</p></div></section>
            @endcan
        </div>
    </div>
    @endfragment
</x-app-layout>
