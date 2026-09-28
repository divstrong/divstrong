<?php

namespace App\Support;

use App\Models\Meeting;

/**
 * Builds the .ics both parties get attached to their confirmation.
 *
 * METHOD:REQUEST rather than PUBLISH, because that is what makes Outlook and Apple Mail
 * show Accept/Decline instead of a file to download, and what puts the call on the
 * calendar in one click. The UID stays constant for the life of the booking and SEQUENCE
 * increments, so a later CANCEL removes the original event rather than adding a second one.
 */
class CalendarInvite
{
    public static function request(Meeting $meeting): string
    {
        return static::build($meeting, 'REQUEST', 0, 'CONFIRMED');
    }

    public static function cancel(Meeting $meeting): string
    {
        return static::build($meeting, 'CANCEL', 1, 'CANCELLED');
    }

    public static function filename(Meeting $meeting): string
    {
        return 'divstrong-call-' . $meeting->starts_at->format('Y-m-d-Hi') . '.ics';
    }

    protected static function build(Meeting $meeting, string $method, int $sequence, string $status): string
    {
        $host = config('scheduling.host');
        $location = (string) config('scheduling.location');

        $summary = 'divStrong · ' . ($meeting->company ?: $meeting->name);
        $description = static::description($meeting);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//divStrong//Booking//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:' . $method,
            'BEGIN:VEVENT',
            'UID:' . static::uid($meeting),
            'SEQUENCE:' . $sequence,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $meeting->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $meeting->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . static::escape($summary),
            'DESCRIPTION:' . static::escape($description),
            'LOCATION:' . static::escape($location),
            'STATUS:' . $status,
            'ORGANIZER;CN=' . static::escape($host['name']) . ':mailto:' . $host['email'],
            'ATTENDEE;CN=' . static::escape($meeting->name) . ';ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:mailto:' . $meeting->email,
            'ATTENDEE;CN=' . static::escape($host['name']) . ';ROLE=CHAIR;PARTSTAT=ACCEPTED:mailto:' . $host['email'],
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // RFC 5545 wants CRLF endings; some clients silently ignore a file without them.
        return implode("\r\n", array_map([static::class, 'fold'], $lines)) . "\r\n";
    }

    /** Stable per booking, so a cancellation can find the event it is cancelling. */
    protected static function uid(Meeting $meeting): string
    {
        return $meeting->token . '@divstrong.com';
    }

    protected static function description(Meeting $meeting): string
    {
        $parts = [
            'Intro call with ' . $meeting->name . ($meeting->company ? ' (' . $meeting->company . ')' : '') . '.',
        ];

        if (filled($meeting->phone)) {
            $parts[] = 'Phone: ' . $meeting->phone;
        }

        $parts[] = 'Email: ' . $meeting->email;

        if (filled($meeting->notes)) {
            $parts[] = 'What they want to talk about: ' . $meeting->notes;
        }

        $parts[] = 'Reschedule or cancel: ' . $meeting->cancelUrl();

        return implode("\n\n", $parts);
    }

    /** Commas, semicolons, backslashes and newlines are structural in iCalendar. */
    protected static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n'],
            $value,
        );
    }

    /** Lines over 75 octets must be folded, or strict parsers reject the file. */
    protected static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = substr($line, 0, 75);
        $rest = substr($line, 75);

        foreach (str_split($rest, 74) as $chunk) {
            $folded .= "\r\n " . $chunk;
        }

        return $folded;
    }
}
