<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Support\ProspectMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Postmark event webhooks (Delivery, Open, Click, Bounce, SpamComplaint) and appends
 * them to the matching prospect's activity timeline.
 *
 * Correlation, in order:
 *   1. X-PM-Metadata-prospect-id, stamped by ProspectMailer and echoed back by Postmark on
 *      every event. This is the reliable path and works over SMTP.
 *   2. The Postmark MessageID matched against the original send's external_id. Only works when
 *      sending through Postmark's API transport — over SMTP that column holds an MTA queue id.
 *      Kept so events for mail sent before metadata existed still land.
 *
 * Auth: Postmark supports HTTP Basic Auth embedded in the webhook URL. Configure the webhook as
 *   https://user:secret@yourhost/api/webhooks/postmark
 * and set POSTMARK_WEBHOOK_SECRET to match `secret`.
 */
class PostmarkWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (! $this->authorized($request)) {
            Log::warning('Postmark webhook: unauthorized', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $recordType = $payload['RecordType'] ?? null;
        $messageId = $payload['MessageID'] ?? null;

        [$prospectId, $label] = $this->resolveProspect($payload, $messageId);

        if (! $prospectId) {
            // Not a prospect email (could be an order/quote email sharing the account).
            return response()->json(['status' => 'ignored', 'reason' => 'unattributable event']);
        }

        $mapped = match ($recordType) {
            'Open' => [
                'type' => ProspectActivity::EMAIL_OPENED,
                'description' => 'Opened: ' . ($label ?: 'email'),
                'occurred_at' => $this->timestamp($payload['ReceivedAt'] ?? null),
                'meta' => [
                    'label' => $label,
                    'email' => $payload['Recipient'] ?? null,
                    'client' => $payload['Client'] ?? null,
                    'os' => $payload['OS'] ?? null,
                    'first_open' => $payload['FirstOpen'] ?? null,
                ],
            ],
            'Click' => [
                'type' => ProspectActivity::EMAIL_CLICKED,
                'description' => 'Clicked link in: ' . ($label ?: 'email'),
                'occurred_at' => $this->timestamp($payload['ReceivedAt'] ?? null),
                'meta' => [
                    'label' => $label,
                    'email' => $payload['Recipient'] ?? null,
                    'url' => $payload['OriginalLink'] ?? null,
                ],
            ],
            'Bounce' => [
                'type' => ProspectActivity::EMAIL_BOUNCED,
                'description' => 'Bounced: ' . ($payload['Type'] ?? 'bounce'),
                'occurred_at' => $this->timestamp($payload['BouncedAt'] ?? null),
                'meta' => [
                    'label' => $label,
                    'email' => $payload['Email'] ?? null,
                    'bounce_type' => $payload['Type'] ?? null,
                    'details' => $payload['Description'] ?? null,
                ],
            ],
            /*
             * A spam complaint is an opt-out that arrived the expensive way. Continuing to mail
             * someone who pressed "this is spam" is both the fastest route to a blocked sending
             * domain and, once they have plainly asked, a CAN-SPAM problem — so this suppresses
             * immediately rather than only timelining the event.
             */
            'SpamComplaint' => [
                'type' => ProspectActivity::UNSUBSCRIBED,
                'description' => 'Marked as spam: '.($label ?: 'email'),
                'occurred_at' => $this->timestamp($payload['BouncedAt'] ?? null),
                'meta' => [
                    'label' => $label,
                    'email' => $payload['Email'] ?? null,
                    'source' => Prospect::UNSUB_COMPLAINT,
                ],
            ],
            // Delivery and other record types are acknowledged but not timelined.
            default => null,
        };

        if ($recordType === 'SpamComplaint') {
            Prospect::find($prospectId)?->forceFill([
                'unsubscribed_at' => now(),
                'unsubscribe_source' => Prospect::UNSUB_COMPLAINT,
            ])->save();
        }

        if (! $mapped) {
            return response()->json(['status' => 'ok', 'recordType' => $recordType]);
        }

        ProspectActivity::record(array_merge($mapped, [
            'prospect_id' => $prospectId,
            'external_id' => $messageId,
        ]));

        return response()->json(['status' => 'recorded', 'type' => $mapped['type']]);
    }

    /**
     * @return array{0: int|null, 1: string|null} prospect id and the label of the email it belongs to
     */
    protected function resolveProspect(array $payload, ?string $messageId): array
    {
        $metaProspectId = $this->metadata($payload, ProspectMailer::META_PROSPECT_ID);

        /*
         * The id is echoed back to us by Postmark, which means it is external input and can
         * name a prospect that no longer exists — mail sent months ago to somebody since
         * deleted, or a test event replayed against another environment's database.
         *
         * Confirming it exists is not belt-and-braces: writing the activity blind violates the
         * foreign key, the request 500s, and Postmark treats a non-2xx as a delivery failure
         * and retries the same doomed event on a schedule. One dead prospect becomes a
         * permanent stream of 500s in the log.
         */
        if ($metaProspectId !== null && ctype_digit((string) $metaProspectId)) {
            $exists = Prospect::whereKey((int) $metaProspectId)->exists();

            if ($exists) {
                return [(int) $metaProspectId, $this->metadata($payload, ProspectMailer::META_LABEL)];
            }

            Log::info('Postmark webhook: event for a prospect that no longer exists', [
                'prospect_id' => $metaProspectId,
                'record_type' => $payload['RecordType'] ?? null,
            ]);

            // Fall through to message-id correlation rather than returning early: the id may
            // simply be wrong while the MessageID still matches a send we recorded.
        }

        if (! $messageId) {
            return [null, null];
        }

        $origin = ProspectActivity::where('type', ProspectActivity::EMAIL_SENT)
            ->where('external_id', $messageId)
            ->first();

        return $origin
            ? [$origin->prospect_id, $origin->meta['label'] ?? null]
            : [null, null];
    }

    /**
     * Postmark returns metadata under a "Metadata" object. Matched case-insensitively because
     * the casing of the key is not something the docs commit to.
     */
    protected function metadata(array $payload, string $key): ?string
    {
        $metadata = $payload['Metadata'] ?? null;

        if (! is_array($metadata)) {
            return null;
        }

        foreach ($metadata as $name => $value) {
            if (strcasecmp((string) $name, $key) === 0 && $value !== '' && $value !== null) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function authorized(Request $request): bool
    {
        $secret = config('services.postmark.webhook_secret');

        // If no secret is configured, refuse rather than silently accept unauthenticated posts.
        if (! $secret) {
            Log::warning('Postmark webhook: POSTMARK_WEBHOOK_SECRET is not set; rejecting.');

            return false;
        }

        // Basic-auth password, or a ?token= fallback for setups that can't embed credentials.
        $provided = $request->getPassword() ?: $request->query('token');

        return is_string($provided) && hash_equals($secret, $provided);
    }

    protected function timestamp(?string $value): \Illuminate\Support\Carbon
    {
        try {
            return $value ? \Illuminate\Support\Carbon::parse($value) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
