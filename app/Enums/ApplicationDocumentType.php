<?php

namespace App\Enums;

enum ApplicationDocumentType: string
{
    case NationalIdentityCard = 'national_identity_card';
    case InternationalPassport = 'international_passport';
    case DriversLicense = 'drivers_license';
    case VotersCard = 'voters_card';
    case Education = 'education';
    case LegacyIdentity = 'legacy_identity';

    /**
     * @return list<self>
     */
    public static function identityTypes(): array
    {
        return [
            self::NationalIdentityCard,
            self::InternationalPassport,
            self::DriversLicense,
            self::VotersCard,
        ];
    }

    public function isIdentityType(): bool
    {
        return in_array($this, self::identityTypes(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NationalIdentityCard => 'National Identity Card / NIN Slip',
            self::InternationalPassport => 'International Passport',
            self::DriversLicense => "Driver's License",
            self::VotersCard => "Voter's Card",
            self::Education => 'Educational Qualification',
            self::LegacyIdentity => 'Legacy identity document',
        };
    }
}
