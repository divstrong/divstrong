@php
    $who = $prospect->company ?: ($prospect->name ?: $prospect->email);
    $yesNo = fn (?bool $answer, string $yes, string $no) => $answer === null ? null : ($answer ? $yes : $no);
    $fit = $prospect->fit_rating
        ? str_repeat('★', $prospect->fit_rating) . str_repeat('☆', 5 - $prospect->fit_rating) . '  ' . $prospect->fit_rating . '/5'
        : null;
    $isComment = $trigger === \App\Mail\PreviewFeedbackReceived::COMMENT;
@endphp

<x-mail.layout
    :title="$isComment ? 'Preview comment' : 'Preview feedback'"
    :preheader="$isComment
        ? ($prospect->name ?: $who) . ' added a comment on their preview.'
        : ($prospect->name ?: $who) . ' answered the questions on their preview.'"
>
    <x-mail.heading :eyebrow="$isComment ? 'New comment' : 'Feedback in'" :title="$who">
        <x-mail.text>
            @if($isComment)
                {{ $prospect->name ?: 'They' }} added a comment on the concept we built them.
            @elseif($prospect->interested)
                {{ $prospect->name ?: 'They' }} would consider moving onto the concept, and the calendar is open to them now.
            @else
                {{ $prospect->name ?: 'They' }} answered the questions on their preview page.
            @endif
        </x-mail.text>
    </x-mail.heading>

    @if($isComment && filled($comment))
        <x-mail.note label="In their words">{{ $comment }}</x-mail.note>
    @endif

    <x-mail.details :rows="[
        ['label' => 'Likes the direction', 'value' => $yesNo($prospect->likes_design, 'Yes', 'Not quite')],
        $fit ? ['label' => 'Fit', 'value' => $fit] : null,
        ['label' => 'Would switch', 'value' => $yesNo($prospect->interested, 'Yes', 'No thanks'), 'accent' => $prospect->interested === true],
        $prospect->name ? ['label' => 'Name', 'value' => $prospect->name] : null,
        $prospect->email ? ['label' => 'Email', 'value' => $prospect->email, 'url' => 'mailto:' . $prospect->email] : null,
        $prospect->phone ? ['label' => 'Phone', 'value' => $prospect->phone, 'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $prospect->phone)] : null,
        $prospect->preview_url ? ['label' => 'Their preview', 'value' => 'View the design', 'url' => $prospect->preview_url] : null,
    ]" />

    @if(! $isComment && filled($prospect->preview_comments))
        <x-mail.note label="In their words">{!! nl2br(e($prospect->preview_comments)) !!}</x-mail.note>
    @endif

    <x-mail.button :url="url('/admin/prospects/' . $prospect->id . '/edit')">Open the prospect</x-mail.button>

    <x-mail.signoff>
        Reply to this email to answer {{ $prospect->name ?: 'them' }} directly.
    </x-mail.signoff>
</x-mail.layout>
