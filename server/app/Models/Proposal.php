<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\ProposalTerm;

class Proposal extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'estimator_id', 'client_id', 'proposal_date', 'client_name', 'client_email',
        'client_company', 'client_domain', 'project_title', 'rfp_number', 'cover_image',
        'introduction', 'overview_image', 'cost_notes', 'valid_until',
        'discount_enabled', 'discount_type', 'discount_value',
        'roadmap_enabled', 'roadmap_title', 'roadmap_subtitle', 'roadmap_hours_per_sprint', 'roadmap_months',
        'differentiator_enabled', 'differentiator_headline', 'differentiator_attribution', 'differentiator_background',
        'about_enabled', 'overview_enabled',
        'investment_enabled', 'milestones_enabled', 'changes_enabled', 'terms_enabled',
        'vpat_enabled', 'performance_enabled', 'references_enabled', 'team_enabled',
        'process_enabled', 'process_eyebrow', 'process_heading', 'process_subheading',
        'process_background', 'process_stages',
        'nav_hidden_sections',
        'status',
        'change_request_content', 'cr_signature_name', 'cr_signature_data', 'cr_signed_at',
        'tc_signature_name', 'tc_signature_data', 'tc_signed_at',
        'sent_at', 'first_viewed_at', 'last_viewed_at', 'view_count',
        'accepted_at', 'declined_at', 'accepted_ip', 'signature_data', 'signature_name',
    ];

    protected function casts(): array
    {
        return [
            'proposal_date' => 'date',
            'valid_until' => 'date',
            'status' => ProposalStatus::class,
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cr_signed_at' => 'datetime',
            'tc_signed_at' => 'datetime',
            'discount_enabled' => 'boolean',
            'discount_value' => 'decimal:2',
            'roadmap_enabled' => 'boolean',
            'roadmap_hours_per_sprint' => 'integer',
            'roadmap_months' => 'integer',
            'differentiator_enabled' => 'boolean',
            'about_enabled' => 'boolean',
            'overview_enabled' => 'boolean',
            'investment_enabled' => 'boolean',
            'milestones_enabled' => 'boolean',
            'changes_enabled' => 'boolean',
            'terms_enabled' => 'boolean',
            'vpat_enabled' => 'boolean',
            'performance_enabled' => 'boolean',
            'references_enabled' => 'boolean',
            'team_enabled' => 'boolean',
            'process_enabled' => 'boolean',
            'process_stages' => 'array',
            'nav_hidden_sections' => 'array',
        ];
    }

    /**
     * Every section that can appear in the sticky nav, with its label and whether it
     * is currently enabled on this proposal. Used by the customize-nav modal so the
     * admin can pick which enabled sections appear in the nav.
     */
    public function getNavCandidatesAttribute(): array
    {
        return collect([
            ['id' => 'overview',    'label' => 'Overview',      'enabled' => (bool) $this->overview_enabled],
            ['id' => 'roadmap',     'label' => 'Roadmap',       'enabled' => (bool) $this->roadmap_enabled],
            ['id' => 'about',       'label' => 'About',         'enabled' => (bool) $this->about_enabled],
            ['id' => 'scope',       'label' => 'Scope',         'enabled' => true],
            ['id' => 'process',     'label' => 'Process',       'enabled' => (bool) $this->process_enabled],
            ['id' => 'investment',  'label' => 'Investment',    'enabled' => (bool) $this->investment_enabled],
            ['id' => 'why-custom',  'label' => 'Why Custom',    'enabled' => (bool) $this->differentiator_enabled],
            ['id' => 'milestones',  'label' => 'Milestones',    'enabled' => (bool) $this->milestones_enabled],
            ['id' => 'team',        'label' => 'Team',          'enabled' => (bool) $this->team_enabled],
            ['id' => 'performance', 'label' => 'Past Work',     'enabled' => (bool) $this->performance_enabled],
            ['id' => 'references',  'label' => 'References',    'enabled' => (bool) $this->references_enabled],
            ['id' => 'changes',     'label' => 'Changes',       'enabled' => (bool) $this->changes_enabled],
            ['id' => 'vpat',        'label' => 'Accessibility', 'enabled' => (bool) $this->vpat_enabled],
            ['id' => 'terms',       'label' => 'Terms',         'enabled' => (bool) $this->terms_enabled],
        ])->filter(fn ($s) => $s['enabled'])->values()->all();
    }

    /** Sections actually rendered in the nav: enabled AND not hidden. */
    public function getNavSectionsAttribute(): array
    {
        $hidden = $this->nav_hidden_sections ?? [];

        return collect($this->nav_candidates)
            ->reject(fn ($s) => in_array($s['id'], $hidden, true))
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal) {
            if (empty($proposal->uuid)) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (static::where('uuid', $code)->exists());

                $proposal->uuid = $code;
            }
        });
    }

    public function scopeForUser(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && $user->role) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('estimator_id', $user->id);
            });
        }

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estimator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estimator_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(ProposalScopeItem::class)->orderBy('sort_order');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProposalCostItem::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProposalMilestone::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProposalPayment::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(ProposalTerm::class)->orderBy('sort_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProposalNote::class)->orderBy('created_at');
    }

    public function roadmapPhases(): HasMany
    {
        return $this->hasMany(ProposalRoadmapPhase::class)->orderBy('sort_order');
    }

    public function projectReferences(): BelongsToMany
    {
        return $this->belongsToMany(ProjectReference::class, 'proposal_project_reference')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function portfolioItems(): BelongsToMany
    {
        return $this->belongsToMany(PortfolioItem::class, 'portfolio_item_proposal')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class, 'proposal_team_member')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->costItems->sum('amount');
    }

    public function getDiscountAmountAttribute(): float
    {
        if (! $this->discount_enabled || $this->discount_value <= 0) {
            return 0.0;
        }

        if ($this->discount_type === 'percent') {
            return round($this->subtotal * ($this->discount_value / 100), 2);
        }

        return min((float) $this->discount_value, $this->subtotal);
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/proposal/{$this->uuid}");
    }

    public static function defaultProcessStages(): array
    {
        return [
            ['label' => 'Skateboard',  'caption' => 'Prove the core idea works',           'image' => 'images/skateboard.png'],
            ['label' => 'Bicycle',     'caption' => 'Faster, easier to steer',             'image' => 'images/bicycle.png'],
            ['label' => 'Vespa',       'caption' => 'Real range, real utility',            'image' => 'images/vespa.png'],
            ['label' => 'Motorcycle',  'caption' => 'Power and speed at scale',            'image' => 'images/motorcycle.png'],
            ['label' => 'Batmobile',   'caption' => 'The finished vision &mdash; and beyond', 'image' => 'images/batmobile.png'],
        ];
    }

    public function getProcessStagesResolvedAttribute(): array
    {
        $stored = $this->process_stages;
        $defaults = static::defaultProcessStages();

        if (! is_array($stored) || empty($stored)) {
            return $defaults;
        }

        $resolved = [];
        foreach ($defaults as $i => $default) {
            $entry = $stored[$i] ?? [];
            $resolved[] = [
                'label'   => $entry['label']   ?? $default['label'],
                'caption' => $entry['caption'] ?? $default['caption'],
                'image'   => $entry['image']   ?? $default['image'],
            ];
        }

        return $resolved;
    }
}
