<x-mail::message>
# Reset your password

Hi {{ $user->first_name }},

We received a request to reset the password on your MartHire account.

<x-mail::button :url="$url">
Reset Password
</x-mail::button>

This link expires in **{{ $expires }} minutes**. If you didn't request a reset, you can safely ignore this email. Your password stays the same.

<x-mail::subcopy>
If the button doesn't work, copy or  click this link into your browser:
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
