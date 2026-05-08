<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('claude.api_key');
        $this->model = config('claude.model');
        $this->maxTokens = config('claude.max_tokens');
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function analyzeRfp(string $filePath, string $prompt, array $attachmentPaths = []): array
    {
        // Claude analysis can take 30-90s; raise PHP's execution-time cap for this request.
        @set_time_limit(0);

        $content = $this->buildMultiDocContent($filePath, $prompt, $attachmentPaths);

        $response = $this->sendWithRetry($content);

        $rawText = $response->json('content.0.text', '');

        return $this->parseResponse($rawText);
    }

    private function buildMultiDocContent(string $primaryPath, string $prompt, array $attachmentPaths): array
    {
        $content = [];
        $textPieces = [];

        $docs = array_merge([$primaryPath], $attachmentPaths);

        foreach ($docs as $i => $path) {
            $fullPath = Storage::disk('public')->path($path);
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $label = $i === 0 ? 'PRIMARY RFP DOCUMENT' : 'SUPPORTING DOCUMENT ' . $i . ' (' . basename($path) . ')';

            if ($extension === 'pdf') {
                $content[] = [
                    'type' => 'text',
                    'text' => "--- {$label} ---",
                ];
                $content[] = [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => 'application/pdf',
                        'data' => base64_encode(file_get_contents($fullPath)),
                    ],
                ];
            } else {
                $textPieces[] = "--- BEGIN {$label} ---\n\n"
                    . $this->extractText($fullPath, $extension)
                    . "\n\n--- END {$label} ---";
            }
        }

        $instructions = $prompt;

        if (count($docs) > 1) {
            $instructions .= "\n\nNOTE: Multiple documents are provided — the PRIMARY RFP DOCUMENT plus SUPPORTING DOCUMENTS. Consider all of them together when scoring and summarizing. Supporting documents may clarify requirements, add scope, or change the fit assessment.";
        }

        if (! empty($textPieces)) {
            $instructions .= "\n\n" . implode("\n\n", $textPieces);
        }

        $content[] = ['type' => 'text', 'text' => $instructions];

        return $content;
    }

    private function sendWithRetry(array $content, int $maxRetries = 2): \Illuminate\Http\Client\Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $response = Http::timeout(180)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return $response;
            }

            $status = $response->status();
            $body = $response->json();
            $errorType = $body['error']['type'] ?? '';

            Log::warning('Claude API request failed', [
                'attempt' => $attempt,
                'status' => $status,
                'error_type' => $errorType,
            ]);

            // Retry on rate limit (429) or overloaded (529)
            if (in_array($status, [429, 529]) && $attempt < $maxRetries) {
                $retryAfter = (int) $response->header('retry-after', 60);
                $waitSeconds = min($retryAfter, 90);

                Log::info("Rate limited — waiting {$waitSeconds}s before retry (attempt {$attempt}/{$maxRetries})");
                sleep($waitSeconds);
                continue;
            }

            // Friendly error messages
            $message = match ($errorType) {
                'rate_limit_error' => 'Rate limit reached — the document may be too large for your current API tier. Please wait a minute and try again.',
                'overloaded_error' => 'Claude is currently overloaded. Please try again in a few moments.',
                'authentication_error' => 'Invalid API key. Check your ANTHROPIC_API_KEY in Settings.',
                'invalid_request_error' => 'Invalid request — ' . ($body['error']['message'] ?? 'check the document format.'),
                default => 'Claude API error: ' . ($body['error']['message'] ?? $response->body()),
            };

            throw new \RuntimeException($message);
        }
    }

    private function extractText(string $fullPath, string $extension): string
    {
        $text = match ($extension) {
            'txt', 'csv', 'md' => file_get_contents($fullPath),
            'docx' => $this->extractDocxText($fullPath),
            'doc' => $this->extractDocText($fullPath),
            default => throw new \RuntimeException("Unsupported file type: {$extension}"),
        };

        if (empty(trim($text))) {
            throw new \RuntimeException('Could not extract text from the uploaded document.');
        }

        // Sanitize to valid UTF-8
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    private function extractDocText(string $path): string
    {
        $output = [];
        $returnCode = 0;
        exec('antiword ' . escapeshellarg($path), $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            return implode("\n", $output);
        }

        throw new \RuntimeException(
            'Could not extract text from .doc file. Install antiword for .doc support, or convert to .docx or PDF first.'
        );
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

    public function generateProposalContent(string $rfpName, string $summary, array $requirements, array $redFlags): array
    {
        @set_time_limit(0);

        $requirementsList = collect($requirements)->map(fn ($r, $i) => ($i + 1) . ". {$r}")->implode("\n");
        $redFlagsList = collect($redFlags)->map(fn ($r) => "- {$r}")->implode("\n");

        $prompt = <<<PROMPT
You are a proposal writer for a boutique web development and SaaS company called divStrong. Based on the following RFP analysis, generate proposal content.

RFP NAME: {$rfpName}

SUMMARY: {$summary}

KEY REQUIREMENTS:
{$requirementsList}

RED FLAGS (be aware of these but don't mention them in the proposal):
{$redFlagsList}

RESPOND IN THIS EXACT JSON FORMAT:
```json
{
    "introduction": "<A professional 2-4 paragraph HTML introduction/overview for the proposal. Use <p> tags for paragraphs. Address the client's needs, briefly describe our approach, and express enthusiasm for the project. Do NOT use placeholder names — write generically about 'your organization' or 'your team'. Keep it concise and professional.>",
    "scope_items": [
        {
            "category": "<one of: Design, Development, SEO, Hosting, Content, Maintenance>",
            "title": "<short scope item title>",
            "description": "<brief description of this scope item>",
            "bullets": ["<specific deliverable or task>", "<another deliverable>"]
        }
    ],
    "contact_name": "<extracted contact person name from the RFP if available, or null>",
    "contact_email": "<extracted contact email from the RFP if available, or null>",
    "contact_company": "<extracted organization/company name from the RFP if available, or null>"
}
```

Generate 3-6 scope items that logically cover the RFP requirements. Each scope item should have 2-4 bullets.
PROMPT;

        $content = [
            ['type' => 'text', 'text' => $prompt],
        ];

        $response = $this->sendWithRetry($content);
        $rawText = $response->json('content.0.text', '');

        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $rawText, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[1], true);
        } else {
            $parsed = json_decode($rawText, true);
        }

        if (is_array($parsed) && isset($parsed['introduction'])) {
            return $parsed;
        }

        return [
            'introduction' => '<p>' . e($summary) . '</p>',
            'scope_items' => [],
            'contact_name' => null,
            'contact_email' => null,
            'contact_company' => null,
        ];
    }

    private function parseResponse(string $rawText): array
    {
        // Try to extract JSON from the response
        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $rawText, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[1], true);
        } else {
            $parsed = json_decode($rawText, true);
        }

        if (is_array($parsed) && isset($parsed['score'])) {
            return [
                'rfp_name' => $parsed['rfp_name'] ?? null,
                'contact_name' => $this->cleanContactField($parsed['contact_name'] ?? null),
                'contact_title' => $this->cleanContactField($parsed['contact_title'] ?? null),
                'contact_department' => $this->cleanContactField($parsed['contact_department'] ?? null),
                'contact_email' => $this->cleanContactField($parsed['contact_email'] ?? null),
                'contact_phone' => $this->cleanContactField($parsed['contact_phone'] ?? null),
                'due_date' => $this->parseDueDate($parsed['due_date'] ?? null),
                'pre_bid_conference_date' => $this->parseDueDate($parsed['pre_bid_conference_date'] ?? null),
                'pre_bid_conference_details' => $this->cleanContactField($parsed['pre_bid_conference_details'] ?? null),
                'locality_city' => $this->cleanContactField($parsed['locality_city'] ?? null),
                'locality_state' => $this->cleanContactField($parsed['locality_state'] ?? null),
                'locality_county' => $this->cleanContactField($parsed['locality_county'] ?? null),
                'target_department' => $this->cleanContactField($parsed['target_department'] ?? null),
                'score' => (int) max(0, min(100, $parsed['score'])),
                'summary' => $parsed['summary'] ?? '',
                'red_flags' => $parsed['red_flags'] ?? [],
                'requirements' => $parsed['requirements'] ?? [],
                'submission_requirements' => $parsed['submission_requirements'] ?? [],
                'raw_response' => $rawText,
            ];
        }

        // If JSON parsing failed, return raw text with a default structure
        return [
            'contact_name' => null,
            'contact_title' => null,
            'contact_department' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'due_date' => null,
            'pre_bid_conference_date' => null,
            'pre_bid_conference_details' => null,
            'score' => null,
            'summary' => $rawText,
            'red_flags' => [],
            'requirements' => [],
            'submission_requirements' => [],
            'raw_response' => $rawText,
        ];
    }

    private function cleanContactField(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function parseDueDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '' || strtolower($value) === 'null') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
