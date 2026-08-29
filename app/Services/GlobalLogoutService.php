<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlobalLogoutService
{
    public function revoke(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        $this->deleteDatabaseSessions($user);
    }

    private function deleteDatabaseSessions(User $user): void
    {
        if (Config::get('session.driver') !== 'database') {
            return;
        }

        DB::connection(Config::get('session.connection'))
            ->table(Config::get('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
