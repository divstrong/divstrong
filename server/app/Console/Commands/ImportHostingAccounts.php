<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\HostingAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Loads hosting accounts from a CSV: name, domain, term_start, term_end, rate, total.
 *
 * Each row is linked to an existing client by company, name or domain where one matches;
 * the rest import unlinked and are listed, to be assigned in the admin. Clients are never
 * created here — a client needs an email address, and a spreadsheet of domains does not
 * have one. Re-running updates accounts by domain instead of duplicating them.
 */
class ImportHostingAccounts extends Command
{
    protected $signature = 'hosting:import {file=database/data/hosting-accounts.csv : CSV to import}';

    protected $description = 'Import hosting accounts from a CSV';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), array_shift($rows));
        $clients = Client::all();
        $table = [];

        foreach ($rows as $line => $cells) {
            $row = array_combine($header, array_pad($cells, count($header), ''));
            $domain = Str::lower(preg_replace('#^https?://#i', '', rtrim(trim($row['domain']), '/')));
            $name = trim($row['name']);

            if ($domain === '' || $name === '') {
                $this->warn('Row ' . ($line + 2) . ': missing name or domain — skipped.');

                continue;
            }

            $start = Carbon::parse($row['term_start']);
            $end = Carbon::parse($row['term_end']);
            $rate = (float) preg_replace('/[^0-9.]/', '', $row['rate']);
            $total = (float) preg_replace('/[^0-9.]/', '', $row['total']);

            $client = $this->matchClient($clients, $name, $domain);

            HostingAccount::updateOrCreate(['domain' => $domain], [
                'client_id' => $client?->id,
                'name' => $name,
                'term_start' => $start->toDateString(),
                'term_end' => $end->toDateString(),
                'monthly_rate' => $rate,
                'term_amount' => $total ?: $rate * 12,
                'status' => HostingAccount::STATUS_ACTIVE,
            ]);

            $flags = array_filter([
                $client ? null : 'no client match',
                $end->lt($start) ? 'END BEFORE START' : null,
                $end->isPast() ? 'term already ended' : null,
                abs(($total ?: $rate * 12) - $rate * 12) > 0.009 ? 'total ≠ rate × 12' : null,
            ]);

            $table[] = [$name, $domain, $start->format('n/j/Y') . ' – ' . $end->format('n/j/Y'), '$' . number_format($total ?: $rate * 12, 2), $client ? ($client->company ?: $client->name) : '—', implode(', ', $flags)];
        }

        $this->table(['Account', 'Domain', 'Term', 'Total', 'Client', 'Check'], $table);
        $this->info(count($table) . ' account(s) imported or updated. Fix anything under "Check" in Admin → Hosting.');

        return self::SUCCESS;
    }

    private function matchClient($clients, string $name, string $domain): ?Client
    {
        $norm = fn (?string $s) => Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $s));
        $bare = fn (?string $d) => Str::lower(preg_replace(['#^https?://#i', '#^www\.#i', '#/.*$#'], '', trim((string) $d)));

        // The registrable part, so app.awhearn.com matches a client on awhearn.com.
        $root = implode('.', array_slice(explode('.', $bare($domain)), -2));

        return $clients->first(fn (Client $c) => $norm($c->company) !== '' && $norm($c->company) === $norm($name))
            ?? $clients->first(fn (Client $c) => filled($c->domain) && implode('.', array_slice(explode('.', $bare($c->domain)), -2)) === $root)
            ?? $clients->first(fn (Client $c) => $norm($c->name) === $norm($name));
    }
}
