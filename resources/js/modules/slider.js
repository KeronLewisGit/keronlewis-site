const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Shows the home page testimonials one at a time. Arrows, dots, the arrow
 * keys and a sideways swipe all move it. While it is on screen it also moves
 * on by itself: the active dot's fill animation is the timer, so pausing that
 * animation (pointer over it, focus inside it, tab hidden) pauses the slider.
 * Once someone uses a control it stays put. No autoplay for reduced motion.
 */
export function initSlider() {
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const slides = [...track.querySelectorAll('[data-slide]')];
        const dots = [...slider.querySelectorAll('[data-slider-dot]')];
        const count = slider.querySelector('[data-slider-count]');
        if (slides.length < 2) return;

        const pad = (n) => String(n).padStart(2, '0');
        const holds = new Set(['view']);
        let current = 0;

        function show(index, dir) {
            const next = (index + slides.length) % slides.length;
            if (next === current) return;
            const from = slides[current];
            const to = slides[next];

            slider.style.setProperty('--dir', dir ?? (next > current ? 1 : -1));
            slides.forEach((slide) => slide.classList.remove('is-leaving'));

            // Park the incoming slide on the side it enters from, without animating it there.
            to.style.transition = 'none';
            void to.offsetWidth;
            to.style.transition = '';

            from.classList.replace('is-active', 'is-leaving');
            to.classList.add('is-active');
            slides.forEach((slide, i) => (slide.inert = i !== next));

            dots[current].classList.remove('is-active');
            dots[current].removeAttribute('aria-current');
            dots[next].classList.add('is-active');
            dots[next].setAttribute('aria-current', 'true');
            if (count) count.textContent = `${pad(next + 1)} / ${pad(slides.length)}`;

            current = next;
        }

        // Someone took over: stop moving on its own and announce changes from now on.
        function stop() {
            slider.classList.remove('is-playing');
            track.setAttribute('aria-live', 'polite');
        }

        function hold(reason, on) {
            holds[on ? 'add' : 'delete'](reason);
            slider.classList.toggle('is-paused', holds.size > 0);
        }

        slides.forEach((slide, i) => (slide.inert = i !== 0));
        slider.querySelector('[data-slider-controls]').hidden = false;
        slider.classList.toggle('is-playing', !reduceMotion);
        hold('view', true);

        slider.querySelector('[data-slider-prev]').addEventListener('click', () => {
            stop();
            show(current - 1, -1);
        });
        slider.querySelector('[data-slider-next]').addEventListener('click', () => {
            stop();
            show(current + 1, 1);
        });
        dots.forEach((dot, i) =>
            dot.addEventListener('click', () => {
                stop();
                show(i);
            }),
        );

        // Opening a long testimonial in the pop-up counts as taking over too.
        track.addEventListener('click', (event) => {
            if (event.target.closest('.quote-more')) stop();
        });

        slider.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            const dir = event.key === 'ArrowRight' ? 1 : -1;
            stop();
            show(current + dir, dir);
            if (event.target.matches('[data-slider-dot]')) dots[current].focus();
        });

        // The fill on the active dot has run out: time for the next one.
        slider.addEventListener('animationend', (event) => {
            if (event.animationName === 'dot-fill' && slider.classList.contains('is-playing')) show(current + 1, 1);
        });

        let startX = null;
        let startY = 0;
        track.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse') return;
            startX = event.clientX;
            startY = event.clientY;
        });
        track.addEventListener('pointerup', (event) => {
            if (startX === null) return;
            const dx = event.clientX - startX;
            const dy = event.clientY - startY;
            startX = null;
            if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy)) return;
            const dir = dx < 0 ? 1 : -1;
            stop();
            show(current + dir, dir);
        });
        track.addEventListener('pointercancel', () => (startX = null));

        slider.addEventListener('pointerenter', (event) => event.pointerType === 'mouse' && hold('pointer', true));
        slider.addEventListener('pointerleave', () => hold('pointer', false));
        slider.addEventListener('focusin', () => hold('focus', true));
        slider.addEventListener('focusout', (event) => {
            if (!slider.contains(event.relatedTarget)) hold('focus', false);
        });
        document.addEventListener('visibilitychange', () => hold('tab', document.hidden));

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => hold('view', !entry.isIntersecting), { threshold: 0.5 }).observe(slider);
        } else {
            hold('view', false);
        }
    });
}
