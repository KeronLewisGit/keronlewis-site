import { initTheme } from './modules/theme';

const SVG = 'http://www.w3.org/2000/svg';

function el(name, attrs = {}, text) {
    const node = document.createElementNS(SVG, name);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
    if (text !== undefined) node.textContent = text;
    return node;
}

// Pick a clean gap between gridlines so the ticks read 0 / 25 / 50, not 0 / 23.5 / 47.
function niceTick(max) {
    const raw = Math.max(max, 4) / 4;
    const magnitude = 10 ** Math.floor(Math.log10(raw));
    const steps = magnitude >= 10 ? [1, 2, 2.5, 5, 10] : [1, 2, 5, 10];
    return steps.find((step) => step * magnitude >= raw) * magnitude;
}

/**
 * Visitors-per-day line chart. Drawn at the container's real pixel size so
 * the text stays legible on a phone; the table below it holds the same data.
 */
function lineChart(root) {
    const days = JSON.parse(root.querySelector('script').textContent);
    const HEIGHT = 260;
    const pad = { top: 16, right: 18, bottom: 28, left: 40 };

    const svg = el('svg', { height: HEIGHT, 'aria-hidden': 'true' });
    const tip = document.createElement('div');
    tip.className = 'chart-tip';
    tip.hidden = true;
    root.append(svg, tip);

    let width = 0;
    let active = null;
    const top = niceTick(Math.max(...days.map((day) => day.visitors))) * 4;
    const x = (i) => pad.left + (days.length > 1 ? (i / (days.length - 1)) * (width - pad.left - pad.right) : 0);
    const y = (value) => pad.top + (1 - value / top) * (HEIGHT - pad.top - pad.bottom);

    function draw() {
        width = root.clientWidth;
        svg.setAttribute('viewBox', `0 0 ${width} ${HEIGHT}`);
        svg.replaceChildren();

        // Horizontal gridlines with value ticks.
        for (let i = 0; i <= 4; i++) {
            const value = (top / 4) * i;
            svg.append(
                el('line', { class: i === 0 ? 'chart-base' : 'chart-grid', x1: pad.left, x2: width - pad.right, y1: y(value), y2: y(value) }),
                el('text', { x: pad.left - 8, y: y(value) + 4, 'text-anchor': 'end' }, value.toLocaleString()),
            );
        }

        // Date labels, as many as fit without touching.
        const every = Math.ceil(days.length / Math.max(Math.floor((width - pad.left - pad.right) / 72), 2));
        days.forEach((day, i) => {
            if ((days.length - 1 - i) % every === 0) {
                svg.append(el('text', { x: x(i), y: HEIGHT - 8, 'text-anchor': i === days.length - 1 ? 'end' : 'middle' }, day.label));
            }
        });

        const points = days.map((day, i) => `${x(i)},${y(day.visitors)}`);
        svg.append(
            el('path', { class: 'chart-area', d: `M${x(0)},${y(0)} L${points.join(' L')} L${x(days.length - 1)},${y(0)} Z` }),
            el('path', { class: 'chart-line', d: `M${points.join(' L')}` }),
        );

        // Label only the latest value; the axis, tooltip and table carry the rest.
        const last = days.length - 1;
        svg.append(
            el('circle', { class: 'chart-dot', cx: x(last), cy: y(days[last].visitors), r: 4 }),
            el('text', { class: 'end-label', x: x(last), y: y(days[last].visitors) - 10, 'text-anchor': 'end' }, days[last].visitors.toLocaleString()),
        );

        svg.append(el('line', { class: 'chart-cross', 'data-cross': '', y1: pad.top, y2: HEIGHT - pad.bottom, visibility: 'hidden' }));
        svg.append(el('circle', { class: 'chart-dot', 'data-dot': '', r: 5, visibility: 'hidden' }));
        if (active !== null) show(active);
    }

    function row(label, value, plotted) {
        const line = document.createElement('div');
        const key = document.createElement('i');
        if (plotted) key.className = 'is-series';
        const strong = document.createElement('strong');
        strong.textContent = value.toLocaleString();
        const name = document.createElement('span');
        name.textContent = label;
        line.append(key, strong, name);
        return line;
    }

    function show(i) {
        active = Math.min(Math.max(i, 0), days.length - 1);
        const day = days[active];
        const cx = x(active);

        const cross = svg.querySelector('[data-cross]');
        cross.setAttribute('x1', cx);
        cross.setAttribute('x2', cx);
        cross.setAttribute('visibility', 'visible');
        const dot = svg.querySelector('[data-dot]');
        dot.setAttribute('cx', cx);
        dot.setAttribute('cy', y(day.visitors));
        dot.setAttribute('visibility', 'visible');

        const heading = document.createElement('b');
        heading.textContent = new Date(`${day.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
        tip.replaceChildren(heading, row('visitors', day.visitors, true), row('page views', day.views, false));
        tip.hidden = false;

        // Keep the tooltip beside the crosshair and inside the chart.
        const flip = cx + 14 + tip.offsetWidth > width;
        tip.style.left = `${flip ? cx - 14 - tip.offsetWidth : cx + 14}px`;
    }

    function hide() {
        active = null;
        tip.hidden = true;
        svg.querySelector('[data-cross]')?.setAttribute('visibility', 'hidden');
        svg.querySelector('[data-dot]')?.setAttribute('visibility', 'hidden');
    }

    // The pointer only has to be near a date, never on the line itself.
    root.addEventListener('pointermove', (event) => {
        const offset = event.clientX - root.getBoundingClientRect().left - pad.left;
        const plot = width - pad.left - pad.right;
        show(Math.round((offset / plot) * (days.length - 1)));
    });
    root.addEventListener('pointerleave', hide);

    root.addEventListener('focus', () => show(active ?? days.length - 1));
    root.addEventListener('blur', hide);
    root.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        show((active ?? days.length - 1) + (event.key === 'ArrowRight' ? 1 : -1));
    });

    new ResizeObserver(draw).observe(root);
}

// "Copy" buttons beside a private link.
function copyButton(button) {
    const label = button.textContent;

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            button.textContent = 'Copied';
        } catch {
            // No clipboard access: select the link so it can be copied by hand.
            button.previousElementSibling?.select();
            button.textContent = 'Press Ctrl/Cmd+C';
        }
        setTimeout(() => { button.textContent = label; }, 2000);
    });
}

initTheme();
document.querySelectorAll('[data-line-chart]').forEach(lineChart);
document.querySelectorAll('[data-copy]').forEach(copyButton);
