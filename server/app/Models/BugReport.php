<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReport extends Model
{
    public const STATUSES = ['new', 'triaged', 'in_progress', 'resolved', 'wontfix'];

    public const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    public const SEVERITIES = ['critical', 'high', 'low', 'unsure'];

    protected $fillable = [
        'bug_reporter_site_id',
        'what_happened',
        'url',
        'reporter_email',
        'user_agent',
        'viewport_width',
        'viewport_height',
        'console_errors',
        'referrer',
        'screenshot_path',
        'status',
        'priority',
        'severity',
        'ip_address',
        'meta',
        'internal_notes',
        'resolved_at',
    ];

    protected $casts = [
        'console_errors' => 'array',
        'meta' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(BugReporterSite::class, 'bug_reporter_site_id');
    }
}
