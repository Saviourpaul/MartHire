<section aria-labelledby="profile-information-heading">
    <header class="nw-panel-heading">
        <span class="nw-eyebrow">{{ __('The essentials') }}</span>
        <h2 id="profile-information-heading">{{ __('Personal details') }}</h2>
        <p>{{ __('Update your photo and personal details.') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="nw-form-stack" enctype="multipart/form-data"
        x-data="{
            countries: @js($countries),
            countryCode: @js(old('country_code', $user->country_code ?? '')),
            state: @js(old('state', $user->state ?? '')),
            city: @js(old('city', $user->city ?? '')),
            states: [],
            cities: [],
            countryName() {
                return this.countries.find((option) => option.value === this.countryCode)?.label ?? '';
            },
            loadingStates: false,
            loadingCities: false,
            async loadStates(keepState = false) {
                if (! this.countryCode) { this.states = []; this.cities = []; return; }
                this.loadingStates = true;
                try {
                    const response = await fetch('{{ route('locations.states') }}?country=' + encodeURIComponent(this.countryCode));
                    const json = await response.json();
                    this.states = json.data;
                } finally {
                    this.loadingStates = false;
                }
                if (! keepState) { this.state = ''; this.city = ''; this.cities = []; }
            },
            async loadCities(keepCity = false) {
                if (! this.countryCode || ! this.state) { this.cities = []; return; }
                this.loadingCities = true;
                try {
                    const response = await fetch('{{ route('locations.cities') }}?country=' + encodeURIComponent(this.countryCode) + '&state=' + encodeURIComponent(this.state));
                    const json = await response.json();
                    this.cities = json.data;
                } finally {
                    this.loadingCities = false;
                }
                if (! keepCity) { this.city = ''; }
            },
            init() {
                if (this.countryCode) {
                    this.loadStates(true).then(() => { if (this.state) this.loadCities(true); });
                }
            },
        }">
        @csrf
        @method('patch')

        <div class="nw-avatar-upload" x-data="{ preview: null, onFileChange(event) {
                const file = event.target.files[0];
                if (! file) { this.preview = null; return; }
                const reader = new FileReader();
                reader.onload = (loaded) => { this.preview = loaded.target.result; };
                reader.readAsDataURL(file);
            } }">
            <img :src="preview ?? '{{ $user->profileImageUrl() }}'" alt="" class="nw-avatar-upload__image">
            <div>
                <label for="profile_image" class="nw-button nw-button--outline nw-button--small">
                    <x-icon name="camera" /> {{ __('Change photo') }}
                </label>
                <p class="nw-form-hint">{{ __('JPG, PNG, WEBP or GIF. Up to 2MB.') }}</p>
                <input id="profile_image" class="nw-sr-only" type="file" name="profile_image"
                    accept="image/jpeg,image/png,image/webp,image/gif" x-on:change="onFileChange">
            </div>
        </div>
        <x-input-error id="profile_image-error" :messages="$errors->get('profile_image')" />

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error id="first_name-error" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error id="last_name-error" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" name="email" type="email" :value="$user->email" readonly autocomplete="username" />
            <p class="nw-form-hint">{{ __('Your email address cannot be changed from your profile.') }}</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="nw-notice">
                    <p>{{ __('Your email address is unverified.') }}</p>
                    <button type="submit" form="send-verification" class="nw-link">
                        {{ __('Resend verification email') }}
                    </button>

                    @if (session('status') === 'verification-code-sent')
                        <p role="status">{{ __('A new verification code has been sent to your email address.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="phone" :value="__('Phone number')" />
                <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error id="phone-error" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" />
                <x-input-error id="date_of_birth-error" :messages="$errors->get('date_of_birth')" />
            </div>
        </div>

        <div>
            <x-input-label for="address" :value="__('Address')" />
            <x-text-input id="address" name="address" type="text" :value="old('address', $user->address)" autocomplete="street-address" />
            <x-input-error id="address-error" :messages="$errors->get('address')" />
        </div>

        <div class="nw-form-row nw-form-row--fields">
            <div>
                <x-input-label for="country_code" :value="__('Country')" />
                <select id="country_code" class="nw-select" name="country_code" x-model="countryCode" x-on:change="loadStates()">
                    <option value="">{{ __('Select a country') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country['value'] }}">{{ $country['label'] }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="country" :value="countryName()">
                <x-input-error id="country_code-error" :messages="$errors->get('country_code')" />
            </div>
            <div>
                <x-input-label for="state" :value="__('State')" />
                <select id="state" class="nw-select" name="state" x-model="state" x-on:change="loadCities()" x-bind:disabled="! countryCode || loadingStates">
                    <option value="" x-text="loadingStates ? @js(__('Loading…')) : @js(__('Select a state'))"></option>
                    <template x-for="option in states" :key="option.value">
                        <option :value="option.value" x-text="option.label" x-bind:selected="option.value === state"></option>
                    </template>
                </select>
                <x-input-error id="state-error" :messages="$errors->get('state')" />
            </div>
        </div>

        <div>
            <x-input-label for="city" :value="__('City / Local government area')" />
            <select id="city" class="nw-select" name="city" x-model="city" x-bind:disabled="! state || loadingCities">
                <option value="">{{ __('Select a city') }}</option>
                <template x-for="option in cities" :key="option.value">
                    <option :value="option.value" x-text="option.label" x-bind:selected="option.value === city"></option>
                </template>
            </select>
            <x-input-error id="city-error" :messages="$errors->get('city')" />
        </div>

        <x-form.actions>
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>

            @if (session('success'))
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="nw-form-hint" role="status">{{ session('success') }}</p>
            @endif
        </x-form.actions>
    </form>
</section>
