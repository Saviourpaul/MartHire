<x-auth-layout title="Confirm Password - MartHire" card-width="600px">
    <h1 class="auth-title">Confirm Password</h1>
    <p class="auth-description">
        This is a secure area of the application. Please confirm your password before proceeding.
    </p>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-auth-field name="password" label="Password" type="password" autocomplete="current-password" required help-text="Show password" minlength="8" />

        <button class="auth-primary-btn" type="submit">
            Confirm Password <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </button>
        
       </form>
</x-auth-layout>
