<x-mail::message>
# Verify your email address

Hi <strong>{{ $user->first_name }}</strong>,

Welcome to MartHire. Confirm your email address to activate your account..

<x-mail::button :url="$url">
Verify Email Address
</x-mail::button>

This link expires in {{ $expires }} minutes. If you didn't create a MartHire account, you can safely ignore this email.

<x-mail::subcopy>
If the button doesn't work,  click on the link below to verify your email address:
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>