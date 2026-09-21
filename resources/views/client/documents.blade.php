<x-admin-layout title="Documents">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Documents</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-16">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Uploaded Documents</h5>
                    <p class="text-muted">Track the review status of documents submitted with your job applications.</p>

                    <div class="table-responsive">
                        <table class="table table-center table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Application</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Uploaded</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td>
                                            <div>{{ $document->document_name }}</div>
                                            <div class="text-muted small">{{ $document->original_name }}</div>
                                        </td>
                                        <td>
                                            <a href="{{ route('client.applications.show', $document->applicationForm) }}">{{ $document->applicationForm->reference }}</a>
                                            <div class="text-muted small">{{ $document->applicationForm->job->title }}</div>
                                        </td>
                                        <td>Submitted</td>
                                        <td>{{ $document->employer_remarks ?: 'No remarks' }}</td>
                                        <td>{{ $document->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No documents available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $documents->links() }}
                    </div>
                </div>
            </div>
        </div>

        
    </div>
</x-admin-layout>
