<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProspectResource;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Totals atop the Prospects list: book size and triage, plus how far the outreach is
 * actually getting.
 *
 * Engagement is counted per PROSPECT, not per event. Postmark re-posts an Open every time a
 * message is re-displayed unless "only post on first open" is ticked, so counting raw events
 * would inflate these the moment somebody scrolls past an email twice. The chart beside this
 * widget counts events, deliberately — see its class note.
 *
 * Dismissed leads are excluded from every tile, matching the list below, which hides them by
 * default. A total including them would disagree with the row count on screen, and an
 * Emailed rate against a book padded with dead leads reads lower than it is.
 */
class ProspectStatsWidget extends BaseWidget
{
    /** Three of the header row's five columns; the activity chart takes the other two. */
    protected int|string|array $columnSpan = 3;

    protected static ?int $sort = 1;

    /** Three across, so six tiles land as two tidy rows in the space left for them. */
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $scoped = fn () => Prospect::query()
            ->where(fn ($q) => $q
                ->where('lead_status', '!=', Prospect::LEAD_DISMISSED)
                ->orWhereNull('lead_status'));

        $total = $scoped()->count();

        // Qualified and Agencies used to have tiles here. Both are one click away on the
        // list's own filters, and neither is a number anyone watches daily — the row is
        // for the funnel, and six tiles leave the activity chart room to be readable.
        $converted = $scoped()->whereNotNull('client_id')->count();

        $emailed = $this->countWithActivity($scoped, ProspectActivity::EMAIL_SENT);
        $opened = $this->countWithActivity($scoped, ProspectActivity::EMAIL_OPENED);
        $clicked = $this->countWithActivity($scoped, ProspectActivity::EMAIL_CLICKED);

        // Straight off the Called toggle in the table, so the tile and the column can never
        // disagree about what "called" means.
        $called = $scoped()->whereNotNull('called_at')->count();

        return [
            Stat::make('Total Prospects', number_format($total))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Converted', number_format($converted))
                ->description($this->share($converted, $total).' became clients')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            /*
             * No bounce tile here.
             *
             * It would read as a total and not be one, which invites exactly the comparison
             * it fails: the Email Activity chart counts bounce EVENTS across the whole book,
             * while this widget counts PROSPECTS and excludes dismissed leads — and a lead
             * whose address hard-bounced is precisely the one somebody then dismisses. Add a
             * re-sent prospect no longer counting as bounced, and one number could be a
             * handful while the other showed dozens, both correctly.
             *
             * Bounces stay reachable where they mean something: the red chip on the row and
             * the Bounced option on the Engagement filter.
             */
            Stat::make('Emailed', number_format($emailed))
                ->description($this->share($emailed, $total).' of all prospects')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray'),

            Stat::make('Opened', number_format($opened))
                // Rate is against prospects emailed, not the whole book — the rest were never
                // given the chance to open anything.
                ->description($this->share($opened, $emailed).' of those emailed')
                ->icon('heroicon-o-envelope-open')
                ->color('info')
                // Links to the "Opened" filter, which counts clickers too — the same set this
                // number reports. Linking to "opened, no click yet" would land on fewer rows
                // than the tile shows, which reads as a bug.
                ->url($this->filterUrl('engagement', 'opened'))
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Clicked', number_format($clicked))
                ->description($this->share($clicked, $emailed).' of those emailed')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('success')
                ->url($this->filterUrl('engagement', 'clicked'))
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Called', number_format($called))
                ->description($this->share($called, $total).' of all prospects')
                ->icon('heroicon-o-phone')
                // The panel's red. Not 'info' — Opened already owns blue on this row, and two
                // tiles in the same blue read as the same kind of number.
                ->color('primary')
                ->url($this->filterUrl('called_at', true))
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }

    /**
     * Distinct prospects with at least one activity of this type. whereHas keeps it to a
     * single correlated subquery rather than loading timelines.
     */
    protected function countWithActivity(\Closure $scoped, string $type): int
    {
        return $scoped()
            ->whereHas('activities', fn ($q) => $q->where('type', $type))
            ->count();
    }

    /**
     * Deep link to the Prospects list with a table filter already applied.
     *
     * Filament reads table filter state straight off the query string, so the tile only has
     * to name the filter and its value — no session juggling, and the URL is shareable.
     *
     * The key is `filters`: ListRecords binds $tableFilters with #[Url(as: 'filters')]. The
     * v3-era `tableFilters` key is silently ignored, so the tile reloaded an unfiltered list.
     */
    protected function filterUrl(string $filter, mixed $value): string
    {
        return ProspectResource::getUrl('index', [
            'filters' => [$filter => ['value' => $value]],
        ]);
    }

    protected function share(int $part, int $whole): string
    {
        return $whole > 0 ? round($part / $whole * 100).'%' : '—';
    }
}
