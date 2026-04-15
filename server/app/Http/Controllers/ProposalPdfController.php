<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProposalPdfController extends Controller
{
    public function download(string $uuid): Response
    {
        $proposal = Proposal::where('uuid', $uuid)
            ->with(['scopeItems', 'costItems', 'milestones', 'terms', 'roadmapPhases', 'client'])
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.proposal', compact('proposal'))
            ->setPaper('letter')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = Str::slug(
            ($proposal->client_company ?: $proposal->client_name ?: 'proposal')
                . ' ' . ($proposal->project_title ?: 'proposal')
        ) . '.pdf';

        return $pdf->download($filename);
    }
}
