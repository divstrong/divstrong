@php
    $approved = in_array($action, ['accepted', 'converted']);
    $signedBy = $proposal->tc_signature_name ?? $proposal->signature_name;
@endphp

<x-mail.layout
    :title="($approved ? 'Proposal Approved' : 'Proposal Declined') . ': ' . $proposal->project_title"
    :preheader="$proposal->client_name . ' ' . ($approved ? 'approved' : 'declined') . ' the proposal for ' . $proposal->project_title . '.'"
>
    <x-mail.status
        :tone="$approved ? 'success' : 'danger'"
        :symbol="$approved ? '&#10003;' : '&#10005;'"
        :title="$approved ? 'Proposal Approved' : 'Proposal Declined'"
    >
        {{ $proposal->client_name }} {{ $approved ? 'signed off on' : 'declined' }} {{ $proposal->project_title }}.
    </x-mail.status>

    <x-mail.details :rows="[
        ['label' => 'Project', 'value' => $proposal->project_title],
        ['label' => 'Client', 'value' => $proposal->client_name],
        $approved && $signedBy ? ['label' => 'Signed by', 'value' => $signedBy] : null,
        $approved && $proposal->accepted_at ? ['label' => 'Approved at', 'value' => $proposal->accepted_at->format('F j, Y g:i A')] : null,
    ]" />

    <x-mail.button :url="url('/admin')">View in Admin</x-mail.button>
</x-mail.layout>
