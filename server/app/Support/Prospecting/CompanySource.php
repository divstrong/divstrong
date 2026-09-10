<?php

namespace App\Support\Prospecting;

/**
 * Something that can produce a list of candidate companies.
 *
 * Two implementations, with very different economics:
 *
 *   PlacesDiscovery   Google Places. Enumerates real businesses in a metro by category for a
 *                     fraction of a cent per twenty. Gives name, site, phone and address, and
 *                     no principal's name at all.
 *   ContactDiscovery  Claude with web search. Finds the founder by name, reads local press and
 *                     association listings, and costs six figures of tokens a round because
 *                     web search re-bills its accumulated context on every step of its loop.
 *
 * Both feed the same pipeline: whichever one supplies the company, PageHarvester still fetches the
 * site and reads the address off it, so the provenance guarantee does not depend on the source.
 *
 * The runner picks between them from config and never needs to know which it got.
 */
interface CompanySource
{
    /** Can this source run at all — is its key configured? */
    public function isConfigured(): bool;

    /** Short name for logs and the run report. */
    public function name(): string;

    /**
     * Fetch several rounds' worth of companies.
     *
     * Rounds are just an index: each maps to a different region and discipline so repeated
     * rounds do not re-cover the same ground.
     *
     * A failed round is reported in `errors` rather than thrown, so one bad request cannot cost
     * the rest of the batch.
     *
     * @param  list<int>  $rounds
     * @param  array<string, mixed>  $criteria  region override and free-text targeting
     * @param  list<string>  $excludeDomains  already in the book
     * @return array{
     *     companies: list<array<string, mixed>>,
     *     errors: list<string>,
     *     usage: array{input: int, output: int, searches: int}
     * }
     */
    public function discoverRounds(array $rounds, int $perRound, array $criteria = [], array $excludeDomains = []): array;
}
