<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Renders a URL to PDF with headless Chrome.
 *
 * The renderer script is kept here rather than in a checked-in .mjs file: the
 * production deploy ships only the Laravel tree and public/build, so a loose
 * script under scripts/ is not guaranteed to exist on the server. Writing it
 * next to the output at render time removes that dependency, and Node still
 * resolves `puppeteer` by walking up to the project's node_modules.
 */
class PdfRenderer
{
    public const MINIMUM_NODE_MAJOR = 18;

    /**
     * Render $url into $outputPath. Returns the finished process so callers can
     * report exit code and stderr themselves.
     */
    public function render(string $url, string $outputPath, ?int $timeout = null): Process
    {
        $tmpDir = $this->tmpDir();
        $scriptPath = $tmpDir . DIRECTORY_SEPARATOR . 'render-' . Str::uuid()->toString() . '.mjs';

        file_put_contents($scriptPath, $this->script());

        try {
            $process = new Process(
                [$this->nodeBinary(), $scriptPath, $url, $outputPath],
                base_path(),
                $this->environment(),
            );
            $process->setTimeout($timeout ?? (int) config('services.pdf.timeout', 180));
            $process->run();

            return $process;
        } finally {
            @unlink($scriptPath);
        }
    }

    public function nodeBinary(): string
    {
        return config('services.pdf.node_binary') ?: 'node';
    }

    public function tmpDir(): string
    {
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        return $tmpDir;
    }

    /**
     * Environment for the Node child process.
     *
     * php-fpm hands children a stripped environment: without TEMP, Node's
     * os.tmpdir() resolves to "undefined\temp" and Chrome cannot create its
     * profile; without HOME, Puppeteer cannot find the Chrome it downloaded
     * into ~/.cache/puppeteer. Both are supplied explicitly.
     *
     * @return array<string, string>
     */
    public function environment(): array
    {
        $tmpDir = $this->tmpDir();

        // On Windows prefer USERPROFILE: a shell-provided HOME is often a
        // POSIX-style path (/c/Users/...) that Chrome cannot resolve.
        $home = PHP_OS_FAMILY === 'Windows'
            ? (getenv('USERPROFILE') ?: getenv('HOME') ?: null)
            : (getenv('HOME') ?: getenv('USERPROFILE') ?: null);

        return array_filter([
            'PATH' => getenv('PATH') ?: getenv('Path') ?: null,
            'TEMP' => $tmpDir,
            'TMP' => $tmpDir,
            'TMPDIR' => $tmpDir,
            'HOME' => $home,
            'USERPROFILE' => $home,
            'SystemRoot' => getenv('SystemRoot') ?: null,
            'windir' => getenv('windir') ?: null,
            'PUPPETEER_CACHE_DIR' => config('services.pdf.cache_dir')
                ?: ($home ? $home . DIRECTORY_SEPARATOR . '.cache' . DIRECTORY_SEPARATOR . 'puppeteer' : null),
            'PUPPETEER_EXECUTABLE_PATH' => config('services.pdf.chrome_path') ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * The renderer, as ES module source.
     *
     * Deliberately avoids top-level await and static imports so that an ancient
     * Node parses the file and reaches the version guard, reporting something
     * useful instead of a syntax error.
     */
    public function script(): string
    {
        $minimum = self::MINIMUM_NODE_MAJOR;

        return <<<JS
        const [url, outputPath] = process.argv.slice(2);

        (async () => {
            if (!url || !outputPath) {
                console.error('Usage: node <script> <url> <outputPath>');
                process.exit(1);
            }

            const major = Number(process.versions.node.split('.')[0]);

            if (major < {$minimum}) {
                console.error(
                    'Node ' + process.versions.node + ' is too old for Puppeteer, which needs '
                    + '{$minimum} or newer. Point NODE_BINARY in .env at a modern Node '
                    + '(Plesk installs them under /opt/plesk/node/<version>/bin/node).'
                );
                process.exit(3);
            }

            let puppeteer;

            try {
                puppeteer = (await import('puppeteer')).default;
            } catch (e) {
                console.error('Could not load Puppeteer: ' + e.message);
                console.error('Install it on this host with: npm ci --omit=dev');
                process.exit(4);
            }

            const { mkdir, writeFile } = await import('node:fs/promises');
            const { dirname } = await import('node:path');

            const browser = await puppeteer.launch({
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
            });

            try {
                const page = await browser.newPage();
                await page.setViewport({ width: 1280, height: 1600, deviceScaleFactor: 2 });

                await page.goto(url, { waitUntil: 'networkidle0', timeout: 90000 });

                // Let Alpine.js initialize and any async content settle
                await new Promise(r => setTimeout(r, 1500));

                const pdfBuffer = await page.pdf({
                    format: 'Letter',
                    printBackground: true,
                    preferCSSPageSize: true,
                    margin: { top: '0.5in', right: '0.5in', bottom: '0.5in', left: '0.5in' },
                });

                await mkdir(dirname(outputPath), { recursive: true });
                await writeFile(outputPath, pdfBuffer);
            } finally {
                await browser.close();
            }
        })().catch(err => {
            console.error(err && err.stack ? err.stack : String(err));
            process.exit(1);
        });
        JS;
    }
}
