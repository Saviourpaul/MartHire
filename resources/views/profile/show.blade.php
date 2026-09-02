<x-admin-layout title="Applicant Profile">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Applicant Profile</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
                        <img class="rounded-circle" src="{{ $user->profileImageUrl() }}" alt="Profile image" width="150" height="150" style="object-fit: cover;">
                        <div>
                            <h5 class="card-title mb-1">{{ $user->first_name }} {{ $user->last_name }}</h5>
                            <p class="mb-0">{{ $user->email }}</p>
                            <span class="badge bg-success-light">{{ $user->status->label() }}</span>
                            <span class="text-muted ms-2">This profile is read-only.</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Username:</strong> {{ $user->username }}</p>
                            <p><strong>Date of Birth:</strong> {{ $user->date_of_birth ? $user->date_of_birth->format('M d, Y') : 'Not provided' }}</p>
                            <p><strong>Phone:</strong> {{ $user->phone ?: 'Not provided' }}</p>
                            <p><strong>Address:</strong> {{ $user->address ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Nationality:</strong> {{ $user->nationality ?: 'Not provided' }}</p>
                            <p><strong>State of Origin:</strong> {{ $user->state_of_origin ?: 'Not provided' }}</p>
                            <p><strong>LGA:</strong> {{ $user->local_government_area ?: 'Not provided' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
