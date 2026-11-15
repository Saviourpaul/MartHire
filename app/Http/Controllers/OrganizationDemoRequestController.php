<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationDemoRequestRequest;
use App\Services\OrganizationDemoRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationDemoRequestController extends Controller
{
    public function create(): View
    {
        return view('organization-demo.create');
    }

    public function store(StoreOrganizationDemoRequestRequest $request, OrganizationDemoRequestService $demoRequests): RedirectResponse
    {
        $demoRequests->submit($request->validated());

        return to_route('organization-demo.create')
            ->with('success', 'Your demo request has been received. Our team will contact you soon.')
            ->with('demo-request-submitted', true);
    }
}
