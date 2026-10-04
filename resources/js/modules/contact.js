import { track } from './track';

export function initContact() {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;

    const status = form.querySelector('[data-form-status]');
    const submit = form.querySelector('[data-submit]');
    const submitLabel = form.querySelector('[data-submit-label]');
    const message = form.elements.message;
    const counter = form.querySelector('[data-counter]');

    const updateCounter = () => {
        const left = message.maxLength - message.value.length;
        counter.textContent = left <= 400 ? `${left} left` : '';
    };
    message.addEventListener('input', updateCounter);

    // Some topics have extra questions; show a row only while its topic is picked.
    const topicFields = form.querySelectorAll('[data-topic-fields]');
    const updateTopicFields = () => {
        topicFields.forEach((row) => {
            row.hidden = row.dataset.topicFields !== form.elements.topic.value;
        });
    };
    form.addEventListener('change', (event) => {
        if (event.target.name === 'topic') updateTopicFields();
    });
    updateTopicFields();

    function setStatus(text, kind) {
        status.textContent = text;
        status.hidden = !text;
        status.classList.toggle('is-ok', kind === 'ok');
        status.classList.toggle('is-bad', kind === 'bad');
    }

    function showErrors(errors = {}) {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            const text = errors[el.dataset.errorFor]?.[0] ?? '';
            el.textContent = text;
            el.closest('.field').classList.toggle('has-error', Boolean(text));
        });
    }

    // Clear a field's error as soon as the visitor starts fixing it.
    form.addEventListener('input', (event) => {
        const field = event.target.closest('.field');
        const error = field?.querySelector('[data-error-for]');
        if (error?.textContent) {
            error.textContent = '';
            field.classList.remove('has-error');
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors();
        setStatus('');
        submit.disabled = true;
        submitLabel.textContent = 'Sending…';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const body = await response.json().catch(() => ({}));

            if (response.ok) {
                track('contact_message', { topic: form.elements.topic.value });
                form.reset();
                updateCounter();
                updateTopicFields();
                setStatus(body.message, 'ok');
            } else if (response.status === 422) {
                showErrors(body.errors);
                form.querySelector('.has-error input, .has-error select, .has-error textarea')?.focus();
            } else if (response.status === 429) {
                setStatus("That's a few messages in a short time. Please try again in a few minutes, or email me directly.", 'bad');
            } else if (response.status === 419) {
                setStatus('This page has been open a while and the form expired. Reload the page and try again.', 'bad');
            } else {
                throw new Error(`Unexpected status ${response.status}`);
            }
        } catch {
            setStatus("Something went wrong on my side and the message didn't send. Please email me directly instead.", 'bad');
        } finally {
            submit.disabled = false;
            submitLabel.textContent = 'Send message';
        }
    });
}
