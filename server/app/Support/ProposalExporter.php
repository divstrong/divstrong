<?php

namespace App\Support;

use App\Models\Proposal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Every proposal's content in a form that is easy to search and quote from: one Markdown file
 * per proposal plus a combined JSON file, zipped.
 *
 * It is the words that are exported — overview, scope, investment, milestones, terms, change
 * requests, roadmap, internal notes. Signature images, signer IP addresses and reference
 * contact details are deliberately left out: nothing a search needs, and not worth carrying
 * around in a file that gets downloaded and shared.
 */
class ProposalExporter
{
    private const RELATIONS = [
        'client', 'estimator', 'scopeItems', 'costItems', 'milestones', 'terms', 'roadmapPhases',
        'notes.user', 'projectReferences', 'teamMembers',
    ];

    /** Build the ZIP and return its path. The caller deletes it once sent. */
    public function zip(?string $path = null): string
    {
        $path ??= storage_path('app/tmp/proposals-export-' . now()->format('Y-m-d-His') . '.zip');
        @mkdir(dirname($path), 0775, true);

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create {$path}");
        }

        $all = [];
        $index = ["# Proposal export", '', 'Exported ' . now()->format('F j, Y g:i A') . '.', '', '| Code | Date | Client | Project | Status | Total |', '|---|---|---|---|---|---|'];

        $this->proposals()->each(function (Proposal $proposal) use ($zip, &$all, &$index) {
            $data = $this->toArray($proposal);
            $all[] = $data;

            $file = 'proposals/' . $proposal->proposal_date?->format('Y-m-d') . '-' . $proposal->uuid . '-'
                . Str::slug(Str::limit($proposal->project_title ?: $proposal->client_company ?: $proposal->client_name, 60, '')) . '.md';
            $zip->addFromString($file, $this->toMarkdown($data));

            $index[] = '| ' . implode(' | ', [
                $proposal->uuid,
                $data['date'] ?? '',
                str_replace('|', '/', $data['client']['company'] ?: $data['client']['name']),
                str_replace('|', '/', (string) $data['project_title']),
                $data['status'],
                '$' . number_format($data['investment']['total'], 2),
            ]) . ' |';
        });

        $zip->addFromString('proposals.json', json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('INDEX.md', implode("\n", $index) . "\n");
        $zip->close();

        return $path;
    }

    /** @return Collection<int, Proposal> */
    public function proposals(): Collection
    {
        return Proposal::with(self::RELATIONS)->orderByDesc('proposal_date')->orderByDesc('id')->get();
    }

    /** @return array<string, mixed> */
    public function toArray(Proposal $p): array
    {
        $status = $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status;

        return [
            'code' => $p->uuid,
            'url' => url('/proposal/' . $p->uuid),
            'project_title' => $p->project_title,
            'rfp_number' => $p->rfp_number,
            'status' => $status,
            'date' => $p->proposal_date?->toDateString(),
            'valid_until' => $p->valid_until?->toDateString(),
            'sent_at' => $p->sent_at?->toDateTimeString(),
            'accepted_at' => $p->accepted_at?->toDateTimeString(),
            'declined_at' => $p->declined_at?->toDateTimeString(),
            'view_count' => (int) $p->view_count,
            'estimator' => $p->estimator?->name,
            'client' => [
                'name' => $p->client_name,
                'email' => $p->client_email,
                'company' => $p->client_company,
                'domain' => $p->client_domain,
            ],
            'overview' => $this->text($p->introduction),
            'roadmap' => $p->roadmap_enabled ? [
                'title' => $p->roadmap_title,
                'subtitle' => $p->roadmap_subtitle,
                'phases' => $p->roadmapPhases->sortBy('sort_order')->map(fn ($r) => [
                    'title' => $r->title,
                    'subtitle' => $r->subtitle,
                    'description' => $this->text($r->description),
                    'weeks' => $r->duration_weeks,
                    'hours' => $r->hours,
                ])->values()->all(),
            ] : null,
            'scope' => $p->scopeItems->sortBy('sort_order')->groupBy(fn ($s) => $s->category ?: 'Scope')
                ->map(fn ($items, $section) => [
                    'section' => $section,
                    'items' => $items->map(fn ($s) => [
                        'title' => $s->title,
                        'description' => $this->text($s->description),
                        'bullets' => array_values((array) $s->bullets),
                    ])->values()->all(),
                ])->values()->all(),
            'investment' => [
                'notes' => $this->text($p->cost_notes),
                'items' => $p->costItems->sortBy('sort_order')->map(fn ($c) => [
                    'description' => $c->description,
                    'quantity' => (float) $c->quantity,
                    'rate' => (float) $c->unit_price,
                    'amount' => (float) $c->amount,
                ])->values()->all(),
                'subtotal' => $p->subtotal,
                'discount' => $p->discount_amount,
                'total' => $p->total,
            ],
            'milestones' => $p->milestones->sortBy('sort_order')->map(fn ($m) => [
                'title' => $m->title,
                'percentage' => (float) $m->percentage,
                'amount' => round(($m->percentage / 100) * $p->total, 2),
                'status' => $m->payment_status,
                'paid_at' => $m->paid_at?->toDateString(),
            ])->values()->all(),
            'change_requests' => $this->text($p->change_request_content),
            'terms' => $p->terms->sortBy('sort_order')->map(fn ($t) => $this->text($t->content))->values()->all(),
            'team' => $p->team_enabled ? $p->teamMembers->map(fn ($t) => trim($t->name . ' — ' . $t->title, ' —'))->values()->all() : [],
            'references' => $p->references_enabled ? $p->projectReferences->map(fn ($r) => array_filter([
                'name' => $r->name,
                'company' => $r->company,
                'project' => $r->project_name,
                'year' => $r->year_completed,
            ]))->values()->all() : [],
            'signed_by' => array_values(array_filter([$p->signature_name, $p->tc_signature_name, $p->cr_signature_name])),
            'internal_notes' => $p->notes->sortBy('created_at')->map(fn ($n) => [
                'at' => $n->created_at?->toDateTimeString(),
                'by' => $n->user?->name,
                'note' => $this->text($n->body),
            ])->values()->all(),
        ];
    }

    public function toMarkdown(array $d): string
    {
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $client = $d['client'];
        $out = [];

        $out[] = '# ' . ($d['project_title'] ?: ($client['company'] ?: $client['name']));
        $out[] = '';
        $out[] = implode("  \n", array_filter([
            '**Code:** ' . $d['code'] . ' · ' . $d['url'],
            '**Client:** ' . implode(' · ', array_filter([$client['name'], $client['company'], $client['email'], $client['domain']])),
            '**Status:** ' . $d['status'] . ($d['accepted_at'] ? ' (accepted ' . $d['accepted_at'] . ')' : ''),
            '**Date:** ' . $d['date'] . ($d['valid_until'] ? ' · valid until ' . $d['valid_until'] : ''),
            $d['rfp_number'] ? '**RFP #:** ' . $d['rfp_number'] : null,
            $d['estimator'] ? '**Estimator:** ' . $d['estimator'] : null,
            '**Total:** ' . $money($d['investment']['total']),
        ]));

        if ($d['overview']) {
            array_push($out, '', '## Overview', '', $d['overview']);
        }

        if ($d['roadmap'] && $d['roadmap']['phases']) {
            array_push($out, '', '## Roadmap' . ($d['roadmap']['title'] ? ' — ' . $d['roadmap']['title'] : ''), '');
            foreach ($d['roadmap']['phases'] as $i => $r) {
                $out[] = ($i + 1) . '. **' . $r['title'] . '**' . ($r['subtitle'] ? ' — ' . $r['subtitle'] : '')
                    . ($r['weeks'] ? " ({$r['weeks']} wk)" : '') . ($r['description'] ? ': ' . $r['description'] : '');
            }
        }

        if ($d['scope']) {
            array_push($out, '', '## Scope of Work');
            foreach ($d['scope'] as $section) {
                array_push($out, '', '### ' . $section['section']);
                foreach ($section['items'] as $item) {
                    array_push($out, '', '**' . $item['title'] . '**' . ($item['description'] ? ' — ' . $item['description'] : ''));
                    foreach ($item['bullets'] as $b) {
                        $out[] = '- ' . $b;
                    }
                }
            }
        }

        $inv = $d['investment'];
        if ($inv['items']) {
            array_push($out, '', '## Investment', '');
            if ($inv['notes']) {
                array_push($out, $inv['notes'], '');
            }
            array_push($out, '| Service | Qty | Rate | Amount |', '|---|---:|---:|---:|');
            foreach ($inv['items'] as $c) {
                $out[] = '| ' . str_replace('|', '/', $c['description']) . ' | ' . rtrim(rtrim(number_format($c['quantity'], 2, '.', ''), '0'), '.')
                    . ' | ' . $money($c['rate']) . ' | ' . $money($c['amount']) . ' |';
            }
            if ($inv['discount'] > 0) {
                $out[] = '| Subtotal | | | ' . $money($inv['subtotal']) . ' |';
                $out[] = '| Discount | | | (' . $money($inv['discount']) . ') |';
            }
            $out[] = '| **Total** | | | **' . $money($inv['total']) . '** |';
        }

        if ($d['milestones']) {
            array_push($out, '', '## Payment Milestones', '');
            foreach ($d['milestones'] as $m) {
                $out[] = '- ' . rtrim(rtrim(number_format($m['percentage'], 2, '.', ''), '0'), '.') . '% (' . $money($m['amount']) . ') — '
                    . $m['title'] . ($m['status'] === 'paid' ? ' — **paid** ' . $m['paid_at'] : '');
            }
        }

        if ($d['change_requests']) {
            array_push($out, '', '## Change Requests', '', $d['change_requests']);
        }

        if ($d['terms']) {
            array_push($out, '', '## Terms & Conditions', '');
            foreach ($d['terms'] as $i => $t) {
                $out[] = ($i + 1) . '. ' . $t;
            }
        }

        if ($d['team']) {
            array_push($out, '', '## Team', '', ...array_map(fn ($t) => '- ' . $t, $d['team']));
        }

        if ($d['references']) {
            array_push($out, '', '## References', '', ...array_map(fn ($r) => '- ' . implode(' · ', $r), $d['references']));
        }

        if ($d['signed_by']) {
            array_push($out, '', '**Signed by:** ' . implode(', ', array_unique($d['signed_by'])));
        }

        if ($d['internal_notes']) {
            array_push($out, '', '## Internal notes', '');
            foreach ($d['internal_notes'] as $n) {
                $out[] = '- ' . $n['at'] . ($n['by'] ? ' (' . $n['by'] . ')' : '') . ': ' . $n['note'];
            }
        }

        return implode("\n", $out) . "\n";
    }

    /** Rich text to plain text, keeping paragraph and list breaks. */
    private function text(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $html = preg_replace(['#<\s*br\s*/?>#i', '#</\s*(p|div|h[1-6])\s*>#i', '#<\s*li[^>]*>#i'], ["\n", "\n\n", "\n- "], $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace(["/[ \t]+/", "/\n{3,}/"], [' ', "\n\n"], $text);

        return trim($text) ?: null;
    }
}
