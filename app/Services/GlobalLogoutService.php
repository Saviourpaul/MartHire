<?php

namespace App\Services;

use App\Models\User;

class GlobalLogoutService
{
    public function __construct(private AccountLifecycleService $lifecycle) {}

    public function revoke(User $user): void
    {
        $this->lifecycle->logout($user);
    }
}
