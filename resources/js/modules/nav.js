export function initNav() {
    const nav = document.querySelector('[data-nav]');
    const bar = document.querySelector('[data-progress]');
    if (!nav) return;

    let ticking = false;
    const update = () => {
        ticking = false;
        nav.classList.toggle('is-scrolled', window.scrollY > 8);
        if (bar) {
            const max = document.documentElement.scrollHeight - window.innerHeight;
            bar.style.transform = `scaleX(${max > 0 ? Math.min(window.scrollY / max, 1) : 0})`;
        }
    };
    const onScroll = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };
    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);

    // Mobile menu
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.getElementById('menu');
    const setOpen = (open) => {
        menu.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    toggle?.addEventListener('click', () => setOpen(!menu.classList.contains('is-open')));
    menu?.addEventListener('click', (event) => {
        if (event.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menu?.classList.contains('is-open')) setOpen(false);
    });

    // Highlight the nav link for the section currently in view.
    const links = new Map();
    document.querySelectorAll('[data-spy]').forEach((link) => {
        const section = document.getElementById(link.dataset.spy);
        if (section) links.set(section, link);
    });
    if (!links.size) return;

    const spy = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                links.forEach((link) => link.classList.remove('is-active'));
                links.get(entry.target).classList.add('is-active');
            });
        },
        { rootMargin: '-45% 0px -50% 0px' },
    );
    links.forEach((_, section) => spy.observe(section));
}
