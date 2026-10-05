const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Long testimonials are cut to a few lines by CSS. This adds a "Read more"
 * button to each one that is cut, which opens the whole testimonial in a
 * pop-up so the cards beside it keep their shape. Without JavaScript the
 * full text is simply shown.
 */
export function initQuotes() {
    const pop = document.querySelector('[data-quote-pop]');
    const body = pop?.querySelector('[data-quote-pop-body]');

    // Play the closing animation, then actually close.
    function close() {
        if (!pop.open || pop.classList.contains('is-closing')) return;
        const finish = () => {
            pop.classList.remove('is-closing');
            pop.close();
        };
        if (reduceMotion) return finish();
        pop.classList.add('is-closing');
        pop.addEventListener('animationend', finish, { once: true });
    }

    if (pop?.showModal) {
        pop.querySelector('[data-quote-pop-close]').addEventListener('click', close);
        pop.addEventListener('click', (event) => {
            if (event.target === pop) close();
        });
        pop.addEventListener('cancel', (event) => {
            event.preventDefault();
            close();
        });
    }

    document.querySelectorAll('[data-quote]').forEach((quote) => {
        const text = quote.querySelector('p');
        if (text.scrollHeight <= text.clientHeight + 2) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'quote-more';
        button.textContent = 'Read more';

        if (pop?.showModal) {
            button.setAttribute('aria-haspopup', 'dialog');
            button.addEventListener('click', () => {
                const copy = quote.closest('.quote').cloneNode(true);
                copy.querySelector('.quote-more')?.remove();
                copy.querySelector('[data-quote]').classList.add('is-open');
                copy.classList.remove('reveal');
                body.replaceChildren(copy);
                pop.showModal();
                body.scrollTop = 0;
            });
        } else {
            // No <dialog> support: open the text in place instead.
            button.setAttribute('aria-expanded', 'false');
            button.addEventListener('click', () => {
                const open = quote.classList.toggle('is-open');
                button.textContent = open ? 'Show less' : 'Read more';
                button.setAttribute('aria-expanded', String(open));
            });
        }

        quote.after(button);
    });
}
