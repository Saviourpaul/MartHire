<x-guest-layout :wide="true" :title="__('Request an organization demo')" :description="__('See how MarHire can support your hiring workflow and organization team.')">
    <section class="nw-demo-page" aria-labelledby="demo-title">
        <div class="nw-demo-page__inner">
            <div class="nw-demo-page__story">
                <h1 id="demo-title">{{ __('See how MarHire can work for your business') }}</h1>
                <p>{{ __('Explore a clearer way to manage candidates, interviews, and your hiring workflow from start to finish.') }}</p>
                <ul class="nw-demo-page__benefits">
                    <li><x-icon name="check" /> {{ __('Keep candidates and hiring activity in one workspace') }}</li>
                    <li><x-icon name="check" /> {{ __('Coordinate interviews and team feedback') }}</li>
                    <li><x-icon name="check" /> {{ __('Give your hiring team permissions that fit their roles') }}</li>
                    <li><x-icon name="check" /> {{ __('Track every applicant through a clear pipeline') }}</li>
                    <li><x-icon name="check" /> {{ __('Build an organization workspace around your needs') }}</li>
                </ul>
                <div class="nw-demo-page__mark" aria-hidden="true"><x-icon name="briefcase" /></div>
            </div>

            <section class="nw-demo-card" aria-labelledby="demo-form-title">
                @if (session('demo-request-submitted'))
                    <div class="nw-demo-card__success" role="status">
                        <span class="nw-demo-card__success-icon"><x-icon name="check" /></span>
                        <h2 id="demo-form-title">{{ __('Thanks for your interest') }}</h2>
                        <p>{{ __('Your request is with our team. We will contact you at your business email to arrange the next step.') }}</p>
                    </div>
                @else
                    <h2 id="demo-form-title">{{ __('Request a demo') }}</h2>
                    @if ($errors->any())
                        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('organization-demo.store') }}" class="nw-form-stack">
                        @csrf
                        <div class="nw-sr-only" aria-hidden="true">
                            <label for="website">{{ __('Leave this field empty') }}</label>
                            <input id="website" name="website" type="text" value="" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="nw-form-row nw-form-row--fields">
                            <div class="nw-field">
                                <x-input-label for="contact_first_name" :value="__('First name')" />
                                <x-text-input id="contact_first_name" name="contact_first_name" :value="old('contact_first_name')" required autocomplete="given-name" />
                                <x-input-error :messages="$errors->get('contact_first_name')" />
                            </div>
                            <div class="nw-field">
                                <x-input-label for="contact_last_name" :value="__('Last name')" />
                                <x-text-input id="contact_last_name" name="contact_last_name" :value="old('contact_last_name')" required autocomplete="family-name" />
                                <x-input-error :messages="$errors->get('contact_last_name')" />
                            </div>
                        </div>

                        <div class="nw-form-row nw-form-row--fields">
                            <div class="nw-field">
                                <x-input-label for="business_email" :value="__('Work email')" />
                                <x-text-input id="business_email" name="business_email" type="email" :value="old('business_email')" required autocomplete="email" />
                                <x-input-error :messages="$errors->get('business_email')" />
                            </div>
                            <div class="nw-field">
                                <x-input-label for="company_name" :value="__('Company name')" />
                                <x-text-input id="company_name" name="company_name" :value="old('company_name')" required autocomplete="organization" />
                                <x-input-error :messages="$errors->get('company_name')" />
                            </div>
                        </div>

                        <div class="nw-field">
                            <x-input-label for="phone" :value="__('Contact phone')" />
                            <x-phone-input id="phone" name="phone" :value="old('phone')" :country="old('phone_country', config('phone.default_country', 'NG'))" :required="true" />
                            <x-input-error :messages="$errors->get('phone')" />
                            <x-input-error :messages="$errors->get('phone_country')" />
                        </div>

                        <div class="nw-field">
                            <x-input-label for="organization_size" :value="__('Organization size')" />
                            <select id="organization_size" name="organization_size" class="nw-select" required>
                                <option value="">{{ __('Choose a range') }}</option>
                                @foreach (['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'] as $size)
                                    <option value="{{ $size }}" @selected(old('organization_size') === $size)>{{ $size }} {{ __('employees') }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('organization_size')" />
                        </div>

                        @if ($captchaEnabled)
                            @if ($captchaSiteKey)
                                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                <div class="g-recaptcha" data-sitekey="{{ $captchaSiteKey }}"></div>
                                <x-input-error :messages="$errors->get('captcha')" />
                            @else
                                <div class="nw-notice nw-notice--error" role="alert">{{ __('Demo requests are temporarily unavailable. Please try again later.') }}</div>
                            @endif
                        @endif

                        <button type="submit" class="nw-button nw-button--primary nw-demo-card__submit" @disabled($captchaEnabled && ! $captchaSiteKey)>Submit <x-icon name="arrow-right" /></button>
                    </form>
                @endif
            </section>
        </div>
    </section>
</x-guest-layout>
