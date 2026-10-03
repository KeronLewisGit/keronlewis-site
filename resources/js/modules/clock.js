export function initClock() {
    const el = document.querySelector('[data-clock]');
    if (!el) return;

    const format = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', timeZone: el.dataset.tz });
    const tick = () => (el.textContent = format.format(new Date()));

    tick();
    setInterval(tick, 15_000);
}
