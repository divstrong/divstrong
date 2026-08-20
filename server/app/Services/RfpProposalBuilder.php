<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\RfpScreen;
use App\Models\TermsLibrary;
use App\Support\EngagementPlan;
use Illuminate\Support\Facades\DB;

/**
 * Turns a completed RFP screen into a draft proposal shaped by an engagement
 * plan: the scope is split into phases, each phase becoming one Scope of Work
 * category and one Investment row billed in the chosen unit. Shared by the
 * Filament admin action and the mobile API.
 */
class RfpProposalBuilder
{
    public function __construct(private ?ClaudeService $claude = null)
    {
        $this->claude ??= new ClaudeService();
    }

    public function build(RfpScreen $screen, EngagementPlan $plan, ?int $userId = null): Proposal
    {
        $content = $this->claude->generateProposalContent($this->rfpContext($screen), $plan);

        return DB::transaction(function () use ($screen, $content, $plan, $userId) {
            $proposal = Proposal::create([
                'user_id' => $userId ?? $screen->user_id,
                'project_title' => $screen->rfp_name ?: 'Untitled Project',
                'proposal_date' => now(),
                'valid_until' => now()->addDays(60),
                'client_name' => $content['contact_name'] ?? $screen->contact_name ?? '',
                'client_email' => $content['contact_email'] ?? $screen->contact_email ?? '',
                'client_company' => $content['contact_company'] ?? $screen->contact_department ?? '',
                'introduction' => $content['introduction'] ?? '',
                'cost_notes' => $content['cost_notes'] ?? $this->defaultCostNotes($plan),
                'investment_enabled' => true,
                'status' => ProposalStatus::Draft,
                'view_count' => 0,
            ]);

            $this->attachPhases($proposal, $content['phases'] ?? [], $plan);
            $this->attachTerms($proposal);

            $screen->update(['proposal_id' => $proposal->id]);

            return $proposal;
        });
    }

    /**
     * Everything the drafter gets to work from — the extracted analysis plus
     * the source documents themselves, so the draft is grounded in the real
     * RFP rather than just its summary.
     */
    private function rfpContext(RfpScreen $screen): array
    {
        return [
            'rfp_name' => $screen->rfp_name ?: 'Untitled RFP',
            'summary' => $screen->summary ?? '',
            'requirements' => $screen->requirements ?? [],
            'red_flags' => $screen->red_flags ?? [],
            'submission_requirements' => $screen->submission_requirements ?? [],
            'contact_name' => $screen->contact_name,
            'contact_email' => $screen->contact_email,
            'contact_company' => $screen->contact_department,
            'locality' => $screen->locality_label,
            'file_path' => $screen->file_path,
            'attachment_paths' => $screen->attachments()->pluck('file_path')->all(),
        ];
    }

    /**
     * Scope items are categorised "Sprint 2 — Core Build" so the public
     * proposal, which groups scope by category, renders one block per phase.
     * Each phase also gets a single Investment line at the unit rate.
     */
    private function attachPhases(Proposal $proposal, array $phases, EngagementPlan $plan): void
    {
        $scopeOrder = 0;

        foreach ($phases as $i => $phase) {
            $number = $phase['number'] ?? ($i + 1);
            $title = trim((string) ($phase['title'] ?? 'Delivery'));
            $category = $plan->phaseLabel($number) . ' — ' . $title;
            $quantity = max(1, (int) ($phase['quantity'] ?? 1));

            foreach ($phase['scope_items'] ?? [] as $item) {
                $proposal->scopeItems()->create([
                    'category' => $category,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'bullets' => $item['bullets'] ?? [],
                    'sort_order' => $scopeOrder++,
                ]);
            }

            $proposal->costItems()->create([
                'description' => $this->costDescription($category, $phase['summary'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $plan->rate,
                'amount' => $quantity * $plan->rate,
                'sort_order' => $i,
            ]);
        }
    }

    private function costDescription(string $category, string $summary): string
    {
        $summary = trim($summary);

        // The Investment table is a single-line cell; only inline the phase
        // summary when it stays readable there.
        if ($summary === '' || mb_strlen($summary) > 140) {
            return $category;
        }

        return "{$category}: {$summary}";
    }

    private function attachTerms(Proposal $proposal): void
    {
        $terms = TermsLibrary::where('is_active', true)->orderBy('sort_order')->get();

        foreach ($terms as $i => $term) {
            $proposal->terms()->create([
                'content' => $term->content,
                'sort_order' => $i,
            ]);
        }
    }

    private function defaultCostNotes(EngagementPlan $plan): string
    {
        $total = number_format($plan->total(), 0);

        return ucfirst($plan->quantityLabel()) . " — \${$total} total. "
            . 'One ' . $plan->unitLabel(1) . ' is ' . $plan->blurb() . '.';
    }
}
