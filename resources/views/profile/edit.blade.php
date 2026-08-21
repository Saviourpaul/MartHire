<x-admin-layout title="Profile">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Profile</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    @php
                        $requiresApplicantProfile = $user->isApplicant();
                        $requiredMark = $requiresApplicantProfile ? ' *' : '';
                        $selectedState = old('state_of_origin', $user->state_of_origin);
                        $selectedLga = old('local_government_area', $user->local_government_area);
                        $originalProfile = [
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'username' => $user->username,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                            'address' => $user->address,
                            'nationality' => $user->nationality,
                            'state_of_origin' => $user->state_of_origin,
                            'local_government_area' => $user->local_government_area,
                            'zipcode' => $user->zipcode,
                            'profile_image_src' => $user->profileImageUrl(),
                            'profile_image_path' => $user->profile_image_path,
                        ];
                    @endphp

                    <form id="profile-form" action="{{ route('profile.update') }}" method="POST"
                        enctype="multipart/form-data" data-confirm data-confirm-title="Save profile changes?"
                        data-confirm-text="Your profile information will be updated."
                        data-confirm-button="Save Changes">
                        @csrf
                        @method('PATCH')

                        <div class="col-md-12 col-lg-12">
                            <div class="pro-form-img">
                                <div class="profile-pic">

                                    <img id="profile-image-preview" class="rounded-circle"
                                        src="{{ $user->profileImageUrl() }}" alt="Profile image" width="100"
                                        height="100" style="object-fit: cover;">
                                </div>

                                <div class="upload-files">
                                    <label class="file-upload image-upbtn">
                                        <i class="feather-upload me-2"></i>Upload Photo
                                        <input id="profile-image-input" type="file" name="profile_image"
                                            class="form-control mt-2" accept="image/*" @required($requiresApplicantProfile && !$user->profile_image_path) disabled>
                                    </label>
                                    <span>For better preview recommended size is 450px x 450px. Max size 5mb.</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control"
                                        value="{{ old('first_name', $user->first_name) }}" required readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control"
                                        value="{{ old('last_name', $user->last_name) }}" required readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control"
                                value="{{ old('username', $user->username) }}" required readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $user->email) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number{{ $requiredMark }}</label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ old('phone', $user->phone) }}" @required($requiresApplicantProfile) readonly>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth{{ $requiredMark }}</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                @required($requiresApplicantProfile) readonly>
                        </div>
                        <div class="form-group">
                            <label>Address{{ $requiredMark }}</label>
                            <input type="text" name="address" class="form-control"
                                value="{{ old('address', $user->address) }}" @required($requiresApplicantProfile) readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nationality{{ $requiredMark }}</label>
                                    <input type="text" name="nationality" class="form-control"
                                        value="{{ old('nationality', $user->nationality ?? 'Nigeria') }}"
                                        @required($requiresApplicantProfile) readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>State of Origin{{ $requiredMark }}</label>
                                    <select name="state_of_origin" class="form-control" @required($requiresApplicantProfile)
                                        data-profile-state disabled>
                                        <option value="">Select state</option>
                                            @foreach ($states as $state)
                                            <option value="{{ $state->name }}"
                                                data-lga-url="{{ route('locations.states.local-government-areas', $state) }}"
                                                @selected($selectedState === $state->name)>
                                                {{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Local Government Area{{ $requiredMark }}</label>
                                    <select name="local_government_area" class="form-control" @required($requiresApplicantProfile)
                                        data-profile-lga data-selected-lga="{{ $selectedLga }}" disabled>
                                        <option value="">Select LGA</option>
                                        @if ($selectedLga)
                                            <option value="{{ $selectedLga }}" selected>{{ $selectedLga }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Zipcode{{ $requiredMark }}</label>
                                    <input type="text" name="zipcode" class="form-control"
                                        value="{{ old('zipcode', $user->zipcode) }}" @required($requiresApplicantProfile) readonly>
                                </div>
                            </div>
                        </div>

                        <button id="edit-profile-button" type="button" class="btn btn-primary">Update Profile</button>
                        <div id="profile-actions" class="d-none mt-3">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button id="cancel-profile-edit" type="button"
                                class="btn btn-secondary ms-2">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delete Account</h5>
                    <form action="{{ route('profile.destroy') }}" method="POST" data-confirm
                        data-confirm-title="Delete account?"
                        data-confirm-text="This action is permanent and will remove your account."
                        data-confirm-button="Delete Account">
                        @csrf
                        @method('DELETE')
                        <div class="form-group">
                            <label>Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" class="form-control" required minlength="8" placeholder="Enter your password">
                                <button type="button" class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-2" data-password-toggle="password" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password', 'userDeletion')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const form = document.getElementById('profile-form');
                const editButton = document.getElementById('edit-profile-button');
                const cancelButton = document.getElementById('cancel-profile-edit');
                const profileActions = document.getElementById('profile-actions');
                const profileImageInput = document.getElementById('profile-image-input');
                const profileImagePreview = document.getElementById('profile-image-preview');
                const stateSelect = form?.querySelector('[data-profile-state]');
                const lgaSelect = form?.querySelector('[data-profile-lga]');
                const originalProfile = @json($originalProfile);
                let profileReadonly = true;

                if (!form) {
                    return;
                }

                const resetLgaOptions = (placeholder = 'Select LGA') => {
                    if (!lgaSelect) {
                        return;
                    }

                    lgaSelect.innerHTML = '';
                    lgaSelect.append(new Option(placeholder, ''));
                };

                const populateLgas = async (selectedLga = '') => {
                    if (!stateSelect || !lgaSelect) {
                        return;
                    }

                    const selectedOption = stateSelect.selectedOptions[0];
                    const lgaUrl = selectedOption?.dataset.lgaUrl || '';

                    resetLgaOptions(lgaUrl ? 'Loading LGAs...' : 'Select state first');
                    lgaSelect.disabled = true;

                    if (!lgaUrl) {
                        return;
                    }

                    try {
                        const response = await fetch(lgaUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load LGAs.');
                        }

                        const payload = await response.json();
                        const localGovernmentAreas = Array.isArray(payload.data) ? payload.data : [];

                        resetLgaOptions(localGovernmentAreas.length > 0 ? 'Select LGA' : 'No LGAs available');

                        localGovernmentAreas.forEach((lga) => {
                            const option = new Option(lga.name, lga.name, false, lga.name === selectedLga);
                            option.dataset.lgaId = lga.id;
                            option.dataset.lgaSlug = lga.slug;
                            lgaSelect.append(option);
                        });

                        lgaSelect.disabled = profileReadonly || localGovernmentAreas.length === 0;
                    } catch (error) {
                        resetLgaOptions('Unable to load LGAs');
                        lgaSelect.disabled = true;
                        console.error(error);
                    }
                };

                const setReadonly = (readonly) => {
                    profileReadonly = readonly;

                    form.querySelectorAll('input, select').forEach((field) => {
                        if (field.name === 'profile_image') {
                            field.disabled = readonly;
                            return;
                        }

                        if (field.tagName === 'SELECT') {
                            field.disabled = readonly || (field === lgaSelect && !stateSelect?.value);
                            return;
                        }

                        field.readOnly = readonly || field.name === 'email';
                    });
                };

                const resetToOriginalValues = () => {
                    form.querySelectorAll('input, select').forEach((field) => {
                        if (!originalProfile.hasOwnProperty(field.name)) {
                            return;
                        }

                        field.value = originalProfile[field.name] || '';
                    });

                    populateLgas(originalProfile.local_government_area || '');

                    if (profileImageInput) {
                        profileImageInput.value = '';
                    }

                    if (profileImagePreview) {
                        profileImagePreview.src = originalProfile.profile_image_src;
                    }
                };

                populateLgas(lgaSelect?.dataset.selectedLga || '');
                setReadonly(true);

                editButton?.addEventListener('click', () => {
                    editButton.classList.add('d-none');
                    profileActions?.classList.remove('d-none');
                    setReadonly(false);
                    profileImageInput?.focus();
                });

                cancelButton?.addEventListener('click', () => {
                    resetToOriginalValues();
                    setReadonly(true);
                    editButton?.classList.remove('d-none');
                    profileActions?.classList.add('d-none');
                });

                profileImageInput?.addEventListener('change', () => {
                    const file = profileImageInput.files?.[0];

                    if (!file || !profileImagePreview) {
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = () => {
                        profileImagePreview.src = reader.result;
                    };

                    reader.readAsDataURL(file);
                });

                stateSelect?.addEventListener('change', () => {
                    lgaSelect.dataset.selectedLga = '';
                    populateLgas();
                });
            })();
        </script>
    @endpush
</x-admin-layout>
