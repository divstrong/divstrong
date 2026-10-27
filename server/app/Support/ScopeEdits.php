<?php

namespace App\Support;

use App\Models\Proposal;
use App\Models\ProposalScopeItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Changes to a proposal's scope expressed as operations — what ClaudeService::planScopeChanges()
 * returns from a free-text instruction, previewed on the page and then applied here.
 *
 *   { "action": "create" | "update" | "delete" | "rename_section" | "delete_section",
 *     "item_id": 12, "section": "...", "new_section": "...", "after_section": "...",
 *     "title": "...", "description": "...", "bullets": ["..."] }
 *
 * Sections are just the "category" string shared by their items, so a section's position is
 * where its items sit in sort_order. Applying rebuilds that order from scratch.
 */
class ScopeEdits
{
    private const ACTIONS = ['create', 'update', 'delete', 'rename_section', 'delete_section'];

    private const MAX_OPERATIONS = 250;

    private const MAX_BULLETS = 12;

    /** The current scope as the prompt sees it: sections in order, items with their ids. */
    public static function describe(Collection $items): string
    {
        return $items->groupBy(fn ($i) => $i->category ?: 'Scope')
            ->map(function (Collection $rows, string $section) {
                $lines = ["## {$section}"];

                foreach ($rows as $row) {
                    $lines[] = "- [id {$row->id}] {$row->title}" . (filled($row->description) ? " — {$row->description}" : '');

                    foreach ((array) $row->bullets as $bullet) {
                        $lines[] = "    • {$bullet}";
                    }
                }

                return implode("\n", $lines);
            })
            ->implode("\n\n");
    }

    public static function describeLibrary(Collection $library): string
    {
        return $library->map(fn ($l) => "- [{$l->category}] {$l->title}" . (filled($l->description) ? " — " . mb_substr($l->description, 0, 200) : ''))
            ->implode("\n");
    }

    /**
     * Clean the model's operations against the scope as it stands: unknown ids and no-op
     * updates are dropped. Returns the operations to apply and a readable list for the preview.
     *
     * @return array{operations: array<int, array<string, mixed>>, changes: array<int, array<string, mixed>>}
     */
    public static function prepare(array $operations, Collection $items): array
    {
        $byId = $items->keyBy('id');
        $sections = $items->pluck('category')->map(fn ($c) => $c ?: 'Scope')->unique()->values()->all();
        $clean = [];
        $changes = [];

        foreach (array_slice($operations, 0, self::MAX_OPERATIONS) as $op) {
            if (! is_array($op) || ! in_array($op['action'] ?? null, self::ACTIONS, true)) {
                continue;
            }

            $op = [
                'action' => $op['action'],
                'item_id' => (int) ($op['item_id'] ?? 0),
                'section' => self::text($op['section'] ?? null, 255),
                'new_section' => self::text($op['new_section'] ?? null, 255),
                'after_section' => self::text($op['after_section'] ?? null, 255),
                'title' => self::text($op['title'] ?? null, 255),
                'description' => self::text($op['description'] ?? null, 2000),
                'bullets' => self::bullets($op['bullets'] ?? []),
            ];

            $item = $byId->get($op['item_id']);

            switch ($op['action']) {
                case 'create':
                    if ($op['title'] === '') {
                        continue 2;
                    }
                    $op['section'] = $op['section'] ?: 'Scope';
                    $changes[] = ['action' => 'create', 'section' => $op['section'], 'title' => $op['title'], 'description' => $op['description'], 'bullets' => $op['bullets']];
                    break;

                case 'update':
                    if (! $item) {
                        continue 2;
                    }
                    $diff = array_filter([
                        'title' => $op['title'] !== '' && $op['title'] !== $item->title ? $op['title'] : null,
                        'description' => $op['description'] !== '' && $op['description'] !== (string) $item->description ? $op['description'] : null,
                        'bullets' => $op['bullets'] !== [] && $op['bullets'] !== array_values((array) $item->bullets) ? $op['bullets'] : null,
                        'section' => $op['section'] !== '' && $op['section'] !== ($item->category ?: 'Scope') ? $op['section'] : null,
                    ]);
                    if ($diff === []) {
                        continue 2;
                    }
                    // Naming the item's own section is not a move; blank it so a rename earlier
                    // in the list cannot turn it into one.
                    $op['section'] = $diff['section'] ?? '';
                    $changes[] = array_filter([
                        'action' => 'update',
                        'section' => $item->category ?: 'Scope',
                        'from' => $item->title,
                        'title' => $diff['title'] ?? null,
                        'description' => $diff['description'] ?? null,
                        'bullets' => $diff['bullets'] ?? null,
                        'move_to' => $diff['section'] ?? null,
                    ]);
                    break;

                case 'delete':
                    if (! $item) {
                        continue 2;
                    }
                    $changes[] = ['action' => 'delete', 'section' => $item->category ?: 'Scope', 'title' => $item->title];
                    break;

                case 'rename_section':
                    if (! in_array($op['section'], $sections, true) || $op['new_section'] === '' || $op['new_section'] === $op['section']) {
                        continue 2;
                    }
                    $changes[] = ['action' => 'rename_section', 'section' => $op['section'], 'title' => $op['new_section']];
                    break;

                case 'delete_section':
                    if (! in_array($op['section'], $sections, true)) {
                        continue 2;
                    }
                    $count = $items->filter(fn ($i) => ($i->category ?: 'Scope') === $op['section'])->count();
                    $changes[] = ['action' => 'delete_section', 'section' => $op['section'], 'title' => $op['section'], 'count' => $count];
                    break;
            }

            $clean[] = $op;
        }

        return ['operations' => $clean, 'changes' => $changes];
    }

    /**
     * Apply prepared operations in one transaction and re-sequence sort_order so every section
     * stays contiguous. Operations naming items that no longer exist are skipped.
     *
     * @return array{created: int, updated: int, deleted: int}
     */
    public static function apply(Proposal $proposal, array $operations): array
    {
        $items = $proposal->scopeItems()->orderBy('sort_order')->get();
        $operations = self::prepare($operations, $items)['operations'];

        // Ordered section => list of entries; an entry is an existing model or a new row array.
        $sections = [];
        foreach ($items as $item) {
            $sections[$item->category ?: 'Scope'][] = $item;
        }

        $deleted = [];
        $touched = [];
        $created = 0;

        $find = function (int $id) use (&$sections): ?array {
            foreach ($sections as $name => $entries) {
                foreach ($entries as $i => $entry) {
                    if ($entry instanceof ProposalScopeItem && $entry->id === $id) {
                        return [$name, $i];
                    }
                }
            }

            return null;
        };

        foreach ($operations as $op) {
            switch ($op['action']) {
                case 'create':
                    if (! isset($sections[$op['section']])) {
                        $sections = self::insertSection($sections, $op['section'], $op['after_section']);
                    }
                    $sections[$op['section']][] = [
                        'title' => $op['title'],
                        'description' => $op['description'] ?: null,
                        'bullets' => $op['bullets'] ?: null,
                    ];
                    $created++;
                    break;

                case 'update':
                    if (! $at = $find($op['item_id'])) {
                        break;
                    }
                    [$name, $i] = $at;
                    $model = $sections[$name][$i];
                    $model->title = $op['title'] ?: $model->title;
                    $model->description = $op['description'] ?: $model->description;
                    $model->bullets = $op['bullets'] ?: $model->bullets;
                    $touched[$model->id] = true;

                    if ($op['section'] !== '' && $op['section'] !== $name) {
                        array_splice($sections[$name], $i, 1);
                        if (! isset($sections[$op['section']])) {
                            $sections = self::insertSection($sections, $op['section'], $op['after_section']);
                        }
                        $sections[$op['section']][] = $model;
                    }
                    break;

                case 'delete':
                    if ($at = $find($op['item_id'])) {
                        $deleted[] = $op['item_id'];
                        array_splice($sections[$at[0]], $at[1], 1);
                    }
                    break;

                case 'rename_section':
                    if (! isset($sections[$op['section']])) {
                        break;
                    }
                    // Renaming onto an existing section merges the two at whichever comes first.
                    $renamed = [];
                    foreach ($sections as $name => $entries) {
                        $key = $name === $op['section'] ? $op['new_section'] : $name;
                        $renamed[$key] = array_merge($renamed[$key] ?? [], $entries);
                    }
                    $sections = $renamed;
                    break;

                case 'delete_section':
                    foreach ($sections[$op['section']] ?? [] as $entry) {
                        if ($entry instanceof ProposalScopeItem) {
                            $deleted[] = $entry->id;
                        } else {
                            $created--;
                        }
                    }
                    unset($sections[$op['section']]);
                    break;
            }
        }

        DB::transaction(function () use ($proposal, $sections, $deleted) {
            if ($deleted) {
                $proposal->scopeItems()->whereIn('id', $deleted)->delete();
            }

            $sort = 0;
            foreach ($sections as $name => $entries) {
                foreach ($entries as $entry) {
                    if ($entry instanceof ProposalScopeItem) {
                        $entry->category = $name;
                        $entry->sort_order = $sort++;
                        $entry->save();
                    } else {
                        $proposal->scopeItems()->create($entry + ['category' => $name, 'sort_order' => $sort++]);
                    }
                }
            }
        });

        return ['created' => max(0, $created), 'updated' => count(array_diff_key($touched, array_flip($deleted))), 'deleted' => count(array_unique($deleted))];
    }

    /** Insert an empty section after $after ("" = at the end, "^" = at the start). */
    private static function insertSection(array $sections, string $name, string $after): array
    {
        if ($after === '^') {
            return [$name => []] + $sections;
        }

        if ($after === '' || ! isset($sections[$after])) {
            return $sections + [$name => []];
        }

        $result = [];
        foreach ($sections as $key => $entries) {
            $result[$key] = $entries;
            if ($key === $after) {
                $result[$name] = [];
            }
        }

        return $result;
    }

    private static function text(mixed $value, int $max): string
    {
        if (! is_string($value)) {
            return '';
        }

        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? ''), 0, $max);
    }

    private static function bullets(mixed $bullets): array
    {
        return collect(is_array($bullets) ? $bullets : [])
            ->map(fn ($b) => self::text($b, 500))
            ->filter()
            ->take(self::MAX_BULLETS)
            ->values()
            ->all();
    }
}
