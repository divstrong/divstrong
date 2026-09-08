<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Proposal;
use App\Services\Concerns\AttachesProposalTerms;
use App\Support\EngagementMix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Turns a client plus a plain-English brief into a draft proposal: Claude
 * writes the overview and splits the work into sprints, and this builder lands
 * that shape in the database — one Scope of Work category, one Investment row
 * and one payment milestone per sprint, plus a row each for any day-rate and
 * hour-rate development time sold alongside them.
 *
 * The sibling of {@see RfpProposalBuilder}, which starts from a screened RFP.
 */
class ClientProposalBuilder
{
    use AttachesProposalTerms;

    /** How long a generated proposal stays valid. */
    private const VALID_FOR_WEEKS = 6;

    public function __construct(private ?ClaudeService $claude = null)
    {
        $this->claude ??= new ClaudeService();
    }

    public function build(
        Client $client,
        EngagementMix $mix,
        ?string $projectTitle = null,
        ?int $userId = null,
    ): Proposal {
        $content = $this->claude->generateClientProposalContent([
            'client_name' => $client->name,
            'client_company' => $client->company,
            'project_title' => $projectTitle,
        ], $mix);

        $userId ??= Auth::id();

        return DB::transaction(function () use ($client, $content, $mix, $projectTitle, $userId) {
            $proposal = Proposal::create([
                'user_id' => $userId,
                'client_id' => $client->id,
                'project_title' => $projectTitle
                    ?: ($content['project_title'] ?: ($client->company ?: $client->name) . ' Project'),
                'proposal_date' => now(),
                'valid_until' => now()->addWeeks(self::VALID_FOR_WEEKS),
                'client_name' => $client->name ?? '',
                'client_email' => $client->email ?? '',
                'client_company' => $client->company ?? '',
                'client_domain' => $client->domain,
                'introduction' => $content['introduction'] ?? '',
                'cost_notes' => $content['cost_notes'] ?: $this->defaultCostNotes($mix),
                'overview_enabled' => true,
                'investment_enabled' => true,
                'milestones_enabled' => true,
                'status' => ProposalStatus::Draft,
                'view_count' => 0,
            ]);

            $sprints = $content['sprints'] ?? [];

            $this->attachScope($proposal, $sprints, $mix);
            $this->attachCostItems($proposal, $sprints, $mix, $content);
            $this->attachMilestones($proposal, $sprints, $mix);
            $this->attachTerms($proposal);

            return $proposal;
        });
    }

    /**
     * Scope items are categorised "Sprint 2 — Core Build" so the public
     * proposal, which groups scope by category, renders one block per sprint.
     */
    private function attachScope(Proposal $proposal, array $sprints, EngagementMix $mix): void
    {
        $sortOrder = 0;

        foreach ($sprints as $i => $sprint) {
            $category = $this->sprintCategory($mix, $sprint, $i);

            foreach ($sprint['scope_items'] ?? [] as $item) {
                $proposal->scopeItems()->create([
                    'category' => $category,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'bullets' => $item['bullets'] ?? [],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * One row per sprint at the sprint rate, then a single row for the day-rate
     * block and a single row for the hour-rate block when either was sold.
     */
    private function attachCostItems(Proposal $proposal, array $sprints, EngagementMix $mix, array $content): void
    {
        $sortOrder = 0;

        foreach ($sprints as $i => $sprint) {
            $proposal->costItems()->create([
                'description' => $this->sprintCategory($mix, $sprint, $i),
                'quantity' => 1,
                'unit_price' => $mix->sprintRate,
                'amount' => $mix->sprintRate,
                'sort_order' => $sortOrder++,
            ]);
        }

        if ($mix->hasDays()) {
            $proposal->costItems()->create([
                'description' => $this->developmentLabel($content['day_work'] ?? null),
                'quantity' => $mix->days,
                'unit_price' => $mix->dayRate,
                'amount' => $mix->daySubtotal(),
                'sort_order' => $sortOrder++,
            ]);
        }

        if ($mix->hasHours()) {
            $proposal->costItems()->create([
                'description' => $this->developmentLabel($content['hour_work'] ?? null),
                'quantity' => $mix->hours,
                'unit_price' => $mix->hourRate,
                'amount' => $mix->hourSubtotal(),
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    /**
     * One milestone per sprint, each carrying that sprint's share of the total,
     * plus a closing milestone for the day/hour time when any was sold. The
     * public proposal prices milestones off the percentage, so the percentages
     * are settled to sum to exactly 100.
     */
    private function attachMilestones(Proposal $proposal, array $sprints, EngagementMix $mix): void
    {
        $rows = [];

        foreach ($sprints as $i => $sprint) {
            // Rendered as "25% ($3,000) {title}", so the title reads on from
            // the figure the way the existing proposals do.
            $rows[] = [
                'title' => 'due upon completion of ' . $this->sprintCategory($mix, $sprint, $i),
                'description' => $sprint['goal'] ?? '',
                'due_description' => $mix->sprintLabel($sprint['number'] ?? ($i + 1)),
                'amount' => $mix->sprintRate,
            ];
        }

        $supplemental = $mix->daySubtotal() + $mix->hourSubtotal();

        if ($supplemental > 0) {
            $rows[] = [
                'title' => 'due as additional development time is used',
                'description' => implode(', ', array_filter([$mix->dayLine(), $mix->hourLine()])),
                'due_description' => 'Monthly in arrears',
                'amount' => $supplemental,
            ];
        }

        $percentages = $this->settlePercentages(array_column($rows, 'amount'));

        foreach ($rows as $i => $row) {
            $proposal->milestones()->create([
                'title' => $row['title'],
                'description' => $row['description'],
                'due_description' => $row['due_description'],
                'percentage' => $percentages[$i],
                'amount' => round($row['amount'], 2),
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Split 100% across the given amounts by largest remainder, so the stored
     * percentages sum to exactly 100.00 even when the shares do not divide
     * evenly — the client-facing dollar figures are derived from them.
     *
     * @param  array<int, float>  $amounts
     * @return array<int, float>
     */
    private function settlePercentages(array $amounts): array
    {
        $total = array_sum($amounts);

        if (count($amounts) === 0) {
            return [];
        }

        // Percentages are stored to two decimals, so work in hundredths of a percent.
        $exact = $total > 0
            ? array_map(fn ($a) => $a / $total * 10000, $amounts)
            : array_fill(0, count($amounts), 10000 / count($amounts));

        $units = array_map('intval', $exact);
        $remainder = 10000 - array_sum($units);

        $fractions = [];
        foreach ($exact as $i => $value) {
            $fractions[$i] = $value - $units[$i];
        }
        arsort($fractions);

        foreach (array_keys($fractions) as $i) {
            if ($remainder <= 0) {
                break;
            }

            $units[$i]++;
            $remainder--;
        }

        return array_map(fn ($u) => $u / 100, $units);
    }

    /** "Sprint 2 — Core Build" */
    private function sprintCategory(EngagementMix $mix, array $sprint, int $index): string
    {
        $number = (int) ($sprint['number'] ?? ($index + 1));
        $title = trim((string) ($sprint['title'] ?? '')) ?: 'Delivery';

        return $mix->sprintLabel($number) . ' — ' . $title;
    }

    /** "Development — Content migration and design refinements" */
    private function developmentLabel(?string $work): string
    {
        $work = trim((string) $work);

        return $work === '' ? 'Development — Additional time' : "Development — {$work}";
    }

    private function defaultCostNotes(EngagementMix $mix): string
    {
        return 'This engagement is sold as ' . $mix->summaryLine() . '. One sprint is '
            . $mix->sprintBlurb() . '.';
    }
}
