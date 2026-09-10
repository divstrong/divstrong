<?php

namespace App\Support\Prospecting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Finds creative and media agencies using Claude with web search.
 *
 * The division of labour matters more than anything else here. The model is asked for things a
 * web search can establish and a person could check — company name, town, what they sell,
 * who runs it, which page says so. It is explicitly NOT asked for email addresses, because a
 * model asked for fifty addresses will produce fifty, and the invented ones are
 * indistinguishable from the real ones until they bounce. Addresses are read off the cited
 * pages afterwards by PageHarvester.
 *
 * Work is split into rounds, each steered at a different region and speciality. Two reasons:
 * one request generating fifty researched agencies runs long enough to hit PHP's execution limit,
 * and asking the same broad question repeatedly returns the same well-known agencies. Varying the
 * angle is what makes a second run worth running.
 */
class ContactDiscovery implements CompanySource
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    /**
     * Regions cycled through across rounds. Spread deliberately so consecutive rounds do not
     * re-search neighbouring markets.
     */
    public const REGIONS = [
        'the Upper Midwest (Minnesota, Wisconsin, Iowa)',
        'the Southeast (Georgia, North Carolina, South Carolina, Tennessee)',
        'the Northeast (New York, New Jersey, Pennsylvania, Connecticut)',
        'Texas and Oklahoma',
        'the Mountain West (Colorado, Utah, Arizona, Idaho)',
        'the Pacific Northwest (Washington, Oregon)',
        'the Ohio Valley (Ohio, Indiana, Kentucky, Michigan)',
        'New England (Massachusetts, Maine, New Hampshire, Vermont, Rhode Island)',
        'California',
        'Florida',
        'the Mid-Atlantic (Virginia, Maryland, Delaware, West Virginia)',
        'the Plains (Missouri, Kansas, Nebraska, the Dakotas)',
    ];

    /**
     * Disciplines, varied per round so a run is not all SEO firms.
     *
     * Every one of these sells strategy, creative or media rather than engineering,
     * which is the whole premise: they win work that needs a build and have nobody
     * in-house to build it.
     */
    public const SPECIALTIES = [
        'full-service digital marketing agencies',
        'SEO and search marketing firms',
        'brand identity and graphic design studios',
        'web design studios that outsource development',
        'video production and media companies',
        'advertising and creative agencies',
        'content marketing and social media agencies',
        'UX and product design consultancies',
    ];

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $model = 'claude-sonnet-5',
        private readonly int $timeout = 180,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            // Same credentials as ClaudeService, so there is one Anthropic key in
            // this app rather than two that can drift apart.
            apiKey: config('claude.api_key'),
            // Deliberately NOT config('claude.model'): that is Opus for RFP analysis.
            // Discovery is web-search-grounded extraction across many calls, where a
            // smaller model reads the same pages for a fraction of the spend.
            model: (string) env('PROSPECTING_MODEL', 'claude-sonnet-5'),
            timeout: (int) config('prospecting.discovery_timeout', 180),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function name(): string
    {
        return 'Claude web search';
    }

    /**
     * One round of research.
     *
     * @param  array<string, mixed>  $criteria  modal input: notes, region override
     * @param  list<string>  $excludeDomains  already in the book; do not return these again
     * @return list<array<string, mixed>>
     */
    public function discoverRound(int $round, int $perRound, array $criteria = [], array $excludeDomains = []): array
    {
        if (! $this->isConfigured()) {
            throw ProspectingException::missingApiKey();
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->connectTimeout(20)
            ->post(self::API_URL, $this->payload($round, $perRound, $criteria, $excludeDomains));

        if (! $response->successful()) {
            throw ProspectingException::apiFailed($response->status(), $response->body());
        }

        return $this->parseCompanies($response->json());
    }

    /**
     * Several rounds at once.
     *
     * Rounds are independent by construction — each covers a different region and speciality —
     * so running them sequentially just adds up their latency. One round with web search takes
     * the better part of a minute, and a 50-prospect run needs ten of them; in series that is a
     * ten-minute wait, in parallel it is closer to one.
     *
     * A round that fails does not take the batch down with it. The pool is only worth having if
     * one slow or rate-limited request cannot cost the other nine.
     *
     * @param  list<int>  $rounds
     * @param  array<string, mixed>  $criteria
     * @param  list<string>  $excludeDomains
     * @return array{companies: list<array<string, mixed>>, errors: list<string>}
     */
    public function discoverRounds(array $rounds, int $perRound, array $criteria = [], array $excludeDomains = []): array
    {
        if (! $this->isConfigured()) {
            throw ProspectingException::missingApiKey();
        }

        $responses = Http::pool(fn ($pool) => array_map(
            fn (int $round) => $pool->as((string) $round)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout(20)
                ->post(self::API_URL, $this->payload($round, $perRound, $criteria, $excludeDomains)),
            $rounds,
        ));

        $companies = [];
        $errors = [];
        $usage = ['input' => 0, 'output' => 0, 'searches' => 0];

        foreach ($rounds as $round) {
            $response = $responses[(string) $round] ?? null;

            try {
                if ($response instanceof \Throwable) {
                    throw new ProspectingException($response->getMessage());
                }

                if (! $response || ! $response->successful()) {
                    throw ProspectingException::apiFailed(
                        $response?->status() ?? 0,
                        $response?->body() ?? 'no response',
                    );
                }

                $json = $response->json();

                // Counted even for a round that then fails to parse — it was still billed.
                foreach (self::usageOf($json) as $key => $value) {
                    $usage[$key] += $value;
                }

                $companies = [...$companies, ...$this->parseCompanies($json)];
            } catch (\Throwable $e) {
                $errors[] = "round {$round}: ".$e->getMessage();
            }
        }

        $this->lastUsage = $usage;

        return ['companies' => $companies, 'errors' => $errors, 'usage' => $usage];
    }

    /**
     * Token usage from the most recent call, for cost reporting.
     *
     * @var array{input: int, output: int, searches: int}
     */
    public array $lastUsage = ['input' => 0, 'output' => 0, 'searches' => 0];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{input: int, output: int, searches: int}
     */
    public static function usageOf(?array $payload): array
    {
        $usage = $payload['usage'] ?? [];

        return [
            // Cache reads are counted separately by the API and are far cheaper, but they are
            // still input, so a total that ignored them would understate the round.
            'input' => (int) ($usage['input_tokens'] ?? 0)
                + (int) ($usage['cache_read_input_tokens'] ?? 0)
                + (int) ($usage['cache_creation_input_tokens'] ?? 0),
            'output' => (int) ($usage['output_tokens'] ?? 0),
            'searches' => (int) ($usage['server_tool_use']['web_search_requests'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  list<string>  $excludeDomains
     * @return array<string, mixed>
     */
    private function payload(int $round, int $perRound, array $criteria, array $excludeDomains): array
    {
        return [
            'model' => $this->model,
            /*
             * Generous, because running out mid-object is what broke a live run: the response
             * stopped partway through a company and the whole round was reported unreadable.
             * The parser salvages complete objects now, but a truncated round still silently
             * returns fewer agencies than it found, so the ceiling is set well clear of what a
             * dozen entries plus the model's search commentary needs.
             */
            'max_tokens' => (int) config('prospecting.max_tokens', 16000),
            'tools' => [[
                'type' => config('prospecting.web_search_tool', 'web_search_20250305'),
                'name' => 'web_search',
                /*
                 * The single biggest cost lever in this whole feature.
                 *
                 * Server-side web search runs an agentic loop inside one request: every search
                 * result is appended to the context, and the WHOLE accumulated context is fed
                 * back in for the next step. So input tokens grow with the square of the search
                 * count, not linearly. At twelve searches a single round billed hundreds of
                 * thousands of input tokens; at four it is roughly a ninth of that.
                 *
                 * Fewer searches means fewer agencies per round, which is why rounds are cheap and
                 * run concurrently instead of being few and large.
                 */
                'max_uses' => (int) config('prospecting.max_searches_per_round', 4),
            ]],
            'messages' => [[
                'role' => 'user',
                'content' => $this->promptBlocks($round, $perRound, $criteria, $excludeDomains),
            ]],
        ];
    }

    /**
     * The brief, split so the unchanging half can be cached.
     *
     * Standing instructions and the exclusion list are identical for every round of a run and
     * get re-read on every step of the search loop, so they go first behind a cache breakpoint.
     * The region and speciality vary per round and go last, because anything before a varying
     * section cannot be cached.
     *
     * Worth doing, but keep it in proportion: this saves a couple of thousand tokens a round
     * against the hundreds of thousands that search results cost. max_uses is the real lever.
     *
     * @param  array<string, mixed>  $criteria
     * @param  list<string>  $excludeDomains
     * @return list<array<string, mixed>>
     */
    private function promptBlocks(int $round, int $perRound, array $criteria, array $excludeDomains): array
    {
        return [
            [
                'type' => 'text',
                'text' => $this->standingBrief($perRound, $excludeDomains),
                'cache_control' => ['type' => 'ephemeral'],
            ],
            [
                'type' => 'text',
                'text' => $this->roundFocus($round, $criteria),
            ],
        ];
    }

    /**
     * The standing half of the brief: identical for every round of a run, so it is cacheable.
     *
     * The instruction not to guess addresses is repeated and given a reason. It is the one
     * instruction that, if ignored, produces output that looks perfect and is worthless — and
     * "leave it blank" has to be visibly the approved answer, or the model will fill the field
     * because the field is there.
     *
     * @param  list<string>  $excludeDomains
     */
    private function standingBrief(int $perRound, array $excludeDomains): string
    {
        $exclusions = '';

        if ($excludeDomains !== []) {
            $sample = array_slice($excludeDomains, 0, 150);
            $exclusions = "

Already in our database — skip any agency using these domains:
"
                .implode(', ', $sample);
        }

        return <<<PROMPT
        Find {$perRound} small-to-midsize creative and media agencies in the United States.

        These are our sales targets: digital marketing agencies, SEO and search firms,
        graphic design and brand studios, web design shops, advertising agencies, video and
        media production companies, content and social agencies, and UX/product design
        consultancies. Typically 3-60 people.

        What makes one a target is the shape of their bench, not their size. We augment teams
        that sell strategy, creative and media but have little or no in-house engineering, so
        that when a client needs something custom built — a web application, an integration, a
        portal, automation — they either turn the work away or scramble for a subcontractor.
        Prefer agencies whose own site advertises strategy, brand, content, SEO, media or
        design and does NOT advertise a software engineering practice.

        Exclude: software development firms and dev shops (they are competitors, not clients),
        staffing and recruiting agencies, offshore outsourcing vendors, the global holding-
        company networks and their subsidiaries, franchise marketing brands, one-person
        freelancers with no company presence, and any agency over roughly 200 employees.

        For each agency, use web search to establish:
        - the legal or trading name
        - the website
        - the city and state
        - the founder, owner, principal, president or managing director BY NAME, if it is
          published anywhere (about page, team page, local business press, AAF/AIGA or chamber
          listing, award announcements, LinkedIn snippet)
        - their job title
        - a public phone number
        - the URL of the page on their own website most likely to carry direct contact details,
          normally a contact, about, team or people page
        - what they actually sell, in a few words

        Be economical with searches. A search that lists many agencies at once — an award
        winners page, a "best agencies in <city>" roundup, an association member directory, a
        local business journal list — is worth far more than one search per company, and you
        have very few searches available.

        CRITICAL INSTRUCTION ABOUT EMAIL ADDRESSES:

        Do NOT guess, infer, construct or pattern-match an email address. Never assemble one
        from a person's name and their company domain. If you did not read a specific address
        in a specific search result, the correct and expected answer is null.

        Our system fetches the contact_page_url you provide and reads the address off the page
        itself. A guessed address is worse than no address: it looks correct, passes every
        format check, and bounces when we send to it, which damages our sending reputation. An
        entry with a good contact_page_url and a null email is a useful result. An entry with a
        plausible invented email is a harmful one.

        Only fill in "email" if you saw that exact address in search results, and if you do,
        put the URL you saw it on in "email_source_url".{$exclusions}

        Return ONLY a JSON array, no prose before or after, in exactly this shape:

        [
          {
            "company": "Northbound Creative",
            "website": "https://northboundcreative.com",
            "city": "Asheville",
            "state": "NC",
            "contact_name": "Dana Ruiz",
            "title": "Founder & Creative Director",
            "phone": "828-555-0142",
            "contact_page_url": "https://northboundcreative.com/about",
            "email": null,
            "email_source_url": null,
            "specialties": "brand identity, web design, content"
          }
        ]

        Use null for anything you could not establish. Do not pad the list to reach
        {$perRound} entries — fewer real agencies is the better outcome.
        PROMPT;
    }

    /**
     * The part that changes per round, kept last so everything above it stays cacheable.
     *
     * @param  array<string, mixed>  $criteria
     */
    private function roundFocus(int $round, array $criteria): string
    {
        $region = $criteria['region']
            ?? self::REGIONS[$round % count(self::REGIONS)];

        $specialty = self::SPECIALTIES[$round % count(self::SPECIALTIES)];

        $notes = trim((string) ($criteria['notes'] ?? ''));

        $extra = $notes !== '' ? "

Additional targeting from the sales team: {$notes}" : '';

        return "Focus this search on: {$specialty}
"
            ."Focus this search on businesses located in: {$region}{$extra}";
    }

    /**
     * Pull the JSON array out of the reply.
     *
     * The response interleaves search calls, their results and the model's own text, so the
     * text blocks are concatenated and the array located within them. Tolerant of code fences
     * and of commentary either side, because both show up occasionally and neither is worth
     * failing a whole round over.
     *
     * @param  array<string, mixed>|null  $payload
     * @return list<array<string, mixed>>
     */
    public function parseCompanies(?array $payload): array
    {
        $text = '';

        foreach ($payload['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        if (trim($text) === '') {
            throw ProspectingException::unparseable('the model returned no text');
        }

        // Worth knowing about: a truncated round still yields usable agencies now, but it yields
        // fewer than it found, and the fix is a bigger ceiling rather than more rounds.
        if (($payload['stop_reason'] ?? null) === 'max_tokens') {
            Log::warning('Prospect discovery round hit the output limit and was truncated', [
                'max_tokens' => config('prospecting.max_tokens', 16000),
            ]);
        }

        $decoded = $this->extractObjects($text);

        if ($decoded === null) {
            throw ProspectingException::unparseable($text);
        }

        $companies = [];

        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }

            $company = $this->cleanString($row['company'] ?? null);
            $website = $this->cleanString($row['website'] ?? null);

            // A row with neither a name nor a site is not a lead.
            if ($company === null && $website === null) {
                continue;
            }

            $companies[] = [
                'company' => $company,
                'website' => $website,
                'city' => $this->cleanString($row['city'] ?? null),
                'state' => $this->cleanString($row['state'] ?? null),
                'contact_name' => $this->cleanString($row['contact_name'] ?? null),
                'title' => $this->cleanString($row['title'] ?? null),
                'phone' => $this->cleanString($row['phone'] ?? null),
                'contact_page_url' => $this->cleanString($row['contact_page_url'] ?? null),
                // Kept only as a hint. Never imported without being re-read off the page.
                'email_hint' => $this->cleanString($row['email'] ?? null),
                'email_source_url' => $this->cleanString($row['email_source_url'] ?? null),
                'specialties' => $this->cleanString($row['specialties'] ?? null),
            ];
        }

        if ($companies === []) {
            Log::info('Prospect discovery round returned no usable companies', [
                'text' => Str::limit($text, 500),
            ]);
        }

        return $companies;
    }

    /**
     * Pull the agency objects out of free text, one at a time.
     *
     * Object-by-object rather than decoding the array as a whole, because the array is
     * routinely incomplete. A round that runs into the output limit stops mid-object, and
     * decoding the whole thing then fails on a response that contained a dozen perfectly good
     * agencies — which is exactly what happened live: "Could not read the company list back from the
     * model" printed with a visibly correct list of companies inside the error text.
     *
     * So each balanced {...} is decoded on its own and kept if it parses. A truncated tail is
     * discarded and the rest of the round survives.
     *
     * Brace-balancing rather than a regex: values contain braces, and a lazy match stops at the
     * first inner one. Quoted strings are tracked so a brace inside a company name is not
     * counted as structure.
     *
     * @return list<array<string, mixed>>|null  null only when there is no object at all
     */
    private function extractObjects(string $text): ?array
    {
        $start = strpos($text, '[');
        $offset = $start === false ? 0 : $start;

        $objects = [];
        $depth = 0;
        $inString = false;
        $escaped = false;
        $objectStart = null;

        for ($i = $offset, $len = strlen($text); $i < $len; $i++) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\' && $inString) {
                $escaped = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;

                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                if ($depth === 0) {
                    $objectStart = $i;
                }

                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0 && $objectStart !== null) {
                    $candidate = json_decode(substr($text, $objectStart, $i - $objectStart + 1), true);

                    if (is_array($candidate)) {
                        $objects[] = $candidate;
                    }

                    $objectStart = null;
                }

                // A stray closing brace before any opening one: resynchronise rather than
                // letting the depth go negative and swallow everything after it.
                if ($depth < 0) {
                    $depth = 0;
                }
            }
        }

        return $objects === [] ? null : $objects;
    }

    /** Normalise a model-supplied string, treating its ways of saying "nothing" as null. */
    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);

        if (in_array($lower, ['null', 'n/a', 'na', 'none', 'unknown', 'not found', 'not available', '-'], true)) {
            return null;
        }

        return Str::limit($value, 250, '');
    }
}
