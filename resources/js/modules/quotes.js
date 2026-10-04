/**
 * Long testimonials are cut to a few lines by CSS; this adds the button that
 * opens them. Without JavaScript the full text is simply shown.
 */
export function initQuotes() {
    document.querySelectorAll('[data-quote]').forEach((quote) => {
        const text = quote.querySelector('p');
        if (text.scrollHeight <= text.clientHeight + 2) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'quote-more';
        button.textContent = 'Read more';
        button.setAttribute('aria-expanded', 'false');

        button.addEventListener('click', () => {
            const open = quote.classList.toggle('is-open');
            button.textContent = open ? 'Show less' : 'Read more';
            button.setAttribute('aria-expanded', String(open));
        });

        quote.after(button);
    });
}
