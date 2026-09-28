<?php

namespace App\Models;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One email in a sequence: which template to render, and how long after the previous
 * step it goes out.
 */
class CampaignStep extends Model
{
    protected $fillable = [
        'campaign_id', 'position', 'name', 'template_key', 'delay_days', 'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'delay_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): ?EmailTemplate
    {
        return EmailTemplate::forKey($this->template_key);
    }

    /**
     * The label this step's activity is tagged with, and the one the Postmark webhook
     * stamps onto opens and clicks. Campaign-scoped so two campaigns using the same
     * template still report separately.
     */
    public function activityLabel(): string
    {
        return $this->campaign->name . ' · ' . $this->name;
    }
}
