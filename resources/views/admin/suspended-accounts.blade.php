<x-layout>
    <div class="px-5 py-4 sm:px-6 sm:py-5">
        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
            {{ __('Applicants') }}
        </h3>
    </div>
    <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                x-init="setTimeout(() => show = false, 5000)"
                class="mb-6 flex items-center gap-3 rounded-lg border border-success-500/30 bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                <div class="-mt-0.5 text-success-500 shrink-0">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.70186 12.0001C3.70186 7.41711 7.41711 3.70186 12.0001 3.70186C16.5831 3.70186 20.2984 7.41711 20.2984 12.0001C20.2984 16.5831 16.5831 20.2984 12.0001 20.2984C7.41711 20.2984 3.70186 16.5831 3.70186 12.0001ZM12.0001 1.90186C6.423 1.90186 1.90186 6.423 1.90186 12.0001C1.90186 17.5772 6.423 22.0984 12.0001 22.0984C17.5772 22.0984 22.0984 17.5772 22.0984 12.0001C22.0984 6.423 17.5772 1.90186 12.0001 1.90186ZM15.6197 10.7395C15.9712 10.388 15.9712 9.81819 15.6197 9.46672C15.2683 9.11525 14.6984 9.11525 14.347 9.46672L11.1894 12.6243L9.6533 11.0883C9.30183 10.7368 8.73198 10.7368 8.38051 11.0883C8.02904 11.4397 8.02904 12.0096 8.38051 12.3611L10.553 14.5335C10.7217 14.7023 10.9507 14.7971 11.1894 14.7971C11.428 14.7971 11.657 14.7023 11.8257 14.5335L15.6197 10.7395Z" fill=""></path>
                    </svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                x-init="setTimeout(() => show = false, 5000)"
                class="mb-6 flex items-center gap-3 rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                <div class="-mt-0.5 text-error-500 shrink-0">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M20.3499 12.0004C20.3499 16.612 16.6115 20.3504 11.9999 20.3504C7.38832 20.3504 3.6499 12.0004C3.6499 7.38881 7.38833 3.65039 11.9999 3.65039C16.6115 3.65039 20.3499 7.38881 20.3499 12.0004ZM11.9999 22.1504C17.6056 22.1504 22.1499 17.6061 22.1499 12.0004C22.1499 6.3947 17.6056 1.85039 11.9999 1.85039C6.39421 1.85039 1.8499 6.3947 1.8499 12.0004C1.8499 17.6061 6.39421 22.1504 11.9999 22.1504ZM13.0008 16.4753C13.0008 15.923 12.5531 15.4753 12.0008 15.4753L11.9998 15.4753C11.4475 15.4753 10.9998 15.923 10.9998 16.4753C10.9998 17.0276 11.4475 17.4753 11.9998 17.4753L12.0008 17.4753C12.5531 17.4753 13.0008 17.0276 13.0008 16.4753ZM11.9998 6.62898C12.414 6.62898 12.7498 6.96476 12.7498 7.37898L12.7498 13.0555C12.7498 13.4697 12.414 13.8055 11.2498 13.8055C11.5856 13.8055 11.2498 13.4697 11.2498 13.0555L11.2498 7.37898C11.2498 6.96476 11.5856 6.62898 11.9998 6.62898Z" fill="#F04438"></path>
                    </svg>
                </div>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        <!-- DataTable -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-4 flex flex-col gap-2 px-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-gray-500 dark:text-gray-400">Show</span>
                    <form method="GET" action="{{ url()->current() }}" class="relative flex items-center">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="role" value="{{ request('role') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <select
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-9 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none py-2 pr-8 pl-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            onchange="this.form.submit()"
                            name="per_page">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="absolute top-1/2 right-2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </form>
                    <span class="text-gray-500 dark:text-gray-400">entries</span>
                </div>

                <form method="GET" action="{{ url()->current() }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <button type="submit" class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C11.2676 15.7053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053Z" fill="" />
                            </svg>
                        </button>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <select name="role" onchange="this.form.submit()" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" {{ request('role') == $role->value ? 'selected' : '' }}>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" onchange="this.form.submit()" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="shadow-theme-xs flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        Filter
                    </button>
                </form>
            </div>

            <div class="max-w-full overflow-x-auto">
                <div class="min-w-[1102px]">
                    <!-- table header start -->
                    <div class="grid grid-cols-12 border-t border-gray-200 dark:border-gray-800">
                    <div class="col-span-3 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">User</p>
                    </div>
                    <div class="col-span-2 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Email</p>
                    </div>
                    <div class="col-span-2 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Phone</p>
                    </div>
                    <div class="col-span-1 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Role</p>
                    </div>
                    <div class="col-span-1 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Status</p>
                    </div>
                    <div class="col-span-1 flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Registered</p>
                    </div>
                   
                    <div class="col-span-1 flex items-center px-4 py-3">
                        <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">Action</p>
                    </div>
                </div>
                    <!-- table header end -->

                    <!-- table body start -->
                    @forelse ($users as $user)
                        <div class="grid grid-cols-12 border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                            <div class="col-span-3 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <div class="flex gap-3">
                                    <div class="h-10 w-10 overflow-hidden rounded-full">
                                        <img src="{{ $user->profileImageUrl() }}" alt="{{ $user->first_name }}" class="h-full w-full object-cover" />
                                    </div>
                                    <div>
                                        <p class="text-theme-sm block font-medium text-gray-800 dark:text-white/90">
                                           {{ $user->first_name }} {{ $user->last_name }}
                                        </p>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-2 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400 truncate" title="{{ $user->email }}">{{ $user->email }}</p>
                            </div>
                            <div class="col-span-2 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400 truncate" title="{{ $user->phone ?? '' }}">{{ $user->phone_number ?? 'Not provided' }}</p>
                            </div>
                            <div class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400">{{ $user->role->label() }}</p>
                            </div>
                            <div class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-xs rounded-full px-2 py-0.5 font-medium {{ $user->status->label() }}">
                                    {{ $user->status->label() }}
                                </p>
                            </div>
                            <div class="col-span-1 flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400">
                                    {{ $user->created_at->format('M j, Y') }}
                                </p>
                            </div>
                            
                            <div class="col-span-1 flex items-center gap-2 px-4 py-3">
                                <button
                                    x-on:click="$dispatch('open-modal', 'view-user-{{ $user->id }}')"
                                    class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.37533 3.04199C5.04926 3.04199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053ZM9.37533 15.7053C6.3403 15.7053 3.91699 13.2753 3.91699 9.37363C3.91699 5.47193 6.3403 3.04199 9.37533 3.04199C12.4104 3.04199 14.8337 5.47193 14.8337 9.37363C14.8337 13.2753 12.4104 15.7053 9.37533 15.7053ZM9.37533 7.29199C8.57107 7.29199 7.91699 7.94607 7.91699 8.75033C7.91699 9.55459 8.57107 10.2087 9.37533 10.2087C10.1796 10.2087 10.8337 9.55459 10.8337 8.75033C10.8337 7.94607 10.1796 7.29199 9.37533 7.29199ZM7.91699 8.75033C7.91699 7.94607 8.57107 7.29199 9.37533 7.29199C10.1796 7.29199 10.8337 7.94607 10.8337 8.75033C10.8337 9.55459 10.1796 10.2087 9.37533 10.2087C8.57107 10.2087 7.91699 9.55459 7.91699 8.75033Z" fill="" />
                                    </svg>
                                </button>
                                <button
                                    x-on:click="$dispatch('open-modal', 'edit-user-{{ $user->id }}')"
                                    class="text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.0911 3.53206C16.2124 2.65338 14.7878 2.65338 13.9091 3.53206L5.6074 11.8337C5.29899 12.1421 5.08687 12.5335 4.99684 12.9603L4.26177 16.445C4.20943 16.6931 4.286 16.9508 4.46529 17.1301C4.64458 17.3094 4.90232 17.3859 5.15042 17.3336L8.63507 16.5985C9.06184 16.5085 9.45324 16.2964 9.76165 15.988L18.0633 7.68631C18.942 6.80763 18.942 5.38301 18.0633 4.50433L17.0911 3.53206ZM14.9697 4.59272C15.2626 4.29982 15.7375 4.29982 16.0304 4.59272L17.0027 5.56499C17.2956 5.85788 17.2956 6.33276 17.0027 6.62565L16.1043 7.52402L14.0714 5.49109L14.9697 4.59272ZM13.0107 6.55175L6.66806 12.8944C6.56526 12.9972 6.49455 13.1277 6.46454 13.2699L5.96704 15.6283L8.32547 15.1308C8.46772 15.1008 8.59819 15.0301 8.70099 14.9273L15.0436 8.58468L13.0107 6.55175Z" fill="" />
                                    </svg>
                                </button>
                                <button
                                    x-on:click="$dispatch('open-modal', 'delete-user-{{ $user->id }}')"
                                    class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-500">
                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.04142 4.29199C7.04142 3.04935 8.04878 2.04199 9.29142 2.04199H11.7081C12.9507 2.04199 13.9581 3.04935 13.9581 4.29199V4.54199H16.1252H17.166C17.5802 4.54199 17.916 4.87778 17.916 5.29199C17.916 5.70621 17.5802 6.04199 17.166 6.04199H16.8752V8.74687V13.7469V16.7087C16.8752 17.9513 15.8678 18.9587 14.6252 18.9587H6.37516C5.13252 18.9587 4.12516 17.9513 4.12516 16.7087V13.7469V8.74687V6.04199H3.8335C3.41928 6.04199 3.0835 5.70621 3.0835 5.29199C3.0835 4.87778 3.41928 4.54199 3.8335 4.54199H4.87516H7.04142V4.29199ZM15.3752 13.7469V8.74687V6.04199H13.9581H13.2081H7.79142H7.04142H5.62516V8.74687V13.7469V16.7087C5.62516 17.1229 5.96095 17.4587 6.37516 17.4587H14.6252C15.0394 17.4587 15.3752 17.1229 15.3752 16.7087V13.7469ZM8.54142 4.54199H12.4581V4.29199C12.4581 3.87778 12.1223 3.54199 11.7081 3.54199H9.29142C8.87721 3.54199 8.54142 3.87778 8.54142 4.29199V4.54199ZM8.8335 8.50033C9.24771 8.50033 9.5835 8.83611 9.5835 9.25033V14.2503C9.5835 14.6645 9.24771 15.0003 8.8335 15.0003C8.41928 15.0003 8.0835 14.6645 8.0835 14.2503V9.25033C8.0835 8.83611 8.41928 8.50033 8.8335 8.50033ZM12.9168 9.25033C12.9168 8.83611 12.581 8.50033 12.1668 8.50033C11.7526 8.50033 11.4168 8.83611 11.4168 9.25033V14.2503C11.4168 14.6645 11.7526 15.0003 12.1668 15.0003C12.581 15.0003 12.9168 14.6645 12.9168 14.2503V9.25033Z" fill="" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- View User Modal -->
                        <x-modal name="view-user-{{ $user->id }}" :show="false" focusable>
                            @include('admin.partials.user-show', ['user' => $user])
                        </x-modal>

                        <!-- Edit User Modal -->
                        <x-modal name="edit-user-{{ $user->id }}" :show="false" focusable>
                            @include('admin.partials.user-edit', ['user' => $user, 'roles' => $roles, 'statuses' => $statuses])
                        </x-modal>

                        <!-- Delete User Modal -->
                        <x-modal name="delete-user-{{ $user->id }}" :show="false" focusable>
                            <div class="p-6">
                                <div class="px-2 pr-14">
                                    <h2 class="text-lg font-medium text-gray-900">Delete User</h2>
                                    <p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p>
                                </div>

                                <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="mt-6">
                                    @csrf
                                    @method('delete')

                                    <p class="text-sm text-gray-600">
                                        Are you sure you want to delete <strong>{{ $user->full_name }}</strong>? Once deleted, all of the user's resources and data will be permanently removed.
                                    </p>

                                    <div class="mt-6 flex items-center justify-end gap-3">
                                        <x-secondary-button type="button" @click="show = false">
                                            Cancel
                                        </x-secondary-button>
                                        <x-danger-button type="submit" style="color:black; background-color:#e53e3e;">
                                            Delete User
                                        </x-danger-button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                    @empty
                        <div class="border-t border-gray-100 px-5 py-12 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            No users found matching the current filters.
                        </div>
                    @endforelse
                    <!-- table body end -->
                </div>
            </div>

            <!-- Pagination Controls -->
            <div class="border-t border-gray-100 py-4 pr-4 pl-[18px] dark:border-gray-800">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
                    <p
                        class="border-b border-gray-100 pb-3 text-center text-sm font-medium text-gray-500 xl:border-b-0 xl:pb-0 xl:text-left dark:border-gray-800 dark:text-gray-400">
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of
                        {{ $users->total() }} entries
                    </p>
                    <div class="flex items-center justify-center gap-0.5 pt-3 xl:justify-end xl:pt-0">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
