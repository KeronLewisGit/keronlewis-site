/**
 * The hero's dot grid. Dots near the pointer swell and warm towards coral,
 * then settle back when the pointer leaves.
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

    let width = 0;
    let height = 0;
    let colors = readColors();
    let pointer = { x: -999, y: -999, strength: 0, target: 0 };
    let frame = null;

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

    function draw() {
        ctx.clearRect(0, 0, width, height);

        for (let x = GAP / 2; x < width; x += GAP) {
            for (let y = GAP / 2; y < height; y += GAP) {
                const distance = Math.hypot(x - pointer.x, y - pointer.y);
                const pull = distance < REACH ? (1 - distance / REACH) ** 2 * pointer.strength : 0;

                ctx.beginPath();
                ctx.arc(x, y, BASE + pull * 2.4, 0, Math.PI * 2);
                ctx.fillStyle = pull > 0.04 ? colors.hot : colors.dot;
                ctx.globalAlpha = pull > 0.04 ? 0.35 + pull * 0.65 : 0.75;
                ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
    }

    function loop() {
        pointer.strength += (pointer.target - pointer.strength) * 0.14;
        draw();
        frame = Math.abs(pointer.target - pointer.strength) > 0.01 || pointer.target === 1 ? requestAnimationFrame(loop) : null;
    }

    const wake = () => (frame ??= requestAnimationFrame(loop));

    if (!reduceMotion) {
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
