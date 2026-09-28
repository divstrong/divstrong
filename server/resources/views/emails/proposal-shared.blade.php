{{--
    Shared from the proposal page itself, so the recipient is often not the client —
    a colleague, a procurement contact, someone being looped in. The copy stays neutral
    about who is reading, which is the one thing that separates it from proposal-sent.
--}}
@php
    $preparedFor = $proposal->client_company ?: $proposal->client_name;
    $showInvestment = $proposal->investment_enabled && $proposal->total > 0;
    $estimator = $proposal->estimator;
@endphp

<x-mail.layout
    :title="'Proposal: ' . $proposal->project_title"
    :preheader="'A proposal for ' . $proposal->project_title . ' has been shared with you.'"
    :footer="'This proposal was prepared for ' . $preparedFor . '.'"
>
    <x-mail.heading eyebrow="Proposal" :title="$proposal->project_title">
        <x-mail.text>
            A proposal has been prepared for {{ $preparedFor }} and shared with you for review. Scope,
            timeline{{ $showInvestment ? ', investment' : '' }} and next steps are all laid out in the interactive
            version below.
        </x-mail.text>
    </x-mail.heading>

    <x-mail.details :rows="[
        ['label' => 'Prepared for', 'value' => $preparedFor],
        $proposal->rfp_number ? ['label' => 'RFP', 'value' => $proposal->rfp_number] : null,
        $proposal->proposal_date ? ['label' => 'Date', 'value' => $proposal->proposal_date->format('F j, Y')] : null,
        $proposal->valid_until ? ['label' => 'Valid until', 'value' => $proposal->valid_until->format('F j, Y')] : null,
        $showInvestment ? ['label' => 'Investment', 'value' => '$' . number_format($proposal->total, 0)] : null,
    ]" />

    @if(!empty($notes))
        <x-mail.note :label="'A note' . ($estimator?->name ? ' from ' . trim(strtok($estimator->name, ' ')) : '')">{{ $notes }}</x-mail.note>
    @endif

    <x-mail.button :url="$url" :link="true">View The Proposal</x-mail.button>

    <x-mail.signoff :name="$estimator?->name ?? 'The divStrong Team'" :email="$estimator?->email">
        Questions about anything in here? Just reply to this email.
    </x-mail.signoff>
</x-mail.layout>
