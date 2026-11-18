@component('mail::message')
# {{ __('Reset your password') }}

{{ __('Hello :name,', ['name' => $user->first_name ?: $user->name]) }}

{{ __('We received a request to reset the password for your :app account.', ['app' => config('app.name')]) }}

@component('mail::button', ['url' => $url])
{{ __('Reset password') }}
@endcomponent

{{ __('This link expires in :minutes minutes and can only be used once.', ['minutes' => $expires]) }}

{{ __('If you did not request a password reset, you can ignore this email. Your password will remain unchanged.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endcomponent
