<x-mail.layout
    title="New Appointment Request"
    :preheader="$name . ' requested a call on ' . \Carbon\Carbon::parse($date)->format('l, F j') . ' at ' . $time . ' EST.'"
>
    <x-mail.heading eyebrow="Appointment Request" :title="$name">
        <x-mail.text>A new appointment has been requested from the website.</x-mail.text>
    </x-mail.heading>

    <x-mail.details :rows="[
        ['label' => 'Email', 'value' => $email, 'url' => 'mailto:' . $email],
        ['label' => 'Project type', 'value' => $projectType],
        ['label' => 'Preferred date', 'value' => \Carbon\Carbon::parse($date)->format('l, F j, Y')],
        ['label' => 'Preferred time', 'value' => $time . ' EST'],
    ]" />

    @if($description)
        {{-- Their own words, kept intact rather than squeezed into a detail row. --}}
        <x-mail.note label="Project description">{{ $description }}</x-mail.note>
    @endif

    <x-mail.button :url="'mailto:' . $email">Reply to {{ $name }}</x-mail.button>

    <x-mail.signoff>
        Replying directly to this email reaches {{ $name }} too.
    </x-mail.signoff>
</x-mail.layout>
