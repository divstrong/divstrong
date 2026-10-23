<?php

namespace App\Console\Commands;

use App\Support\ProposalExporter;
use Illuminate\Console\Command;

/** The admin "Export all" ZIP, from the command line. */
class ExportProposals extends Command
{
    protected $signature = 'proposals:export {path? : Where to write the ZIP (default: storage/app/tmp)}';

    protected $description = 'Export every proposal as Markdown + JSON in a ZIP';

    public function handle(ProposalExporter $exporter): int
    {
        $path = $exporter->zip($this->argument('path') ? base_path($this->argument('path')) : null);

        $this->info('Wrote ' . $path . ' (' . round(filesize($path) / 1024) . ' KB, ' . $exporter->proposals()->count() . ' proposals).');

        return self::SUCCESS;
    }
}
