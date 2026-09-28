<x-mail.layout
    title="Scanna Analysis Complete"
    :preheader="$rfpScreen->rfp_name . ' scored ' . $rfpScreen->score . '/100 — ' . $rfpScreen->score_label . '.'"
>
    <x-mail.heading eyebrow="Scanna" :title="$rfpScreen->rfp_name">
        <x-mail.text>A new RFP has been screened and is ready for review.</x-mail.text>
    </x-mail.heading>

    {{-- The score is the whole point of the message, so it gets the accent treatment. --}}
    <x-mail.details :rows="[
        ['label' => 'Fit score', 'value' => $rfpScreen->score . '/100 · ' . $rfpScreen->score_label, 'accent' => true],
    ]" />

    @if($rfpScreen->summary)
        <x-mail.note label="Summary" :space="16">{{ Str::limit($rfpScreen->summary, 400) }}</x-mail.note>
    @endif

    <x-mail.button :url="$url" :link="true">View Results</x-mail.button>
</x-mail.layout>
