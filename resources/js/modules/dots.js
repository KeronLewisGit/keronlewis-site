/**
 * The hero's dot grid. Dots near the pointer swell and warm towards coral,
 * then settle back when the pointer leaves. On load a single ripple rolls out
 * from the name as it rises into place.
 */
export function initDots() {
    const canvas = document.querySelector('[data-dots]');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const hero = canvas.parentElement;
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const GAP = 26;
    const BASE = 1.1;
    const REACH = 150;
    const RIPPLE_DELAY = 250;
    const RIPPLE_TIME = 2200;
    const RIPPLE_BAND = 70;

    let width = 0;
    let height = 0;
    let colors = readColors();
    let pointer = { x: -999, y: -999, strength: 0, target: 0 };
    let frame = null;
    let ripple = null;

    function readColors() {
        const css = getComputedStyle(document.documentElement);
        return { dot: css.getPropertyValue('--line-strong').trim(), hot: css.getPropertyValue('--coral').trim() };
    }

    function resize() {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        width = hero.clientWidth;
        height = hero.clientHeight;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        draw();
    }

    // Where the ring is now (radius) and how strong it still is (fades out as it spreads).
    function rippleState(now) {
        if (!ripple) return null;
        const progress = (now - ripple.start) / RIPPLE_TIME;
        if (progress < 0) return { radius: -RIPPLE_BAND, strength: 0 };
        if (progress >= 1) {
            ripple = null;
            return null;
        }
        const eased = 1 - (1 - progress) ** 3;
        return { radius: eased * ripple.reach, strength: (1 - progress) ** 1.5 * 0.8 };
    }

    function draw(now = performance.now()) {
        ctx.clearRect(0, 0, width, height);
        const ring = rippleState(now);

        for (let x = GAP / 2; x < width; x += GAP) {
            for (let y = GAP / 2; y < height; y += GAP) {
                const distance = Math.hypot(x - pointer.x, y - pointer.y);
                let pull = distance < REACH ? (1 - distance / REACH) ** 2 * pointer.strength : 0;

                if (ring) {
                    const off = Math.abs(Math.hypot(x - ripple.x, y - ripple.y) - ring.radius);
                    if (off < RIPPLE_BAND) pull = Math.max(pull, (1 - off / RIPPLE_BAND) ** 2 * ring.strength);
                }

                ctx.beginPath();
                ctx.arc(x, y, BASE + pull * 2.4, 0, Math.PI * 2);
                ctx.fillStyle = pull > 0.04 ? colors.hot : colors.dot;
                ctx.globalAlpha = pull > 0.04 ? 0.35 + pull * 0.65 : 0.75;
                ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
    }

    function loop(now) {
        pointer.strength += (pointer.target - pointer.strength) * 0.14;
        draw(now);
        const settling = Math.abs(pointer.target - pointer.strength) > 0.01 || pointer.target === 1;
        frame = settling || ripple ? requestAnimationFrame(loop) : null;
    }

    const wake = () => (frame ??= requestAnimationFrame(loop));

    if (!reduceMotion) {
        const name = hero.querySelector('.hero-name');
        if (name) {
            const box = hero.getBoundingClientRect();
            const from = name.getBoundingClientRect();
            const x = from.left - box.left + Math.min(from.width, 240) / 2;
            const y = from.top - box.top + from.height / 2;
            const reach = Math.max(Math.hypot(x, y), Math.hypot(box.width - x, box.height - y), Math.hypot(box.width - x, y), Math.hypot(x, box.height - y));
            ripple = { x, y, reach, start: performance.now() + RIPPLE_DELAY };
            wake();
        }

        hero.addEventListener('pointermove', (event) => {
            if (event.pointerType === 'touch') return;
            const box = hero.getBoundingClientRect();
            pointer.x = event.clientX - box.left;
            pointer.y = event.clientY - box.top;
            pointer.target = 1;
            wake();
        });
        hero.addEventListener('pointerleave', () => {
            pointer.target = 0;
            wake();
        });
    }

    document.addEventListener('themechange', () => {
        colors = readColors();
        draw();
    });
    new ResizeObserver(resize).observe(hero);
}
