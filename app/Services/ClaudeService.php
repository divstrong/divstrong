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

    public function analyzeRfp(string $filePath, string $prompt): array
    {
        $fullPath = Storage::disk('public')->path($filePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $content = $this->buildMessageContent($fullPath, $extension, $prompt);

        $response = $this->sendWithRetry($content);

        $rawText = $response->json('content.0.text', '');

        return $this->parseResponse($rawText);
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

    private function buildMessageContent(string $fullPath, string $extension, string $prompt): array
    {
        // For PDFs, send as a document block — Claude reads PDFs natively
        if ($extension === 'pdf') {
            $base64 = base64_encode(file_get_contents($fullPath));

            return [
                [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => 'application/pdf',
                        'data' => $base64,
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $prompt,
                ],
            ];
        }

        // For text-based formats, extract and send as text
        $documentText = $this->extractText($fullPath, $extension);

        return [
            [
                'type' => 'text',
                'text' => $prompt . "\n\n--- BEGIN RFP DOCUMENT ---\n\n" . $documentText . "\n\n--- END RFP DOCUMENT ---",
            ],
        ];
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
                'score' => (int) max(0, min(100, $parsed['score'])),
                'summary' => $parsed['summary'] ?? '',
                'red_flags' => $parsed['red_flags'] ?? [],
                'requirements' => $parsed['requirements'] ?? [],
                'raw_response' => $rawText,
            ];
        }

        // If JSON parsing failed, return raw text with a default structure
        return [
            'score' => null,
            'summary' => $rawText,
            'red_flags' => [],
            'requirements' => [],
            'raw_response' => $rawText,
        ];
    }
}
