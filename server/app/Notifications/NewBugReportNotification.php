<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBugReportNotification extends Notification
{
    use Queueable;

    public function __construct(public BugReport $report) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = $this->report->site;
        $siteName = $site?->name ?? 'a site';
        $reviewUrl = url('/admin/bug-reports/'.$this->report->id.'/edit');

        $message = (new MailMessage)
            ->subject('New bug report on '.$siteName)
            ->greeting('A new bug report came in')
            ->line('**Site:** '.$siteName)
            ->line('**Severity (reporter):** '.ucfirst($this->report->severity ?? 'unsure'))
            ->line('**URL:** '.$this->report->url)
            ->line('**What happened:**')
            ->line($this->report->what_happened);

        if ($this->report->reporter_email) {
            $message->line('**Reporter email:** '.$this->report->reporter_email);
        }

        return $message
            ->action('Review in admin', $reviewUrl)
            ->line('You can change notification recipients in the bug reporter site settings.');
    }
}
