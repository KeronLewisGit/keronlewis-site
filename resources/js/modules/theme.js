const root = document.documentElement;

function apply(theme) {
    root.dataset.theme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
    });
    document.dispatchEvent(new CustomEvent('themechange', { detail: theme }));
}

export function toggleTheme() {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem('theme', next);
    } catch {
        // Private mode or blocked storage: the choice just won't persist.
    }
    apply(next);
}

export function initTheme() {
    apply(root.dataset.theme || 'light');

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => btn.addEventListener('click', toggleTheme));

    // Keep following the system setting until the visitor picks a theme themselves.
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        let stored = null;
        try {
            stored = localStorage.getItem('theme');
        } catch {}
        if (!stored) apply(event.matches ? 'dark' : 'light');
    });
}
