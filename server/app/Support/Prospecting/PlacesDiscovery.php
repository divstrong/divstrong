<?php

namespace App\Support\Prospecting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Enumerates creative and media agencies from Google Places.
 *
 * The cheap half of the pipeline. Places knows which businesses exist, where, and what they
 * are, and answers in one request per twenty companies for a fraction of a cent — against the six
 * figures of tokens a single web-search round costs to establish the same facts.
 *
 * What it cannot do is tell you who runs the place. Places has no owner field, so the name has
 * to come from the agency's own website afterwards (see NameFinder), which is free because the
 * harvester is fetching those pages anyway.
 *
 * That trade is what makes this worth having: at this price, enumerating three times as many
 * companies to compensate for a lower name-extraction rate costs almost nothing, where the same
 * multiple on the model path would treble a token bill that was already the problem.
 *
 * Uses the current Places API (places.googleapis.com), not the legacy maps.googleapis.com
 * endpoints, which Google no longer enables for new keys.
 */
class PlacesDiscovery implements CompanySource
{
    private const API_URL = 'https://places.googleapis.com/v1/places:searchText';

    /**
     * Only what the pipeline uses. The field mask decides the billing tier, so asking for
     * everything would cost more for data nothing reads. websiteUri is non-negotiable — an agency
     * with no site has no page to read an address off.
     */
    private const FIELD_MASK = 'places.displayName,places.formattedAddress,places.websiteUri,'
        .'places.nationalPhoneNumber,places.addressComponents,places.businessStatus,'
        .'places.primaryType,places.userRatingCount,nextPageToken';

    /**
     * Search terms, phrased the way someone would type them into Maps rather than the way the
     * model brief phrases them. Cycled per round so a run is not all SEO firms.
     *
     * Note what is absent: "software development company" and "web development agency". Those
     * are competitors, not prospects — the pitch only works on a team that sells creative and
     * has nobody in-house to build with.
     */
    public const QUERIES = [
        'digital marketing agency',
        'seo agency',
        'graphic design studio',
        'advertising agency',
        'branding agency',
        'video production company',
        'web design agency',
        'social media marketing agency',
    ];

    /**
     * Metros to search, keyed by the region labels the modal offers so the region selector
     * keeps working across both sources.
     *
     * Places answers on cities, not on "the Upper Midwest", so each region expands into real
     * markets. Mid-size cities on purpose: the target is an independent agency, and the big
     * metros return holding-company offices and platform listings first.
     *
     * @var array<string, list<string>>
     */
    public const METROS = [
        'the Upper Midwest (Minnesota, Wisconsin, Iowa)' => [
            'Minneapolis, MN', 'Saint Paul, MN', 'Duluth, MN', 'Rochester, MN',
            'Milwaukee, WI', 'Madison, WI', 'Green Bay, WI', 'Appleton, WI',
            'Des Moines, IA', 'Cedar Rapids, IA', 'Davenport, IA', 'Iowa City, IA',
        ],
        'the Southeast (Georgia, North Carolina, South Carolina, Tennessee)' => [
            'Atlanta, GA', 'Savannah, GA', 'Augusta, GA', 'Macon, GA',
            'Charlotte, NC', 'Raleigh, NC', 'Greensboro, NC', 'Asheville, NC',
            'Charleston, SC', 'Greenville, SC', 'Columbia, SC',
            'Nashville, TN', 'Knoxville, TN', 'Chattanooga, TN', 'Memphis, TN',
        ],
        'the Northeast (New York, New Jersey, Pennsylvania, Connecticut)' => [
            'Buffalo, NY', 'Rochester, NY', 'Syracuse, NY', 'Albany, NY',
            'Newark, NJ', 'Trenton, NJ', 'Cherry Hill, NJ',
            'Philadelphia, PA', 'Pittsburgh, PA', 'Allentown, PA', 'Harrisburg, PA',
            'Hartford, CT', 'New Haven, CT', 'Bridgeport, CT',
        ],
        'Texas and Oklahoma' => [
            'Dallas, TX', 'Fort Worth, TX', 'Houston, TX', 'San Antonio, TX',
            'Austin, TX', 'El Paso, TX', 'Lubbock, TX', 'Corpus Christi, TX',
            'Oklahoma City, OK', 'Tulsa, OK', 'Norman, OK',
        ],
        'the Mountain West (Colorado, Utah, Arizona, Idaho)' => [
            'Denver, CO', 'Colorado Springs, CO', 'Fort Collins, CO', 'Pueblo, CO',
            'Salt Lake City, UT', 'Provo, UT', 'Ogden, UT',
            'Phoenix, AZ', 'Tucson, AZ', 'Mesa, AZ', 'Flagstaff, AZ',
            'Boise, ID', 'Idaho Falls, ID',
        ],
        'the Pacific Northwest (Washington, Oregon)' => [
            'Seattle, WA', 'Tacoma, WA', 'Spokane, WA', 'Vancouver, WA', 'Bellingham, WA',
            'Portland, OR', 'Eugene, OR', 'Salem, OR', 'Bend, OR', 'Medford, OR',
        ],
        'the Ohio Valley (Ohio, Indiana, Kentucky, Michigan)' => [
            'Columbus, OH', 'Cleveland, OH', 'Cincinnati, OH', 'Toledo, OH', 'Dayton, OH',
            'Indianapolis, IN', 'Fort Wayne, IN', 'Evansville, IN', 'South Bend, IN',
            'Louisville, KY', 'Lexington, KY',
            'Detroit, MI', 'Grand Rapids, MI', 'Lansing, MI', 'Ann Arbor, MI',
        ],
        'New England (Massachusetts, Maine, New Hampshire, Vermont, Rhode Island)' => [
            'Boston, MA', 'Worcester, MA', 'Springfield, MA', 'Lowell, MA',
            'Portland, ME', 'Bangor, ME',
            'Manchester, NH', 'Nashua, NH',
            'Burlington, VT', 'Providence, RI', 'Warwick, RI',
        ],
        'California' => [
            'Los Angeles, CA', 'San Diego, CA', 'San Jose, CA', 'Sacramento, CA',
            'Fresno, CA', 'Long Beach, CA', 'Oakland, CA', 'Bakersfield, CA',
            'Anaheim, CA', 'Riverside, CA', 'Santa Rosa, CA', 'Modesto, CA',
        ],
        'Florida' => [
            'Jacksonville, FL', 'Miami, FL', 'Tampa, FL', 'Orlando, FL',
            'St. Petersburg, FL', 'Fort Lauderdale, FL', 'Tallahassee, FL',
            'Sarasota, FL', 'Naples, FL', 'Pensacola, FL',
        ],
        'the Mid-Atlantic (Virginia, Maryland, Delaware, West Virginia)' => [
            'Richmond, VA', 'Virginia Beach, VA', 'Norfolk, VA', 'Roanoke, VA', 'Alexandria, VA',
            'Baltimore, MD', 'Annapolis, MD', 'Frederick, MD',
            'Wilmington, DE', 'Dover, DE',
            'Charleston, WV', 'Morgantown, WV',
        ],
        'the Plains (Missouri, Kansas, Nebraska, the Dakotas)' => [
            'Kansas City, MO', 'St. Louis, MO', 'Springfield, MO', 'Columbia, MO',
            'Wichita, KS', 'Topeka, KS', 'Overland Park, KS',
            'Omaha, NE', 'Lincoln, NE',
            'Sioux Falls, SD', 'Rapid City, SD', 'Fargo, ND', 'Bismarck, ND',
        ],
    ];

    /**
     * Who Places hands back that we do not want.
     *
     * Three groups, all of which rank first for exactly the queries that find real
     * independents, because they buy the category:
     *
     *   Holding networks and their subsidiaries. They have thousands of engineers in-house
     *   and a procurement process no cold email survives.
     *
     *   Franchise marketing brands. The outlet runs whatever head office mandates, so the
     *   person who answers has no authority to hire a development partner.
     *
     *   Platforms and marketplaces that surface in these searches by category rather than by
     *   being an agency at all.
     *
     * Competitor dev shops are excluded by the query set and the model brief instead, because
     * there is no finite list of those to name.
     */
    public const EXCLUDED_BRANDS = [
        // Holding companies and their networks.
        'wpp', 'omnicom', 'publicis', 'interpublic', 'dentsu', 'havas', 'accenture',
        'deloitte digital', 'mckinsey', 'ogilvy', 'bbdo', 'ddb', 'leo burnett',
        'saatchi', 'grey group', 'mccann', 'wunderman', 'vmly', 'razorfish',
        'digitas', 'sapient', 'huge inc', 'r/ga', 'edelman', 'weber shandwick',
        'fleishmanhillard', 'ketchum', 'golin', 'mediacom', 'mindshare', 'zenith media',
        // Franchise marketing and print-marketing networks.
        'alphagraphics', 'minuteman press', 'signarama', 'sign-a-rama', 'fastsigns',
        'allegra marketing', 'sir speedy', 'postnet', 'proforma', 'wsi digital',
        'united franchise', 'the growth coach',
        // Platforms and marketplaces, not agencies.
        'yelp', 'thumbtack', 'angi', 'godaddy', 'wix', 'squarespace', 'shopify',
        'hubspot', 'salesforce', 'adobe', 'vistaprint', 'fiverr', 'upwork',
        'yellow pages', 'yellowpages', 'hibu', 'reachlocal', 'thryv',
    ];

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly int $timeout = 30,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.google.places_key'),
            timeout: (int) config('prospecting.places_timeout', 30),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function name(): string
    {
        return 'Google Places';
    }

    /**
     * {@inheritDoc}
     */
    public function discoverRounds(array $rounds, int $perRound, array $criteria = [], array $excludeDomains = []): array
    {
        if (! $this->isConfigured()) {
            throw ProspectingException::missingPlacesKey();
        }

        $companies = [];
        $errors = [];
        $requests = 0;

        $excluded = array_flip(array_map('strtolower', $excludeDomains));

        foreach ($rounds as $round) {
            try {
                [$query, $metro] = $this->queryFor($round, $criteria);

                foreach ($this->search($query.' in '.$metro, $perRound, $requests) as $place) {
                    $company = $this->toCompany($place, $query);

                    if ($company === null) {
                        continue;
                    }

                    // Domain already in the book, so nothing downstream would keep it anyway.
                    $host = $company['website'] ? parse_url($company['website'], PHP_URL_HOST) : null;

                    if ($host && isset($excluded[strtolower(preg_replace('/^www\./i', '', $host))])) {
                        continue;
                    }

                    $companies[] = $company;
                }
            } catch (\Throwable $e) {
                $errors[] = "round {$round}: ".$e->getMessage();
            }
        }

        return [
            'companies' => $companies,
            'errors' => $errors,
            // No tokens are spent here at all. Requests are counted so the run report can show
            // what the cheap path actually cost.
            'usage' => ['input' => 0, 'output' => 0, 'searches' => $requests],
        ];
    }

    /**
     * The search phrase and market for a round.
     *
     * Both advance with the round number, but on different cycle lengths, so consecutive rounds
     * change discipline AND city rather than grinding through one city's every category.
     *
     * @param  array<string, mixed>  $criteria
     * @return array{0: string, 1: string}
     */
    public function queryFor(int $round, array $criteria = []): array
    {
        $regions = array_keys(self::METROS);

        $region = isset($criteria['region']) && isset(self::METROS[$criteria['region']])
            ? $criteria['region']
            : $regions[$round % count($regions)];

        $metros = self::METROS[$region];

        // intdiv on the region cycle so a fixed region still walks its whole metro list.
        $metro = $metros[intdiv($round, isset($criteria['region']) ? 1 : count($regions)) % count($metros)];

        $query = self::QUERIES[$round % count(self::QUERIES)];

        // Free-text targeting from the modal rides along with the category term.
        if (filled($criteria['notes'] ?? null)) {
            $query = trim($criteria['notes']).' '.$query;
        }

        return [$query, $metro];
    }

    /**
     * Run one text search, following pagination until enough results or the pages run out.
     *
     * @return list<array<string, mixed>>
     */
    private function search(string $textQuery, int $wanted, int &$requests): array
    {
        $places = [];
        $pageToken = null;
        $maxPages = max(1, (int) config('prospecting.places_max_pages', 2));

        for ($page = 0; $page < $maxPages; $page++) {
            $body = ['textQuery' => $textQuery, 'pageSize' => 20];

            if ($pageToken !== null) {
                $body['pageToken'] = $pageToken;
            }

            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => self::FIELD_MASK,
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->post(self::API_URL, $body);

            $requests++;

            if (! $response->successful()) {
                throw ProspectingException::placesFailed(
                    $response->status(),
                    (string) ($response->json('error.message') ?? $response->body()),
                );
            }

            $places = [...$places, ...($response->json('places') ?? [])];
            $pageToken = $response->json('nextPageToken');

            if ($pageToken === null || count($places) >= $wanted * 3) {
                break;
            }
        }

        return $places;
    }

    /**
     * Turn a Places result into the shape the pipeline expects, or null if it is not a target.
     *
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>|null
     */
    public function toCompany(array $place, string $specialty): ?array
    {
        $name = $place['displayName']['text'] ?? null;
        $website = $place['websiteUri'] ?? null;

        if (blank($name)) {
            return null;
        }

        // No website means no page to read an address off, and the whole provenance rule says
        // an address has to come off a page we fetched.
        if (blank($website)) {
            return null;
        }

        if (($place['businessStatus'] ?? 'OPERATIONAL') !== 'OPERATIONAL') {
            return null;
        }

        if ($this->isExcludedBrand($name, $website)) {
            return null;
        }

        return [
            'company' => $name,
            'website' => $website,
            'city' => $this->addressPart($place, 'locality'),
            'state' => $this->addressPart($place, 'administrative_area_level_1', short: true),
            // Places has no owner field. NameFinder recovers this from the agency's own site.
            'contact_name' => null,
            'title' => null,
            'phone' => $place['nationalPhoneNumber'] ?? null,
            // No cited page, so the harvester falls back to its usual contact/about/team walk.
            'contact_page_url' => null,
            'email_hint' => null,
            'email_source_url' => null,
            'specialties' => $specialty,
        ];
    }

    public function isExcludedBrand(string $name, ?string $website): bool
    {
        $haystack = strtolower($name.' '.($website ?? ''));

        foreach (self::EXCLUDED_BRANDS as $brand) {
            if (str_contains($haystack, $brand)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $place
     */
    private function addressPart(array $place, string $type, bool $short = false): ?string
    {
        foreach ($place['addressComponents'] ?? [] as $component) {
            if (in_array($type, $component['types'] ?? [], true)) {
                return $short
                    ? ($component['shortText'] ?? $component['longText'] ?? null)
                    : ($component['longText'] ?? $component['shortText'] ?? null);
            }
        }

        // Fall back to parsing the formatted address: "123 Main St, Madison, WI 53703, USA".
        if ($type === 'locality' && filled($place['formattedAddress'] ?? null)) {
            $parts = array_map('trim', explode(',', $place['formattedAddress']));

            return $parts[1] ?? null;
        }

        if ($type === 'administrative_area_level_1' && filled($place['formattedAddress'] ?? null)) {
            $parts = array_map('trim', explode(',', $place['formattedAddress']));

            return isset($parts[2]) ? Str::before($parts[2], ' ') : null;
        }

        return null;
    }
}
