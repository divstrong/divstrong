<x-mail.layout
    :title="'You\'re invited to ' . $companyName"
    :preheader="'Set your password and activate your ' . $companyName . ' account.'"
>
    <x-mail.heading eyebrow="Invitation" :title="'Welcome to ' . $companyName">
        <x-mail.text>Hello {{ $user->name }},</x-mail.text>
        <x-mail.text :space="12">
            You've been invited to join {{ $companyName }}. Use the button below to set your password and activate
            your account.
        </x-mail.text>
    </x-mail.heading>

    @if($notes)
        <x-mail.note label="A note for you" :space="28">{{ $notes }}</x-mail.note>
    @endif

    <x-mail.button :url="$inviteUrl" :link="true">Get Started</x-mail.button>

    <x-mail.signoff>
        This invitation is tied to {{ $user->email }}. If you weren't expecting it, you can ignore this email.
    </x-mail.signoff>
</x-mail.layout>
