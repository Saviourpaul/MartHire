<?php

namespace App\Http\Controllers;

use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private readonly LocationService $locations) {}

    public function states(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->states((string) $request->query('country', '')),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->cities(
                (string) $request->query('country', ''),
                (string) $request->query('state', ''),
            ),
        ]);
    }

    public function countries(): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->countries(),
        ]);
    }
}
