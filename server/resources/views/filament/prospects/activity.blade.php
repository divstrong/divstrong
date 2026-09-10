{{--
    The prospect's timeline, beside the form on the edit page.

    Everything that has ever happened to this person in one column: emails we sent, opens and
    clicks the Postmark webhook reported, notes, the opt-out, the conversion. It sits next to
    the form rather than behind a tab because it is the thing you want in view while writing a
    note or changing status.

    Styling lives in filament/prospects/panel-styles.blade.php — see that file for why it is
    CSS rather than Tailwind utilities.
--}}
@php
    $record = $getRecord();

    // Tolerate the table not existing yet (migration not run) — show the empty state, not a 500.
    $activities = ($record && \Illuminate\Support\Facades\Schema::hasTable('prospect_activities'))
        ? $record->activities()->with('user')->limit(200)->get()
        : collect();
@endphp

<div class="ds-timeline">
    @forelse ($activities as $activity)
        @php $p = $activity->presentation(); @endphp

        <div class="ds-event" data-tone="{{ $p['color'] }}">
            <div class="ds-event__icon">
                <x-filament::icon :icon="$p['icon']" />
            </div>

            <div class="ds-event__body">
                <div class="ds-event__head">
                    <p class="ds-event__title">{{ $activity->description ?: $p['label'] }}</p>
                    <time class="ds-event__when" datetime="{{ $activity->occurred_at?->toIso8601String() }}">
                        {{ $activity->occurred_at?->diffForHumans() }}
                    </time>
                </div>

                <div class="ds-event__meta">
                    <span>{{ $activity->occurred_at?->format('M j, Y g:i A') }}</span>

                    @if ($activity->user)
                        <span>by {{ $activity->user->name }}</span>
                    @endif

                    @if (! empty($activity->meta['email']))
                        <span>{{ $activity->meta['email'] }}</span>
                    @endif

                    @if (! empty($activity->meta['url']))
                        <span>
                            <a href="{{ $activity->meta['url'] }}" target="_blank" rel="noopener">
                                {{ \Illuminate\Support\Str::limit($activity->meta['url'], 50) }}
                            </a>
                        </span>
                    @endif

                    @if (! empty($activity->meta['bounce_type']))
                        <span>{{ $activity->meta['bounce_type'] }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="ds-timeline__empty">
            <x-filament::icon icon="heroicon-o-clock" />
            <p>No activity yet. Emails, opens, clicks and notes will appear here.</p>
        </div>
    @endforelse
</div>
