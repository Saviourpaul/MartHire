<x-admin-layout title="Notifications">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Notifications</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Recent Notifications</h5>

                    <div class="list-group">
                        @forelse ($notifications as $notification)
                            <a class="list-group-item list-group-item-action" href="{{ $notification->data['action_url'] ?? route('client.jobs') }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $notification->data['title'] ?? 'Application update' }}</h6>
                                    <small>{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">{{ $notification->data['message'] ?? 'Your application status changed.' }}</p>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">No notifications yet.</div>
                        @endforelse
                    </div>

                    <div class="mt-3">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
