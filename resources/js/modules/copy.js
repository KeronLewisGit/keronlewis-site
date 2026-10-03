let timer;

export function toast(message) {
    const el = document.querySelector('[data-toast]');
    if (!el) return;
    el.textContent = message;
    el.classList.add('is-on');
    clearTimeout(timer);
    timer = setTimeout(() => el.classList.remove('is-on'), 2200);
}

export async function copyText(text, label = 'Copied') {
    try {
        await navigator.clipboard.writeText(text);
        toast(label);
    } catch {
        // Clipboard access can be blocked; show the text so it can be copied by hand.
        toast(text);
    }
}

export function initCopy() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-copy]');
        if (trigger) copyText(trigger.dataset.copy, trigger.dataset.copyLabel);
    });
}
