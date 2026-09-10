<?php

namespace App\Support\Prospecting;

use RuntimeException;

/**
 * A discovery run failed in a way worth showing the person who started it.
 *
 * Messages here reach the UI verbatim, so they say what to do about it rather than what threw.
 */
class ProspectingException extends RuntimeException
{
    public static function missingApiKey(): self
    {
        return new self(
            'No Anthropic API key configured. Add ANTHROPIC_API_KEY to the .env on this server, '
            .'then run php artisan config:cache.',
        );
    }

    public static function missingPlacesKey(): self
    {
        return new self(
            'No Google Places key configured. Add GOOGLE_PLACES_API_KEY to the .env on this '
            .'server, then run php artisan config:cache.',
        );
    }

    public static function placesFailed(int $status, string $message): self
    {
        $hint = match (true) {
            // By far the most common first-run problem: the key exists but the new Places API
            // has never been switched on for the project.
            $status === 403 => ' — check that "Places API (New)" is enabled for this key in the '
                .'Google Cloud console, and that the key has no referrer restriction blocking '
                .'server-side use.',
            $status === 400 => ' — the request was rejected as malformed.',
            $status === 429 => ' — quota exceeded for now.',
            default => '',
        };

        return new self("Google Places request failed (HTTP {$status}){$hint} ".\Illuminate\Support\Str::limit($message, 300));
    }

    public static function apiFailed(int $status, string $body): self
    {
        $hint = match (true) {
            $status === 401 => ' — the API key was rejected.',
            $status === 429 => ' — rate limited, try again shortly.',
            $status >= 500 => ' — the API is having problems, try again shortly.',
            default => '',
        };

        return new self("Discovery request failed (HTTP {$status}){$hint} ".\Illuminate\Support\Str::limit($body, 300));
    }

    public static function unparseable(string $text): self
    {
        return new self('Could not read the shop list back from the model: '.\Illuminate\Support\Str::limit($text, 300));
    }
}
