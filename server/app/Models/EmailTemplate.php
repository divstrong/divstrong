<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Editable copy for the outreach emails.
 *
 * Templates are deliberately NOT Blade — they are user-editable content stored in the
 * database, and compiling user input as Blade would be arbitrary PHP execution. Instead a
 * small, closed mustache-like syntax is supported:
 *
 *   {{ name }}          escaped substitution
 *   {{{ body }}}        raw substitution (trusted HTML only)
 *   {{#notes}}...{{/notes}}   render the block only when `notes` is non-empty
 *   {{^notes}}...{{/notes}}   render the block only when `notes` IS empty
 *
 * Anything else is left alone.
 */
class EmailTemplate extends Model
{
    /**
     * The three outreach stories. Keys are what the mailables look up, so renaming
     * one detaches its template and drops the app back to the Blade fallback.
     */
    public const AGENCY_INTRO = 'agency_intro';

    public const CLIENT_INTRO = 'client_intro';

    public const GENERAL_UPDATE = 'general_update';

    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'description',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Look up an active template, tolerating the table not existing yet.
     *
     * The mailables fall back to their original Blade views when this returns null, so the
     * app keeps sending correctly whether or not the migration has been run.
     */
    public static function forKey(string $key): ?self
    {
        if (! Schema::hasTable('email_templates')) {
            return null;
        }

        return static::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    public function renderBody(array $vars): string
    {
        return static::interpolate($this->body, $vars);
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    public function renderSubject(array $vars): string
    {
        // Subjects are plain text; strip any tags a raw placeholder might drag in.
        return strip_tags(static::interpolate($this->subject, $vars));
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    public static function interpolate(string $template, array $vars): string
    {
        $filled = static::filledKeys($vars);

        // Conditional sections first, so placeholders inside a dropped block never render.
        $template = preg_replace_callback(
            '/\{\{#\s*(\w+)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s',
            fn (array $m): string => in_array($m[1], $filled, true) ? $m[2] : '',
            $template,
        ) ?? $template;

        // Inverted sections: render only when the value is absent/empty.
        $template = preg_replace_callback(
            '/\{\{\^\s*(\w+)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s',
            fn (array $m): string => in_array($m[1], $filled, true) ? '' : $m[2],
            $template,
        ) ?? $template;

        // Raw substitution — must run before the escaped form, or {{{ x }}} would be
        // consumed as {{ x }} leaving stray braces.
        $template = preg_replace_callback(
            '/\{\{\{\s*(\w+)\s*\}\}\}/',
            fn (array $m): string => (string) ($vars[$m[1]] ?? ''),
            $template,
        ) ?? $template;

        // Escaped substitution.
        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn (array $m): string => e((string) ($vars[$m[1]] ?? '')),
            $template,
        ) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $vars
     * @return array<int, string>
     */
    protected static function filledKeys(array $vars): array
    {
        return array_keys(array_filter(
            $vars,
            fn ($value) => $value !== null && $value !== '' && $value !== false && $value !== [],
        ));
    }
}
