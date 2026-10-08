<?php

namespace App\Http\Controllers;

use App\Exceptions\LocationUnavailable;
use App\Services\WorldLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    public function __construct(private WorldLocationService $locations) {}

    public function countries(): JsonResponse
    {
        return $this->respond(fn () => $this->locations->countries());
    }

    public function states(Request $request): JsonResponse
    {
        return $this->respond(function () use ($request) {
            $data = $request->validate(['country' => ['required', 'string', 'regex:/^[A-Z]{2}$/D']]);

            return $this->locations->states($data['country']);
        });
    }

    public function cities(Request $request): JsonResponse
    {
        return $this->respond(function () use ($request) {
            $data = $request->validate(['country' => ['required', 'string', 'regex:/^[A-Z]{2}$/D'], 'state' => ['required', 'string', 'max:255']]);

            return $this->locations->cities($data['country'], $data['state']);
        });
    }

    private function respond(callable $resolve): JsonResponse
    {
        try {
            return new JsonResponse($this->locations->publicResult($resolve()));
        } catch (LocationUnavailable $exception) {
            return new JsonResponse(['message' => $exception->getMessage(), 'meta' => ['available' => false]], 503);
        } catch (ValidationException $exception) {
            return new JsonResponse(['message' => 'Please select a valid location.', 'errors' => $exception->errors()], 422);
        }
    }
}
