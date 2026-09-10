<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use Illuminate\Http\Request;

/**
 * The opt-out page reached from the footer of divStrong's outreach.
 *
 * Public and unauthenticated by necessity — CAN-SPAM says the mechanism must not require the
 * recipient to log in, create an account, or supply anything beyond their address and an
 * optional reason. The route is signed, so the prospect id in the URL cannot be edited to opt
 * somebody else out.
 *
 * GET shows a confirmation, POST performs it. That split matters twice over: a link prefetched
 * by a mail client or scanned by a security appliance must not silently unsubscribe someone,
 * and RFC 8058 one-click unsubscribes arrive as a bare POST from Gmail or Yahoo with no page
 * ever loaded.
 */
class UnsubscribeController extends Controller
{
    /** Confirmation page. Changes nothing — see the class note on link prefetching. */
    public function show(Request $request, Prospect $prospect)
    {
        return view('unsubscribe', [
            'prospect' => $prospect,
            'done' => $prospect->isUnsubscribed(),
            // Carried through so the button can POST to a still-signed URL.
            'actionUrl' => $request->fullUrl(),
        ]);
    }

    /**
     * Perform the opt-out.
     *
     * Answers 200 to everything, including a repeat request for someone already unsubscribed:
     * a mail provider's one-click POST reads a non-2xx as a broken mechanism, and being marked
     * as having a broken unsubscribe is precisely the reputational damage this exists to avoid.
     */
    public function store(Request $request, Prospect $prospect)
    {
        // A one-click POST from a mail provider carries no browser session and renders no page.
        $oneClick = $request->input('List-Unsubscribe') === 'One-Click'
            || ! $request->acceptsHtml();

        $prospect->unsubscribe(
            $oneClick ? Prospect::UNSUB_ONE_CLICK : Prospect::UNSUB_LINK,
        );

        if ($oneClick) {
            return response('Unsubscribed', 200)->header('Content-Type', 'text/plain');
        }

        return view('unsubscribe', [
            'prospect' => $prospect,
            'done' => true,
            'actionUrl' => $request->fullUrl(),
        ]);
    }
}
