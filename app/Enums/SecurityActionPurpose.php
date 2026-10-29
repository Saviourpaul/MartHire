<?php

namespace App\Enums;

enum SecurityActionPurpose: string
{
    case PasswordChange = 'password_change';
    case SessionsRevokeOthers = 'sessions_revoke_others';
    case AccountClose = 'account_close';

    public function label(): string
    {
        return match ($this) {
            self::PasswordChange => 'change your password',
            self::SessionsRevokeOthers => 'sign out other devices',
            self::AccountClose => 'close your account',
        };
    }
}
