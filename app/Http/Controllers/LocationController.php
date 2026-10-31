<?php

namespace App\Http\Controllers;

use App\Exceptions\LocationDataUnavailableException;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    public function __construct(private readonly LocationService $locations) {}

    public function states(Request $request): JsonResponse
    {
        $input = $request->validate(['country' => ['required', 'string', 'regex:/^[a-zA-Z]{2}$/D']]);

        try {
            $this->validateCountry($input['country']);

            return response()->json($this->locations->stateOptions($input['country']));
        } catch (LocationDataUnavailableException) {
            return $this->unavailable();
        }
    }

    public function cities(Request $request): JsonResponse
    {
        $input = $request->validate([
            'country' => ['required', 'string', 'regex:/^[a-zA-Z]{2}$/D'],
            'state' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->validateCountry($input['country']);
            if (! $this->locations->stateIsValid($input['country'], $input['state'])) {
                throw ValidationException::withMessages(['state' => 'Select a state that belongs to the selected country.']);
            }

            return response()->json($this->locations->cityOptions($input['country'], $input['state']));
        } catch (LocationDataUnavailableException) {
            return $this->unavailable();
        }
    }

    public function countries(): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->countries(),
        ]);
    }

    private function validateCountry(string $iso2): void
    {
        if (! $this->locations->countryIsValid($iso2)) {
            throw ValidationException::withMessages(['country' => 'Select a valid country.']);
        }
    }

    private function unavailable(): JsonResponse
    {
        return response()->json(['message' => 'Location data is temporarily unavailable. Please try again.'], 503);
    }
}
