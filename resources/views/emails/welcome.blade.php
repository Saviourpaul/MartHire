<x-mail::message>

# Welcome to NextHire

Hello {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->username }},

Your account has been successfully created.

<x-mail::button :url="url('/login')">
Login
</x-mail::button>

Thank you for choosing NextHire.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>