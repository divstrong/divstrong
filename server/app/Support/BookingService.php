<?php

namespace App\Support;

use App\Mail\MeetingBooked;
use App\Mail\MeetingCanceled;
use App\Mail\MeetingConfirmed;
use App\Models\CampaignEnrollment;
use App\Models\Meeting;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Booking a call: hold the slot, tell both parties, and move the prospect forward.
 *
 * The slot check and the insert run inside one transaction with the day's bookings locked,
 * because two people clicking the same slot three seconds apart is the one race this
 * feature actually has — and a double-booked calendar is worse than a failed booking.
 */
class BookingService
{
    /**
     * @param  array{name: string, email: string, company?: ?string, phone?: ?string, notes?: ?string, timezone?: ?string}  $details
     *
     * @throws \RuntimeException when the slot went while they were filling the form
     */
    public static function book(Carbon $startUtc, array $details, ?Prospect $prospect = null, string $source = 'preview'): Meeting
    {
        $duration = Availability::durationMinutes();

        $meeting = DB::transaction(function () use ($startUtc, $details, $prospect, $source, $duration) {
            // Re-checked inside the transaction: the slot list the visitor was shown was
            // generated when the page loaded, which may have been a while ago.
            Meeting::query()->booked()
                ->whereBetween('starts_at', [$startUtc->copy()->subDay(), $startUtc->copy()->addDay()])
                ->lockForUpdate()
                ->get();

            if (! Availability::isBookable($startUtc)) {
                throw new \RuntimeException('That time has just been taken. Please pick another.');
            }

            return Meeting::create([
                'prospect_id' => $prospect?->id,
                'name' => $details['name'],
                'email' => $details['email'],
                'company' => $details['company'] ?? $prospect?->company,
                'phone' => $details['phone'] ?? null,
                'notes' => $details['notes'] ?? null,
                'starts_at' => $startUtc,
                'ends_at' => $startUtc->copy()->addMinutes($duration),
                'timezone' => $details['timezone'] ?: Availability::hostTimezone(),
                'status' => Meeting::STATUS_BOOKED,
                'source' => $source,
            ]);
        });

        static::notifyBooked($meeting);
        static::advanceProspect($meeting, $prospect);

        return $meeting;
    }

    public static function cancel(Meeting $meeting, string $by = 'prospect'): void
    {
        if ($meeting->isCanceled()) {
            return;
        }

        $meeting->forceFill([
            'status' => Meeting::STATUS_CANCELED,
            'canceled_at' => now(),
            'canceled_by' => $by,
        ])->save();

        if ($meeting->prospect) {
            ProspectActivity::record([
                'prospect_id' => $meeting->prospect_id,
                'type' => ProspectActivity::MEETING_CANCELED,
                'description' => 'Canceled the call on ' . $meeting->readableTime(),
                'meta' => ['meeting_id' => $meeting->id, 'by' => $by],
            ]);
        }

        // Both sides are told, and both get the CANCEL invite so the slot clears from
        // their calendar rather than sitting there as a meeting nobody attends.
        static::send(config('scheduling.host.email'), new MeetingCanceled($meeting, forHost: true));
        static::send($meeting->email, new MeetingCanceled($meeting, forHost: false));
    }

    protected static function notifyBooked(Meeting $meeting): void
    {
        static::send($meeting->email, new MeetingConfirmed($meeting));
        static::send(config('scheduling.host.email'), new MeetingBooked($meeting));
    }

    /**
     * A booking is the conversion this whole campaign exists to produce, so it stops the
     * drip, timelines the event and promotes the lead in one place.
     */
    protected static function advanceProspect(Meeting $meeting, ?Prospect $prospect): void
    {
        if (! $prospect) {
            return;
        }

        ProspectActivity::record([
            'prospect_id' => $prospect->id,
            'type' => ProspectActivity::MEETING_BOOKED,
            'description' => 'Booked a call for ' . $meeting->readableTime(),
            'meta' => [
                'meeting_id' => $meeting->id,
                'starts_at' => $meeting->starts_at->toIso8601String(),
                'source' => $meeting->source,
            ],
        ]);

        $updates = [];

        // Someone who books is qualified by any reasonable definition, but an explicit
        // pipeline position set by a human is not overwritten by an automatic one.
        if ($prospect->lead_status !== Prospect::LEAD_QUALIFIED) {
            $updates['lead_status'] = Prospect::LEAD_QUALIFIED;
        }

        if (in_array($prospect->status, ['new', 'contacted'], true)) {
            $updates['status'] = 'qualified';
        }

        if ($updates !== []) {
            $prospect->forceFill($updates)->save();
        }

        $prospect->enrollments()
            ->where('status', CampaignEnrollment::STATUS_ACTIVE)
            ->get()
            ->each(fn (CampaignEnrollment $enrollment) => $enrollment->campaign?->stop_on_booking
                ? $enrollment->stop(CampaignEnrollment::STOP_BOOKED)
                : null);
    }

    /**
     * Send after the response has gone out.
     *
     * Two SMTP round trips is a couple of seconds the person who just picked a time would
     * otherwise spend watching a spinner, having already done everything asked of them.
     * afterResponse() keeps that off the request without needing a queue worker; a failure
     * is logged rather than raised, because the booking is already in the calendar and
     * losing it over a mail error would be the worse outcome.
     */
    protected static function send(?string $to, \Illuminate\Mail\Mailable $mailable): void
    {
        if (blank($to)) {
            return;
        }

        dispatch(function () use ($to, $mailable) {
            try {
                Mail::to($to)->send($mailable);
            } catch (\Throwable $e) {
                Log::error('Booking mail failed', ['to' => $to, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
