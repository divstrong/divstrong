import puppeteer from 'puppeteer';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname } from 'node:path';

const [, , url, outputPath] = process.argv;

if (!url || !outputPath) {
    console.error('Usage: node render-pdf.mjs <url> <outputPath>');
    process.exit(1);
}

const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
});

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 1600, deviceScaleFactor: 2 });

    await page.goto(url, { waitUntil: 'networkidle0', timeout: 90_000 });

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
