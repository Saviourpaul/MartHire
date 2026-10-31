<?php

use App\Services\PhoneNumberService;

test('international phone normalization respects national formats and significant zeroes', function (string $country, string $number, string $e164, string $national) {
    $parts = (new PhoneNumberService)->normalize($number, $country);

    expect($parts['phone'])->toBe($e164)
        ->and($parts['phone_country'])->toBe($country)
        ->and($parts['phone_national_number'])->toBe($national);
})->with([
    ['US', '(202) 555-0123', '+12025550123', '2025550123'],
    ['CA', '(416) 555-0123', '+14165550123', '4165550123'],
    ['GB', '020 7946 0018', '+442079460018', '2079460018'],
    ['NG', '0803 123 4567', '+2348031234567', '8031234567'],
    ['IN', '09876543210', '+919876543210', '9876543210'],
    ['IT', '02 36618 300', '+390236618300', '0236618300'],
    ['FR', '06 12 34 56 78', '+33612345678', '612345678'],
    ['AU', '0412 345 678', '+61412345678', '412345678'],
]);

test('international phone validation rejects malformed numbers and mismatching countries', function (string $country, string $number) {
    expect(fn () => (new PhoneNumberService)->normalize($number, $country))->toThrow(InvalidArgumentException::class);
})->with([
    ['US', '123'], ['US', '0000000000'], ['NG', '00000000000'],
    ['US', '+44 20 7946 0018'], ['GB', 'Call +44 20 7946 0018'],
    ['US', '+12025550123 ext 42'], ['ZZ', '+12025550123'], ['CA', '+12025550123'], ['US', '+80012345678'],
]);

test('phone preparation derives split values and ignores submitted dialing metadata', function () {
    $parts = (new PhoneNumberService)->prepare([
        'phone' => '020 7946 0018', 'phone_country' => 'gb',
        'phone_dial_code' => '+999', 'phone_national_number' => 'fake',
    ]);

    expect($parts)->toBe([
        'phone' => '+442079460018', 'phone_country' => 'GB',
        'phone_dial_code' => '+44', 'phone_national_number' => '2079460018',
    ]);
});
