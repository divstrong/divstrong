<?php

namespace App\Support\Prospecting;

use Illuminate\Support\Str;

/**
 * Works out who to address, from the agency's own page and its email address.
 *
 * Without a name there is nobody to write to, and "Hi there" to a stranger is the difference
 * between a cold email and junk. Google Places has no owner field at all, so if the name had to
 * come from the source, the cheap enumeration path would be useless. It does not: the page has
 * usually already said who runs the place, and the harvester has already fetched that page.
 *
 * Four attempts, most reliable first:
 *
 *   1. A full name anchored on the email itself. laurie@ plus "Laurie Riley, Owner" on the page
 *      is as close to certain as this gets — the address and the page agree.
 *   2. A dotted address. dana.ruiz@ is Dana Ruiz whether or not the page says so.
 *   3. A name sitting next to an owner-ish job title.
 *   4. The local part alone as a first name, heavily guarded.
 *
 * Guards matter more than reach here. A wrong name is worse than no name: "Hi Teesquaredmpls"
 * marks the sender as a bot in the first three words, where "Hi there" is merely impersonal.
 * Every path below refuses rather than guesses when the evidence is thin.
 */
class NameFinder
{
    /** Titles that mark the person worth writing to. */
    private const OWNER_TITLES = [
        'owner', 'co-owner', 'founder', 'co-founder', 'president', 'vice president',
        'ceo', 'coo', 'proprietor', 'general manager', 'gm', 'managing partner',
        'partner', 'principal', 'director', 'manager',
    ];

    /**
     * Capitalised word pairs that are not people.
     *
     * A two-capitalised-words regex over an agency site finds a lot of Main Street, New Jersey
     * and Brand Strategy before it finds anyone's name. Agency sites are worse than most for
     * this: the whole page is capitalised service names.
     */
    private const NOT_A_NAME = [
        'contact us', 'about us', 'our team', 'get in', 'work with', 'let us',
        'privacy policy', 'terms of', 'all rights', 'united states', 'main street', 'read more',
        'learn more', 'free quote', 'get started', 'book a', 'sign up', 'log in', 'view all',
        'quick links', 'business hours', 'monday friday', 'customer service',
        'follow us', 'email us', 'call us', 'find us', 'visit us', 'our story', 'our work',
        // Service names, which on an agency site outnumber the people by an order of magnitude.
        'brand strategy', 'creative direction', 'art direction', 'graphic design',
        'web design', 'digital marketing', 'content marketing', 'social media',
        'search engine', 'paid media', 'media buying', 'video production', 'motion graphics',
        'user experience', 'case study', 'case studies', 'our services', 'our process',
        'our clients', 'our approach', 'client work', 'selected work', 'featured work',
        'trade show', 'marketing strategy', 'brand identity', 'visual identity',
        'new york', 'new jersey', 'north carolina', 'south carolina', 'west virginia',
        'rhode island', 'new hampshire', 'san diego', 'las vegas', 'los angeles', 'st louis',
    ];

    /** Local parts that look like words but are never a person. */
    private const NOT_A_FIRST_NAME = [
        'admin', 'team', 'staff', 'crew', 'studio', 'design', 'designs', 'graphics',
        'creative', 'brand', 'branding', 'agency', 'digital', 'media', 'content',
        'social', 'seo', 'ads', 'marketing', 'strategy', 'projects', 'accounts',
        'video', 'films', 'photo', 'works', 'work', 'house', 'lab', 'labs', 'collective',
        'company', 'group', 'partners', 'consulting', 'newbiz', 'hello',
    ];

    /**
     * Find a name for a contact.
     *
     * @param  string|null  $known  name already supplied by the source, kept if present
     * @param  string|null  $email  the harvested address, the strongest anchor available
     * @param  string|null  $html  the page the address was read off
     * @param  string|null  $company  used to reject a company name masquerading as a person
     * @return array{name: ?string, title: ?string, source: ?string}
     */
    public function find(?string $known, ?string $email, ?string $html, ?string $company = null): array
    {
        if (filled($known)) {
            return ['name' => $this->tidy($known), 'title' => null, 'source' => 'listing'];
        }

        $local = $email ? strtolower(explode('@', $email)[0]) : null;

        if ($local !== null && $html !== null) {
            $matched = $this->nameAnchoredOnEmail($local, $html);

            if ($matched !== null) {
                return [
                    'name' => $matched,
                    'title' => $this->titleNear($matched, $html),
                    'source' => 'page matches address',
                ];
            }
        }

        if ($local !== null) {
            $dotted = $this->nameFromDottedLocalPart($local, $company);

            if ($dotted !== null) {
                return ['name' => $dotted, 'title' => null, 'source' => 'address'];
            }
        }

        if ($html !== null) {
            $byTitle = $this->nameBesideOwnerTitle($html, $company);

            if ($byTitle !== null) {
                return ['name' => $byTitle['name'], 'title' => $byTitle['title'], 'source' => 'page'];
            }
        }

        if ($local !== null) {
            $first = $this->firstNameFromLocalPart($local, $company);

            if ($first !== null) {
                return ['name' => $first, 'title' => null, 'source' => 'address'];
            }
        }

        return ['name' => null, 'title' => null, 'source' => null];
    }

    /**
     * Keep looking on the pages that actually introduce people.
     *
     * The harvester stops at the first page carrying a usable address, which is normally
     * /contact — a form and a phone number, and nobody's name. The owner is one page over on
     * /about or /team. Measured on five real sites, reading only the address page found a name
     * for one of them; these pages are where the other four say who runs the place.
     *
     * Only called when the cheaper paths have already failed, and the fetches cost nothing but
     * time, which on the Places path is the only budget that is not already tiny.
     *
     * @return array{name: ?string, title: ?string, source: ?string}
     */
    public function findOnSite(
        PageHarvester $harvester,
        ?string $website,
        ?string $email,
        ?string $company = null,
        ?string $alreadyRead = null,
    ): array {
        $base = $harvester->normaliseUrl($website);

        if ($base === null) {
            return ['name' => null, 'title' => null, 'source' => null];
        }

        $root = rtrim($base, '/');
        $local = $email ? strtolower(explode('@', $email)[0]) : null;
        $budget = (int) config('prospecting.max_name_pages', 3);
        $read = 0;

        foreach (self::PEOPLE_PATHS as $path) {
            if ($read >= $budget) {
                break;
            }

            $url = $root.$path;

            if ($alreadyRead !== null && rtrim($url, '/') === rtrim($alreadyRead, '/')) {
                continue;
            }

            $html = $harvester->fetch($url);

            if ($html === null) {
                continue;
            }

            $read++;

            // Anchored on the address first: it is the only path here that two independent
            // sources have to agree on.
            if ($local !== null) {
                $anchored = $this->nameAnchoredOnEmail($local, $html);

                if ($anchored !== null && str_contains($anchored, ' ')) {
                    return [
                        'name' => $anchored,
                        'title' => $this->titleNear($anchored, $html),
                        'source' => 'team page matches address',
                    ];
                }
            }

            $byTitle = $this->nameBesideOwnerTitle($html, $company);

            if ($byTitle !== null) {
                return ['name' => $byTitle['name'], 'title' => $byTitle['title'], 'source' => 'team page'];
            }
        }

        return ['name' => null, 'title' => null, 'source' => null];
    }

    /**
     * Pages that introduce people, as opposed to pages that take enquiries.
     *
     * Deliberately excludes /contact: it carries the address, which is why the harvester
     * stopped there, and almost never carries a name.
     */
    public const PEOPLE_PATHS = [
        '/about', '/about-us', '/our-team', '/team', '/meet-the-team', '/staff',
        '/who-we-are', '/leadership', '/our-story', '/aboutus',
    ];

    /**
     * A full name on the page whose first word matches the email's local part.
     *
     * The high-confidence case: laurie@oldcapitol.com next to "Laurie Riley" on their own site.
     * Two independent sources agreeing, so no guessing is involved.
     */
    public function nameAnchoredOnEmail(string $local, string $html): ?string
    {
        $text = $this->readableText($html);

        // The leading alphabetic run: "laurie" from laurie, "dana" from dana.ruiz, "lkage"
        // from lkage (too short to anchor on, and correctly skipped below).
        preg_match('/^[a-z]+/', $local, $m);
        $token = $m[0] ?? '';

        if (strlen($token) < 3) {
            return null;
        }

        $quoted = preg_quote(ucfirst($token), '/');

        if (preg_match('/\b('.$quoted.")\s+([A-Z][A-Za-z'’\-]{1,20})\b/", $text, $found) !== 1) {
            return null;
        }

        $candidate = $found[1].' '.$found[2];

        return $this->looksLikeAPerson($candidate) ? $candidate : ucfirst($token);
    }

    /**
     * dana.ruiz@ or dana_ruiz@ or dana-ruiz@ is Dana Ruiz.
     *
     * Deterministic and needs no page at all, which is what makes it worth trying before the
     * heuristics.
     */
    public function nameFromDottedLocalPart(string $local, ?string $company = null): ?string
    {
        if (preg_match('/^([a-z]{2,15})[._-]([a-z]{2,20})$/', $local, $m) !== 1) {
            return null;
        }

        [, $first, $last] = $m;

        if (in_array($first, self::NOT_A_FIRST_NAME, true) || in_array($last, self::NOT_A_FIRST_NAME, true)) {
            return null;
        }

        $candidate = ucfirst($first).' '.ucfirst($last);

        return $this->isCompanyEcho($candidate, $company) ? null : $candidate;
    }

    /**
     * A capitalised name sitting next to Owner, Founder or President.
     *
     * Weaker than the anchored match, so it runs after it, and the name has to be close to the
     * title — a page-wide search would pair the founder's title with whoever is mentioned next.
     *
     * @return array{name: string, title: string}|null
     */
    public function nameBesideOwnerTitle(string $html, ?string $company = null): ?array
    {
        $text = $this->readableText($html);
        $titles = implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::OWNER_TITLES));

        // "Laurie Riley, Owner" and "Owner: Laurie Riley" are both common; try both directions.
        $patterns = [
            "/\b([A-Z][a-z'’\-]{1,15}\s+[A-Z][A-Za-z'’\-]{1,20})\s*[,–—\-|]?\s*(?:is\s+)?(?:the\s+)?({$titles})\b/i",
            "/\b({$titles})\s*[:,–—\-|]\s*([A-Z][a-z'’\-]{1,15}\s+[A-Z][A-Za-z'’\-]{1,20})\b/i",
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $text, $m) !== 1) {
                continue;
            }

            $name = $index === 0 ? $m[1] : $m[2];
            $title = $index === 0 ? $m[2] : $m[1];

            if (! $this->looksLikeAPerson($name) || $this->isCompanyEcho($name, $company)) {
                continue;
            }

            return ['name' => $this->tidy($name), 'title' => Str::title($title)];
        }

        return $this->nameFromOwnershipStory($text, $company);
    }

    /**
     * A name in a sentence that describes owning the place, with no job title in sight.
     *
     * Small studios rarely write "Greg Payne, Founder". They write "in late 2014, Greggory Payne
     * and his sister were given the opportunity to purchase the business" — which says exactly
     * who to write to, and which a title-based search walks straight past. Measured on real
     * sites, this narrative form was as common as the title form.
     *
     * Scoped to the sentence rather than a character window, because the verb can sit a long
     * way from the name and a wide window starts pairing people with whatever is mentioned next.
     *
     * @return array{name: string, title: string}|null
     */
    private function nameFromOwnershipStory(string $text, ?string $company): ?array
    {
        // "founded by Dana Ruiz" is unambiguous enough to take on its own.
        if (preg_match(
            "/\b(?:founded|started|opened|established|owned|run|purchased|bought)\s+by\s+([A-Z][a-z'’\-]{1,15}\s+[A-Z][A-Za-z'’\-]{1,20})\b/",
            $text,
            $m,
        ) === 1 && $this->looksLikeAPerson($m[1]) && ! $this->isCompanyEcho($m[1], $company)) {
            return ['name' => $this->tidy($m[1]), 'title' => 'Owner'];
        }

        $verbs = 'founded|started|opened|established|purchase[ds]?|bought|owns|own|runs|acquired|launched';

        foreach (preg_split('/(?<=[.!?])\s+/', $text) ?: [] as $sentence) {
            if (strlen($sentence) > 400 || preg_match("/\b(?:{$verbs})\b/i", $sentence) !== 1) {
                continue;
            }

            preg_match_all("/\b([A-Z][a-z'’\-]{1,15}\s+[A-Z][A-Za-z'’\-]{1,20})\b/", $sentence, $names);

            foreach ($names[1] as $candidate) {
                if (! $this->looksLikeAPerson($candidate) || $this->isCompanyEcho($candidate, $company)) {
                    continue;
                }

                // "purchased equipment from Brother Industries" — a company, not a founder.
                $after = Str::after($sentence, $candidate);

                if (preg_match('/^\s*(?:Inc|LLC|L\.L\.C|Ltd|Co|Company|Corp|Corporation|Industries|Group|Partners|Supply|Brands)\b/i', $after) === 1) {
                    continue;
                }

                return ['name' => $this->tidy($candidate), 'title' => 'Owner'];
            }
        }

        return null;
    }

    /**
     * The local part on its own as a first name.
     *
     * The weakest path and the most heavily guarded, because this is where
     * teesquaredmpls@gmail.com would become "Hi Teesquaredmpls". It must be a single short
     * alphabetic word, not a trade term, and not an echo of the company name.
     */
    public function firstNameFromLocalPart(string $local, ?string $company = null): ?string
    {
        if (preg_match('/^[a-z]{3,12}$/', $local) !== 1) {
            return null;
        }

        if (in_array($local, self::NOT_A_FIRST_NAME, true)) {
            return null;
        }

        // Every shared mailbox is also not a person, and EmailVerifier already maintains that
        // list — info@ became "Hi Info" until this deferred to it. One list, not two.
        if ((new EmailVerifier)->isRoleAccount($local)) {
            return null;
        }

        $candidate = ucfirst($local);

        return $this->isCompanyEcho($candidate, $company) ? null : $candidate;
    }

    /**
     * Is this "name" just the business name again?
     *
     * northboundmpls@ against "Northbound" catches the common case: an agency's own address reads
     * like a word but names the business, not a person.
     */
    public function isCompanyEcho(string $candidate, ?string $company): bool
    {
        if (blank($company)) {
            return false;
        }

        $flatten = fn (string $s) => preg_replace('/[^a-z]/', '', strtolower($s));

        $name = $flatten($candidate);
        $business = $flatten($company);

        if ($name === '' || $business === '') {
            return false;
        }

        if (str_contains($business, $name) || str_contains($name, $business)) {
            return true;
        }

        // Any single word of the business name reused as the whole "first name".
        foreach (preg_split('/\s+/', strtolower($company)) ?: [] as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);

            if (strlen($word) >= 4 && $word === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Words that are never part of a person's name.
     *
     * Checked per word rather than as phrases, because the phrase list can only ever cover
     * pairs somebody thought of. "Printing Co" got through as a contact name on a live site:
     * two capitalised words, in neither the phrase list nor the corporate-suffix check, which
     * only looked at what followed the candidate.
     */
    private const NOT_A_NAME_WORD = [
        'inc', 'llc', 'ltd', 'co', 'company', 'corp', 'corporation', 'group', 'industries',
        'partners', 'supply', 'brands', 'services', 'solutions', 'enterprises', 'holdings',
        // The agency lexicon. Every one of these has been somebody's company name, and every
        // one of them pairs with a capitalised word somewhere on the page.
        'agency', 'studio', 'studios', 'creative', 'creatives', 'design', 'designs',
        'graphics', 'digital', 'media', 'marketing', 'advertising', 'brand', 'branding',
        'interactive', 'communications', 'collective', 'lab', 'labs', 'works', 'workshop',
        'house', 'haus', 'shop', 'co-op', 'consulting', 'consultancy', 'strategy',
        'strategies', 'content', 'social', 'seo', 'search', 'ppc', 'video', 'films',
        'productions', 'production', 'photo', 'photography', 'motion', 'pixel', 'pixels',
        'street', 'avenue', 'road', 'drive', 'suite', 'north', 'south', 'east', 'west',
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
        'january', 'february', 'march', 'april', 'june', 'july', 'august', 'september',
        'october', 'november', 'december', 'privacy', 'policy', 'terms', 'copyright',
    ];

    /** Reject the Main Streets and Brand Strategies a capitalised-pair regex turns up. */
    public function looksLikeAPerson(string $candidate): bool
    {
        $lower = strtolower(trim($candidate));

        foreach (self::NOT_A_NAME as $phrase) {
            if ($lower === $phrase || str_starts_with($lower, $phrase.' ')) {
                return false;
            }
        }

        foreach (preg_split('/\s+/', $lower) ?: [] as $word) {
            if (in_array(trim($word, ".,'’-"), self::NOT_A_NAME_WORD, true)) {
                return false;
            }
        }

        // Two words, both starting with a capital, neither absurdly long.
        return preg_match("/^[A-Z][a-z'’\-]{1,15}\s+[A-Z][A-Za-z'’\-]{1,20}$/", trim($candidate)) === 1;
    }

    /** The job title printed near a name, if the page gives one. */
    public function titleNear(string $name, string $html): ?string
    {
        $text = $this->readableText($html);
        $position = stripos($text, $name);

        if ($position === false) {
            return null;
        }

        // A job title follows or precedes the name closely; a wider window starts picking up
        // whoever is described in the next paragraph.
        $window = substr($text, max(0, $position - 60), strlen($name) + 120);
        $titles = implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::OWNER_TITLES));

        return preg_match("/\b({$titles})\b/i", $window, $m) === 1 ? Str::title($m[1]) : null;
    }

    /** Markup stripped to the words a reader would see. */
    private function readableText(string $html): string
    {
        // Script and style bodies are full of capitalised identifiers that read like names.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $html = preg_replace('/<[^>]+>/', ' ', $html) ?? $html;
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/\s+/', ' ', $html) ?? $html;
    }

    private function tidy(string $name): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $name)), 80, '');
    }
}
