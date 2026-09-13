<x-mail::message>

# Welcome to MartHire

Hello {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->username }},

Your account has been successfully created.

<x-mail::button :url="url('/login')">
Login
</x-mail::button>

Thank you for choosing MartHire.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>