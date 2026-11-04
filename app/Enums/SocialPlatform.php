<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook_url';
    case X = 'x_url';
    case Instagram = 'instagram_url';
    case LinkedIn = 'linkedin_url';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook', self::X => 'X', self::Instagram => 'Instagram',
            self::LinkedIn => 'LinkedIn',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Facebook => 'fa-facebook-f', self::X => 'fa-twitter', self::Instagram => 'fa-instagram',
            self::LinkedIn => 'fa-linkedin-in',
        };
    }
}
