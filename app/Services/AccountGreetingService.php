<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AccountGreetingService
{
    public function __construct(private readonly UserPreferencesService $preferences) {}

    /** @return array{message: string, emoji: string} */
    public function forUser(User $user): array
    {
        $hour = Carbon::now($this->preferences->displayTimezone($user))->hour;
        [$greeting, $emoji] = match (true) {
            $hour >= 5 && $hour < 12 => [__('Good morning'), '☀️'],
            $hour >= 12 && $hour < 17 => [__('Good afternoon'), '🌤️'],
            $hour >= 17 && $hour < 21 => [__('Good evening'), '🏙️'],
            default => [__('Good night'), '🌙'],
        };

        $firstName = trim((string) ($user->getAttribute('first_name')
            ?: $user->getAttribute('firstname')
            ?: $user->getAttribute('firstName')));

        if ($firstName === '') {
            $name = trim((string) $user->getAttribute('name'));
            $firstName = $name !== '' ? Str::before($name, ' ') : (string) $user->email;
        }

        return [
            'message' => __(':greeting, :name', ['greeting' => $greeting, 'name' => $firstName]),
            'emoji' => $emoji,
        ];
    }
}
