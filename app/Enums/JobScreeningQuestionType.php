<?php

namespace App\Enums;

enum JobScreeningQuestionType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case SingleSelect = 'single_select';
    case MultiSelect = 'multi_select';
    case YesNo = 'yes_no';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::ShortText->value => __('Short text'),
            self::LongText->value => __('Long text'),
            self::SingleSelect->value => __('Single selection'),
            self::MultiSelect->value => __('Multiple selection'),
            self::YesNo->value => __('Yes / No'),
        ];
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::SingleSelect, self::MultiSelect], true);
    }
}
