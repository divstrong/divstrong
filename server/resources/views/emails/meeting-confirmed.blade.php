<x-mail.layout
    title="Your call with divStrong"
    :preheader="'Confirmed for ' . $meeting->readableTime() . '.'"
>
    <x-mail.status tone="success" symbol="&#10003;" title="You're on the calendar">
        {{ $meeting->readableTime() }}
    </x-mail.status>

    <x-mail.details :rows="[
        ['label' => 'When', 'value' => $meeting->readableTime(), 'accent' => true],
        ['label' => 'How', 'value' => config('scheduling.location')],
        ['label' => 'With', 'value' => $host['name'] . ' · divStrong'],
        $meeting->phone ? ['label' => 'We will call', 'value' => $meeting->phone] : null,
    ]" />

    <x-mail.text :space="28">
        The invitation is attached — opening it will drop the call straight into your calendar.
        If something changes, you can release the time with the link below and pick another.
    </x-mail.text>

    <x-mail.button :url="$meeting->cancelUrl()">Reschedule or cancel</x-mail.button>

    <x-mail.signoff :name="$host['name']" :email="$host['email']">
        Looking forward to it. Reply to this email if anything comes up beforehand.
    </x-mail.signoff>
</x-mail.layout>
