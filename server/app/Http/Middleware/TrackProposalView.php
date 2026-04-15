<?php

namespace App\Http\Middleware;

use App\Enums\ProposalStatus;
use App\Mail\ProposalViewedNotification;
use App\Models\Proposal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class TrackProposalView
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->route('uuid');
        $proposal = Proposal::where('uuid', $uuid)->first();

        if ($proposal && $proposal->status !== ProposalStatus::Draft) {
            $isFirstView = is_null($proposal->first_viewed_at);

            $proposal->increment('view_count');
            $proposal->update([
                'last_viewed_at' => now(),
                'first_viewed_at' => $proposal->first_viewed_at ?? now(),
                'status' => $proposal->status === ProposalStatus::Sent
                    ? ProposalStatus::Viewed
                    : $proposal->status,
            ]);

            if ($isFirstView && $proposal->user) {
                Mail::to($proposal->user->email)
                    ->send(new ProposalViewedNotification($proposal));
            }
        }

        return $next($request);
    }
}
