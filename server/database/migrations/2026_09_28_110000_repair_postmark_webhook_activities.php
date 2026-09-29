<?php

use App\Models\ProspectActivity;
use App\Support\ProspectMailer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repairs the opens, clicks and bounces the Postmark webhook recorded before two fixes.
 *
 *   1. Labels. Campaign labels carry a "·", which leaves in the metadata header as RFC 2047
 *      encoded-words and came back that way, so no campaign open or click matched its send.
 *      Decoded here with the same normalizeLabel() the webhook now uses.
 *
 *   2. Times. Postmark's UTC timestamps were stored as app-time wall clock, hours in the
 *      future. A row is only shifted when its event time is later than the moment the row
 *      was written — impossible for a correct row, so rows recorded after the fix, or
 *      written by anything else, are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tz = config('app.timezone');

        DB::table('prospect_activities')
            ->whereIn('type', [
                ProspectActivity::EMAIL_OPENED,
                ProspectActivity::EMAIL_CLICKED,
                ProspectActivity::EMAIL_BOUNCED,
            ])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($tz) {
                foreach ($rows as $row) {
                    $changes = [];

                    $meta = json_decode((string) $row->meta, true) ?: [];
                    $label = $meta['label'] ?? null;
                    $fixed = ProspectMailer::normalizeLabel($label);

                    if ($label !== null && $fixed !== $label) {
                        $meta['label'] = $fixed;
                        $changes['meta'] = json_encode($meta);
                        $changes['description'] = str_replace($label, $fixed, (string) $row->description);
                    }

                    if ($row->occurred_at && $row->created_at && $row->occurred_at > $row->created_at) {
                        $changes['occurred_at'] = Carbon::parse($row->occurred_at, 'UTC')
                            ->setTimezone($tz)
                            ->format('Y-m-d H:i:s');
                    }

                    if ($changes) {
                        DB::table('prospect_activities')->where('id', $row->id)->update($changes);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data repair; the broken values are not worth restoring.
    }
};
