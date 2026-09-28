<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Find Prospects — automated discovery
    |--------------------------------------------------------------------------
    |
    | divStrong sells development capacity to creative and media teams: digital
    | marketing agencies, SEO firms, design studios, video/media houses. Those
    | shops sell strategy and craft; most of them have no deep engineering bench,
    | so custom builds for their own clients get subcontracted. That is the pitch,
    | and this is the machinery that finds the people to make it to.
    |
    | Two sources feed one pipeline. Google Places enumerates real agencies by
    | category and metro for a fraction of a cent per twenty; Claude with web
    | search costs six figures of tokens a round but comes back with principals
    | named. Either way the email address itself is read off the agency's own
    | site by PageHarvester — the model is never trusted for an address, because
    | a model asked for fifty will produce fifty and the invented ones look
    | exactly like the real ones until they bounce.
    |
    | Credentials live in .env, never here.
    */
    'source' => env('PROSPECTING_SOURCE', 'auto'),

    'places_timeout' => (int) env('PROSPECTING_PLACES_TIMEOUT', 30),

    // Pages of 20 results per query. Each page is one billable Places request.
    'places_max_pages' => (int) env('PROSPECTING_PLACES_MAX_PAGES', 2),

    /*
    | Refuse to import anyone we cannot address by name.
    |
    | "Hi there" to a stranger reads as bulk mail inside three words, and an
    | agency principal is exactly the reader who bins it. NameFinder tries the
    | address, the page and the job titles on it before giving up, so this
    | rejects only agencies where nobody is named anywhere on their own site.
    */
    'require_contact_name' => (bool) env('PROSPECTING_REQUIRE_NAME', true),

    'max_rounds' => (int) env('PROSPECTING_MAX_ROUNDS', 10),

    /*
    | THE cost control here. Server-side web search runs an agentic loop inside
    | one request and re-bills the whole accumulated context on every step, so
    | input tokens grow with the SQUARE of the search budget while useful output
    | does not. Six is the measured sweet spot on the PromoSoft build this is
    | modelled on; twelve cost 3.4x and found no more companies.
    */
    'max_searches_per_round' => (int) env('PROSPECTING_MAX_SEARCHES', 6),

    // Rounds issued at once. They are independent by construction — each covers
    // a different region and discipline — so running them in series just adds up
    // their latency. Lower it if the API starts rate limiting.
    'round_concurrency' => (int) env('PROSPECTING_ROUND_CONCURRENCY', 4),

    // Companies asked for per round. Kept small: a round asked for twenty-odd
    // runs past the output limit and comes back truncated mid-object.
    'per_round_cap' => (int) env('PROSPECTING_PER_ROUND_CAP', 12),

    'max_tokens' => (int) env('PROSPECTING_MAX_TOKENS', 16000),

    // Agencies researched per prospect wanted. Agencies publish contact details
    // more readily than most trades, but plenty still hide behind a form, so the
    // pool has to run ahead of the target.
    'pool_multiplier' => (float) env('PROSPECTING_POOL_MULTIPLIER', 1.6),

    // Pages fetched per company before giving up: the cited page, then /contact,
    // /about, /team and friends.
    'max_pages_per_company' => (int) env('PROSPECTING_MAX_PAGES', 5),

    // Pages read looking for a human name, once an address is in hand.
    'max_name_pages' => (int) env('PROSPECTING_MAX_NAME_PAGES', 3),

    // Per-poll work budget. Each poll processes candidates until this many
    // seconds have passed, so one slow mail server cannot time the request out.
    'batch_seconds' => (float) env('PROSPECTING_BATCH_SECONDS', 15),
    'batch_size' => (int) env('PROSPECTING_BATCH_SIZE', 6),

    'discovery_timeout' => (int) env('PROSPECTING_DISCOVERY_TIMEOUT', 180),
    'fetch_timeout' => (int) env('PROSPECTING_FETCH_TIMEOUT', 12),

    /*
    | Mailbox-level verification: connect to the recipient's mail server and ask,
    | via RCPT TO, whether the address exists. Stops before DATA, so nothing is
    | delivered. This is the only check here that speaks to whether a send will
    | bounce — an MX lookup proves the DOMAIN takes mail, which an invented
    | address at a real agency also satisfies.
    |
    | Needs outbound port 25, which most hosts block. Blocked reads as "unknown",
    | never as "invalid" — a firewall rule must not silently bin every lead.
    */
    'smtp_probe' => (bool) env('PROSPECTING_SMTP_PROBE', true),
    'smtp_timeout' => (int) env('PROSPECTING_SMTP_TIMEOUT', 8),

    // Announced in EHLO and used as the probe's envelope sender. Strict servers
    // refuse to talk to a host that introduces itself as something unresolvable.
    'helo_host' => env('PROSPECTING_HELO_HOST'),
    'probe_sender' => env('PROSPECTING_PROBE_SENDER'),

    // Sent when fetching contact pages. Honest about who is asking, so anyone
    // reading their logs can see what it was and block it if they want to.
    'user_agent' => env(
        'PROSPECTING_USER_AGENT',
        'divStrongProspector/1.0 (+https://divstrong.com)',
    ),

    // The web search server tool identifier, pinned so an API-side rename is a
    // config change rather than a failing feature.
    'web_search_tool' => env('PROSPECTING_WEB_SEARCH_TOOL', 'web_search_20250305'),

    /*
    |--------------------------------------------------------------------------
    | Cold outreach compliance
    |--------------------------------------------------------------------------
    |
    | CAN-SPAM requires two things on commercial email: a valid physical postal
    | address for the sender, and a working opt-out. Both are rendered by the
    | outreach mail shell, and only for outreach — proposals, invoices and
    | receipts are transactional and must NOT carry an unsubscribe link, or
    | people will opt out of their own paperwork.
    |
    | postal_address has no default on purpose: a wrong address is worse than an
    | obviously missing one. Until it is set the footer omits the block.
    */
    'outreach' => [
        // One line, e.g. "divStrong, 123 Main St, Richmond, VA 23220".
        // A registered agent or PO box is acceptable under the Act; nothing is not.
        'postal_address' => env('OUTREACH_POSTAL_ADDRESS'),

        // Fallback opt-out route for clients that cannot use the link, offered
        // alongside it in the List-Unsubscribe header.
        'unsubscribe_mailto' => env('OUTREACH_UNSUBSCRIBE_MAILTO'),

        // Where the emails send people to book time.
        'schedule_url' => env('OUTREACH_SCHEDULE_URL', 'https://divstrong.com/contact'),

        /*
        | The window drip campaigns may send in, as hours in the booking timezone,
        | weekdays only. Cold email landing at 3am local reads as automated before it
        | is read at all, and a send window is the cheapest deliverability control
        | there is.
        */
        'send_from_hour' => (int) env('OUTREACH_SEND_FROM_HOUR', 8),
        'send_until_hour' => (int) env('OUTREACH_SEND_UNTIL_HOUR', 17),
    ],
];
