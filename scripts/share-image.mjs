// Turn one share-card HTML file into a 1200x630 PNG using the Chrome already on this machine.
// Called by `php artisan portfolio:share-images`; usage: node scripts/share-image.mjs <card.html> <out.png>
import { chromium } from 'playwright-core';

const [html, out] = process.argv.slice(2);
if (!html || !out) {
    console.error('usage: node scripts/share-image.mjs <card.html> <out.png>');
    process.exit(2);
}

const browser = await chromium.launch({ channel: 'chrome' });
try {
    const page = await browser.newPage({ viewport: { width: 1200, height: 630 } });
    await page.goto(`file://${html}`);
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: out });
} finally {
    await browser.close();
}
