<div class="invite-card">
    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="invite-logo" />

    @if($invalid)
        <h1 class="invite-heading">Invalid Invite</h1>
        <p class="invite-invalid" style="margin-top: 1rem;">
            This invite link is invalid or has already been used.
        </p>
        <p class="invite-invalid" style="margin-top: 1rem;">
            <a href="{{ url('/admin/login') }}">Go to Sign In</a>
        </p>
    @else
        <h1 class="invite-heading">Welcome, {{ $user->name }}!</h1>
        <p class="invite-sub">Set a password to activate your account.</p>

        <form wire:submit="accept">
            <div class="invite-field">
                <label class="invite-label" for="password">Password</label>
                <input type="password"
                       id="password"
                       wire:model="password"
                       class="invite-input"
                       autocomplete="new-password" />
                @error('password')
                    <p class="invite-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="invite-field">
                <label class="invite-label" for="password_confirmation">Confirm Password</label>
                <input type="password"
                       id="password_confirmation"
                       wire:model="password_confirmation"
                       class="invite-input"
                       autocomplete="new-password" />
            </div>

            <button type="submit" class="invite-btn">
                Set Password & Sign In
            </button>
        </form>
    @endif
</div>
