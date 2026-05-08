<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BudgetIntelService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('claude.api_key');
        $this->model = config('claude.model');
        $this->maxTokens = (int) config('claude.max_tokens', 4096);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Scan a user-uploaded budget document directly (PDF native, DOCX/TXT/CSV/MD text-extracted).
     * Skips web search entirely — Claude reads only the document.
     */
    public function analyzeFromDocument(string $filePath, ?string $city, ?string $state, ?string $county, ?string $department): array
    {
        @set_time_limit(0);

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $absolute = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);

        if (! is_file($absolute)) {
            throw new \RuntimeException('Uploaded budget file could not be read.');
        }

        $promptText = $this->buildDocumentPrompt($city, $state, $county, $department);

        $content = [];

        if ($extension === 'pdf') {
            $content[] = ['type' => 'text', 'text' => '--- BUDGET DOCUMENT ---'];
            $content[] = [
                'type' => 'document',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'application/pdf',
                    'data' => base64_encode(file_get_contents($absolute)),
                ],
            ];
            $content[] = ['type' => 'text', 'text' => $promptText];
        } else {
            $extracted = $this->extractDocumentText($absolute, $extension);
            $content[] = [
                'type' => 'text',
                'text' => "--- BUDGET DOCUMENT ---\n\n" . $extracted . "\n\n--- END BUDGET DOCUMENT ---\n\n" . $promptText,
            ];
        }

        $response = Http::timeout(180)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => max($this->maxTokens, 4096),
                'messages' => [[
                    'role' => 'user',
                    'content' => $content,
                ]],
            ]);

        if (! $response->successful()) {
            $this->throwApiError($response);
        }

        $rawText = $this->extractText($response->json('content', []));

        return $this->parseResponse($rawText);
    }

    /**
     * Run a web-search-powered budget intel pass for a municipality.
     * Returns a focused snapshot: total budget, CIP, technology and department set-asides, and a brief summary.
     */
    public function search(string $city = null, string $state = null, string $county = null, string $department = null): array
    {
        // Web-search calls can run 60-180s; raise PHP's execution-time cap for this request.
        @set_time_limit(0);

        $prompt = $this->buildPrompt($city, $state, $county, $department);

        $response = Http::timeout(180)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => max($this->maxTokens, 4096),
                'tools' => [[
                    'type' => 'web_search_20250305',
                    'name' => 'web_search',
                    'max_uses' => 8,
                ]],
                'messages' => [[
                    'role' => 'user',
                    'content' => $prompt,
                ]],
            ]);

        if (! $response->successful()) {
            $this->throwApiError($response);
        }

        $rawText = $this->extractText($response->json('content', []));

        return $this->parseResponse($rawText);
    }

    private function throwApiError(\Illuminate\Http\Client\Response $response): void
    {
        $body = $response->json();
        $errorType = $body['error']['type'] ?? '';
        $message = match ($errorType) {
            'rate_limit_error' => 'Rate limit reached — please wait a minute and try again.',
            'overloaded_error' => 'Claude is currently overloaded. Please try again shortly.',
            'authentication_error' => 'Invalid API key. Check ANTHROPIC_API_KEY in Settings.',
            'invalid_request_error' => 'Invalid request: ' . ($body['error']['message'] ?? 'check the request format.'),
            default => 'Claude API error: ' . ($body['error']['message'] ?? $response->body()),
        };
        Log::warning('Budget Intel API failure', ['status' => $response->status(), 'body' => $body]);
        throw new \RuntimeException($message);
    }

    private function extractDocumentText(string $absolutePath, string $extension): string
    {
        $text = match ($extension) {
            'txt', 'csv', 'md' => file_get_contents($absolutePath),
            'docx' => $this->extractDocxText($absolutePath),
            'doc' => $this->extractDocText($absolutePath),
            default => throw new \RuntimeException("Unsupported budget document type: .{$extension}. Try a PDF, DOCX, or TXT/CSV/MD file."),
        };

        if (empty(trim((string) $text))) {
            throw new \RuntimeException('Could not extract any text from the uploaded budget document.');
        }

        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    private function extractDocxText(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open .docx file.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new \RuntimeException('Could not read content from .docx file.');
        }
        $text = strip_tags(str_replace('<', ' <', $xml));
        return preg_replace('/\s+/', ' ', trim($text));
    }

    private function extractDocText(string $path): string
    {
        $output = [];
        $rc = 0;
        exec('antiword ' . escapeshellarg($path), $output, $rc);
        if ($rc === 0 && ! empty($output)) {
            return implode("\n", $output);
        }
        throw new \RuntimeException('Could not extract text from .doc file. Convert it to PDF or DOCX first.');
    }

    private function extractText(array $content): string
    {
        $text = '';
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= ($block['text'] ?? '') . "\n";
            }
        }
        return trim($text);
    }

    private function parseResponse(string $rawText): array
    {
        $parsed = $this->extractJsonPayload($rawText);

        if (! is_array($parsed)) {
            return [
                'fiscal_year' => null,
                'source_url' => null,
                'summary' => trim($rawText),
                'total_budget' => null,
                'cip_budget' => null,
                'tech_set_aside' => null,
                'tech_set_aside_notes' => null,
                'department_name' => null,
                'department_set_aside' => null,
                'department_set_aside_notes' => null,
                'search_notes' => 'Could not parse a structured response from the model.',
            ];
        }

        return [
            'fiscal_year' => $parsed['fiscal_year'] ?? null,
            'source_url' => $parsed['source_url'] ?? null,
            'summary' => $parsed['summary'] ?? '',
            'total_budget' => $this->numericOrNull($parsed['total_budget'] ?? null),
            'cip_budget' => $this->numericOrNull($parsed['cip_budget'] ?? null),
            'tech_set_aside' => $this->numericOrNull($parsed['tech_set_aside'] ?? null),
            'tech_set_aside_notes' => $parsed['tech_set_aside_notes'] ?? null,
            'department_name' => $parsed['department_name'] ?? null,
            'department_set_aside' => $this->numericOrNull($parsed['department_set_aside'] ?? null),
            'department_set_aside_notes' => $parsed['department_set_aside_notes'] ?? null,
            'search_notes' => $parsed['search_notes'] ?? null,
        ];
    }

    /**
     * Pull a JSON object out of Claude's free-form output. Tries (in order):
     * 1. The LAST ```json ... ``` fenced block (web-search responses often have multiple)
     * 2. The LAST ``` ... ``` fenced block that parses as JSON
     * 3. The whole trimmed text
     * 4. Each balanced {...} block found in the text, preferring later ones whose keys
     *    match our expected schema.
     */
    private function extractJsonPayload(string $text): ?array
    {
        // 1. Last ```json fence
        if (preg_match_all('/```json\s*(\{[\s\S]*?\})\s*```/u', $text, $m)) {
            for ($i = count($m[1]) - 1; $i >= 0; $i--) {
                $decoded = json_decode($m[1][$i], true);
                if (is_array($decoded)) return $decoded;
            }
        }

        // 2. Last generic ``` fence containing what looks like a JSON object
        if (preg_match_all('/```[a-z]*\s*(\{[\s\S]*?\})\s*```/u', $text, $m)) {
            for ($i = count($m[1]) - 1; $i >= 0; $i--) {
                $decoded = json_decode($m[1][$i], true);
                if (is_array($decoded)) return $decoded;
            }
        }

        // 3. Whole text
        $whole = json_decode(trim($text), true);
        if (is_array($whole)) return $whole;

        // 4. Walk the text and try every balanced {...} block. Prefer the one that
        //    actually looks like a budget snapshot.
        $candidates = $this->findBalancedObjects($text);
        $best = null;
        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);
            if (! is_array($decoded)) continue;
            $score = 0;
            foreach (['fiscal_year', 'total_budget', 'cip_budget', 'summary', 'tech_set_aside'] as $key) {
                if (array_key_exists($key, $decoded)) $score++;
            }
            if ($score > 0 && ($best === null || $score >= $best['score'])) {
                $best = ['decoded' => $decoded, 'score' => $score];
            }
        }
        if ($best !== null) return $best['decoded'];

        // Last-ditch: any decodable object at all
        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) return $decoded;
        }

        return null;
    }

    /** Return every brace-balanced substring of $text that looks like a JSON object. */
    private function findBalancedObjects(string $text): array
    {
        $out = [];
        $len = strlen($text);
        $offset = 0;
        while (($start = strpos($text, '{', $offset)) !== false) {
            $depth = 0;
            $inString = false;
            $escape = false;
            $end = null;
            for ($i = $start; $i < $len; $i++) {
                $c = $text[$i];
                if ($escape) { $escape = false; continue; }
                if ($c === '\\') { $escape = true; continue; }
                if ($c === '"') { $inString = ! $inString; continue; }
                if ($inString) continue;
                if ($c === '{') {
                    $depth++;
                } elseif ($c === '}') {
                    $depth--;
                    if ($depth === 0) { $end = $i; break; }
                }
            }
            if ($end !== null) {
                $out[] = substr($text, $start, $end - $start + 1);
                $offset = $end + 1;
            } else {
                break;
            }
        }
        return $out;
    }

    private function numericOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '' || ! is_numeric($v)) return null;
        return (float) $v;
    }

    public static function getDefaultPrompt(): string
    {
        return <<<'PROMPT'
You are a budget intelligence analyst for divStrong, a custom web & SaaS development consultancy. Use the web_search tool to find the most recent publicly available annual budget (and CIP if available) for the target municipality, then extract a focused snapshot.

YOUR JOB IS NARROW: extract just four headline dollar figures plus a short summary. Do NOT list every line item. Do NOT itemize the CIP. We want a fast snapshot, not a deep analysis.

THE FOUR HEADLINE NUMBERS:

1. total_budget — the municipality's TOTAL adopted annual budget across all funds for the most recent fiscal year you can confirm. Express as USD whole-dollar amount (e.g., 521000000 for $521M).
2. cip_budget — the total Capital Improvement Program / capital budget for that same fiscal year. If the city publishes a multi-year CIP, use the current-year programmed total. Null if no separate capital budget is published.
3. tech_set_aside — total dollars in the budget specifically tagged for software / IT / technology / digital / cybersecurity / system replacement / modernization. This may be a single I&T department total, a CIP track for technology, or the sum of obvious tech-tagged line items. Null if not separable.
4. department_set_aside — only fill if the RFP targets a specific department/division. Total dollars in that department's adopted budget for the current fiscal year. Null if the RFP is enterprise-wide or the department isn't found in the budget.

For each numeric value, also write a short notes string explaining how you arrived at it (e.g., "I&T department CIP track + Application Services + Cyber Security programs", or "Library Services FY 2026 operating budget"). The notes line is what helps a human verify the figure.

The summary is a 3-5 sentence narrative orientation: fiscal year, total size, the technology spending posture, any standout dynamics (sharp YoY growth, dedicated CIP track, recent modernization push). It should give a salesperson context to walk into a conversation; specific numbers in it are fine but the four headline values above are the source of truth for storage.

Search strategy: Start with "{city} {state} adopted budget {recent year}" and "{city} CIP capital improvement plan". Prefer the official municipality website (.gov / .us / .org) and the most recently adopted fiscal year. If only a budget summary or budget book is available, that's fine — extract what you can.

RESPOND IN THIS EXACT JSON FORMAT (and ONLY JSON, wrapped in a ```json fence):
```json
{
    "fiscal_year": "<the fiscal year covered, e.g., 'FY 2026' or '2025-2026', or null>",
    "source_url": "<URL of the single most authoritative budget document you used, or null>",
    "summary": "<3-5 sentence narrative summary>",
    "total_budget": <number in USD whole dollars, or null>,
    "cip_budget": <number in USD whole dollars, or null>,
    "tech_set_aside": <number in USD whole dollars, or null>,
    "tech_set_aside_notes": "<short string explaining how the tech figure was derived, or null>",
    "department_name": "<the department this RFP targets if it was provided, or null>",
    "department_set_aside": <number in USD whole dollars, or null>,
    "department_set_aside_notes": "<short string explaining how the department figure was derived, or null>",
    "search_notes": "<any caveats: budget not yet adopted, summary-only data, multiple years merged, locality unclear, etc. Null if straightforward.>"
}
```

If you cannot find a public budget for this municipality, still respond with the JSON structure: nulls for the dollar values, and search_notes explaining what you tried and why nothing was found.
PROMPT;
    }

    private function buildPrompt(?string $city, ?string $state, ?string $county, ?string $department): string
    {
        return self::getDefaultPrompt() . "\n\n" . $this->localityContext($city, $state, $county, $department);
    }

    private function buildDocumentPrompt(?string $city, ?string $state, ?string $county, ?string $department): string
    {
        // Document-scan variant — same JSON output shape, but instructs the model to read
        // ONLY the attached document (no web search). Reuses the headline-only schema.
        $base = preg_replace(
            '/Search strategy:.*?adopted fiscal year\.[^\n]*/s',
            'Source: read ONLY the attached budget document. Do not invent data. If the document is incomplete (e.g., a summary slide deck), populate what you can and explain gaps in search_notes.',
            self::getDefaultPrompt()
        );

        return $base . "\n\n" . $this->localityContext($city, $state, $county, $department);
    }

    private function localityContext(?string $city, ?string $state, ?string $county, ?string $department): string
    {
        $localityParts = array_filter([
            $city,
            $county ? ($county . ' County') : null,
            $state,
        ]);
        $locality = empty($localityParts) ? 'the target municipality' : implode(', ', $localityParts);

        $context = "TARGET MUNICIPALITY: " . $locality;

        if (! empty($department)) {
            $context .= "\nTARGET DEPARTMENT (the RFP is focused on this division): " . $department
                . "\nWhen calculating department_set_aside, look for this department's adopted budget specifically.";
        } else {
            $context .= "\nThis RFP appears enterprise-wide; leave department_set_aside null.";
        }

        return $context;
    }
}
