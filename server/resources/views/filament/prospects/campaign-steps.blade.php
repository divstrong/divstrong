{{--
    Per-step engagement chips for the Prospects list's Campaign column.

    The same chips as the outreach emails (see engagement.blade.php), one per campaign step,
    keyed by the step's activity label — the label ProspectMailer stamps into Postmark
    metadata and the webhook writes back onto every open and click. The line underneath is
    where the sequence stands: when the next step goes, or why it stopped.

    Expects $record (Prospect, with checklistActivities and enrollments eager-loaded),
    $campaign (Campaign with steps) and $enrollment (the latest, or null).
--}}
@php
    $states = \App\Models\Prospect::class;

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

    $status = match ($enrollment?->status) {
        null => null,
        // Past due means the scheduler has not picked it up yet, not that it is "9 hours ago".
        \App\Models\CampaignEnrollment::STATUS_ACTIVE => match (true) {
            $enrollment->next_send_at === null => 'Next unscheduled',
            $enrollment->next_send_at->isPast() => 'Next due now',
            default => 'Next ' . $enrollment->next_send_at->diffForHumans(),
        },
        \App\Models\CampaignEnrollment::STATUS_COMPLETED => 'Done',
        default => \App\Models\CampaignEnrollment::stopReasons()[$enrollment->stop_reason] ?? 'Stopped',
    };
@endphp

<div class="ds-chips">
    @foreach ($campaign->steps as $step)
        @php
            $label = $step->activityLabel();
            $state = $record->emailEngagement($label);
            $at = $record->emailEngagementAt($label, $state);
            $title = $step->position . '. ' . $step->name . ' — ' . $words[$state] . ($at ? ' ' . $at->diffForHumans() : '');
        @endphp

        <span class="ds-chip" data-hue="campaign" data-state="{{ $state }}" title="{{ $title }}">
            @if ($icons[$state])
                <x-filament::icon :icon="$icons[$state]" />
            @endif
            Step {{ $step->position }}
        </span>
    @endforeach
</div>

@if ($status)
    <div style="margin-top: 4px; font-size: 12px; line-height: 1.3; color: #9ca3af;">{{ $status }}</div>
@endif
