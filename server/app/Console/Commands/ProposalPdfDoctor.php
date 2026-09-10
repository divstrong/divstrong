<?php

namespace App\Console\Commands;

use App\Models\Proposal;
use App\Support\PdfRenderer;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ProposalPdfDoctor extends Command
{
    protected $signature = 'proposal:pdf-doctor {--render : Also try a full render of a real proposal}';

    protected $description = 'Check everything the proposal PDF renderer needs (Node, Puppeteer, Chrome, temp dir)';

    public function handle(PdfRenderer $renderer): int
    {
        $this->newLine();
        $this->line('<options=bold>Proposal PDF renderer check</>');
        $this->newLine();

        $failures = 0;
        $node = $renderer->nodeBinary();

        // 1. Node, and a version Puppeteer can actually use
        $version = $this->exec([$node, '--version']);

        if (! $version['ok']) {
            $failures++;
            $this->bad('node', 'cannot execute "' . $node . '"');
            $this->suggestNodeBinaries();
        } else {
            $reported = trim($version['out']);
            $major = (int) ltrim(explode('.', $reported)[0], 'v');

            if ($major < PdfRenderer::MINIMUM_NODE_MAJOR) {
                $failures++;
                $this->bad('node', $reported . ' at "' . $node . '" — too old, Puppeteer needs ' . PdfRenderer::MINIMUM_NODE_MAJOR . '+');
                $this->suggestNodeBinaries();
            } else {
                $this->good('node', $reported . '  (' . $node . ')');
            }
        }

        // 2. Puppeteer package
        $resolve = $this->exec([$node, '-e', 'console.log(require.resolve("puppeteer/package.json"))']);

        if ($resolve['ok']) {
            $installed = json_decode(@file_get_contents(trim($resolve['out'])) ?: '{}', true);
            $this->good('puppeteer', 'v' . ($installed['version'] ?? '?'));
        } else {
            $failures++;
            $this->bad('puppeteer', 'not installed in ' . base_path('node_modules'));

            if (! is_file(base_path('package.json'))) {
                $this->hint('package.json is missing from this host — deploy it before npm can run.');
            } elseif (! is_file(base_path('package-lock.json'))) {
                $this->hint('package-lock.json is missing — use `npm install --omit=dev` instead of `npm ci`.');
            } else {
                $this->hint('On this host, with a modern Node first on PATH: npm ci --omit=dev');
            }
        }

        // 3. The Chrome binary Puppeteer would launch
        if ($resolve['ok']) {
            $chrome = $this->exec([
                $node, '-e',
                'import("puppeteer").then(p => console.log(p.default.executablePath())).catch(e => { console.error(e.message); process.exit(1); })',
            ]);
            $path = trim($chrome['out']);

            if ($chrome['ok'] && $path && is_file($path)) {
                $this->good('chrome', $path);

                $launch = $this->exec([
                    $node, '-e',
                    'import("puppeteer").then(async p => { const b = await p.default.launch({ headless: true, args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"] }); console.log(await b.version()); await b.close(); }).catch(e => { console.error(e.message); process.exit(1); })',
                ], 90);

                if ($launch['ok']) {
                    $this->good('chrome launch', trim($launch['out']));
                } else {
                    $failures++;
                    $this->bad('chrome launch', trim($launch['err']) ?: 'failed');
                    $this->hint('Missing shared libraries are the usual cause on a fresh box. On Debian/Ubuntu:');
                    $this->hint('sudo apt-get install -y libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2');
                }
            } else {
                $failures++;
                $this->bad('chrome', $path ? 'not found at ' . $path : (trim($chrome['err']) ?: 'could not resolve'));
                $this->hint('On this host: npx puppeteer browsers install chrome');
                $this->hint('Then set PUPPETEER_CACHE_DIR in .env to the directory it reports — php-fpm usually has no HOME.');
            }
        }

        // 4. Temp dir
        $tmpDir = $renderer->tmpDir();
        is_dir($tmpDir) && is_writable($tmpDir)
            ? $this->good('temp dir', $tmpDir)
            : ($failures++ && $this->bad('temp dir', 'not writable: ' . $tmpDir));

        // 5. Config in play
        $this->newLine();
        $this->line('  <fg=gray>NODE_BINARY               ' . $node . '</>');
        $this->line('  <fg=gray>PUPPETEER_CACHE_DIR       ' . (config('services.pdf.cache_dir') ?: '(unset — Puppeteer looks in ~/.cache/puppeteer)') . '</>');
        $this->line('  <fg=gray>PUPPETEER_EXECUTABLE_PATH ' . (config('services.pdf.chrome_path') ?: '(unset)') . '</>');
        $this->line('  <fg=gray>PDF_RENDER_BASE_URL       ' . (config('services.pdf.base_url') ?: '(unset — uses APP_URL: ' . config('app.url') . ')') . '</>');
        $this->line('  <fg=gray>PDF_RENDER_TIMEOUT        ' . config('services.pdf.timeout') . 's</>');

        // 6. Optional live render
        if ($this->option('render') && $failures === 0) {
            $proposal = Proposal::query()->latest('id')->first();

            $this->newLine();

            if (! $proposal) {
                $this->warn('  no proposals to render');
            } else {
                $url = config('services.pdf.base_url')
                    ? rtrim(config('services.pdf.base_url'), '/') . '/proposal/' . $proposal->uuid . '?pdf=1'
                    : route('proposal.view', ['uuid' => $proposal->uuid]) . '?pdf=1';

                $this->line('  rendering ' . $url . ' …');

                $out = $tmpDir . DIRECTORY_SEPARATOR . 'pdf-doctor.pdf';
                $process = $renderer->render($url, $out);

                if ($process->isSuccessful() && is_file($out)) {
                    $this->good('render', number_format(filesize($out) / 1024) . ' KB');
                    @unlink($out);
                } else {
                    $failures++;
                    $this->bad('render', trim($process->getErrorOutput()) ?: 'failed');
                }
            }
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error('  ' . $failures . ' problem(s) found — Download PDF will fail until these are fixed.');

            return self::FAILURE;
        }

        $this->info('  All checks passed.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $command
     * @return array{ok: bool, out: string, err: string}
     */
    private function exec(array $command, int $timeout = 60): array
    {
        $process = new Process($command, base_path(), app(PdfRenderer::class)->environment());
        $process->setTimeout($timeout);
        $process->run();

        return [
            'ok' => $process->isSuccessful(),
            'out' => $process->getOutput(),
            'err' => $process->getErrorOutput(),
        ];
    }

    /**
     * Look for a usable Node on this host so the operator does not have to hunt.
     */
    private function suggestNodeBinaries(): void
    {
        $candidates = array_merge(
            glob('/opt/plesk/node/*/bin/node') ?: [],
            glob('/usr/local/bin/node') ?: [],
            glob('/usr/bin/node') ?: [],
            glob('/opt/node*/bin/node') ?: [],
            glob((getenv('HOME') ?: '/root') . '/.nvm/versions/node/*/bin/node') ?: [],
        );

        $usable = [];

        foreach (array_unique($candidates) as $candidate) {
            $probe = $this->exec([$candidate, '--version'], 15);

            if (! $probe['ok']) {
                continue;
            }

            $reported = trim($probe['out']);

            if ((int) ltrim(explode('.', $reported)[0], 'v') >= PdfRenderer::MINIMUM_NODE_MAJOR) {
                $usable[$candidate] = $reported;
            }
        }

        if ($usable === []) {
            $this->hint('No Node ' . PdfRenderer::MINIMUM_NODE_MAJOR . '+ found on this host.');
            $this->hint('On Plesk: Tools & Settings > Updates > add a newer Node.js component, or install one yourself.');
            $this->hint('Looked in /opt/plesk/node/*, /usr/local/bin, /usr/bin, /opt/node*, ~/.nvm/versions/node/*');

            return;
        }

        $this->hint('Usable Node found on this host — put one of these in .env as NODE_BINARY:');

        foreach ($usable as $path => $reported) {
            $this->hint('  NODE_BINARY=' . $path . '   (' . $reported . ')');
        }

        $this->hint('Then: php artisan optimize');
    }

    private function good(string $label, string $detail): bool
    {
        $this->line('  <fg=green>OK</>   ' . str_pad($label, 15) . '<fg=gray>' . $detail . '</>');

        return true;
    }

    private function bad(string $label, string $detail): bool
    {
        $this->line('  <fg=red>FAIL</> ' . str_pad($label, 15) . $detail);

        return true;
    }

    private function hint(string $text): void
    {
        $this->line('       <fg=yellow>-> ' . $text . '</>');
    }
}
