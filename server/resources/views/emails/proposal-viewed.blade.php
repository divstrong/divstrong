<x-mail.layout
    :title="'Proposal Viewed: ' . $proposal->project_title"
    :preheader="$proposal->client_name . ' just opened the proposal for ' . $proposal->project_title . '.'"
>
    <x-mail.status tone="info" symbol="&#9673;" title="Proposal Viewed">
        {{ $proposal->client_name }} has opened your proposal for the first time.
    </x-mail.status>

    <x-mail.details :rows="[
        ['label' => 'Project', 'value' => $proposal->project_title],
        ['label' => 'Client', 'value' => $proposal->client_name],
        ['label' => 'Viewed at', 'value' => now()->format('F j, Y g:i A')],
    ]" />

    <x-mail.button :url="url('/admin')">View in Admin</x-mail.button>
</x-mail.layout>
