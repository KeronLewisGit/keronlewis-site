const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = matchMedia('(hover: hover) and (pointer: fine)').matches;

/**
 * Kinetic type, built on the display face's weight axis. On load a wave runs
 * through the letters, thinning each one and letting it settle back; after that,
 * letters near the pointer thin out the way the hero's dots swell.
 *
 * Each letter sits in a slot as wide as it is at full weight, so the line never
 * reflows while the weight moves. The server's text is kept for screen readers
 * and search engines; the letters are decoration.
 *
 * Controls, kept few on purpose:
 *   data-kinetic         turns it on for an element
 *   data-kinetic-light   the lightest weight a letter reaches (default 300)
 *   data-kinetic-reach   how far from the pointer letters react, in px (default 180)
 */
export function initKinetic() {
    if (reduceMotion) return;
    document.querySelectorAll('[data-kinetic]').forEach(setUp);
}

function setUp(el) {
    const text = el.textContent.trim();
    const light = Number(el.dataset.kineticLight || 300);
    const reach = Number(el.dataset.kineticReach || 180);
    const heavy = parseInt(getComputedStyle(el).fontWeight, 10) || 800;

    const label = document.createElement('span');
    label.className = 'sr-only';
    label.textContent = text;

    const art = document.createElement('span');
    art.className = 'kinetic';
    art.setAttribute('aria-hidden', 'true');

    const letters = [];
    // Letters are grouped by word so a long name wraps between words (or after a hyphen,
    // as plain text would), never in the middle of one.
    text.split(/(\s+)/).forEach((part) => {
        if (!part.trim()) {
            if (part) art.append(' ');
            return;
        }
        part.split(/(?<=-)/).forEach((piece) => {
            const word = document.createElement('span');
            word.className = 'kinetic-word';
            for (const character of piece) {
                const letter = document.createElement('span');
                letter.className = 'kinetic-letter';
                letter.textContent = character;
                letter.style.setProperty('--i', letters.length);
                word.append(letter);
                letters.push(letter);
            }
            art.append(word);
        });
    });

    el.replaceChildren(label, art);

    // Slots are measured on a hidden full-weight copy, so a wave already under way can't skew them,
    // and stored in em so they follow the heading's fluid font size.
    const measure = () => {
        const copy = art.cloneNode(true);
        copy.classList.add('is-measuring');
        el.append(copy);
        const size = parseFloat(getComputedStyle(copy).fontSize);
        copy.querySelectorAll('.kinetic-letter').forEach((letter, index) => {
            letters[index].style.setProperty('--slot', `${letter.getBoundingClientRect().width / size}em`);
        });
        copy.remove();
    };

    // The wave waits for the real font, so it never plays on the fallback.
    document.fonts.ready.then(() => {
        measure();
        art.classList.add('is-intro');
        letters.at(-1).addEventListener('animationend', () => art.classList.remove('is-intro'), { once: true });
    });

    if (!finePointer) return;

    const area = el.closest('section') ?? el;
    let pointer = null;
    let frame = null;

    const update = () => {
        frame = null;
        letters.forEach((letter) => {
            if (!pointer) {
                letter.style.removeProperty('font-weight');
                return;
            }
            const box = letter.getBoundingClientRect();
            const distance = Math.hypot(box.left + box.width / 2 - pointer.x, box.top + box.height / 2 - pointer.y);
            const pull = distance < reach ? (1 - distance / reach) ** 2 : 0;
            letter.style.fontWeight = Math.round(heavy - (heavy - light) * pull);
        });
    };
    const schedule = () => (frame ??= requestAnimationFrame(update));

    area.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') return;
        pointer = { x: event.clientX, y: event.clientY };
        schedule();
    });
    area.addEventListener('pointerleave', () => {
        pointer = null;
        schedule();
    });
}
