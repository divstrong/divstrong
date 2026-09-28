@php
    $firstName = trim(strtok((string) $proposal->client_name, ' ')) ?: $proposal->client_name;
    $preparedFor = $proposal->client_company ?: $proposal->client_name;
    $showInvestment = $proposal->investment_enabled && $proposal->total > 0;
    $estimator = $proposal->estimator;
@endphp

<x-mail.layout
    :title="'Proposal: ' . $proposal->project_title"
    :preheader="'Your proposal for ' . $proposal->project_title . ' is ready to review.'"
    :footer="'This proposal was prepared for ' . $preparedFor . '.'"
>
    <x-mail.heading eyebrow="Proposal" :title="$proposal->project_title">
        <x-mail.text>Hi {{ $firstName }},</x-mail.text>
        <x-mail.text :space="12">
            We've put together a proposal for your review. Scope, timeline{{ $showInvestment ? ', investment' : '' }} and
            next steps are all laid out in the interactive version below — you can read it in the browser and accept it
            right from the page.
        </x-mail.text>
    </x-mail.heading>

    <x-mail.details :rows="[
        ['label' => 'Prepared for', 'value' => $preparedFor],
        $proposal->rfp_number ? ['label' => 'RFP', 'value' => $proposal->rfp_number] : null,
        $proposal->proposal_date ? ['label' => 'Date', 'value' => $proposal->proposal_date->format('F j, Y')] : null,
        $proposal->valid_until ? ['label' => 'Valid until', 'value' => $proposal->valid_until->format('F j, Y')] : null,
        $showInvestment ? ['label' => 'Investment', 'value' => '$' . number_format($proposal->total, 0)] : null,
    ]" />

    @if(!empty($note))
        <x-mail.note :label="'A note' . ($estimator?->name ? ' from ' . trim(strtok($estimator->name, ' ')) : '')">{{ $note }}</x-mail.note>
    @endif

    <x-mail.button :url="$url" :link="true">View Your Proposal</x-mail.button>

    <x-mail.signoff :name="$estimator?->name ?? 'The divStrong Team'" :email="$estimator?->email">
        Questions, or want something adjusted? Just reply to this email.
    </x-mail.signoff>
</x-mail.layout>
