import { chromium } from 'playwright';

const chunks = [];

for await (const chunk of process.stdin) {
    chunks.push(chunk);
}

const jobs = JSON.parse(Buffer.concat(chunks).toString() || '[]');
const browser = await chromium.launch({ headless: true });
const succeeded = [];

try {
    let index = 0;
    const workers = Math.min(3, jobs.length);

    async function next() {
        while (index < jobs.length) {
            const job = jobs[index];
            index += 1;
            const page = await browser.newPage({
                viewport: { width: 1280, height: 720 },
            });

            try {
                await page.goto(job.url, {
                    waitUntil: 'domcontentloaded',
                    timeout: 20000,
                });
                await page
                    .waitForLoadState('networkidle', { timeout: 8000 })
                    .catch(() => {});
                await page
                    .waitForFunction(
                        () =>
                            (document.body?.innerText || '').trim().length > 0,
                        { timeout: 5000 },
                    )
                    .catch(() => {});
                await page.waitForTimeout(500);
                await page.screenshot({ path: job.destination, type: 'png' });
                succeeded.push(job.destination);
            } catch {
                // A failed capture leaves the previous preview in place.
            } finally {
                await page.close();
            }
        }
    }

    await Promise.all(Array.from({ length: workers }, () => next()));
} finally {
    await browser.close();
}

process.stdout.write(JSON.stringify(succeeded));
