<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\HostingAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Loads hosting accounts from a CSV of company, domain, term start, term end, rate, total.
 *
 * Columns are read by position, so the header row's wording does not matter, and "$" or
 * thousands separators in the money columns are fine. Blank and total rows are skipped.
 *
 * Each row is linked to an existing client where one matches — same company, a contact
 * email on the same domain, or a domain one typo away (winnerscirleprint.com vs
 * winnerscircleprint.com). With --create-clients, the rest get a placeholder client
 * ("Jane Doe", doe@their-domain) to be filled in later; placeholder addresses are never
 * sent invoices (see HostingAccount::billingEmails()).
 *
 * Re-running updates accounts by domain rather than duplicating them. --dry-run shows the
 * whole plan without writing anything.
 */
class ImportHostingAccounts extends Command
{
    protected $signature = 'hosting:import
        {file=database/data/hosting-accounts.csv : CSV to import, relative to the app folder}
        {--create-clients : Create a placeholder client for rows with no matching client}
        {--dry-run : Show what would happen without changing anything}';

    protected $description = 'Import hosting accounts from a CSV, linking or creating clients';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // header
        $clients = Client::all();
        $dry = (bool) $this->option('dry-run');
        $table = [];
        $created = 0;

        while (($cells = fgetcsv($handle)) !== false) {
            [$name, $domain, $start, $end, $rate, $total] = array_pad(array_map(fn ($c) => trim((string) $c), $cells), 6, '');
            $domain = Str::lower(preg_replace(['#^https?://#i', '#/.*$#'], '', $domain));

            if ($name === '' || $domain === '') {
                continue; // blank and "Total" rows
            }

            $start = Carbon::parse($start);
            $end = Carbon::parse($end);
            $rate = $this->money($rate);
            $total = $this->money($total) ?: $rate * 12;

            [$client, $how] = $this->matchClient($clients, $name, $domain);

            if (! $client && $this->option('create-clients')) {
                $how = 'NEW client';
                $client = new Client([
                    'name' => 'Jane Doe',
                    'email' => HostingAccount::PLACEHOLDER_MAILBOX . '@' . $this->root($domain),
                    'company' => $name,
                    'domain' => $domain,
                ]);

                if (! $dry) {
                    $client->save();
                    $clients->push($client);
                }

                $created++;
            }

            if (! $dry) {
                HostingAccount::updateOrCreate(['domain' => $domain], [
                    'client_id' => $client?->id,
                    'name' => $name,
                    'term_start' => $start->toDateString(),
                    'term_end' => $end->toDateString(),
                    'monthly_rate' => $rate,
                    'term_amount' => $total,
                    'status' => HostingAccount::STATUS_ACTIVE,
                ]);
            }

            $table[] = [
                $name,
                $domain,
                $start->format('n/j/Y') . ' – ' . $end->format('n/j/Y'),
                '$' . number_format($total, 2),
                $client ? trim(($client->company ?: '') . ' · ' . $client->name, ' ·') . " ({$how})" : '— none',
                implode(', ', array_filter([
                    $end->lt($start) ? 'END BEFORE START' : null,
                    $end->isPast() ? 'term already ended' : null,
                    abs($total - $rate * 12) > 0.009 ? 'total ≠ rate × 12' : null,
                ])),
            ];
        }

        fclose($handle);

        $this->table(['Account', 'Domain', 'Term', 'Total', 'Client', 'Check'], $table);

        $this->info(($dry ? '[dry run — nothing saved] ' : '')
            . count($table) . ' hosting account(s) ' . ($dry ? 'would be ' : '') . 'imported; '
            . $created . ' placeholder client(s) ' . ($dry ? 'would be ' : '') . 'created.');

        if ($created && ! $this->option('create-clients')) {
            $this->line('Re-run with --create-clients to create clients for the unmatched rows.');
        }

        return self::SUCCESS;
    }

    /** @return array{0: ?Client, 1: string} */
    private function matchClient(Collection $clients, string $name, string $domain): array
    {
        $norm = fn (?string $s) => Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $s));
        $root = $this->root($domain);
        $rootOf = fn (Client $c) => array_filter([
            $c->domain ? $this->root($c->domain) : null,
            str_contains((string) $c->email, '@') ? $this->root(Str::after($c->email, '@')) : null,
        ]);

        if ($c = $clients->first(fn (Client $c) => $norm($c->company) !== '' && $norm($c->company) === $norm($name))) {
            return [$c, 'same company'];
        }

        if ($c = $clients->first(fn (Client $c) => in_array($root, $rootOf($c), true))) {
            return [$c, 'same domain'];
        }

        // One slip either way, on a name long enough that it is not a coincidence.
        $near = fn (Client $c) => collect($rootOf($c))->contains(fn (string $r) => strlen($r) > 10 && $r !== $root
            && levenshtein(Str::before($r, '.'), Str::before($root, '.')) <= 2);

        if ($c = $clients->first($near)) {
            return [$c, 'near-match domain — check'];
        }

        return [null, ''];
    }

    /** The registrable part of a host: app.awhearn.com → awhearn.com. */
    private function root(string $host): string
    {
        $host = Str::lower(preg_replace(['#^https?://#i', '#^www\.#i', '#[/:].*$#'], '', trim($host)));

        return implode('.', array_slice(explode('.', $host), -2));
    }

    private function money(string $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $value);
    }
}
