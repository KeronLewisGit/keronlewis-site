// Full-page screenshot of one URL using the Chrome already installed on this machine.
// Called by `php artisan portfolio:screenshots`; usage: node scripts/screenshot.mjs <url> <out.png>
import { chromium } from 'playwright-core';

const [url, out] = process.argv.slice(2);
if (!url || !out) {
    console.error('usage: node scripts/screenshot.mjs <url> <out.png>');
    process.exit(2);
}

const MAX_HEIGHT = 5200;

const browser = await chromium.launch({ channel: 'chrome', args: ['--disable-blink-features=AutomationControlled'] });
try {
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
        reducedMotion: 'reduce',
    });
    const page = await context.newPage();
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60_000 });
    await page.waitForLoadState('networkidle', { timeout: 20_000 }).catch(() => {});

    // Give a Cloudflare interstitial time to clear, if there is one.
    for (let i = 0; i < 10 && /just a moment|security verification/i.test(await page.title() + await page.locator('body').innerText()); i++) {
        await page.waitForTimeout(2000);
    }

    // Scroll through once so lazy-loaded images and scroll animations settle.
    await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 600) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 180));
        }
        window.scrollTo(0, 0);
    });
    await page.waitForTimeout(1200);

    // Cookie banners, newsletter pop-ups and chat widgets don't belong in a portfolio shot.
    await page.keyboard.press('Escape').catch(() => {});
    await page.addStyleTag({
        content: `[id*="cookie" i],[class*="cookie" i],[id*="consent" i],[class*="consent" i],[class*="popup" i],
                  [class*="pum-" i],[class*="elementor-popup" i],.dialog-widget,[class*="chat" i],[id*="chat" i],
                  [class*="whatsapp" i],[class*="joinchat" i],iframe[title*="chat" i]{display:none!important}`,
    });
    await page.waitForTimeout(400);

    const height = Math.min(await page.evaluate(() => document.documentElement.scrollHeight), MAX_HEIGHT);
    await page.screenshot({ path: out, fullPage: true, clip: { x: 0, y: 0, width: 1280, height } });
} finally {
    await browser.close();
}
