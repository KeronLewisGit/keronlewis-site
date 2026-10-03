/**
 * Analytics consent. Google Analytics is not loaded at all until the visitor
 * allows it; the choice is remembered in localStorage and can be changed
 * from "Cookie settings" in the footer.
 */
const KEY = 'analytics-consent';

function stored() {
    try {
        return localStorage.getItem(KEY);
    } catch {
        return null;
    }
}

function remember(choice) {
    try {
        localStorage.setItem(KEY, choice);
    } catch {
        // Storage blocked: the choice holds for this page view only.
    }
}

function startAnalytics(id) {
    window[`ga-disable-${id}`] = false;
    if (window.gtag) return;

    window.dataLayer = window.dataLayer || [];
    // gtag.js expects the raw `arguments` object, so this can't be an arrow function.
    window.gtag = function () {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', id);

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.append(script);
}

function stopAnalytics(id) {
    window[`ga-disable-${id}`] = true;

    // Remove the cookies Analytics set while it was allowed.
    const host = window.location.hostname;
    const domains = ['', host, `.${host}`, `.${host.split('.').slice(-2).join('.')}`];
    document.cookie
        .split(';')
        .map((cookie) => cookie.split('=')[0].trim())
        .filter((name) => name === '_ga' || name.startsWith('_ga_'))
        .forEach((name) => {
            domains.forEach((domain) => {
                document.cookie = `${name}=; Max-Age=0; path=/${domain ? `; domain=${domain}` : ''}`;
            });
        });
}

export function initConsent() {
    const id = document.querySelector('meta[name="ga-id"]')?.content;
    const banner = document.querySelector('[data-consent]');
    if (!id || !banner) return;

    const choose = (choice) => {
        remember(choice);
        banner.hidden = true;
        choice === 'granted' ? startAnalytics(id) : stopAnalytics(id);
    };

    banner.querySelector('[data-consent-allow]').addEventListener('click', () => choose('granted'));
    banner.querySelector('[data-consent-deny]').addEventListener('click', () => choose('denied'));
    document.querySelectorAll('[data-consent-open]').forEach((button) => {
        button.addEventListener('click', () => {
            banner.hidden = false;
            banner.querySelector('button').focus();
        });
    });

    const choice = stored();
    // A browser-level "do not track me" signal counts as a no, without asking.
    const optedOut = navigator.globalPrivacyControl === true || navigator.doNotTrack === '1';

    if (choice === 'granted') {
        startAnalytics(id);
    } else if (!choice && !optedOut) {
        banner.hidden = false;
    }
}
