<?php

namespace App\Services\Concerns;

use App\Models\Proposal;
use App\Models\TermsLibrary;

/**
 * Copies the active terms library onto a freshly generated proposal. Terms are
 * snapshotted per proposal rather than referenced, so editing the library
 * later never rewrites a proposal that has already been sent.
 */
trait AttachesProposalTerms
{
    protected function attachTerms(Proposal $proposal): void
    {
        $terms = TermsLibrary::where('is_active', true)->orderBy('sort_order')->get();

        foreach ($terms as $i => $term) {
            $proposal->terms()->create([
                'content' => $term->content,
                'sort_order' => $i,
            ]);
        }
    }
}
