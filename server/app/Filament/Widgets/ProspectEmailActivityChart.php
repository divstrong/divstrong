<?php

namespace App\Filament\Widgets;

use App\Models\ProspectActivity;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/**
 * Sent / opened / bounced per day, beside the Prospects stat tiles.
 *
 * Built against every day in the window rather than the rows the query returns: a day with no
 * activity has no row, and plotting the rows directly would join Friday to Monday and draw a
 * quiet weekend as a straight line.
 *
 * Counts EVENTS, not prospects — unlike the stat tiles beside it, which count prospects. A
 * volume-over-time chart is asking "how busy was this day", so a prospect who opened the same
 * email three times is three events. The tiles answer "how many people opened", where that
 * same repetition would inflate the rate, so they de-duplicate. Two questions, two counts.
 *
 * Palette validated for both light and dark surfaces (categorical, adjacent-pair CVD
 * separation and contrast) — do not re-pick these by eye.
 */
class ProspectEmailActivityChart extends ChartWidget
{
    protected ?string $heading = 'Email Activity';

    /** Selected in the header; the key is also what drives the query window. */
    public ?string $filter = '30d';

    protected static ?int $sort = 2;

    /** One third of the header row; the stat tiles take the other two. */
    protected int|string|array $columnSpan = 2;

    /** Roughly two rows of stat tiles, so the header row squares off. */
    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    /**
     * Selectable windows. Day-count ranges are relative to today; the calendar ranges are
     * absolute, which is why the start and end are resolved rather than subtracted.
     */
    private const RANGES = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'ytd' => 'This year',
        'last_year' => 'Last year',
    ];

    private const SENT = '#16A34A';

    private const OPENED = '#2563EB';

    private const BOUNCED = '#DC2626';

    protected function getFilters(): ?array
    {
        return self::RANGES;
    }

    /**
     * Start and end of the selected window, inclusive.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function range(): array
    {
        $today = CarbonImmutable::today();

        return match ($this->filter) {
            '7d' => [$today->subDays(6), $today],
            '90d' => [$today->subDays(89), $today],
            'ytd' => [$today->startOfYear(), $today],
            // A full calendar year that has already ended, so it stops at 31 December.
            'last_year' => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear()],
            default => [$today->subDays(29), $today],
        };
    }

    protected function getData(): array
    {
        [$start, $end] = $this->range();

        $days = [];
        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $days[] = $day->format('Y-m-d');
        }

        $counts = $this->countsByDay($start, $end);

        $series = fn (string $type): array => array_map(
            fn (string $day): int => $counts[$day][$type] ?? 0,
            $days,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => $series(ProspectActivity::EMAIL_SENT),
                    'borderColor' => self::SENT,
                    // Solid, because Chart.js paints the legend swatch from backgroundColor.
                    // Leaving the translucent area fill here drew a washed-out ring in the
                    // legend that did not match the line it stands for.
                    'backgroundColor' => self::SENT,
                    // The area fill therefore moves onto fill.above, which is what actually
                    // shades the region between the line and the baseline.
                    'fill' => ['target' => 'origin', 'above' => 'rgba(22, 163, 74, 0.12)'],
                    // Pinned so the point marker cannot inherit the panel's default border
                    // colour, which is what put a pink ring on every legend dot.
                    'pointStyle' => 'circle',
                    'pointBackgroundColor' => self::SENT,
                    'pointBorderColor' => self::SENT,
                    'pointBorderWidth' => 0,
                    'tension' => 0.3,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Opened',
                    'data' => $series(ProspectActivity::EMAIL_OPENED),
                    'borderColor' => self::OPENED,
                    // Solid, because Chart.js paints the legend swatch from backgroundColor.
                    // Leaving the translucent area fill here drew a washed-out ring in the
                    // legend that did not match the line it stands for.
                    'backgroundColor' => self::OPENED,
                    // The area fill therefore moves onto fill.above, which is what actually
                    // shades the region between the line and the baseline.
                    'fill' => ['target' => 'origin', 'above' => 'rgba(37, 99, 235, 0.12)'],
                    // Pinned so the point marker cannot inherit the panel's default border
                    // colour, which is what put a pink ring on every legend dot.
                    'pointStyle' => 'circle',
                    'pointBackgroundColor' => self::OPENED,
                    'pointBorderColor' => self::OPENED,
                    'pointBorderWidth' => 0,
                    'tension' => 0.3,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Bounced',
                    'data' => $series(ProspectActivity::EMAIL_BOUNCED),
                    'borderColor' => self::BOUNCED,
                    // Solid, because Chart.js paints the legend swatch from backgroundColor.
                    // Leaving the translucent area fill here drew a washed-out ring in the
                    // legend that did not match the line it stands for.
                    'backgroundColor' => self::BOUNCED,
                    // The area fill therefore moves onto fill.above, which is what actually
                    // shades the region between the line and the baseline.
                    'fill' => ['target' => 'origin', 'above' => 'rgba(220, 38, 38, 0.12)'],
                    // Pinned so the point marker cannot inherit the panel's default border
                    // colour, which is what put a pink ring on every legend dot.
                    'pointStyle' => 'circle',
                    'pointBackgroundColor' => self::BOUNCED,
                    'pointBorderColor' => self::BOUNCED,
                    'pointBorderWidth' => 0,
                    'tension' => 0.3,
                    'borderWidth' => 2,
                ],
            ],
            // A year of daily labels would be unreadable, so long windows drop the day and
            // let Chart.js thin what is left. The tooltip always carries the full date.
            'labels' => array_map(
                fn (string $day): string => CarbonImmutable::parse($day)
                    ->format(count($days) > 120 ? 'M Y' : 'M j'),
                $days,
            ),
        ];
    }

    /**
     * One grouped query for the whole window rather than three counts per day.
     *
     * @return array<string, array<string, int>> date => [type => count]
     */
    protected function countsByDay(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = ProspectActivity::query()
            ->selectRaw('DATE(occurred_at) as day, type, COUNT(*) as total')
            ->whereIn('type', [
                ProspectActivity::EMAIL_SENT,
                ProspectActivity::EMAIL_OPENED,
                ProspectActivity::EMAIL_BOUNCED,
            ])
            // Bounded at both ends: "last year" is a closed window, not everything since.
            ->whereBetween('occurred_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('day', 'type')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            // MySQL hands DATE() back as a string; normalise so the lookup key always matches.
            $counts[CarbonImmutable::parse($row->day)->format('Y-m-d')][$row->type] = (int) $row->total;
        }

        return $counts;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                // Three series, so a legend is mandatory — identity must never be colour alone.
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['boxWidth' => 8, 'boxHeight' => 8, 'usePointStyle' => true],
                ],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Emails are whole things; the default tick generator would offer 2.5.
                    'ticks' => ['precision' => 0],
                    'grid' => ['drawBorder' => false],
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['maxRotation' => 0, 'autoSkip' => true, 'maxTicksLimit' => 6],
                ],
            ],
            'elements' => ['point' => ['radius' => 0, 'hitRadius' => 10, 'hoverRadius' => 4]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }
}
