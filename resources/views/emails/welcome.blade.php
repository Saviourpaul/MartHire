@component('mail::message')
# Welcome aboard!

Your email is verified and your account is ready to go.

@component('mail::button', ['url' => $dashboardUrl])
Go to your dashboard
@endcomponent

If you have any questions, just reply to this email — we're happy to help.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
