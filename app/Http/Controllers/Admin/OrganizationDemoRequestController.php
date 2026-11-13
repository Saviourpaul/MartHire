<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationDemoRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOrganizationDemoInformationRequest;
use App\Models\OrganizationDemoRequest;
use App\Services\OrganizationDemoRequestService;
use App\Support\LiveTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrganizationDemoRequestController extends Controller
{
    public function index(Request $request): View|JsonResponse|Response
    {
        $status = OrganizationDemoRequestStatus::tryFrom((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));
        $query = OrganizationDemoRequest::query()
            ->with(['assignedAdmin:id,first_name,last_name'])
            ->when($status, fn ($builder) => $builder->where('status', $status->value))
            ->when($search !== '', function ($builder) use ($search): void {
                $term = '%'.$search.'%';
                $builder->where(function ($where) use ($term): void {
                    $where->where('company_name', 'like', $term)
                        ->orWhere('business_email', 'like', $term)
                        ->orWhere('contact_first_name', 'like', $term)
                        ->orWhere('contact_last_name', 'like', $term);
                });
            })
            ->orderByDesc('created_at');
        $requests = LiveTables::paginate($query, 15);
        $view = view('admin.demo-requests.index', [
            'requests' => $requests,
            'statuses' => OrganizationDemoRequestStatus::cases(),
            'statusFilter' => $status?->value ?? '',
            'search' => $search,
        ]);
        $response = LiveTables::view($request, $view, ['organization-demo-requests']);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return response()->view($response->name(), $response->getData())
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function show(OrganizationDemoRequest $demoRequest): Response
    {
        return response()->view('admin.demo-requests.show', [
            'demoRequest' => $demoRequest->load(['assignedAdmin', 'reviewedBy', 'convertedUser', 'convertedOrganization', 'possibleDuplicateOf', 'events.actor']),
            'guidedDemoBookingConfigured' => filter_var((string) config('organization_demo.booking_url'), FILTER_VALIDATE_URL)
                && parse_url((string) config('organization_demo.booking_url'), PHP_URL_SCHEME) === 'https',
        ])->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function claim(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $service->claim($demoRequest, $request->user());

        return LiveTables::mutation($request, 'Demo request assigned to you.', back()->with('success', 'Demo request assigned to you.'), ['demo-requests']);
    }

    public function note(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:4000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/']]);
        $service->addNote($demoRequest, $request->user(), $data['note']);

        return LiveTables::mutation($request, 'Internal note saved.', back()->with('success', 'Internal note saved.'), ['demo-requests']);
    }

    public function guidedDemo(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $service->offerGuidedDemo($demoRequest, $request->user());

        return LiveTables::mutation($request, 'Guided demo booking link sent.', back()->with('success', 'Guided demo booking link sent.'), ['demo-requests']);
    }

    public function requestInformation(RequestOrganizationDemoInformationRequest $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $service->requestInformation($demoRequest, $request->user(), $request->validated('message'));

        return LiveTables::mutation($request, 'Information request emailed to the contact.', back()->with('success', 'Information request emailed to the contact.'), ['demo-requests']);
    }

    public function approve(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $sent = $service->approve($demoRequest, $request->user());
        $message = $sent
            ? 'Request approved and setup invitation sent.'
            : 'Request approved, but the setup email could not be sent. Retry the invitation from this request.';
        $type = $sent ? 'success' : 'warning';

        return LiveTables::mutation($request, $message, back()->with($type, $message), ['demo-requests'], $type);
    }

    public function reject(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/']]);
        $service->reject($demoRequest, $request->user(), $data['reason']);

        return LiveTables::mutation($request, 'Demo request rejected.', back()->with('success', 'Demo request rejected.'), ['demo-requests']);
    }

    public function duplicate(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/']]);
        $service->markDuplicate($demoRequest, $request->user(), $data['note'] ?? null);

        return LiveTables::mutation($request, 'Demo request marked as a duplicate.', back()->with('success', 'Demo request marked as a duplicate.'), ['demo-requests']);
    }

    public function resend(Request $request, OrganizationDemoRequest $demoRequest, OrganizationDemoRequestService $service): RedirectResponse|JsonResponse
    {
        $sent = $service->resendInvitation($demoRequest, $request->user());
        $message = $sent ? 'Setup invitation sent again.' : 'The invitation could not be sent. Try again later.';
        $type = $sent ? 'success' : 'warning';

        return LiveTables::mutation($request, $message, back()->with($type, $message), ['demo-requests'], $type);
    }
}
