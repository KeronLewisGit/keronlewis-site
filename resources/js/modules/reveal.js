export function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!items.length) return;

    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-in'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            // Items that enter together (a row of cards) fade in one after another.
            entries
                .filter((entry) => entry.isIntersecting)
                .forEach((entry, index) => {
                    entry.target.style.transitionDelay = `${index * 70}ms`;
                    entry.target.classList.add('is-in');
                    entry.target.addEventListener('transitionend', () => (entry.target.style.transitionDelay = ''), { once: true });
                    observer.unobserve(entry.target);
                });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' },
    );

    items.forEach((el) => observer.observe(el));
}
