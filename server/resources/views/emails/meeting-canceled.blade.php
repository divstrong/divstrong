@php
    $tz = $forHost ? config('scheduling.timezone') : $meeting->timezone;
@endphp

<x-mail.layout
    title="Call canceled"
    :preheader="'The call on ' . $meeting->readableTime($tz) . ' has been canceled.'"
>
    <x-mail.status tone="warning" symbol="&#10005;" title="Call canceled">
        {{ $meeting->readableTime($tz) }}
    </x-mail.status>

    <x-mail.details :rows="[
        ['label' => 'Was', 'value' => $meeting->readableTime($tz)],
        ['label' => $forHost ? 'With' : 'Booked by', 'value' => $meeting->name . ($meeting->company ? ' · ' . $meeting->company : '')],
        $forHost ? ['label' => 'Email', 'value' => $meeting->email, 'url' => 'mailto:' . $meeting->email] : null,
    ]" />

    @if($forHost)
        <x-mail.signoff>
            The time is free again. The attached update clears it from your calendar.
        </x-mail.signoff>
    @else
        <x-mail.text :space="28">
            The time has been released. If you would still like to talk, you can pick another
            slot whenever suits.
        </x-mail.text>

        <x-mail.button :url="url('/book')">Pick a new time</x-mail.button>

        <x-mail.signoff :name="$host['name']" :email="$host['email']">
            No trouble at all — reply to this email any time.
        </x-mail.signoff>
    @endif
</x-mail.layout>
