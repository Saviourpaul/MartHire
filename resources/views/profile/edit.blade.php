<x-layout>

    @php
        $requiresApplicantProfile = $user->isApplicant();
        $requiredMark = $requiresApplicantProfile ? ' *' : '';
        $selectedState = old('state_of_origin', $user->state_of_origin);
        $selectedLga = old('local_government_area', $user->local_government_area);
        $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
        $selectClass = $inputClass.' h-11 appearance-none pr-11';
        $todayLimit = now()->subDay()->format('Y-m-d');
        $requiredAttribute = $requiresApplicantProfile ? 'required' : null;
        $labelClass = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
        $errorClass = 'mt-2 text-sm text-error-500';
        $originalProfile = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
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
    <!-- ===== Main Content Start ===== -->
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <!-- Breadcrumb Start -->
            <div x-data="{ pageName: `User Profile` }">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
                   
                </div>
            </div>
            <!-- Breadcrumb End -->

            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-5 text-lg font-semibold text-gray-800 lg:mb-7 dark:text-white/90">
                    My Profile
                </h3>
                @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    x-init="setTimeout(() => show = false, 5000)"
                    class="mb-6 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-6 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                    Please review the highlighted fields and try again.
                </div>
            @endif

                <!-- Info -->

                <div class="mb-6 rounded-2xl border border-gray-200 p-5 lg:p-6 dark:border-gray-800">
                    <div class="flex flex-col gap-5 sm:flex-row xl:gap-10">
                        <div class="flex-1">
                            <div class="mb-6 flex flex-col gap-5 sm:flex-row xl:items-center xl:justify-between">
                                <div class="flex w-full flex-col items-start gap-6 sm:flex-row sm:items-center">
                                    <div
                                        class="border-gray-20 overflow-hidden rounded-full border dark:border-gray-800">
                                        <img src="{{ $user->profileImageUrl() }}" class="size-20" alt="user" />
                                    </div>
                                    <div class="text-left">
                                        <h4 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </h4>
                                        
                                    </div>
                                </div>
                            </div>
                            <div
                                class="relative grid max-w-4xl grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 xl:gap-x-11 xl:gap-y-7">
                                <div class="w-full">
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        first Name
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->first_name }}
                                    </p>
                                </div>
                                <div class="w-full">
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Last Name
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->last_name }}
                                    </p>
                                </div>
                                 
                                <div class="hidden xl:block"></div>
                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Email address
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <a class="__cf_email__"
                                            data-cfemail="4331222d272c2e3630263103332a2e292c6d202c2e">{{ $user->email }}</a>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Phone Number
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->phone ?: 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Date Of Birth
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->date_of_birth ? $user->date_of_birth->format('M d, Y') : 'N/A' }}
                                    </p>
                                </div>


                            </div>
                        </div>
                        <div>
                            <button @click="isProfileInfoModal = true"
                                class="shadow-theme-xs flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 lg:inline-flex lg:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z"
                                        fill="" />
                                </svg>
                                Edit
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="mb-6 rounded-2xl border border-gray-200 p-5 lg:p-6 dark:border-gray-800">
                    <div class="flex flex-col gap-6 sm:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-800 lg:mb-6 dark:text-white/90">
                                Address
                            </h4>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:gap-7 2xl:gap-x-32">
                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Current Address
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->address ?: 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Nationality
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->nationality ?: 'N/A' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        State of Origin
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->state_of_origin  ?: 'N/A' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                                        Local Government
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->local_government_area  ?: 'N/A' }}
                                    </p>
                                </div>

                                <div>

                                </div>
                            </div>
                        </div>



                    </div>
                </div>

                <!-- Security -->
                <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <section class="rounded-2xl border border-gray-200 p-5 transition hover:border-brand-200 hover:shadow-theme-sm lg:p-6 dark:border-gray-800 dark:hover:border-brand-500/30">
                        <div class="flex h-full flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex gap-4">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                        Change Password
                                    </h4>
                                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                        Verify your current password before setting a new secure password.
                                    </p>
                                    @if (session('status') === 'password-updated')
                                        <p x-data="{ show: true }" x-show="show" x-transition
                                            x-init="setTimeout(() => show = false, 4000)"
                                            class="mt-3 inline-flex items-center rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                            Password updated successfully.
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <button type="button" x-on:click="isChangePasswordModal = true"
                                class="shadow-theme-xs inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-800 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                                Change
                            </button>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-error-500/20 p-5 transition hover:border-error-500/40 hover:shadow-theme-sm lg:p-6 dark:border-error-500/20 dark:hover:border-error-500/40">
                        <div class="flex h-full flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex gap-4">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-error-50 text-error-500 dark:bg-error-500/10 dark:text-error-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                        Delete Account
                                    </h4>
                                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                        Permanently remove access to your account after password confirmation.
                                    </p>
                                </div>
                            </div>
                            <button type="button" x-on:click="isDeleteAccountModal = true"
                                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-error-500 px-4 py-2.5 text-sm font-medium text-error-500 transition hover:bg-error-50 focus:outline-hidden focus:ring-3 focus:ring-error-500/10 sm:w-auto dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    <!-- ===== Main Content End ===== -->
    </div>
    <!-- ===== Content Area End ===== -->
    </div>
    <!-- ===== Page Wrapper End ===== -->

   <!-- BEGIN MODAL -->
    <div x-show="isProfileInfoModal"
        class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isProfileInfoModal = false"
            class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
            <!-- close btn -->
            <button @click="isProfileInfoModal = false"
                class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z"
                        fill="" />
                </svg>
            </button>
            <div class="px-2 pr-14">
                <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">
                    Edit Personal Information
                </h4>
                <p class="mb-6 text-sm text-gray-500 lg:mb-7 dark:text-gray-400">
                    Update your details to keep your profile up-to-date.
                </p>
            </div>

            <form class="flex flex-col" id="profile-form" action="{{ route('profile.update') }}" method="POST"
                enctype="multipart/form-data">
                <div class="custom-scrollbar h-[450px] overflow-y-auto px-2">
                    @csrf
                    @method('PATCH')
                    <div>
                        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">
                            Update Profile Picture
                        </h4>
                        <div class="mb-6 flex max-w-sm items-center gap-6 lg:pr-5">
                            <div class="relative size-20 shrink-0 rounded-full sm:size-25">
                                <img id="profile-image-preview" src="{{ $user->profileImageUrl() }}" alt="Profile Picture"
                                    class="size-20 rounded-full object-cover sm:size-25" />
                                <label for="profile-image-input"
                                    class="absolute right-0 bottom-0 flex size-8 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                    <input type="file" name="profile_image" id="profile-image-input" class="hidden" accept="image/jpeg,image/png,image/webp" @required($requiresApplicantProfile && !$user->profile_image_path) />
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12.6731 3.41904C12.4371 3.10308 12.0659 2.91699 11.6715 2.91699H8.32809C7.93374 2.91699 7.56252 3.10308 7.32656 3.41904L6.83173 4.08164C6.59576 4.3976 6.22454 4.58369 5.83019 4.58369H3.5415C2.85115 4.58369 2.2915 5.14333 2.2915 5.83369V14.3754C2.2915 15.0657 2.85115 15.6254 3.5415 15.6254H16.4582C17.1485 15.6254 17.7082 15.0657 17.7082 14.3754V5.83369C17.7082 5.14333 17.1485 4.58369 16.4582 4.58369H14.1694C13.7751 4.58369 13.4039 4.3976 13.1679 4.08164L12.6731 3.41904Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M13.3332 9.79362C13.3332 11.6346 11.8408 13.127 9.99984 13.127C8.15889 13.127 6.6665 11.6346 6.6665 9.79362C6.6665 7.95267 8.15889 6.46029 9.99984 6.46029C11.8408 6.46029 13.3332 7.95267 13.3332 9.79362Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </label>
                                 <p id="profile-image-client-error" class="{{ $errorClass }}" hidden></p>
                                @error('profile_image')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                               
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Upload a square image (200×200 px) in JPEG or PNG format.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h4 class="mb-5 text-lg font-medium text-gray-800 lg:mb-6 dark:text-white/90">
                            Personal Information
                        </h4>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">
                           <div class="col-span-2 lg:col-span-1">
                                <label for="first_name" class="{{ $labelClass }}">First Name <span class="text-error-500">*</span></label>
                                <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}"
                                    maxlength="255" autocomplete="given-name" required class="{{ $inputClass }}">
                                @error('first_name')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-span-2 lg:col-span-1">
                                <label for="last_name" class="{{ $labelClass }}">Last Name <span class="text-error-500">*</span></label>
                                <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}"
                                    maxlength="255" autocomplete="family-name" required class="{{ $inputClass }}">
                                @error('last_name')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Email Address
                                </label>
                                <input type="text" name="email" value="{{ old('email', $user->email) }}"
                                    readonly
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>

                            
                            <div class="col-span-2 lg:col-span-1">
                                <label for="phone" class="{{ $labelClass }}">
                                    Phone Number @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <input id="phone" name="phone" type="tel"
                                    value="{{ old('phone', $user->phone) }}" maxlength="20"
                                    pattern="[0-9+\-\s()]{7,20}" autocomplete="tel" 
                                    class="{{ $inputClass }}">
                                @error('phone')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                            
                             <div class="col-span-2 lg:col-span-1">
                                <label for="date_of_birth" class="{{ $labelClass }}">
                                    Date of Birth @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <input id="date_of_birth" name="date_of_birth" type="date"
                                    value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                    max="{{ $todayLimit }}" class="{{ $inputClass }}">
                                @error('date_of_birth')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>

                           
                            <div class="col-span-2 lg:col-span-1">
                                <label for="nationality" class="{{ $labelClass }}">
                                    Nationality @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <input id="nationality" name="nationality" type="text"
                                    value="{{ old('nationality', $user->nationality) }}" maxlength="255"
                                    autocomplete="country-name" {{ $requiresApplicantProfile }} class="{{ $inputClass }}">
                                @error('nationality')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                              <div class="col-span-2 lg:col-span-1">
                                <label for="state_of_origin" class="{{ $labelClass }}">
                                    State of Origin @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <div class="relative">
                                    <select id="state_of_origin" name="state_of_origin" data-profile-state
                                        class="{{ $selectClass }}">
                                         <option value="">Select state</option>
                                            @foreach ($states as $state)
                                            <option value="{{ $state->name }}"
                                                data-lga-url="{{ route('locations.states.local-government-areas', $state) }}"
                                                @selected($selectedState === $state->name)>
                                                {{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                   
                                </div>
                                @error('state_of_origin')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                           <div class="col-span-2 lg:col-span-1">
                                <label for="local_government_are" class="{{ $labelClass }}">
                                    Local Government Area @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <div class="relative">
                                    <select id="local_government_area" name="local_government_area" 
                                        data-profile-lga data-selected-lga="{{ $selectedLga }}"
                                        class="{{ $selectClass }}">
                                        <option value="">Select local government Area</option>
                                        @if ($selectedLga)
                                            <option value="{{ $selectedLga }}" selected>{{ $selectedLga }}</option>
                                        @endif
                                    </select>
                                   
                                </div>
                                @error('local_government_area')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                        

                            <div class="col-span-2 lg:col-span-1">
                                <label for="address" class="{{ $labelClass }}">
                                    Current Address @if ($requiresApplicantProfile)<span class="text-error-500">*</span>@endif
                                </label>
                                <textarea id="address" name="address" rows="4" maxlength="255" autocomplete="street-address"
                                    {{ $requiresApplicantProfile }} class="{{ $inputClass }}">{{ old('address', $user->address) }}</textarea>
                                @error('address')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>


                            
                        </div>
                    </div>

                </div>
                <div class="mt-6 flex items-center gap-3 px-2 lg:justify-end">
                    <button @click="isProfileInfoModal = false" type="button"
                        class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white sm:w-auto">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- END MODAL -->
    <div x-show="isChangePasswordModal"
        x-init="@if ($errors->updatePassword->isNotEmpty()) isChangePasswordModal = true @endif"
        class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-4 sm:p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isChangePasswordModal = false"
            class="no-scrollbar relative max-h-[calc(100vh-2rem)] w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 sm:p-6 lg:p-11 dark:bg-gray-900">
            <button type="button" @click="isChangePasswordModal = false"
                class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                        fill="" />
                </svg>
            </button>
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div x-show="isDeleteAccountModal"
        x-init="@if ($errors->userDeletion->isNotEmpty()) isDeleteAccountModal = true @endif"
        class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-4 sm:p-5">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
        <div @click.outside="isDeleteAccountModal = false"
            class="no-scrollbar relative max-h-[calc(100vh-2rem)] w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 sm:p-6 lg:p-11 dark:bg-gray-900">
            <button type="button" @click="isDeleteAccountModal = false"
                class="transition-color absolute top-5 right-5 z-999 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                        fill="" />
                </svg>
            </button>
            @include('profile.partials.delete-user-form')
        </div>
    </div>

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

                        lgaSelect.disabled = localGovernmentAreas.length === 0;
                    } catch (error) {
                        resetLgaOptions('Unable to load LGAs');
                        lgaSelect.disabled = true;
                        console.error(error);
                    }
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
                editButton?.addEventListener('click', () => {
                    editButton.classList.add('d-none');
                    profileActions?.classList.remove('d-none');
                    profileImageInput?.focus();
                });

                cancelButton?.addEventListener('click', () => {
                    resetToOriginalValues();
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
</x-layout>
