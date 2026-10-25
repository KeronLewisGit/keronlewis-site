const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Figures marked [data-count] ("~35%", "2,100+") count up from zero the first
 * time they scroll into view. The finished text is what the server sent, so
 * screen readers and no-JS visitors get it unchanged.
 */
export function initCount() {
    const figures = [...document.querySelectorAll('[data-count]')];
    if (!figures.length || reduceMotion || !('IntersectionObserver' in window)) return;

    const DURATION = 1400;

    const run = (el) => {
        const [, prefix, digits, suffix] = el.textContent.trim().match(/^(\D*)([\d,]+)(.*)$/) ?? [];
        if (!digits) return;

        const target = Number(digits.replaceAll(',', ''));
        const grouped = digits.includes(',');
        const final = el.textContent;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / DURATION, 1);
            const value = Math.round(target * (1 - (1 - progress) ** 3));
            el.textContent = prefix + (grouped ? value.toLocaleString('en-US') : value) + suffix;
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = final;
        };
        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries
                .filter((entry) => entry.isIntersecting)
                .forEach((entry) => {
                    run(entry.target);
                    observer.unobserve(entry.target);
                });
        },
        { threshold: 0.6 },
    );

    figures.forEach((el) => observer.observe(el));
}
