import { chromium } from 'playwright';

const url = process.argv[2];
const destination = process.argv[3];

if (!url || !destination) {
    console.error(
        'Usage: node scripts/capture-screenshot.mjs <url> <destination>',
    );
    process.exit(1);
}

const browser = await chromium.launch({ headless: true });

try {
    const page = await browser.newPage({
        viewport: { width: 1280, height: 720 },
    });
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await page
        .waitForLoadState('networkidle', { timeout: 8000 })
        .catch(() => {});
    await page
        .waitForFunction(
            () => (document.body?.innerText || '').trim().length > 0,
            { timeout: 5000 },
        )
        .catch(() => {});
    await page.waitForTimeout(500);
    await page.screenshot({ path: destination, type: 'png' });
} finally {
    await browser.close();
}
