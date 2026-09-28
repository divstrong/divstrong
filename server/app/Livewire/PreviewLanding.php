<?php

namespace App\Livewire;

use App\Livewire\Concerns\BooksMeetings;
use App\Models\CampaignEnrollment;
use App\Models\Meeting;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use Livewire\Component;

/**
 * The page a campaign email sends people to: the concept we built them, the two questions,
 * and the calendar.
 *
 * Everything here is addressed by the prospect's preview token — there is no login, and
 * the page has to survive being forwarded to a partner, which is why the token is stable
 * rather than a signed one-shot link.
 */
class PreviewLanding extends Component
{
    use BooksMeetings;

    public Prospect $prospect;

    /** Whether to show the calendar, which opens on a yes but can also be asked for. */
    public bool $showBooking = false;

    /*
     * A "no" is the most useful answer on this page and the one nobody follows up on, so
     * each one opens a box asking why. Two of them, because disliking the design and not
     * wanting to move are different objections and a shared box would blur them.
     */
    public string $likeComment = '';

    public string $interestComment = '';

    public bool $likeCommentSent = false;

    public bool $interestCommentSent = false;

    public function mount(string $token): void
    {
        $this->prospect = Prospect::where('preview_token', $token)->firstOrFail();

        abort_if(blank($this->prospect->preview_url), 404);

        $this->initBooking($this->prospect);
        $this->recordVisit();

        // Somebody returning to a page they already answered from should land on the
        // calendar rather than being asked the same question twice.
        if ($this->prospect->interested === true && ! $this->prospect->hasBookedMeeting()) {
            $this->showBooking = true;
        }
    }

    /**
     * A visit is timelined once a day.
     *
     * Every reopened tab is not a new signal, and a timeline full of "viewed their
     * preview" makes the entries that matter harder to see.
     */
    protected function recordVisit(): void
    {
        // Staff opening the page from the admin to check it are not the prospect looking.
        if (auth()->check()) {
            return;
        }

        $alreadyToday = $this->prospect->activities()
            ->where('type', ProspectActivity::PREVIEW_VIEWED)
            ->where('occurred_at', '>=', now()->startOfDay())
            ->exists();

        $this->prospect->forceFill(['preview_viewed_at' => now()])->saveQuietly();

        if ($alreadyToday) {
            return;
        }

        ProspectActivity::record([
            'prospect_id' => $this->prospect->id,
            'type' => ProspectActivity::PREVIEW_VIEWED,
            'description' => 'Opened their preview page',
            'meta' => ['token' => $this->prospect->preview_token],
        ]);
    }

    /** They clicked through to the design itself — the strongest pre-answer signal. */
    public function openedSite(): void
    {
        if (auth()->check()) {
            return;
        }

        ProspectActivity::record([
            'prospect_id' => $this->prospect->id,
            'type' => ProspectActivity::PREVIEW_OPENED_SITE,
            'description' => 'Opened the design',
            'meta' => ['url' => $this->prospect->preview_url],
        ]);
    }

    public function answerLike(bool $answer): void
    {
        $this->prospect->recordPreviewAnswer('like', $answer);
        $this->prospect->refresh();
    }

    public function answerInterest(bool $answer): void
    {
        $this->prospect->recordPreviewAnswer('interested', $answer);
        $this->prospect->refresh();

        if ($answer) {
            $this->showBooking = true;

            return;
        }

        // A "no" ends the sequence here rather than at the next scheduled send, so the
        // next email cannot go out after they have already told us.
        $this->prospect->enrollments()
            ->where('status', CampaignEnrollment::STATUS_ACTIVE)
            ->get()
            ->each(fn (CampaignEnrollment $enrollment) => $enrollment->stop(CampaignEnrollment::STOP_NOT_INTERESTED));
    }

    public function submitLikeComment(): void
    {
        $this->validate(['likeComment' => 'required|string|max:2000'], [
            'likeComment.required' => 'Tell us what you would change and we will read every word.',
        ]);

        $this->prospect->recordPreviewComment('like', $this->likeComment);
        $this->likeCommentSent = true;
    }

    public function submitInterestComment(): void
    {
        $this->validate(['interestComment' => 'required|string|max:2000'], [
            'interestComment.required' => 'A line is plenty — it genuinely helps.',
        ]);

        $this->prospect->recordPreviewComment('interested', $this->interestComment);
        $this->interestCommentSent = true;
    }

    public function startBooking(): void
    {
        $this->showBooking = true;
    }

    protected function bookingProspect(): ?Prospect
    {
        return $this->prospect;
    }

    protected function bookingSource(): string
    {
        return 'preview';
    }

    protected function afterBooked(Meeting $meeting): void
    {
        $this->prospect->refresh();
    }

    public function render()
    {
        return view('livewire.preview-landing')
            ->layout('layouts.preview', [
                'title' => ($this->prospect->company ?: 'Your website concept') . ' · divStrong',
            ]);
    }
}
