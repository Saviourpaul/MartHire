<x-admin-layout title="Application Status">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Application Status</h3>
                <p class="text-muted mb-0">{{ $application->reference }} - {{ $application->job->title }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('client.jobs') }}" class="btn btn-outline-secondary">Back to Jobs</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 class="card-title mb-1">{{ $application->job->title }}</h5>
                            <p class="mb-1">{{ $application->job->company }}</p>
                            <p class="text-muted mb-0">Submitted {{ $application->submitted_at->format('M d, Y') }}</p>
                        </div>
                        <span class="badge {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span>
                    </div>

                    @if ($application->employer_remarks)
                        <div class="alert alert-info mt-3 mb-0">{{ $application->employer_remarks }}</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Document Review</h5>
                    <div class="table-responsive">
                        <table class="table table-center table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Status</th>
                                    
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($application->documents as $document)
                                    <tr>
                                        <td>{{ $document->document_name }}</td>
                                        <td><span class="badge {{ $document->status->badgeClass() }}">{{ $document->status->label() }}</span></td>
                                        <td>{{ $document->reviewed_at?->format('M d, Y') ?: 'Pending review' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile Information </h5>
                    <p><strong>Name:</strong> {{ $application->first_name }} {{ $application->last_name }}</p>
                    <p><strong>Email:</strong> {{ $application->email }}</p>
                    <p><strong>Phone:</strong> {{ $application->phone }}</p>
                    <p><strong>Nationality:</strong> {{ $application->nationality ?: 'Not provided' }}</p>
                    <p><strong>Origin:</strong> {{ collect([$application->local_government_area, $application->state_of_origin])->filter()->implode(', ') ?: 'Not provided' }}</p>
                    <p class="mb-0"><strong>Address:</strong> {{ $application->address ?: 'Not provided' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status History</h5>
                    <div class="list-group">
                        @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $history->to_status->label() }}</strong>
                                    <small>{{ $history->created_at->diffForHumans() }}</small>
                                </div>
                                @if ($history->remarks)
                                    <p class="mb-0">{{ $history->remarks }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-muted">No status changes yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
