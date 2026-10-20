<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The portable form of a proposal's scope — what Export writes, Import reads, and what the
 * Claude scope draft comes back as — so a scope can move between proposals, between local
 * and live, or out of a chat window, without going through a proposal code.
 *
 *   {
 *     "format": "divstrong-scope", "version": 1,
 *     "sections": [
 *       { "title": "Sprint #1 — Discovery", "items": [
 *           { "title": "...", "description": "...", "bullets": ["...", "..."] }
 *       ] }
 *     ]
 *   }
 *
 * Reading is deliberately forgiving: a flat {"items": [...]} with a "category" on each item,
 * or a bare array of either shape, is accepted too, since hand-written and model-written
 * JSON rarely matches a spec exactly.
 */
class ScopeJson
{
    public const FORMAT = 'divstrong-scope';

    /** Hard caps, so a pasted mistake cannot flood a proposal. */
    private const MAX_ITEMS = 200;

    private const MAX_BULLETS = 12;

    /**
     * Turn JSON text into scope rows: [{category, title, description, bullets}].
     *
     * @return array<int, array{category: string, title: string, description: string, bullets: array<int, string>}>
     *
     * @throws \InvalidArgumentException with a message fit to show the person who pasted it
     */
    public static function parse(string|array $json): array
    {
        $data = is_array($json) ? $json : self::decode($json);

        $rows = [];

        if (array_is_list($data)) {
            $data = isset($data[0]['items']) ? ['sections' => $data] : ['items' => $data];
        }

        foreach ((array) ($data['sections'] ?? []) as $i => $section) {
            if (! is_array($section)) {
                continue;
            }

            $category = self::text($section['title'] ?? $section['category'] ?? $section['name'] ?? null) ?? 'Section ' . ($i + 1);

            foreach ((array) ($section['items'] ?? $section['scope_items'] ?? []) as $item) {
                $rows[] = self::row($item, $category);
            }
        }

        foreach ((array) ($data['items'] ?? []) as $item) {
            $rows[] = self::row($item, self::text(is_array($item) ? ($item['category'] ?? $item['section'] ?? null) : null) ?? 'Scope');
        }

        $rows = array_values(array_filter($rows));

        if ($rows === []) {
            throw new \InvalidArgumentException('No scope items found. Expect "sections" with "items", each item having at least a "title".');
        }

        if (count($rows) > self::MAX_ITEMS) {
            throw new \InvalidArgumentException('That is ' . count($rows) . ' items — more than the ' . self::MAX_ITEMS . ' a single import allows.');
        }

        return $rows;
    }

    /**
     * Scope rows (models or arrays) as exportable JSON, grouped into sections in order.
     *
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function export(Collection|array $items, ?string $title = null): string
    {
        $sections = collect($items)
            ->map(fn ($i) => is_array($i) ? $i : $i->only(['category', 'title', 'description', 'bullets']))
            ->groupBy(fn ($i) => $i['category'] ?: 'Scope', preserveKeys: false)
            ->map(fn (Collection $rows, string $category) => [
                'title' => $category,
                'items' => $rows->map(fn ($r) => [
                    'title' => $r['title'],
                    'description' => (string) ($r['description'] ?? ''),
                    'bullets' => array_values((array) ($r['bullets'] ?? [])),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return json_encode(array_filter([
            'format' => self::FORMAT,
            'version' => 1,
            'title' => $title,
            'sections' => $sections,
        ], fn ($v) => $v !== null), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function decode(string $json): array
    {
        $json = trim($json);

        // Tolerate a fenced block pasted straight out of a chat window.
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $json, $m)) {
            $json = $m[1];
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new \InvalidArgumentException('That is not valid JSON' . (json_last_error() ? ' (' . json_last_error_msg() . ')' : '') . '.');
        }

        return $data;
    }

    private static function row(mixed $item, string $category): ?array
    {
        if (! is_array($item) || ($title = self::text($item['title'] ?? null)) === null) {
            return null;
        }

        $bullets = collect((array) ($item['bullets'] ?? $item['deliverables'] ?? []))
            ->map(fn ($b) => self::text(is_string($b) ? $b : null))
            ->filter()
            ->take(self::MAX_BULLETS)
            ->values()
            ->all();

        return [
            'category' => mb_substr($category, 0, 255),
            'title' => mb_substr($title, 0, 255),
            'description' => self::text($item['description'] ?? null) ?? '',
            'bullets' => $bullets,
        ];
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');

        return $value === '' ? null : $value;
    }
}
