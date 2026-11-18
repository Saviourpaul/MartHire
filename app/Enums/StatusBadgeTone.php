<?php

namespace App\Enums;

enum StatusBadgeTone: string
{
    case Success = 'success';
    case Processing = 'processing';
    case Pending = 'pending';
    case Danger = 'danger';
    case Neutral = 'neutral';

    public function chartColor(): string
    {
        return match ($this) {
            self::Success => '#15803d',
            self::Processing => '#0f766e',
            self::Pending => '#b45309',
            self::Danger => '#dc2626',
            self::Neutral => '#64748b',
        };
    }
}
