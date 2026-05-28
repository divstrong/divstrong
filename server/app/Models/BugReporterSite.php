<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BugReporterSite extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'domain',
        'public_key',
        'notify_email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BugReporterSite $site) {
            if (empty($site->public_key)) {
                $site->public_key = self::generateKey();
            }
        });
    }

    public static function generateKey(): string
    {
        return 'bk_'.Str::random(40);
    }

    public function normalizedDomain(): string
    {
        return self::normalizeDomain($this->domain);
    }

    public static function normalizeDomain(?string $value): string
    {
        if (! $value) {
            return '';
        }

        $value = trim($value);
        $value = preg_replace('#^https?://#i', '', $value);
        $value = preg_replace('#/.*$#', '', $value);
        $value = strtolower($value);

        return preg_replace('#^www\.#', '', $value);
    }

    public function matchesOrigin(?string $origin): bool
    {
        if (! $origin) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $host = strtolower($host);
        $host = preg_replace('#^www\.#', '', $host);

        return $host === $this->normalizedDomain();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bugReports(): HasMany
    {
        return $this->hasMany(BugReport::class);
    }
}
