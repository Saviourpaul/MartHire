<x-mail::message>
# Welcome to MartHire, <small>{{ $user->first_name }}</small>

Your email Has been verified. Here is what to do next:

- Browse open vacancies
- Apply and track your application status in one place

<x-mail::button :url="$dashboardUrl">
Go to My Dashboard
</x-mail::button>

Thanks,<br>
The MartHire Team

</x-mail::message>