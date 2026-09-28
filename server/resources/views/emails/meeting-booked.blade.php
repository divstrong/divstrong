@php
    $hostTime = $meeting->starts_at->copy()->setTimezone(config('scheduling.timezone'));
@endphp

<x-mail.layout
    title="Call booked"
    :preheader="($meeting->company ?: $meeting->name) . ' booked a call for ' . $hostTime->format('D M j, g:i A') . '.'"
>
    <x-mail.heading eyebrow="New booking" :title="$meeting->company ?: $meeting->name">
        <x-mail.text>
            {{ $meeting->name }} just booked a call from
            {{ $meeting->source === 'preview' ? 'their preview page' : 'the booking page' }}.
        </x-mail.text>
    </x-mail.heading>

    <x-mail.details :rows="[
        ['label' => 'When', 'value' => $hostTime->format('l, F j · g:i A') . ' ' . $hostTime->format('T'), 'accent' => true],
        ['label' => 'Their time', 'value' => $meeting->readableTime()],
        ['label' => 'Name', 'value' => $meeting->name],
        ['label' => 'Email', 'value' => $meeting->email, 'url' => 'mailto:' . $meeting->email],
        $meeting->phone ? ['label' => 'Phone', 'value' => $meeting->phone, 'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $meeting->phone)] : null,
        $meeting->company ? ['label' => 'Company', 'value' => $meeting->company] : null,
        $meeting->prospect?->preview_url ? ['label' => 'Their preview', 'value' => 'View the design', 'url' => $meeting->prospect->preview_url] : null,
    ]" />

    @if(filled($meeting->notes))
        <x-mail.note label="What they want to talk about">{{ $meeting->notes }}</x-mail.note>
    @endif

    @if($meeting->prospect)
        <x-mail.button :url="url('/admin/prospects/' . $meeting->prospect_id . '/edit')">Open the prospect</x-mail.button>
    @endif

    <x-mail.signoff>
        The invite is attached. Canceling from the prospect's link will email you both.
    </x-mail.signoff>
</x-mail.layout>
