/**
 * Send a custom event to Google Analytics. Does nothing when the tracking tag
 * isn't on the page (no measurement ID saved, or an ad blocker removed it).
 */
export function track(name, params = {}) {
    if (typeof window.gtag === 'function') window.gtag('event', name, params);
}

export function initTracking() {
    // Résumé and contact-card downloads, wherever the link appears.
    document.addEventListener('click', (event) => {
        const path = event.target.closest('a[href]')?.pathname ?? '';
        if (path.endsWith('/resume.pdf')) track('resume_download');
        if (path.endsWith('.vcf')) track('contact_card_download');
    });
}
