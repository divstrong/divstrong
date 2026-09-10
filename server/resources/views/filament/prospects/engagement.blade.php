{{--
    Per-email engagement chips for the Prospects list.

    Reads Prospect::emailEngagement(), which uses the eager-loaded checklistActivities slice —
    so a page of these costs no extra queries. Opens and clicks only appear once the Postmark
    webhook is delivering; until then every sent email legitimately reads "sent", which is the
    truth rather than a bug.

    Styling lives in filament/prospects/panel-styles.blade.php, injected once per page by a
    render hook. See that file for why it is CSS rather than Tailwind utilities.
--}}
@php
    $record = $getState();
    $states = \App\Models\Prospect::class;

    // Same map the list filter and the stat tiles use, so a new email appears in all of them
    // at once rather than in whichever one somebody remembered to update.
    $emails = $states::EMAIL_LABELS;

    // Which hue each email wears. An email with no entry falls back to the neutral outline
    // rather than rendering an unstyled — and on a dark panel, invisible — chip.
    $hues = [
        'Agency Intro' => 'agency',
        'Client Intro' => 'client',
        'General Update' => 'general',
    ];

    $icons = [
        $states::ENGAGEMENT_NONE => null,
        $states::ENGAGEMENT_SENT => 'heroicon-m-check',
        $states::ENGAGEMENT_OPENED => 'heroicon-m-eye',
        $states::ENGAGEMENT_CLICKED => 'heroicon-m-hand-raised',
        $states::ENGAGEMENT_BOUNCED => 'heroicon-m-exclamation-triangle',
    ];

    $words = [
        $states::ENGAGEMENT_NONE => 'not sent',
        $states::ENGAGEMENT_SENT => 'sent',
        $states::ENGAGEMENT_OPENED => 'opened',
        $states::ENGAGEMENT_CLICKED => 'clicked',
        $states::ENGAGEMENT_BOUNCED => 'bounced',
    ];
@endphp

<div class="ds-chips">
    @if ($record->isUnsubscribed())
        {{-- Replaces the chips entirely rather than sitting beside them. Once someone has
             opted out, how far the last email got is history, and the only fact that governs
             what anyone does with this row is that they asked us to stop. --}}
        <span class="ds-chip" data-state="unsubscribed"
              title="Unsubscribed {{ $record->unsubscribed_at?->diffForHumans() }}{{ $record->unsubscribe_source === \App\Models\Prospect::UNSUB_COMPLAINT ? ' — marked it as spam' : '' }}">
            <x-filament::icon icon="heroicon-m-no-symbol" />
            Unsubscribed
        </span>
    @else
        @foreach ($emails as $label => $short)
            @php
                $state = $record->emailEngagement($label);
                $at = $record->emailEngagementAt($label, $state);
                $title = $label . ' — ' . $words[$state] . ($at ? ' ' . $at->diffForHumans() : '');
            @endphp

            <span class="ds-chip"
                  data-hue="{{ $hues[$label] ?? 'agency' }}"
                  data-state="{{ $state }}"
                  title="{{ $title }}">
                @if ($icons[$state])
                    <x-filament::icon :icon="$icons[$state]" />
                @endif
                {{ $short }}
            </span>
        @endforeach
    @endif
</div>
