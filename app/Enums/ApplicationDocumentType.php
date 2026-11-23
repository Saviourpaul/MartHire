<?php

namespace App\Enums;

enum ApplicationDocumentType: string
{
    case NationalIdentityCard = 'national_identity_card';
    case InternationalPassport = 'international_passport';
    case DriversLicense = 'drivers_license';
    case VotersCard = 'voters_card';
    case OtherGovernmentId = 'other_government_id';
    case Resume = 'resume';
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
            self::OtherGovernmentId,
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
            self::NationalIdentityCard => 'National Identity Card',
            self::InternationalPassport => 'International Passport',
            self::DriversLicense => "Driver's License",
            self::VotersCard => "Voter's Card",
            self::OtherGovernmentId => 'Other government-issued ID',
            self::Resume => 'Résumé / CV',
            self::Education => 'Educational Qualification',
            self::LegacyIdentity => 'Legacy identity document',
        };
    }
}
