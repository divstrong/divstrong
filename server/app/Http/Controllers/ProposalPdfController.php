<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ProposalPdfController extends Controller
{
    public function download(string $uuid): Response
    {
        $proposal = Proposal::where('uuid', $uuid)->firstOrFail();

        $url = route('proposal.view', ['uuid' => $uuid]) . '?pdf=1';

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . 'proposal-' . Str::uuid()->toString() . '.pdf';

        $script = base_path('scripts/render-pdf.mjs');
        $node = config('services.node.binary', 'node');

        $process = new Process([$node, $script, $url, $tmpPath], base_path());
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($tmpPath)) {
            Log::error('Proposal PDF generation failed', [
                'uuid' => $uuid,
                'url' => $url,
                'node' => $node,
                'script' => $script,
                'tmp_path' => $tmpPath,
                'tmp_exists' => is_file($tmpPath),
                'exit_code' => $process->getExitCode(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'cwd' => base_path(),
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
