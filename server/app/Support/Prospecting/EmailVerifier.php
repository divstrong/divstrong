<?php

namespace App\Support\Prospecting;

use Illuminate\Support\Facades\Cache;

/**
 * Deliverability checks for a harvested address, strongest-signal-last.
 *
 * WHAT EACH GATE ACTUALLY PROVES — worth being precise about, because the gap between them is
 * where a 26% bounce rate comes from:
 *
 *   syntax      the string is shaped like an address. Proves nothing about delivery.
 *   role        it is not info@/sales@/orders@. A preference, not a deliverability check.
 *   disposable  the domain is not a burner. Cheap, occasionally useful.
 *   MX          the DOMAIN accepts mail. It does NOT say the mailbox exists — an invented
 *               address at a real company passes MX every single time. Anyone treating an MX
 *               lookup as verification is verifying that the company has email at all.
 *   SMTP        the MAILBOX exists, asked of the mail server that owns it. The only gate here
 *               that speaks to whether a specific send will bounce.
 *
 * And SMTP is not free of caveats either:
 *
 *   - Outbound port 25 is blocked by most hosting providers. Blocked means UNKNOWN, never
 *     invalid, or a firewall rule would quietly bin every lead.
 *   - Catch-all domains accept every address, so a 250 there means nothing. Probed for
 *     explicitly with a random local part, and the result downgraded when it is one.
 *   - Greylisting answers 4xx on first contact. Also unknown.
 *   - Large hosts (Google, Microsoft) often answer on policy rather than existence. A 5.7.x, or
 *     any refusal that smells like blocking rather than "no such user", is treated as unknown.
 *
 * So an address that clears everything is likely deliverable, not guaranteed. Verification
 * services that promise otherwise are selling the same probe with a nicer dashboard.
 */
class EmailVerifier
{
    public const PASS = 'pass';

    public const FAIL = 'fail';

    public const UNKNOWN = 'unknown';

    /**
     * Shared-mailbox local parts.
     *
     * Reaching a principal is the point, and a message to info@ lands with whoever answers
     * the info@ box. But plenty of small studios publish nothing else, so these are DEMOTED
     * rather than dropped: PageHarvester takes a named address every time one exists, and falls
     * back to one of these only when the site offers no alternative. See pickFallback().
     *
     * The trade words at the end matter as much as the office ones — an agency's shared inbox
     * is as often named after what it sells as after a department, and hello@ and studio@ are
     * the two most common front doors in this industry.
     */
    public const ROLE_LOCAL_PARTS = [
        'info', 'sales', 'contact', 'admin', 'administrator', 'office', 'orders', 'order',
        'support', 'help', 'helpdesk', 'service', 'customerservice', 'hello', 'hi', 'team',
        'enquiries', 'inquiries', 'enquiry', 'inquiry', 'general', 'mail', 'email', 'webmaster',
        'postmaster', 'hostmaster', 'abuse', 'noreply', 'no-reply', 'donotreply', 'do-not-reply',
        'billing', 'accounts', 'accounting', 'accountspayable', 'ap', 'ar', 'invoices',
        'marketing', 'press', 'media', 'careers', 'jobs', 'hr', 'recruiting', 'legal',
        'privacy', 'security', 'quotes', 'quote', 'art', 'artwork', 'production', 'shipping',
        'returns', 'warranty', 'wholesale', 'purchasing', 'store', 'shop', 'web', 'website',

        // Trade-specific front desks for creative and media shops. An agency's shared
        // inbox is as often named after the discipline as after a department.
        'studio', 'creative', 'design', 'designs', 'brand', 'branding', 'agency', 'digital',
        'projects', 'project', 'accounts', 'strategy', 'newbiz', 'new-business', 'work',
        'hey', 'yo', 'talk', 'connect', 'reachus', 'ideas', 'social', 'content', 'seo',
        'ads', 'advertising', 'campaigns', 'video', 'films', 'film', 'photo', 'photography',
        'bookings', 'proposals', 'rfps',

        // Front desks by another name. This list is a denylist and will always have holes —
        // "reception" was one, and it cost a test the owner's address — so PageHarvester does
        // not rely on it alone: when the contact's name is known, an address with no trace of
        // that name never wins early, whatever it is called.
        'reception', 'frontdesk', 'desk', 'mainoffice', 'headquarters', 'hq', 'main',
        'company', 'business', 'biz', 'corporate', 'retail', 'dealer', 'dealers',
        'distributor', 'booking', 'bookings', 'scheduling', 'appointments', 'estimates',
        'estimate', 'bids', 'rfq', 'newbusiness', 'newaccounts', 'getintouch', 'letstalk',
    ];

    /**
     * Shared mailboxes that are never worth a sales email, even as a last resort.
     *
     * Two kinds: addresses that discard mail or bounce it (noreply@, postmaster@), and
     * departments with no say in hiring a development partner. Mailing these costs sending
     * reputation and returns nothing, so they are excluded from the fallback rather than merely
     * ranked below it.
     *
     * careers@ deserves a note: at an agency it is answered by a recruiter whose whole job is
     * sourcing the very engineers we would be replacing, which is the worst possible first
     * impression of this pitch.
     */
    public const NEVER_MAIL_LOCAL_PARTS = [
        'noreply', 'no-reply', 'donotreply', 'do-not-reply', 'postmaster', 'hostmaster',
        'mailerdaemon', 'mailer-daemon', 'bounce', 'bounces', 'abuse', 'unsubscribe',
        'webmaster', 'privacy', 'security', 'legal', 'careers', 'jobs', 'hr', 'recruiting',
        'press', 'media', 'returns', 'warranty',
    ];

    /**
     * Shared mailboxes worth trying when an agency publishes nothing else, best first.
     *
     * The front desk beats the production queue: whoever reads hello@ at a ten-person studio is
     * usually one desk away from a partner, where projects@ is a delivery queue and support@ is
     * for their clients' problems.
     *
     * hello@ leads because in this industry it is the front door — agencies print it on the
     * contact page the way a trade shop prints info@.
     */
    public const FALLBACK_PREFERENCE = [
        'hello', 'info', 'contact', 'hi', 'studio', 'newbiz', 'new-business', 'newbusiness',
        'sales', 'office', 'admin', 'general', 'enquiries', 'inquiries', 'letstalk',
        'getintouch', 'mail', 'email', 'team', 'work', 'projects', 'support',
    ];

    /**
     * Burner-mail providers. Short by design: this is a sanity net, not a maintained blocklist,
     * and an agency principal is not running their business off a disposable address.
     */
    public const DISPOSABLE_DOMAINS = [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', '10minutemail.com',
        'tempmail.com', 'temp-mail.org', 'throwawaymail.com', 'yopmail.com', 'trashmail.com',
        'sharklasers.com', 'getnada.com', 'dispostable.com', 'maildrop.cc', 'fakeinbox.com',
        'mailnesia.com', 'mintemail.com', 'spamgourmet.com', 'tempinbox.com', 'emailondeck.com',
    ];

    /**
     * Consumer mail hosts. NOT rejected — a two-person studio is very often run off a personal
     * Gmail, and that address is exactly the decision maker this feature is looking for. Tracked
     * only so the caller can tell "dana@theiragency.com" from "theiragency@gmail.com" when
     * scoring which of several harvested addresses belongs to a principal.
     */
    public const FREE_PROVIDERS = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'hotmail.com', 'outlook.com',
        'live.com', 'msn.com', 'aol.com', 'icloud.com', 'me.com', 'mac.com', 'comcast.net',
        'verizon.net', 'sbcglobal.net', 'att.net', 'bellsouth.net', 'cox.net', 'charter.net',
        'earthlink.net', 'protonmail.com', 'proton.me', 'gmx.com', 'mail.com', 'zoho.com',
    ];

    /**
     * Refusal text that means "we are blocking you", not "that mailbox does not exist". Getting
     * this wrong discards good leads whenever the probe runs from an unfamiliar IP.
     */
    private const POLICY_MARKERS = [
        'spam', 'blocked', 'blacklist', 'block list', 'denied', 'reputation', 'policy',
        'not allowed', 'refused', 'rejected due', 'unauthenticated', 'authentication',
        'rate limit', 'too many', 'try again', 'temporarily', 'greylist', 'grey list',
        'service unavailable', 'access denied', 'client host', 'helo', 'ptr', 'rdns',
    ];

    /** Refusal text that genuinely means the mailbox is not there. */
    private const NO_MAILBOX_MARKERS = [
        'no such user', 'user unknown', 'unknown user', 'does not exist', "doesn't exist",
        'no mailbox', 'mailbox unavailable', 'mailbox not found', 'invalid recipient',
        'recipient not found', 'recipient rejected', 'address rejected', 'no such recipient',
        'user not found', 'unrouteable', 'unroutable', 'not a valid mailbox', '5.1.1',
    ];

    /**
     * Consecutive failures to open a connection at all, across this process.
     *
     * Most hosting providers block outbound port 25. When they do, every probe waits out its
     * full timeout and then reports "unknown" — a hundred candidates, two connections each,
     * eight seconds apiece is half an hour of a run spent learning the same fact repeatedly.
     *
     * So after a few refusals in a row the probe stops trying. Static because it is a property
     * of the network this process is on, not of any one verifier instance, and one run should
     * not have to rediscover it per candidate. Any success resets it: a couple of unreachable
     * mail servers must not be mistaken for a blocked port.
     */
    private static int $consecutiveConnectFailures = 0;

    private const CONNECT_FAILURES_BEFORE_GIVING_UP = 4;

    public function __construct(
        private readonly bool $smtpEnabled = true,
        private readonly int $timeout = 8,
    ) {}

    /** Has the probe given up on this network for the rest of the process? */
    public static function smtpLooksBlocked(): bool
    {
        return self::$consecutiveConnectFailures >= self::CONNECT_FAILURES_BEFORE_GIVING_UP;
    }

    /** Let a new run try again — the port may be open somewhere this one is not. */
    public static function resetConnectivity(): void
    {
        self::$consecutiveConnectFailures = 0;
    }

    public static function fromConfig(): self
    {
        return new self(
            smtpEnabled: (bool) config('prospecting.smtp_probe', true),
            timeout: (int) config('prospecting.smtp_timeout', 8),
        );
    }

    /**
     * Run every gate against an address.
     *
     * Short-circuits on the cheap local checks so a typo never costs a DNS query, let alone a
     * connection to somebody's mail server.
     *
     * @return array{ok: bool, reason: ?string, checks: array<string, mixed>}
     */
    public function verify(string $email, bool $allowShared = false): array
    {
        $email = strtolower(trim($email));
        $checks = [];

        if (! $this->hasValidSyntax($email)) {
            return $this->result(false, 'invalid_syntax', $checks + ['syntax' => self::FAIL]);
        }

        $checks['syntax'] = self::PASS;

        [$local, $domain] = explode('@', $email, 2);

        $isRole = $this->isRoleAccount($local);

        if ($isRole && ! $allowShared) {
            // Not a deliverability failure — this address probably works fine. It just is not
            // a person, and reaching a person is the point.
            return $this->result(false, 'role_account', $checks + ['role' => self::FAIL]);
        }

        // $allowShared means the caller already searched the whole site for a named address and
        // found none, and is knowingly taking the front desk over losing the lead.
        $checks['role'] = $isRole ? 'shared' : self::PASS;

        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            return $this->result(false, 'disposable_domain', $checks + ['disposable' => self::FAIL]);
        }

        $checks['disposable'] = self::PASS;
        $checks['free_provider'] = in_array($domain, self::FREE_PROVIDERS, true);

        $mx = $this->mxHosts($domain);

        if ($mx === []) {
            // No MX and no A record to fall back on: nothing will ever accept mail here.
            return $this->result(false, 'no_mx_record', $checks + ['mx' => self::FAIL]);
        }

        $checks['mx'] = self::PASS;
        $checks['mx_host'] = $mx[0];

        if (! $this->smtpEnabled) {
            $checks['smtp'] = self::UNKNOWN;
            $checks['smtp_detail'] = 'probe disabled';

            return $this->result(true, null, $checks);
        }

        $catchAll = $this->isCatchAll($domain, $mx);
        $checks['catch_all'] = $catchAll;

        if ($catchAll === true) {
            // The server says yes to everything, so asking about this address specifically
            // tells us nothing. Accept on the strength of provenance instead.
            $checks['smtp'] = self::UNKNOWN;
            $checks['smtp_detail'] = 'domain accepts all recipients';

            return $this->result(true, null, $checks);
        }

        [$state, $detail] = $this->probeMailbox($email, $mx);

        $checks['smtp'] = $state;
        $checks['smtp_detail'] = $detail;

        if ($state === self::FAIL) {
            return $this->result(false, 'mailbox_not_found', $checks);
        }

        // pass or unknown. Unknown is accepted: the address was published on the company's own
        // site, and throwing that away because a firewall ate port 25 would reject nearly
        // everything on a typical host.
        return $this->result(true, null, $checks);
    }

    public function hasValidSyntax(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        // filter_var accepts things no real mail system will: a domain with no dot, a trailing
        // hyphen, consecutive dots.
        [, $domain] = explode('@', $email, 2);

        return str_contains($domain, '.')
            && ! str_contains($email, '..')
            && ! str_ends_with($domain, '-')
            && preg_match('/^[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+$/i', explode('@', $email)[0]) === 1;
    }

    public function isRoleAccount(string $localPart): bool
    {
        $normalised = preg_replace('/[^a-z0-9]/', '', strtolower($localPart));

        if (in_array($normalised, self::ROLE_LOCAL_PARTS, true)) {
            return true;
        }

        // "sales2", "info-uk", "orders.east" — same box, decorated.
        foreach (self::ROLE_LOCAL_PARTS as $role) {
            if (strlen($role) >= 4 && str_starts_with($normalised, $role)) {
                $tail = substr($normalised, strlen($role));

                if ($tail === '' || ctype_digit($tail)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isFreeProvider(string $domain): bool
    {
        return in_array(strtolower($domain), self::FREE_PROVIDERS, true);
    }

    /**
     * MX hosts for a domain, best first.
     *
     * Falls back to the A record, which RFC 5321 requires senders to treat as an implicit MX —
     * plenty of small studios run mail straight off the web host with no MX published.
     *
     * @return list<string>
     */
    public function mxHosts(string $domain): array
    {
        return Cache::remember("prospecting:mx:{$domain}", now()->addHours(12), function () use ($domain) {
            $records = @dns_get_record($domain, DNS_MX);

            if (is_array($records) && $records !== []) {
                usort($records, fn ($a, $b) => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));

                $hosts = array_values(array_filter(array_map(
                    fn ($r) => $r['target'] ?? null,
                    $records,
                )));

                if ($hosts !== []) {
                    return $hosts;
                }
            }

            return @checkdnsrr($domain, 'A') ? [$domain] : [];
        });
    }

    /**
     * Does this domain accept mail for any address at all?
     *
     * Asked with a random local part nobody could have registered. A 250 back means the server
     * is a catch-all and its answer about the real address is worthless.
     *
     * Cached per domain: without it, fifty candidates at one franchise means fifty pointless
     * connections to the same server, which is how a prospecting tool gets itself blocklisted.
     */
    public function isCatchAll(string $domain, ?array $mx = null): ?bool
    {
        return Cache::remember("prospecting:catchall:{$domain}", now()->addHours(6), function () use ($domain, $mx) {
            $mx ??= $this->mxHosts($domain);

            if ($mx === []) {
                return null;
            }

            $probe = 'ps-'.bin2hex(random_bytes(8)).'@'.$domain;

            [$state] = $this->probeMailbox($probe, $mx);

            return match ($state) {
                self::PASS => true,
                self::FAIL => false,
                default => null,
            };
        });
    }

    /**
     * Ask the receiving server whether it would accept mail for this address.
     *
     * Stops at RCPT TO and never sends DATA, so nothing is delivered. Conservative by design:
     * anything ambiguous comes back UNKNOWN, because a wrong FAIL silently bins a real lead and
     * nobody ever finds out.
     *
     * @param  list<string>  $mx
     * @return array{0: string, 1: string} [state, detail]
     */
    public function probeMailbox(string $email, array $mx): array
    {
        $helo = $this->heloHost();
        $from = $this->probeSender();

        // Only the best host is tried. Falling through every backup MX turns one slow lookup
        // into four, and backups routinely accept everything to spool it onward anyway.
        $host = $mx[0];

        // Already established that nothing on this network can reach port 25. Waiting out the
        // timeout again would tell us the same thing, one candidate at a time.
        if (self::smtpLooksBlocked()) {
            return [self::UNKNOWN, 'outbound port 25 appears blocked on this host'];
        }

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "tcp://{$host}:25",
            $errno,
            $errstr,
            // Connecting is fast when it works at all; only the conversation needs the full
            // budget. A short connect timeout is what makes a blocked port cheap to discover.
            min(5, $this->timeout),
            STREAM_CLIENT_CONNECT,
        );

        if (! $socket) {
            self::$consecutiveConnectFailures++;

            // Almost always outbound 25 blocked by the host, which says nothing about the
            // mailbox.
            return [self::UNKNOWN, 'connect failed: '.($errstr ?: "errno {$errno}")];
        }

        self::$consecutiveConnectFailures = 0;

        try {
            stream_set_timeout($socket, $this->timeout);

            $banner = $this->readResponse($socket);

            if (! str_starts_with($banner, '220')) {
                return [self::UNKNOWN, 'no greeting: '.$this->trim($banner)];
            }

            $ehlo = $this->command($socket, "EHLO {$helo}");

            if (! str_starts_with($ehlo, '250')) {
                $ehlo = $this->command($socket, "HELO {$helo}");

                if (! str_starts_with($ehlo, '250')) {
                    return [self::UNKNOWN, 'helo refused: '.$this->trim($ehlo)];
                }
            }

            $mailFrom = $this->command($socket, "MAIL FROM:<{$from}>");

            if (! str_starts_with($mailFrom, '250')) {
                return [self::UNKNOWN, 'sender refused: '.$this->trim($mailFrom)];
            }

            $rcpt = $this->command($socket, "RCPT TO:<{$email}>");

            $this->command($socket, 'QUIT');

            return $this->interpretRcpt($rcpt);
        } catch (\Throwable $e) {
            return [self::UNKNOWN, 'probe error: '.$e->getMessage()];
        } finally {
            @fclose($socket);
        }
    }

    /**
     * Turn an RCPT TO reply into a verdict.
     *
     * @return array{0: string, 1: string}
     */
    private function interpretRcpt(string $reply): array
    {
        $detail = $this->trim($reply);
        $lower = strtolower($detail);

        if (str_starts_with($reply, '250') || str_starts_with($reply, '251')) {
            return [self::PASS, $detail];
        }

        // 4xx is explicitly temporary: greylisting, rate limiting, server busy.
        if (preg_match('/^4\d\d/', $reply) === 1) {
            return [self::UNKNOWN, $detail];
        }

        if (preg_match('/^5\d\d/', $reply) === 1) {
            foreach (self::NO_MAILBOX_MARKERS as $marker) {
                if (str_contains($lower, $marker)) {
                    return [self::FAIL, $detail];
                }
            }

            // 5.7.x is the policy class — us being blocked, not the mailbox being absent.
            foreach (self::POLICY_MARKERS as $marker) {
                if (str_contains($lower, $marker)) {
                    return [self::UNKNOWN, $detail];
                }
            }

            // A bare 550/551/553 with nothing else to go on is, in practice, user unknown.
            if (preg_match('/^5(50|51|53)/', $reply) === 1) {
                return [self::FAIL, $detail];
            }

            return [self::UNKNOWN, $detail];
        }

        return [self::UNKNOWN, $detail];
    }

    private function command($socket, string $line): string
    {
        fwrite($socket, $line."\r\n");

        return $this->readResponse($socket);
    }

    /**
     * Read one SMTP reply, following multi-line continuations ("250-" then "250 ").
     */
    private function readResponse($socket): string
    {
        $response = '';

        while (! feof($socket)) {
            $line = fgets($socket, 1024);

            if ($line === false) {
                break;
            }

            $response .= $line;

            // A space in the fourth column ends the reply; a hyphen means more is coming.
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }

            $meta = stream_get_meta_data($socket);

            if ($meta['timed_out'] ?? false) {
                break;
            }
        }

        return $response;
    }

    private function trim(string $response): string
    {
        return \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($response)), 180);
    }

    /**
     * The hostname announced in EHLO. Must look like a real host or strict servers refuse the
     * conversation outright.
     */
    private function heloHost(): string
    {
        $configured = config('prospecting.helo_host');

        if (filled($configured)) {
            return $configured;
        }

        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    /** The envelope sender used for the probe. Never receives anything — DATA is never sent. */
    private function probeSender(): string
    {
        $configured = config('prospecting.probe_sender');

        if (filled($configured)) {
            return $configured;
        }

        $from = config('mail.from.address');

        return filled($from) ? $from : 'verify@'.$this->heloHost();
    }

    /**
     * @param  array<string, mixed>  $checks
     * @return array{ok: bool, reason: ?string, checks: array<string, mixed>}
     */
    private function result(bool $ok, ?string $reason, array $checks): array
    {
        return ['ok' => $ok, 'reason' => $reason, 'checks' => $checks];
    }
}
