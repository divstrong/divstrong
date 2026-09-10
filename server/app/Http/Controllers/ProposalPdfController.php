<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Support\PdfRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ProposalPdfController extends Controller
{
    public function download(string $uuid, PdfRenderer $renderer): Response
    {
        @set_time_limit(0);

        $proposal = Proposal::where('uuid', $uuid)->firstOrFail();

        $url = route('proposal.view', ['uuid' => $uuid]) . '?pdf=1';

        // Optional override so a dev server that cannot serve a second concurrent
        // request (artisan serve on Windows) can render through a sibling instance.
        if ($base = config('services.pdf.base_url')) {
            $url = rtrim($base, '/') . '/proposal/' . $uuid . '?pdf=1';
        }

        $tmpPath = $renderer->tmpDir() . DIRECTORY_SEPARATOR . 'proposal-' . Str::uuid()->toString() . '.pdf';

        $process = $renderer->render($url, $tmpPath);

        if (! $process->isSuccessful() || ! is_file($tmpPath)) {
            Log::error('Proposal PDF generation failed', [
                'uuid' => $uuid,
                'url' => $url,
                'node' => $renderer->nodeBinary(),
                'tmp_path' => $tmpPath,
                'tmp_exists' => is_file($tmpPath),
                'exit_code' => $process->getExitCode(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'cwd' => base_path(),
                'hint' => 'Run `php artisan proposal:pdf-doctor` on this host to diagnose.',
            ]);
            @unlink($tmpPath);
            throw new RuntimeException(
                'PDF generation failed (exit ' . $process->getExitCode() . '). '
                . trim($process->getErrorOutput() ?: $process->getOutput() ?: 'no output')
            );
        }

        $pdf = file_get_contents($tmpPath);
        @unlink($tmpPath);

        $filename = Str::slug(
            ($proposal->client_company ?: $proposal->client_name ?: 'proposal')
                . ' ' . ($proposal->project_title ?: 'proposal')
        ) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
