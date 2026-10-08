<?php

namespace App\Http\Requests\Concerns;

use App\Exceptions\LocationUnavailable;
use App\Services\WorldLocationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesLocationSelection
{
    /** @var array{country_code: string|null, country: string|null, state: string|null, city: string|null}|array{} */
    private array $confirmedLocation = [];

    /** @return array{country_code: string|null, country: string|null, state: string|null, city: string|null}|array{} */
    public function locationSelection(): array
    {
        return $this->confirmedLocation;
    }

    protected function validateLocation(Validator $validator, bool $required = true): void
    {
        foreach (['country_code', 'state', 'city'] as $field) {
            if ($validator->errors()->has($field)) {
                return;
            }
        }
        if (! $required && ! $this->filled('country_code') && ! $this->filled('state') && ! $this->filled('city')) {
            if ($this->has('country_code')) {
                $this->confirmedLocation = ['country_code' => null, 'country' => null, 'state' => null, 'city' => null];
            }

            return;
        }
        try {
            $this->confirmedLocation = app(WorldLocationService::class)->validateSelection(
                (string) $this->input('country_code'), $this->input('state'), $this->input('city')
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        } catch (LocationUnavailable $exception) {
            $validator->errors()->add('country_code', $exception->getMessage());
        }
    }
}
