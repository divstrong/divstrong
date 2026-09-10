<?php

namespace App\Support\Prospecting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads email addresses off a company's own web pages.
 *
 * THIS IS THE POINT OF THE WHOLE FEATURE. A language model asked for fifty owner email
 * addresses will return fifty well-formed strings, and a good share of them will be invented —
 * first.last@ at a real domain, entirely plausible, never existed. They pass a syntax check.
 * They pass an MX check, because the company's domain does accept mail. They bounce.
 *
 * So the model is never trusted for the address itself. It finds companies and points at pages;
 * this class fetches those pages server-side and takes the address off the markup. An address
 * that cannot be read off a page we retrieved ourselves does not get imported, no matter how
 * confident the model was. Anything the model did supply is kept only as a hint for scoring,
 * and is checked against what the page actually says.
 *
 * Fetching model-supplied URLs is an SSRF sink, so every hop is resolved and rejected if it
 * points anywhere internal.
 */
class PageHarvester
{
    /** An address belonging to a named person — what this is all for. */
    public const KIND_PERSONAL = 'personal';

    /** A front desk taken because the site published nothing better. */
    public const KIND_SHARED = 'shared';

    /** Pages a small business puts contact details on, tried in descending likelihood. */
    public const CONTACT_PATHS = [
        '/contact', '/contact-us', '/contactus', '/about', '/about-us', '/aboutus',
        '/team', '/our-team', '/meet-the-team', '/staff', '/leadership', '/who-we-are',
    ];

    private const MAX_BYTES = 900_000;

    private const MAX_REDIRECTS = 3;

    /** Assets that will never contain a contact address but are happily linked as "contact". */
    private const SKIP_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'zip', 'mp4', 'mov', 'css', 'js',
    ];

    public function __construct(
        private readonly int $timeout = 12,
        private readonly EmailVerifier $verifier = new EmailVerifier,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            timeout: (int) config('prospecting.fetch_timeout', 12),
            verifier: EmailVerifier::fromConfig(),
        );
    }

    /**
     * Find the best personal address for a contact, and the page it came from.
     *
     * Tries the page the model nominated first, then the usual contact paths on the company's
     * own domain, stopping at the first page that yields a personal address.
     *
     * @param  list<string>  $extraUrls
     * @return array{email: ?string, source_url: ?string, seen: list<string>, pages: int}
     */
    public function harvest(?string $website, ?string $contactName, array $extraUrls = []): array
    {
        // email => the first page it appeared on, so a fallback chosen at the end still knows
        // where it was published.
        $seen = [];
        // The markup of each page, so whichever address wins can hand NameFinder the page it
        // was published on. Small business contact pages are a few kilobytes; the fetch is
        // capped either way.
        $markup = [];
        $pagesRead = 0;

        foreach ($this->candidateUrls($website, $extraUrls) as $url) {
            if ($pagesRead >= (int) config('prospecting.max_pages_per_company', 5)) {
                break;
            }

            $html = $this->fetch($url);

            if ($html === null) {
                continue;
            }

            $pagesRead++;

            $found = $this->extractEmails($html);

            foreach ($found as $email) {
                $seen[$email] ??= $url;
            }

            $markup[$url] = $html;

            $best = $this->pickBest($found, $contactName, $website);

            if ($best === null) {
                continue;
            }

            /*
             * Stop here only if this is as good as it gets.
             *
             * When the contact's name is known, an address carrying no trace of it is not
             * worth ending the search for — the owner's own address may be two pages further
             * in. A test caught exactly that: reception@ on the contact page won over
             * laurie@ on the team page purely because the contact page was read first.
             *
             * The shared-mailbox list cannot be relied on to catch every front desk, so this
             * is the real defence: page order stops deciding, and a name match always beats
             * a stranger's address regardless of where each was found.
             *
             * With no name to match on there is nothing better to hold out for, so the first
             * usable address wins and the run stays fast.
             */
            if (blank($contactName) || $this->nameAffinity(explode('@', $best)[0], $contactName) > 0) {
                return [
                    'email' => $best,
                    'source_url' => $url,
                    'source_html' => $html,
                    'kind' => self::KIND_PERSONAL,
                    'seen' => array_keys($seen),
                    'pages' => $pagesRead,
                ];
            }
        }

        // Whole site read and no name match anywhere. Take the best non-shared address on
        // offer, wherever it turned up.
        $best = $this->pickBest(array_keys($seen), $contactName, $website);

        if ($best !== null) {
            return [
                'email' => $best,
                'source_url' => $seen[$best],
                'source_html' => $markup[$seen[$best]] ?? null,
                'kind' => self::KIND_PERSONAL,
                'seen' => array_keys($seen),
                'pages' => $pagesRead,
            ];
        }

        /*
         * No named address anywhere on the site. Plenty of small studios publish only a front
         * desk, and losing those leads entirely is worse than writing to a shared inbox that a
         * ten-person studio's founder probably reads anyway.
         *
         * Chosen across every page rather than the last one, so page order cannot decide the
         * answer — and only after the personal search has exhausted the whole site, so a
         * fallback can never beat a named address that was two pages further in.
         */
        $fallback = $this->pickFallback(array_keys($seen), $website);

        if ($fallback !== null) {
            return [
                'email' => $fallback,
                'source_url' => $seen[$fallback],
                'source_html' => $markup[$seen[$fallback]] ?? null,
                'kind' => self::KIND_SHARED,
                'seen' => array_keys($seen),
                'pages' => $pagesRead,
            ];
        }

        return [
            'email' => null,
            'source_url' => null,
            'source_html' => null,
            'kind' => null,
            'seen' => array_keys($seen),
            'pages' => $pagesRead,
        ];
    }

    /**
     * Best shared mailbox on offer, or null if the site publishes nothing worth writing to.
     *
     * Only reached when no named address exists. Ranked so the front desk wins over the art
     * department, and addresses that discard mail or reach the wrong department are excluded
     * outright rather than merely ranked last.
     *
     * @param  list<string>  $emails
     */
    public function pickFallback(array $emails, ?string $website): ?string
    {
        $siteHost = $this->hostOf($website);
        $scored = [];

        foreach ($emails as $email) {
            [$local, $domain] = explode('@', $email, 2);

            $normalised = preg_replace('/[^a-z0-9]/', '', strtolower($local));

            if (in_array($normalised, EmailVerifier::NEVER_MAIL_LOCAL_PARTS, true)) {
                continue;
            }

            $rank = array_search($normalised, EmailVerifier::FALLBACK_PREFERENCE, true);

            // Preferred boxes score by position; anything else shared still counts, just below
            // all of them.
            $score = $rank === false
                ? 1
                : count(EmailVerifier::FALLBACK_PREFERENCE) - $rank + 1;

            // Their own domain beats a free-provider address for a shared box: a gmail with no
            // name on it is as likely to be a web developer's as the agency's.
            if ($siteHost && str_contains($domain, $siteHost)) {
                $score += 5;
            }

            $scored[$email] = $score;
        }

        if ($scored === []) {
            return null;
        }

        arsort($scored);

        return array_key_first($scored);
    }

    /**
     * Confirm an address genuinely appears on a page.
     *
     * Used to re-check an address the model supplied with a citation. Obfuscated markup is
     * decoded first, so a page writing "dana [at] agency.com" still counts as a match.
     */
    public function confirmOnPage(string $email, string $url): bool
    {
        $html = $this->fetch($url);

        if ($html === null) {
            return false;
        }

        return in_array(strtolower($email), $this->extractEmails($html), true);
    }

    /**
     * The pages worth reading for one company, best first.
     *
     * @param  list<string>  $extraUrls
     * @return list<string>
     */
    public function candidateUrls(?string $website, array $extraUrls = []): array
    {
        $urls = [];

        foreach ($extraUrls as $extra) {
            $normalised = $this->normaliseUrl($extra);

            if ($normalised !== null) {
                $urls[] = $normalised;
            }
        }

        $base = $this->normaliseUrl($website);

        if ($base !== null) {
            $root = rtrim($base, '/');
            $urls[] = $root;

            foreach (self::CONTACT_PATHS as $path) {
                $urls[] = $root.$path;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Every address on a page, lowercased and de-duplicated.
     *
     * Handles the three ways a small-business site hides an address from scrapers: HTML
     * entities, "name at domain dot com" spelled out, and Cloudflare's hex obfuscation.
     *
     * @return list<string>
     */
    public function extractEmails(string $html): array
    {
        $decoded = $this->decodeCloudflare($html);
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = $this->deobfuscate($decoded);

        preg_match_all(
            '/[a-z0-9][a-z0-9._%+\'-]*@[a-z0-9][a-z0-9.-]*\.[a-z]{2,24}/i',
            $decoded,
            $matches,
        );

        $emails = [];

        foreach ($matches[0] as $match) {
            $email = strtolower(rtrim($match, '.-_'));

            // Sentinel addresses that appear on templated sites and belong to nobody.
            if (Str::startsWith($email, ['example@', 'you@', 'your@', 'name@', 'email@', 'user@'])) {
                continue;
            }

            if (Str::endsWith($email, ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.css', '.js'])) {
                continue;
            }

            if (! $this->verifier->hasValidSyntax($email)) {
                continue;
            }

            $emails[$email] = true;
        }

        return array_keys($emails);
    }

    /**
     * Choose the address most likely to be the named person's.
     *
     * Role accounts are dropped outright — reaching info@ is the thing this feature exists to
     * avoid. Among what is left, an address echoing the contact's name wins; failing that, one
     * on the company's own domain beats a free-provider address, which beats nothing.
     *
     * @param  list<string>  $emails
     */
    public function pickBest(array $emails, ?string $contactName, ?string $website): ?string
    {
        $scored = [];

        foreach ($emails as $email) {
            [$local, $domain] = explode('@', $email, 2);

            if ($this->verifier->isRoleAccount($local)) {
                continue;
            }

            $score = 0;

            if ($contactName) {
                $score += $this->nameAffinity($local, $contactName);
            }

            $siteHost = $this->hostOf($website);

            if ($siteHost && $this->sameSite($domain, $siteHost)) {
                $score += 3;
            } elseif ($this->verifier->isFreeProvider($domain)) {
                // A gmail address on an agency's contact page is usually a principal's, and for a
                // one-person operation it is often the only address there.
                $score += 1;
            }

            $scored[$email] = $score;
        }

        if ($scored === []) {
            return null;
        }

        arsort($scored);

        $best = array_key_first($scored);

        // A no-name, off-domain address with nothing recommending it is not worth an import.
        return $scored[$best] > 0 ? $best : null;
    }

    /**
     * How strongly a local part echoes a person's name.
     *
     * "jim@", "jsmith@", "jim.smith@", "jimsmith@" all score; "orders@" does not.
     */
    public function nameAffinity(string $local, string $contactName): int
    {
        $local = strtolower(preg_replace('/[^a-z0-9]/i', '', $local));

        $parts = array_values(array_filter(
            preg_split('/\s+/', strtolower(trim($contactName))) ?: [],
            fn ($p) => strlen($p) > 1,
        ));

        if ($parts === [] || $local === '') {
            return 0;
        }

        $first = preg_replace('/[^a-z]/', '', $parts[0]);
        $last = preg_replace('/[^a-z]/', '', end($parts));

        $score = 0;

        if ($first !== '' && $last !== '' && $first !== $last) {
            foreach ([$first.$last, $last.$first, $first[0].$last, $first.$last[0]] as $combo) {
                if ($local === $combo) {
                    return 10;
                }
            }
        }

        if ($first !== '' && $local === $first) {
            $score += 8;
        } elseif ($first !== '' && strlen($first) > 2 && str_contains($local, $first)) {
            $score += 5;
        }

        if ($last !== '' && $local === $last) {
            $score += 6;
        } elseif ($last !== '' && strlen($last) > 2 && str_contains($local, $last)) {
            $score += 4;
        }

        return $score;
    }

    /**
     * Fetch a page, or null if it cannot be read safely.
     *
     * Redirects are followed by hand so every hop can be re-validated — an allowed public URL
     * that 302s to 169.254.169.254 is the standard way out of a naive allowlist.
     */
    public function fetch(string $url): ?string
    {
        $current = $this->normaliseUrl($url);

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if ($current === null || ! $this->isPublicUrl($current)) {
                return null;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => config(
                        'prospecting.user_agent',
                        'divStrongProspector/1.0 (+https://divstrong.com)',
                    ),
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                    ->timeout($this->timeout)
                    ->connectTimeout(min(6, $this->timeout))
                    ->withoutRedirecting()
                    ->get($current);
            } catch (\Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if ($location === '') {
                    return null;
                }

                $current = $this->resolveRelative($current, $location);

                continue;
            }

            if (! $response->successful()) {
                return null;
            }

            $type = strtolower($response->header('Content-Type'));

            if ($type !== '' && ! Str::contains($type, ['text/html', 'text/plain', 'application/xhtml'])) {
                return null;
            }

            // Cap what a hostile or merely enormous page can pull into memory.
            return substr($response->body(), 0, self::MAX_BYTES);
        }

        return null;
    }

    /**
     * Reject anything not on a public host.
     *
     * The URL comes from a language model, so it is untrusted input pointed at a server that
     * can see the private network. Every resolved address has to be public.
     */
    public function isPublicUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        if (in_array($host, ['localhost', 'localhost.localdomain', 'metadata.google.internal'], true)) {
            return false;
        }

        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            return false;
        }

        if (isset($parts['port']) && ! in_array((int) $parts['port'], [80, 443, 8080, 8443], true)) {
            return false;
        }

        $extension = strtolower(pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION));

        if ($extension !== '' && in_array($extension, self::SKIP_EXTENSIONS, true)) {
            return false;
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            $public = filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($public === false) {
                return false;
            }
        }

        return true;
    }

    public function normaliseUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    public function hostOf(?string $url): ?string
    {
        $normalised = $this->normaliseUrl($url);

        if ($normalised === null) {
            return null;
        }

        $host = parse_url($normalised, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/^www\./i', '', strtolower($host)) : null;
    }

    /** Treat mail.agency.com and agency.com as the same organisation. */
    private function sameSite(string $domain, string $host): bool
    {
        $domain = preg_replace('/^www\./i', '', strtolower($domain));

        return $domain === $host
            || str_ends_with($domain, '.'.$host)
            || str_ends_with($host, '.'.$domain);
    }

    private function resolveRelative(string $base, string $location): ?string
    {
        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }

        $parts = parse_url($base);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $root = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return $root.'/'.ltrim($location, '/');
    }

    /**
     * Undo "name (at) domain (dot) com" and its variants.
     */
    private function deobfuscate(string $text): string
    {
        $patterns = [
            '/\s*[\(\[\{]\s*at\s*[\)\]\}]\s*/i' => '@',
            '/\s*[\(\[\{]\s*dot\s*[\)\]\}]\s*/i' => '.',
            '/\s+at\s+(?=[a-z0-9-]+\s+dot\s+)/i' => '@',
            '/\s+dot\s+(?=[a-z]{2,24}\b)/i' => '.',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }

    /**
     * Decode Cloudflare's email obfuscation.
     *
     * Cloudflare rewrites addresses to a hex blob whose first byte is an XOR key. It is on by
     * default for a lot of small-business hosting, so skipping it would blank out a meaningful
     * share of contact pages.
     */
    private function decodeCloudflare(string $html): string
    {
        return preg_replace_callback(
            '/data-cfemail="([0-9a-f]+)"/i',
            function (array $m) use (&$html) {
                $hex = $m[1];

                if (strlen($hex) < 4 || strlen($hex) % 2 !== 0) {
                    return $m[0];
                }

                $key = hexdec(substr($hex, 0, 2));
                $email = '';

                for ($i = 2; $i < strlen($hex); $i += 2) {
                    $email .= chr(hexdec(substr($hex, $i, 2)) ^ $key);
                }

                // Appended rather than substituted so the surrounding markup stays intact.
                return $m[0].' '.$email.' ';
            },
            $html,
        ) ?? $html;
    }
}
