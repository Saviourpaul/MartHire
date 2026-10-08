<?php

namespace App\Http\Controllers;

use App\Models\NigeriaState;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function localGovernmentAreas(NigeriaState $nigeriaState): JsonResponse
    {
        $localGovernmentAreas = $nigeriaState
            ->localGovernmentAreas()
            ->ordered()
            ->get(['id', 'name', 'slug'])
            ->map(fn ($localGovernmentArea): array => [
                'id' => $localGovernmentArea->id,
                'name' => $localGovernmentArea->name,
                'slug' => $localGovernmentArea->slug,
            ])
            ->values();

        return response()->json([
            'data' => $localGovernmentAreas,
        ]);
    }
}
